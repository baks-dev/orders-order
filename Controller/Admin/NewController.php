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

namespace BaksDev\Orders\Order\Controller\Admin;


use BaksDev\Core\Services\Security\RoleSecurity;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderForm;
use BaksDev\Orders\Order\UseCase\OrderAggregate;
use BaksDev\Products\Category\Type\Id\CategoryUid;
use BaksDev\Core\Controller\AbstractController;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[RoleSecurity(['ROLE_ADMIN', 'ROLE_ORDERS_NEW'])]
final class NewController extends AbstractController
{
    #[Route('/admin/order/new', name: 'admin.order.newedit.new', methods: ['GET', 'POST'])]
    public function new(
      Request $request,
      OrderAggregate $handler,
      //TranslatorInterface $translator,
      //Handler $handler,
      //EntityManagerInterface $em,
    ) : Response
    {

        $order = new OrderDTO();
        
        /* Форма добавления */
        $form = $this->createForm(OrderForm::class, $order);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {

            if($form->has('Save'))
            {
                $handle = $handler->handle($order);
    
                if($handle)
                {
                    $this->addFlash('success', 'admin.order.new.success', 'admin.order');
                    return $this->redirectToRoute('Orders:admin.order.index');
                }
            }
        }
        
        return $this->render(['form' => $form->createView()]);
        
    }
}