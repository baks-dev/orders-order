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
 */

declare(strict_types=1);

namespace BaksDev\Orders\Order\UseCase\Admin\Finance;


use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Orders\Order\Entity\Event\Finance\OrderFinance;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use Doctrine\ORM\EntityManagerInterface;

final class OrderFinanceHandler extends AbstractHandler
{
    public function __construct(
        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,

        private readonly CurrentOrderEventInterface $CurrentOrderEventRepository
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }

    /** @see OrderFinance */
    public function handle(OrderFinanceDTO $command): string|OrderFinance
    {
        $this->setCommand($command);

        $OrderFinance = $this
            ->getRepository(OrderFinance::class)
            ->findOneBy(['main' => $command->getMain()]);

        if(false === ($OrderFinance instanceof OrderFinance))
        {
            /** Получаем текущий объект события */
            $OrderEvent = $this->CurrentOrderEventRepository
                ->forOrder($command->getMain())
                ->find();

            if(false === ($OrderEvent instanceof OrderEvent))
            {
                $this->validatorCollection->error(
                    sprintf('Объект OrderEvent по идентификатору %s не найден', $command->getMain()),
                    [self::class.':'.__LINE__],
                );

                return $this->validatorCollection->getErrorUniqid();
            }

            $OrderFinance = new OrderFinance($OrderEvent);
            $this->persist($OrderFinance);
        }

        $OrderFinance->setEntity($command);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        /* Сбрасываем кеш заказов */
        $this->messageDispatch->addClearCacheOther('orders-order');

        return $OrderFinance;
    }
}