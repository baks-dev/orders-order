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
use BaksDev\Orders\Order\Forms\ServicesReport\ServicesReportDTO;
use BaksDev\Orders\Order\Forms\ServicesReport\ServicesReportForm;
use BaksDev\Orders\Order\Repository\AllServicesOrdersReport\AllServicesOrdersReportInterface;
use BaksDev\Orders\Order\Repository\AllServicesOrdersReport\AllServicesOrdersReportResult;
use BaksDev\Reference\Money\Type\Money;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
#[RoleSecurity(['ROLE_ORDERS'])]
final class ReportServicesController extends AbstractController
{
    #[Route('/admin/order/document/report/services', name: 'admin.document.report.services', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        AllServicesOrdersReportInterface $allServicesOrdersReportRepository,
        Environment $environment
    ): Response
    {
        $ServicesReportDTO = new ServicesReportDTO();

        /* Форма отчета по услугам */
        $form = $this
            ->createForm(
                type: ServicesReportForm::class,
                data: $ServicesReportDTO,
                options: ['action' => $this->generateUrl('orders-order:admin.document.report.services')],
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('services_report'))
        {
            $this->refreshTokenForm($form);

            $allServicesOrdersReportRepository
                ->to($ServicesReportDTO->getTo())
                ->from($ServicesReportDTO->getFrom());

            if(false === $ServicesReportDTO->isAll())
            {
                $allServicesOrdersReportRepository->forProfile($this->getProfileUid());
            }

            $result = $allServicesOrdersReportRepository->findAll();

            if(false === $result || false === $result->valid())
            {
                $this->addFlash(
                    'Отчет об услугах в заказах',
                    'Отчет за указанный период не найден',
                    'orders-order.admin',
                );

                return $this->redirectToReferer();
            }


            /* Создать новый объект PhpSpreadsheet\Spreadsheet */
            $spreadsheet = new Spreadsheet();
            $writer = new Xlsx($spreadsheet);

            /* Получить текущий активный лист */
            $sheet = $spreadsheet->getActiveSheet();

            /* Записать заголовки в таблице */
            $sheet
                ->setCellValue('A1', '№')
                ->setCellValue('B1', 'Дата')
                ->setCellValue('C1', 'Номер заказа')
                ->setCellValue('D1', 'Наименование услуги')
                ->setCellValue('E1', 'Стоимость услуги за единицу')
                ->setCellValue('F1', 'Стоимость услуги в заказе за единицу')
                ->setCellValue('G1', 'Комментарий');

            /* Автоматическое изменение ширины столбца */
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);

            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);


            $allPrice = new Money(0);
            $key = 2;

            /* Записать данные */
            /** @var AllServicesOrdersReportResult $data */
            foreach($result as $num => $data)
            {
                $name = trim($data->getServiceName());

                /* Информация о стоимости услуги */
                $servicePrice = $data->getServicePrice();
                $orderServicePrice = $data->getOrderServicePrice();

                if($data->isDanger())
                {
                    /* Заливка диапазона красным */
                    $sheet
                        ->getStyle('A'.$key.':H'.$key)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('ff0000');
                }

                /* Заполнение данными */
                $sheet
                    ->setCellValue('A'.$key, $num + 1) // Номер по порядку
                    ->setCellValue('B'.$key, $data->getDate()->format('d.m.Y H:i')) // Дата заказа
                    ->setCellValue('C'.$key, $data->getNumber()) // Номер заказа
                    ->setCellValue('D'.$key, $name)              // Наименование услуги в заказе
                    ->setCellValue('E'.$key, $servicePrice->getValue()) // Стоимость услуги за единицу
                    ->setCellValue('F'.$key, $orderServicePrice->getValue()) // Стоимость услуги в заказе за единицу
                    ->setCellValue('G'.$key, $data->getComment()); // Комментарий по заказу

                $price = $data->getOrderServicePrice();
                $allPrice->add($price);

                $key++;
            }

            /* Заполнить Итого */
            $sheet
                ->setCellValue('A'.$key, 'Итог')
                ->setCellValue('F'.$key, $allPrice->getValue());

            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            }, Response::HTTP_OK);

            $filename =
                'Отчёт об услугах в заказах ('.
                $ServicesReportDTO->getFrom()->format('d.m.Y').($ServicesReportDTO->getFrom()->format('d.m.Y')
                !== $ServicesReportDTO->getTo()->format('d.m.Y') ? '-'.$ServicesReportDTO->getTo()->format('d.m.Y') : '')
                .').xlsx';

            $response->headers->set('Content-Type', 'application/vnd.ms-excel');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        }

        return $this->render(['form' => $form->createView()]);
    }
}