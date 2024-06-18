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

namespace BaksDev\Orders\Order\Messenger\Notifier;

use BaksDev\Auth\Email\Type\Email\AccountEmail;
use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Lock\AppLockInterface;
use BaksDev\Core\Type\Field\InputField;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileValues\UserProfileValuesInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler(priority: 0)]
final class SendClientEmailOrderNews
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private OrderDetailInterface $orderDetail;
    private UserProfileValuesInterface $userProfileValues;
    private MailerInterface $mailer;
    private ParameterBagInterface $parameters;
    private string $HOST;
    private DeduplicatorInterface $deduplicator;


    public function __construct(
        #[Autowire(env: 'HOST')] string $HOST,
        EntityManagerInterface $entityManager,
        LoggerInterface $ordersOrderLogger,
        OrderDetailInterface $orderDetail,
        UserProfileValuesInterface $userProfileValues,
        MailerInterface $mailer,
        ParameterBagInterface $parameters,
        DeduplicatorInterface $deduplicator
    ) {


        $this->entityManager = $entityManager;
        $this->entityManager->clear();
        $this->logger = $ordersOrderLogger;
        $this->orderDetail = $orderDetail;
        $this->userProfileValues = $userProfileValues;

        $this->mailer = $mailer;
        $this->parameters = $parameters;
        $this->HOST = $HOST;

        $this->deduplicator = $deduplicator;
    }

    /**
     * Отправляем сообщение клиенту на указанный Email уведомление о новом заказе
     */
    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace(md5(self::class))
            ->deduplication([
                (string) $message->getId(),
                OrderStatusNew::STATUS
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Новый заказ не имеет предыдущего события */
        if($message->getLast())
        {
            return;
        }

        $OrderEvent = $this->entityManager->getRepository(OrderEvent::class)->find($message->getEvent());

        if(!$OrderEvent)
        {
            return;
        }

        $OrderDTO = new EditOrderDTO();
        $OrderEvent->getDto($OrderDTO);

        /** Если статус не New «Новый»  */
        if(false === $OrderDTO->getStatus()->equals(OrderStatusNew::class))
        {
            return;
        }

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


        $AccountEmail = new AccountEmail($AccountEmail['value']);

        $TemplatedEmail = new TemplatedEmail();


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

        $this->logger->notice('Оправили уведомление клиенту на Email '.$AccountEmail);

        $Deduplicator->save();

    }

}
