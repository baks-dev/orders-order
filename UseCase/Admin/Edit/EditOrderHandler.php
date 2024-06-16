<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use DomainException;

final class EditOrderHandler extends AbstractHandler
{

    public function handle(EditOrderDTO $command,): string|Order
    {
        /** Валидация DTO  */
        $this->validatorCollection->add($command);

        $this->main = new Order();
        $this->event = new OrderEvent();

        try
        {
            $command->getEvent() ? $this->preUpdate($command, true) : $this->prePersist($command);
        }
        catch(DomainException $errorUniqid)
        {
            return $errorUniqid->getMessage();
        }

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->entityManager->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new OrderMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
            transport: 'orders-order'
        );

        return $this->main;
    }


    //    public function OLDhandle(OrderDTO $command,): string|Order
    //    {
    //        // Валидация
    //        $errors = $this->validator->validate($command);
    //
    //        if(count($errors) > 0)
    //        {
    //
    //            /** Ошибка валидации */
    //            $uniqid = uniqid('', false);
    //            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);
    //
    //            return $uniqid;
    //        }
    //
    //        if($command->getEvent())
    //        {
    //            $EventRepo = $this->entityManager->getRepository(OrderEvent::class)->find(
    //                $command->getEvent()
    //            );
    //
    //            if(null === $EventRepo)
    //            {
    //                $uniqid = uniqid('', false);
    //                $errorsString = sprintf(
    //                    'Not found %s by id: %s',
    //                    OrderEvent::class,
    //                    $command->getEvent()
    //                );
    //                $this->logger->error($uniqid.': '.$errorsString);
    //
    //                return $uniqid;
    //            }
    //
    //            $EventRepo->setEntity($command);
    //            $EventRepo->setEntityManager($this->entityManager);
    //            $Event = $EventRepo->cloneEntity();
    //        }
    //        else
    //        {
    //            $Event = new OrderEvent();
    //            $Event->setEntity($command);
    //            $this->entityManager->persist($Event);
    //        }
    //
    //        //        $this->entityManager->clear();
    //        //        $this->entityManager->persist($Event);
    //
    //
    //        if($Event->getOrders())
    //        {
    //            $Main = $this->entityManager->getRepository(Order::class)
    //                ->findOneBy(['event' => $command->getEvent()]);
    //
    //            if(empty($Main))
    //            {
    //                $uniqid = uniqid('', false);
    //                $errorsString = sprintf(
    //                    'Not found %s by event: %s',
    //                    Order::class,
    //                    $command->getEvent()
    //                );
    //                $this->logger->error($uniqid.': '.$errorsString);
    //
    //                return $uniqid;
    //            }
    //        }
    //        else
    //        {
    //            $Main = new Order();
    //            $this->entityManager->persist($Main);
    //            $Event->setMain($Main);
    //        }
    //
    //        // присваиваем событие корню
    //        $Main->setEvent($Event);
    //
    //
    //        /**
    //         * Валидация Event
    //         */
    //
    //        $errors = $this->validator->validate($Event);
    //
    //        if(count($errors) > 0)
    //        {
    //            /** Ошибка валидации */
    //            $uniqid = uniqid('', false);
    //            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);
    //
    //            return $uniqid;
    //        }
    //
    //
    //        /**
    //         * Валидация Main
    //         */
    //
    //        $errors = $this->validator->validate($Event);
    //
    //        if(count($errors) > 0)
    //        {
    //            /** Ошибка валидации */
    //            $uniqid = uniqid('', false);
    //            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);
    //
    //            return $uniqid;
    //        }
    //
    //
    //        $this->entityManager->flush();
    //
    //        /* Отправляем сообщение в шину */
    //        $this->messageDispatch->dispatch(
    //            message: new OrderMessage($Main->getId(), $Main->getEvent(), $command->getEvent()),
    //            transport: 'orders-order'
    //        );
    //
    //        return $Main;
    //    }
}
