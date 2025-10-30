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

namespace BaksDev\Orders\Order\Messenger\Notifier;

use BaksDev\Auth\Email\Entity\Event\AccountEvent;
use BaksDev\Auth\Email\Repository\AccountEventByUser\AccountEventByUserInterface;
use BaksDev\Auth\Email\Repository\AccountEventNotBlockByEventUid\CurrentAccountEventNotBlockByEventInterface;
use BaksDev\Auth\Email\Repository\CurrentAccountEvent\CurrentAccountEventInterface;
use BaksDev\Auth\Email\Type\Email\AccountEmail;
use BaksDev\Auth\Email\Type\Email\AccountEmailType;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Type\Field\InputField;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileValues\UserProfileValuesInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileValues\UserProfileValuesResult;
use BaksDev\Users\Profile\UserProfile\Type\Event\UserProfileEventUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

/**
 * Отправляем сообщение клиенту на указанный Email уведомление о заказе
 */
#[AsMessageHandler(priority: -100)]
final readonly class SendClientEmailOrderNewsDispatcher
{
    public function __construct(
        #[Autowire(env: 'HOST')] private string $HOST,
        #[Target('ordersOrderLogger')] private LoggerInterface $logger,
        private OrderDetailInterface $orderDetail,
        private MailerInterface $mailer,
        private ParameterBagInterface $parameters,
        private DeduplicatorInterface $deduplicator,
        private OrderEventInterface $orderEventRepository,
        private AccountEventByUserInterface $AccountEventByUserRepository
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        /** Не отправляем сообщение дважды */
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getId(),
                self::class,
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->orderEventRepository
            ->find($message->getEvent());

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            return;
        }

        /** Если статус не New «Новый»  */
        if(false === $OrderEvent->isStatusEquals(OrderStatusNew::class))
        {
            return;
        }

        $OrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($OrderDTO);
        $UserProfileEventUid = $OrderDTO->getUsr()?->getProfile();

        if(false === ($UserProfileEventUid instanceof UserProfileEventUid))
        {
            return;
        }

        $UserUid = $OrderDTO->getUsr()?->getUsr();

        if(false === ($UserUid instanceof UserUid))
        {
            return;
        }

        $AccountEvent = $this->AccountEventByUserRepository
            ->forUser($UserUid)
            ->find();

        if(false === ($AccountEvent instanceof AccountEvent))
        {
            return;
        }

        $AccountEmail = $AccountEvent->getEmail();

        if(empty($AccountEmail->getValue()))
        {
            return;
        }

        $TemplatedEmail = new TemplatedEmail();

        $OrderDetailResult = $this->orderDetail
            ->onOrder($message->getId())
            ->find();

        $email = $TemplatedEmail
            ->from(
                new Address(
                    $this->parameters->get('PROJECT_NO_REPLY'), // email отправителя
                    $this->parameters->get('PROJECT_NAME'), // подпись
                ),
            )
            ->to(new Address($AccountEmail->getValue()))
            ->subject(sprintf('Заказ № %s оформлен в онлайн-магазине %s', $OrderDetailResult->getOrderNumber(), $this->HOST))
            ->htmlTemplate('@orders-order/user/email/new.html.twig')
            ->context([
                'order' => $OrderDetailResult,
                'senderName' => $this->parameters->get('PROJECT_NAME'),
                'host' => $this->HOST,
            ]);


        // Отправляем письмо пользователю
        $this->mailer->send($email);

        $this->logger->info(
            sprintf('Оправили уведомление о заказе %s клиенту на Email %s',
                $OrderDetailResult->getOrderNumber(),
                $AccountEmail->getValue()),
        );

        $Deduplicator->save();
    }
}
