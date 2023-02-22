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

namespace BaksDev\Orders\Order\UseCase;

use BaksDev\Orders\Order\Entity\Event\EventInterface;
use App\System\Type\Modify\ModifyActionEnum;
use Doctrine\ORM\EntityManagerInterface;
use BaksDev\Orders\Order\Entity;

final class OrderAggregate
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(
      EntityManagerInterface $entityManager,
    )
    {
        $this->entityManager = $entityManager;
    }
    
    public function handle(EventInterface $command) : bool|Entity\Order
    {

        if($command->getEvent())
        {
            $EventRepo = $this->entityManager->getRepository(Entity\Event\Event::class)->find($command->getEvent());
            $Event = $EventRepo->cloneEntity();
        }
        else
        {
            $Event = new Entity\Event\Event();
        }
        
        $Event->setEntity($command);
    
    
        //dd($command, $Event);
        
        $this->entityManager->clear();
        $this->entityManager->persist($Event);
        
        /** @var Entity\Order $order */
        if($Event->getOrders())
        {
            /* Восстанавливаем из корзины */
            if($Event->isModifyActionEquals(ModifyActionEnum::RESTORE))
            {
                $Order = new Entity\Order();
                $Order->setId($Event->getOrders());
                $this->entityManager->persist($Order);
                
                $remove = $this->entityManager->getRepository(Entity\Event\Event::class)
                  ->find($command->getEvent());
                $this->entityManager->remove($remove);
                
            }
            else
            {
                $Order = $this->entityManager->getRepository(Entity\Order::class)->findOneBy(
                  ['event' => $command->getEvent()]);
            }
            
            if(empty($Order))
            {
                return false;
            }
        }
        else
        {
            $Order = new Entity\Order();
            $this->entityManager->persist($Order);
            
            $Event->setOrders($Order);
            
        }
        
        $Order->setEvent($Event);
        
        /* Удаляем категорию */
        if($Event->isModifyActionEquals(ModifyActionEnum::DELETE))
        {
            $this->entityManager->remove($Order);
        }
        
        
        
        //dump($Event);
        $this->entityManager->flush();

        //        dump($this->entityManager->getUnitOfWork());
        //
        //        dd($Event);
        //        dd($command);
        
        return $Order;
    }
    
}