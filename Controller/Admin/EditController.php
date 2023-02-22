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

//use App\Module\Product\Entity\Category;
//use App\Module\Product\Entity\Product\Info;
//use App\Module\Product\Entity\Product\Event;
//use App\Module\Product\Handler\Admin\Product\NewEdit\Handler;
//use App\Module\Product\Handler\Admin\Product\NewEdit\ProductForm;
//use App\Module\Product\Type\Category\Id\CategoryUid;
use BaksDev\Products\Category\Repository\CategoryPropertyById\CategoryPropertyByIdInterface;
use BaksDev\Products\Category\Type\Event\CategoryEvent;
use BaksDev\Products\Category\Type\Id\CategoryUid;
use BaksDev\Core\Services\Security\RoleSecurity;
use BaksDev\Orders\Order\Entity\Event\Event;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\Category\CategoryCollectionDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\ProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\ProductForm;
use BaksDev\Orders\Order\UseCase\ProductAggregate;
use BaksDev\Core\Controller\AbstractController;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[RoleSecurity(['ROLE_ADMIN', 'ROLE_ORDERS_EDIT'])]
final class EditController extends AbstractController
{
    #[Route('/admin/order/edit/{id}', name: 'admin.order.newedit.edit', methods: ['GET', 'POST'])]
    #[ParamConverter('Event', Event::class)]
    public function edit(
      Request $request,
      //ProductAggregate $handler,
      //Event $Event,
      //CategoryPropertyByIdInterface $categoryProperty,
    ) : Response
    {
        
        dd('admin.order.newedit.edit');

        $product = new ProductDTO();
        $Event->getDto($product);

        /* Форма добавления */
        $form = $this->createForm(ProductForm::class, $product);
        $form->handleRequest($request);
  
        
        if($form->isSubmitted() && $form->isValid())
        {
            $handle = $handler->handle($product);
    
            if($handle)
            {
                $this->addFlash('success', 'admin.order.update.success', 'products.order');
                return $this->redirectToRoute('Product:admin.order.index');
            }
        }
        
        return $this->render(['form' => $form->createView()]);
        
    }

//    #[Route('/zcnimskdzz/style', name: 'admin.order.newedit.new.css', methods: ['GET'], format: "css")]
//    public function css() : Response
//    {
//        return $this->assets(
//          [
//            '/plugins/datepicker/datepicker.min.css', // Календарь
//            '/plugins/nice-select2/nice-select2.min.css', // Select2
//           // '/css/select2.min.css', // Select2
//           // '/css/select2.min.css', // Select2
//          ]);
//    }
//
//    #[Route('/zcnimskdzz/app', name: 'admin.order.newedit.new.js', methods: ['GET'], format: "js")]
//    public function js() : Response
//    {
//        return $this->assets
//        (
//          [
//
//            '/plugins/semantic/semantic.min.js',
//            '/plugins/nice-select2/nice-select2.min.js', // Select2
//
//            /* Календарь */
//            '/plugins/datepicker/datepicker.min.js',
//            '/plugins/datepicker/datepicker.lang.min.js',
//            '/plugins/datepicker/init.min.js',
//
//            '/order/order.min.js',
//
//          ]);
//    }
    
}