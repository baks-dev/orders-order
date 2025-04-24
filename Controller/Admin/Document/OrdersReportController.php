<?php

declare(strict_types=1);

namespace BaksDev\Orders\Order\Controller\Admin\Document;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Twig\CallTwigFuncExtension;
use BaksDev\Orders\Order\Forms\OrdersReport\OrdersReportDTO;
use BaksDev\Orders\Order\Forms\OrdersReport\OrdersReportForm;
use BaksDev\Orders\Order\Repository\AllOrdersReport\AllOrdersReportInterface;
use BaksDev\Orders\Order\Repository\AllOrdersReport\AllOrdersReportResult;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use BaksDev\Reference\Money\Type\Money;

#[AsController]
#[RoleSecurity(['ROLE_ORDERS'])]
final class OrdersReportController extends AbstractController
{
    #[Route('/admin/order/document/orders/report', name: 'admin.document.orders.report', methods: ['GET', 'POST'])]
    public function off(
        Request $request,
        AllOrdersReportInterface $allOrdersReportRepository,
        Environment $environment
    ): Response
    {
        $ordersReportDTO = new OrdersReportDTO();

        $form = $this
            ->createForm(
                OrdersReportForm::class,
                $ordersReportDTO,
                ['action' => $this->generateUrl('orders-order:admin.document.orders.report')]
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
            $sheet->setCellValue('A1', 'Дата');
            $sheet->setCellValue('B1', 'Номер заказа');
            $sheet->setCellValue('C1', 'Наименование');
            $sheet->setCellValue('D1', 'Артикул товара');
            $sheet->setCellValue('E1', 'Стоимость продукта за единицу');
            $sheet->setCellValue('F1', 'Стоимость продукта в заказе за единицу');
            $sheet->setCellValue('G1', 'Количество продукта в заказе');
            $sheet->setCellValue('H1', 'Сумма');
            $sheet->setCellValue('I1', 'Разница в цене между продуктом и продуктом в заказе');
            $sheet->setCellValue('J1', 'Способ доставки');
            $sheet->setCellValue('K1', 'Стоимость доставки');

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

                $sheet->setCellValue('A'.$key, $data->getModDate());
                $sheet->setCellValue('B'.$key, $data->getNumber());
                $sheet->setCellValue('C'.$key, $name);
                $sheet->setCellValue('D'.$key, $data->getProductArticle());
                $sheet->setCellValue('E'.$key, $productPrice->getValue());
                $sheet->setCellValue('F'.$key, $orderPrice->getValue());
                $sheet->setCellValue('G'.$key, $data->getTotal());
                $sheet->setCellValue('H'.$key, $money->getValue());
                $sheet->setCellValue('I'.$key, $profit->getValue());
                $sheet->setCellValue('J'.$key, $data->getDeliveryName());
                $sheet->setCellValue('K'.$key, $deliveryPrice->getValue());

                $allTotal += $data->getTotal();
                $allPrice->add($money);
                $key++;
            }

            /** Итого */
            $sheet->setCellValue('A'.$key, 'Итог');
            $sheet->setCellValue('G'.$key, $allTotal);
            $sheet->setCellValue('H'.$key, $allPrice->getValue());

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