<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin\Document;

use BaksDev\Barcode\Writer\BarcodeFormat;
use BaksDev\Barcode\Writer\BarcodeType;
use BaksDev\Barcode\Writer\BarcodeWrite;
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Forms\SalesInvoice\SalesInvoiceDTO;
use BaksDev\Orders\Order\Forms\SalesInvoice\SalesInvoiceForm;
use BaksDev\Orders\Order\Forms\SalesInvoice\SalesInvoiceOrderDTO;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\UseCase\Admin\Print\OrderEventPrintDTO;
use BaksDev\Orders\Order\UseCase\Admin\Print\OrderEventPrintHandler;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileByIdInterface;
use chillerlan\QRCode\QRCode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;


#[AsController]
#[RoleSecurity('ROLE_ORDERS')]
final class SalesInvoiceController extends AbstractController
{
    /**
     * Расходная накладная
     */
    #[Route('/admin/order/document/sales', name: 'admin.document.sales', methods: ['GET', 'POST'])]
    public function sales(
        #[Target('ordersOrderLogger')] LoggerInterface $logger,
        Request $request,
        OrderDetailInterface $OrderDetail,
        OrderEventPrintHandler $OrderEventPrintHandler,
        UserProfileByIdInterface $UserProfileByIdRepository,
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

        $orders = [];

        $data = '';

        if($form->isSubmitted() && $form->has('order_form_data'))
        {
            /**
             * @var SalesInvoiceOrderDTO $salesInvoiceOrderDTO
             */
            foreach($salesInvoiceFormDTO->getOrderFormData() as $salesInvoiceOrderDTO)
            {
                /** Информация о заказе */
                $OrderInfo = $OrderDetail->onOrder($salesInvoiceOrderDTO->getOrder())->find();

                if(true === empty($OrderInfo))
                {
                    continue;
                }


                /** Генерируем QR-код для заказа */
                $data = sprintf('%s', $salesInvoiceOrderDTO->getOrder());
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

                $OrderInfo->setQrCode($render);

                $orders[] = $OrderInfo;


                if(false === $OrderInfo->isPrinted())
                {
                    $orderEventPrintDTO = new OrderEventPrintDTO($OrderInfo->getOrderEvent());
                    $orderEventPrinted = $OrderEventPrintHandler->handle($orderEventPrintDTO);

                    if(false === $orderEventPrinted instanceof OrderEvent)
                    {
                        $logger->warning(
                            'orders-order: Ошибка сохранения данных о печати накладной',
                            [self::class.':'.__LINE__,]
                        );
                    }
                }

                // Отправляем сокет для скрытия заказа у других менеджеров
                $socket = $publish
                    ->addData(['order' => (string) $OrderInfo->getOrderId()])
                    ->addData(['profile' => (string) $this->getCurrentProfileUid()])
                    ->send('orders');

                if($socket && $socket->isError())
                {
                    return new JsonResponse($socket->getMessage());
                }

            }
        }

        return $this->render([
            'orders' => $orders,
            'profile' => $UserProfileByIdRepository->find(),
        ]);
    }
}
