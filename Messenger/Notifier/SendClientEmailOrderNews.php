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

use BaksDev\Auth\Email\Type\Email\AccountEmail;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Type\Field\InputField;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileValues\UserProfileValuesInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler(priority: 0)]
final class SendClientEmailOrderNews
{
    public function __construct(
        #[Autowire(env: 'HOST')] private readonly string $HOST,
        #[Target('ordersOrderLogger')] private readonly LoggerInterface $logger,
        private readonly OrderDetailInterface $orderDetail,
        private readonly UserProfileValuesInterface $userProfileValues,
        private readonly MailerInterface $mailer,
        private readonly ParameterBagInterface $parameters,
        private readonly DeduplicatorInterface $deduplicator,
        private readonly OrderEventInterface $orderEventRepository,
    ) {}

    /**
     * Отправляем сообщение клиенту на указанный Email уведомление о новом заказе
     */
    public function __invoke(OrderMessage $message): void
    {
        /** Не отправляем сообщение дважды */
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getId(),
                OrderStatusNew::STATUS,
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $OrderEvent = $this->orderEventRepository->find($message->getEvent());

        if($OrderEvent === false)
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

        if(!$UserProfileEventUid)
        {
            return;
        }

        /** Получаем Email клиента */

        $AccountEmail = $this->userProfileValues
            ->field(new InputField('account_email'))
            ->findFieldByEvent($UserProfileEventUid);

        if(empty($AccountEmail) || empty($AccountEmail['value']))
        {
            return;
        }

        $TemplatedEmail = new TemplatedEmail();

        $AccountEmail = new AccountEmail($AccountEmail['value']);
        $orderDetail = $this->orderDetail->fetchDetailOrderAssociative($message->getId());

        $email = $TemplatedEmail
            ->from(
                new Address(
                    $this->parameters->get('PROJECT_NO_REPLY'), // email отправителя
                    $this->parameters->get('PROJECT_NAME') // подпись
                )
            )
            ->to(new Address($AccountEmail->getValue()))
            ->subject('Заказ № '.$orderDetail['order_number'].' оформлен в онлайн-магазине '.$this->HOST)
            ->htmlTemplate('@orders-order/user/email/new.html.twig')
            ->context([
                'order' => $orderDetail,
                'senderName' => $this->parameters->get('PROJECT_NAME'),
                'host' => $this->HOST,
            ]);


        // Отправляем письмо пользователю
        $this->mailer->send($email);
        $this->logger->info('Оправили уведомление о заказе клиенту на Email '.$AccountEmail);

        $Deduplicator->save();
    }
}
