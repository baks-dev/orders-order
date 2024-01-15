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

namespace BaksDev\Orders\Order\UseCase\Admin\AddProduct;


use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Manufacture\Part\Entity\ManufacturePart;
use BaksDev\Manufacture\Part\Entity\Products\ManufacturePartProduct;
use BaksDev\Manufacture\Part\Messenger\ManufacturePartMessage;
use BaksDev\Manufacture\Part\Repository\OpenManufacturePartByAction\OpenManufacturePartByActionInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderDraft\OpenOrderDraftInterface;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\UsersTable\Entity\Actions\Event\UsersTableActionsEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderAddProductsHandler
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private LoggerInterface $logger;

    private MessageDispatchInterface $messageDispatch;

    private OpenManufacturePartByActionInterface $openManufacturePartByAction;

//    private ActionByProductInterface $actionByProduct;
//    private ActionByCategoryInterface $actionByCategory;

    //private ManufacturePartHandler $ManufacturePartHandler;
    private OpenOrderDraftInterface $draft;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        MessageDispatchInterface $messageDispatch,
        OpenOrderDraftInterface $draft
        //OpenManufacturePartByActionInterface $openManufacturePartByAction,

//        ActionByProductInterface $actionByProduct,
//        ActionByCategoryInterface $actionByCategory,
//        ManufacturePartHandler $ManufacturePartHandler,
    )
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->messageDispatch = $messageDispatch;
        //$this->openManufacturePartByAction = $openManufacturePartByAction;

//        $this->actionByProduct = $actionByProduct;
//        $this->actionByCategory = $actionByCategory;
//        $this->ManufacturePartHandler = $ManufacturePartHandler;
        $this->draft = $draft;
    }

    /** @see OrderProduct */
    public function handle(
        OrderAddProductsDTO $command,
        UserProfileUid $current,
    ): string|OrderEvent
    {

        /**
         *  Валидация ManufacturePartProductsDTO
         */
        $errors = $this->validator->validate($command);

        if(count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);

            return $uniqid;
        }

        if($command->getOrderProductId())
        {
            $OrderProduct = $this->entityManager->getRepository(OrderProduct::class)->find($command->getOrderProductId());

            /** Получаем активную открытую производственную партию ответственного лица */
            $OrderEvent = $this->entityManager->getRepository(OrderEvent::class)->find($OrderProduct->getEvent());

        }

        /**
         * Добавляем к открытому черновику продукт
         */

        else
        {

            /** Получаем активную открытую производственную партию ответственного лица */
            $OrderEvent = $this->draft->findDraftEventOrNull($current);

            if(!$OrderEvent)
            {
                $uniqid = uniqid('', false);
                $errorsString = sprintf(
                    'У профиля %s нет открытого заказа',
                    $current,
                );
                $this->logger->error($uniqid.': '.$errorsString);

                return $uniqid;
            }

            $OrderProduct = new OrderProduct($OrderEvent);
            $this->entityManager->persist($OrderProduct);
        }

        $OrderProduct->setEntity($command);


//        $ManufacturePartProduct = new ManufacturePartProduct($ManufacturePartEvent);
//        $ManufacturePartProduct->setEntity($command);

        /**
         * Валидация Event
         */
        $errors = $this->validator->validate($OrderProduct);

        if(count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);

            return $uniqid;
        }


        //dd($OrderProduct->getEvent());

        $this->entityManager->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new OrderMessage($OrderEvent->getMain(), $OrderEvent->getId()),
            transport: 'orders-order'
        );


        // 'manufacture_part_high'
        return $OrderEvent;
    }
}