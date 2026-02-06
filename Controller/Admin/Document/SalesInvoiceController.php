<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin\Document;

use BaksDev\Barcode\Writer\BarcodeFormat;
use BaksDev\Barcode\Writer\BarcodeType;
use BaksDev\Barcode\Writer\BarcodeWrite;
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Forms\SalesInvoice\SalesInvoiceDTO;
use BaksDev\Orders\Order\Forms\SalesInvoice\SalesInvoiceForm;
use BaksDev\Orders\Order\Forms\SalesInvoice\SalesInvoiceOrderDTO;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailResult;
use BaksDev\Orders\Order\Repository\OrderDetailByNumber\OrderDetailByNumberInterface;
use BaksDev\Orders\Order\UseCase\Admin\Print\OrderEventPrintDTO;
use BaksDev\Orders\Order\UseCase\Admin\Print\OrderEventPrintHandler;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileByIdInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity(['ROLE_USER'])]
final class SalesInvoiceController extends AbstractController
{
    private array|null $orders = null;

    /**
     * Расходная накладная
     */
    #[Route('/admin/order/document/sales', name: 'admin.document.sales', methods: ['GET', 'POST'])]
    public function sales(
        #[Target('ordersOrderLogger')] LoggerInterface $logger,
        Request $request,
        OrderDetailInterface $OrderDetailRepository,
        OrderDetailByNumberInterface $orderDetailByPartRepository,
        UserProfileByIdInterface $UserProfileByIdRepository,
        OrderEventPrintHandler $OrderEventPrintHandler,
        BarcodeWrite $BarcodeWrite,
        CentrifugoPublishInterface $publish,
    ): Response
    {

        $salesInvoiceFormDTO = new SalesInvoiceDTO();

        $form = $this
            ->createForm(
                type: SalesInvoiceForm::class,
                data: $salesInvoiceFormDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.document.sales')],
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->has('order_form_data'))
        {
            /**
             * @var SalesInvoiceOrderDTO $salesInvoiceOrderDTO
             */
            foreach($salesInvoiceFormDTO->getOrderFormData() as $salesInvoiceOrderDTO)
            {

                /**
                 * Информация о заказе
                 */

                /** Если нет номера партии - единый заказ. Отдаем информацию о конкретном заказе по его номеру */
                if(null === $salesInvoiceOrderDTO->getNumber())
                {
                    $OrderInfo = $OrderDetailRepository->onOrder($salesInvoiceOrderDTO->getOrder())->find();

                    if(true === empty($OrderInfo))
                    {
                        continue;
                    }

                    $this->process($OrderInfo, $BarcodeWrite, $OrderEventPrintHandler, $publish, $logger);
                }

                /** Если есть номер партии - заказ был разделен. Ищем связанные заказы по номеру партии */
                if(null !== $salesInvoiceOrderDTO->getNumber())
                {
                    $OrdersInfo = $orderDetailByPartRepository
                        ->onNumber($salesInvoiceOrderDTO->getNumber())
                        ->findAll();

                    if(false === $OrdersInfo)
                    {
                        continue;
                    }

                    foreach($OrdersInfo as $OrderDetailResult)
                    {
                        $this->process($OrderDetailResult, $BarcodeWrite, $OrderEventPrintHandler, $publish, $logger);
                    }
                }
            }
        }

        if(true === empty($this->orders))
        {
            return new Response('404 Page Not Found');
        }

        return $this->render([
            'orders' => $this->orders,
            'profile' => $UserProfileByIdRepository->find(),
        ]);
    }

    private function process(
        OrderDetailResult $OrderDetailResult,
        BarcodeWrite $BarcodeWrite,
        OrderEventPrintHandler $OrderEventPrintHandler,
        CentrifugoPublishInterface $publish,
        LoggerInterface $logger,
    )
    {
        /** Генерируем QR-код для заказа */
        $data = sprintf('%s', $OrderDetailResult->getOrderId()); // @TODO что зашиваем в qr???
        $BarcodeWrite
            ->text($data)
            ->type(BarcodeType::QRCode)
            ->format(BarcodeFormat::SVG)
            ->generate();

        if(false === $BarcodeWrite)
        {
            /**
             * Проверить права на исполнение
             * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Writer/Generate
             * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Reader/Decode
             * */
            throw new RuntimeException('Barcode write error');
        }

        $render = $BarcodeWrite->render();
        $BarcodeWrite->remove();

        $OrderDetailResult->setQrCode($render);

        $this->orders[] = $OrderDetailResult;

        if(false === $OrderDetailResult->isPrinted())
        {
            $orderEventPrintDTO = new OrderEventPrintDTO($OrderDetailResult->getOrderEvent());
            $orderEventPrinted = $OrderEventPrintHandler->handle($orderEventPrintDTO);

            if(false === $orderEventPrinted)
            {
                $logger->warning(
                    'orders-order: Ошибка сохранения данных о печати накладной',
                    [self::class.':'.__LINE__,],
                );
            }
        }

        /** Отправляем сокет для скрытия заказа у всех */
        $socket = $publish
            ->addData(['order' => (string) $OrderDetailResult->getOrderId()])
            //->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->send('orders');

        if($socket && $socket->isError())
        {
            return new JsonResponse($socket->getMessage());
        }
    }
}
