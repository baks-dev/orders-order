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

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Twig\CallTwigFuncExtension;
use BaksDev\Orders\Order\Forms\OrdersReport\OrdersReportDTO;
use BaksDev\Orders\Order\Forms\OrdersReport\OrdersReportForm;
use BaksDev\Orders\Order\Repository\AllOrdersReport\AllOrdersReportInterface;
use BaksDev\Orders\Order\Repository\AllOrdersReport\AllOrdersReportResult;
use BaksDev\Reference\Money\Type\Money;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
#[RoleSecurity(['ROLE_ORDERS'])]
final class ReportOrdersController extends AbstractController
{
    #[Route('/admin/order/document/report/orders', name: 'admin.document.report.orders', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        AllOrdersReportInterface $allOrdersReportRepository,
        Environment $environment
    ): Response
    {
        $ordersReportDTO = new OrdersReportDTO();

        $form = $this
            ->createForm(
                type: OrdersReportForm::class,
                data: $ordersReportDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.document.report.orders')]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('orders_report'))
        {
            $this->refreshTokenForm($form);

            // получаем репозиторием...
            $result = $allOrdersReportRepository
                ->date($ordersReportDTO->getDate())
                ->findAll();

            if(false === $result)
            {
                $this->addFlash(
                    'Отчет о заказах',
                    'Отчета за указанный период не найдено',
                    'orders-order.admin'
                );

                return $this->redirectToReferer();
            }

            $call = $environment->getExtension(CallTwigFuncExtension::class);

            // Создаем новый объект Spreadsheet
            $spreadsheet = new Spreadsheet();
            $writer = new Xlsx($spreadsheet);

            // Получаем текущий активный лист
            $sheet = $spreadsheet->getActiveSheet();

            // Запись заголовков
            $sheet
                ->setCellValue('A1', 'Дата')
                ->setCellValue('B1', 'Номер заказа')
                ->setCellValue('C1', 'Наименование')
                ->setCellValue('D1', 'Артикул товара')
                ->setCellValue('E1', 'Стоимость продукта за единицу')
                ->setCellValue('F1', 'Стоимость продукта в заказе за единицу')
                ->setCellValue('G1', 'Количество продукта в заказе')
                ->setCellValue('H1', 'Сумма')
                ->setCellValue('I1', 'Разница в цене между продуктом и продуктом в заказе')
                ->setCellValue('J1', 'Способ доставки')
                ->setCellValue('K1', 'Стоимость доставки');

            $allTotal = 0;
            $allPrice = new Money(0);
            $key = 2;

            // Запись данных
            /** @var AllOrdersReportResult $data */
            foreach($result as $data)
            {
                $name = trim($data->getProductName());

                $variation = $call->call(
                    $environment,
                    $data->getProductVariationValue(),
                    $data->getProductVariationReference().'_render'
                );
                $name .= $variation ? ' '.trim($variation) : '';

                $modification = $call->call(
                    $environment,
                    $data->getProductModificationValue(),
                    $data->getProductModificationReference().'_render'
                );
                $name .= $modification ? ' '.trim($modification) : '';

                $offer = $call->call(
                    $environment,
                    $data->getProductOfferValue(),
                    $data->getProductOfferReference().'_render'
                );

                $name .= $modification ? ' '.trim($offer) : '';
                $name .= $data->getProductOfferPostfix() ? ' '.$data->getProductOfferPostfix() : '';
                $name .= $data->getProductVariationPostfix() ? ' '.$data->getProductVariationPostfix() : '';
                $name .= $data->getProductModificationPostfix() ? ' '.$data->getProductModificationPostfix() : '';

                $orderPrice = $data->getOrderPrice();
                $money = $data->getMoney();
                $deliveryPrice = $data->getDeliveryPrice();
                $productPrice = $data->getProductPrice();
                $profit = $data->getProfit();

                $sheet
                    ->setCellValue('A'.$key, $data->getDate()->format('d.m.Y H:i'))
                    ->setCellValue('B'.$key, $data->getNumber())
                    ->setCellValue('C'.$key, $name)
                    ->setCellValue('D'.$key, $data->getProductArticle())
                    ->setCellValue('E'.$key, $productPrice->getValue())
                    ->setCellValue('F'.$key, $orderPrice->getValue())
                    ->setCellValue('G'.$key, $data->getTotal())
                    ->setCellValue('H'.$key, $money->getValue())
                    ->setCellValue('I'.$key, $profit->getValue())
                    ->setCellValue('J'.$key, $data->getDeliveryName())
                    ->setCellValue('K'.$key, $deliveryPrice->getValue());

                $allTotal += $data->getTotal();
                $allPrice->add($money);

                $key++;
            }

            /** Итого */
            $sheet
                ->setCellValue('A'.$key, 'Итог')
                ->setCellValue('G'.$key, $allTotal)
                ->setCellValue('H'.$key, $allPrice->getValue());

            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            }, Response::HTTP_OK);

            $filename =
                'Отчёт о заказах ('.
                $ordersReportDTO->getDate()->format(('d.m.Y')).').xlsx';

            $response->headers->set('Content-Type', 'application/vnd.ms-excel');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        }

        return $this->render(['form' => $form->createView()]);
    }
}