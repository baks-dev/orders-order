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
use BaksDev\Orders\Order\Entity;
use BaksDev\Orders\Order\UseCase\Admin\Delete\Product\ProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Delete\DeleteForm;
use BaksDev\Orders\Order\UseCase\OrderAggregate;
use BaksDev\Core\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[RoleSecurity(['ROLE_ADMIN', 'ROLE_ORDERS_DELETE'])]
final class DeleteController extends AbstractController
{
    
    #[Route('/admin/order/delete/{id}', name: 'admin.order.delete', methods: ['POST', 'GET'])]
    public function delete(
      Request $request,
      //OrderAggregate $handler,
      //Entity\Event\Event $Event,
    ) : Response
    {
        
        dd('admin.order.delete');

        $category = new ProductDTO();
        $Event->getDto($category);
        
        $form = $this->createForm(DeleteForm::class, $category, [
          'action' => $this->generateUrl('Product:admin.order.delete', ['id' => $category->getEvent()]),
        ]);
        $form->handleRequest($request);
        
        
        if($form->isSubmitted() && $form->isValid())
        {
            if($form->has('delete'))
            {
                $handle = $handler->handle($category);
                
                if($handle)
                {
                    $this->addFlash('success', 'admin.order.delete.success', 'products.order');
                    return $this->redirectToRoute('Product:admin.order.index');
                }
            }
            
            $this->addFlash('danger', 'admin.order.delete.danger', 'products.order');
            return $this->redirectToRoute('Product:admin.order.index');
            
            //return $this->redirectToReferer();
        }

        return $this->render
        (
          [
            'form' => $form->createView(),
            'name' => $Event->getNameByLocale($this->getLocale()) /*  название согласно локали  */
          ]
        );
    }
    
}