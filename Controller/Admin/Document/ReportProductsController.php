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
use BaksDev\Orders\Order\Forms\ProductsReport\ProductsReportDTO;
use BaksDev\Orders\Order\Forms\ProductsReport\ProductsReportForm;
use BaksDev\Orders\Order\Repository\AllProductsOrdersReport\AllProductsOrdersReportInterface;
use BaksDev\Orders\Order\Repository\AllProductsOrdersReport\AllProductsOrdersReportResult;
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
final class ReportProductsController extends AbstractController
{
    #[Route('/admin/order/document/report/products', name: 'admin.document.report.products', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        AllProductsOrdersReportInterface $allProductsOrdersReport,
        Environment $environment
    ): Response
    {
        $productsReportDTO = new ProductsReportDTO();

        $form = $this
            ->createForm(
                ProductsReportForm::class,
                $productsReportDTO,
                ['action' => $this->generateUrl('orders-order:admin.document.report.products')],
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('products_report'))
        {
            $this->refreshTokenForm($form);

            $allProductsOrdersReport
                ->to($productsReportDTO->getTo())
                ->from($productsReportDTO->getFrom());

            if(false === $productsReportDTO->isAll())
            {
                $allProductsOrdersReport->forProfile($this->getProfileUid());
            }

            $result = $allProductsOrdersReport->findAll();

            if(false === $result || false === $result->valid())
            {
                $this->addFlash(
                    'Отчет о заказах',
                    'Отчета за указанный период не найдено',
                    'orders-order.admin',
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
                ->setCellValue('A1', 'Наименование товара')
                ->setCellValue('B1', 'Торговое предложение')
                ->setCellValue('C1', 'Артикул товара')
                ->setCellValue('D1', 'Общее количество за период')
                ->setCellValue('E1', 'Суммарная стоимость за период')
                ->setCellValue('F1', 'Остаток');

            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);

            $allTotal = 0;
            $allStock = 0;
            $allPrice = new Money(0);
            $key = 2;

            // Запись данных
            /** @var AllProductsOrdersReportResult $data */
            foreach($result as $data)
            {
                $strOffer = '';

                /**
                 * Множественный вариант
                 */

                $variation = $call->call(
                    $environment,
                    $data->getProductVariationValue(),
                    $data->getProductVariationReference().'_render',
                );

                $strOffer .= $variation ? ' '.trim($variation) : '';

                /**
                 * Модификация множественного варианта
                 */

                $modification = $call->call(
                    $environment,
                    $data->getProductModificationValue(),
                    $data->getProductModificationReference().'_render',
                );

                $strOffer .= $modification ? ' '.trim($modification) : '';

                /**
                 * Торговое предложение
                 */

                $offer = $call->call(
                    $environment,
                    $data->getProductOfferValue(),
                    $data->getProductOfferReference().'_render',
                );

                $strOffer .= $modification ? ' '.trim($offer) : '';

                $strOffer .= $data->getProductOfferPostfix() ? ' '.$data->getProductOfferPostfix() : '';
                $strOffer .= $data->getProductVariationPostfix() ? ' '.$data->getProductVariationPostfix() : '';
                $strOffer .= $data->getProductModificationPostfix() ? ' '.$data->getProductModificationPostfix() : '';

                /**
                 * Информация о стоимости
                 */

                $money = $data->getMoney();

                $sheet
                    ->setCellValue('A'.$key, $data->getProductName())
                    ->setCellValue('B'.$key, str_replace(' /', '/', $strOffer))
                    ->setCellValue('C'.$key, $data->getProductArticle())
                    ->setCellValue('D'.$key, $data->getTotal())
                    ->setCellValue('E'.$key, $money->getValue())
                    ->setCellValue('F'.$key, $data->getStockTotal());

                $allTotal += $data->getTotal();
                $allStock += $data->getStockTotal();
                $allPrice->add($money);
                $key++;
            }

            // Общее количество и общая стоимость
            $sheet
                ->setCellValue('A'.$key, "Итого")
                ->setCellValue('D'.$key, $allTotal)
                ->setCellValue('E'.$key, $allPrice)
                ->setCellValue('F'.$key, $allStock);

            $filename =
                'Отчёт о заказах по продуктам ('.
                $productsReportDTO->getFrom()->format(('d.m.Y')).'-'.
                $productsReportDTO->getTo()->format(('d.m.Y')).').xlsx';

            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            }, Response::HTTP_OK);

            // Redirect output to a client’s web browser (Xls)
            $response->headers->set('Content-Type', 'application/vnd.ms-excel');
            $response->headers->set(
                'Content-Disposition',
                'attachment;filename="'.str_replace('"', '', $filename).'"',
            );
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        }

        return $this->render(['form' => $form->createView()]);
    }
}