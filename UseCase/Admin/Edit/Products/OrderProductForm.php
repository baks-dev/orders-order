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

namespace BaksDev\Orders\Order\UseCase\Admin\Edit\Products;

use BaksDev\Products\Product\Repository\UpdateProductQuantity\AddProductQuantityInterface;
use BaksDev\Products\Product\Repository\UpdateProductQuantity\SubProductQuantityInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderProductForm extends AbstractType
{
    private int|false $total = false;

    private int|false $newTotal = false;

    private bool $error = false;

    public function __construct(
        private readonly RequestStack $request,
        private readonly AddProductQuantityInterface $AddProductQuantity,
        private readonly SubProductQuantityInterface $SubProductQuantity,

    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('price', Price\OrderPriceForm::class);

        $builder->add('product', HiddenType::class);
        $builder->add('offer', HiddenType::class);
        $builder->add('variation', HiddenType::class);
        $builder->add('modification', HiddenType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event): void {

            /** @var OrderProductDTO $OrderProductDTO */
            $OrderProductDTO = $event->getData();

            if(is_null($OrderProductDTO))
            {
                return;
            }

            if(false === $this->total)
            {
                /** Присваиваем первоначальное значение */
                $this->total = $OrderProductDTO->getPrice()->getTotal();
                return;
            }

            if(false === $this->newTotal)
            {
                /** Присваиваем первоначальное значение */
                $this->newTotal = $OrderProductDTO->getPrice()->getTotal();
                return;
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event): void {

            /** TODO Фиксить проблемы (ломается добавление в заказ из-за getCard) */
            return;

            if($this->total === false || $this->newTotal === false)
            {
                return;
            }

            /** @var OrderProductDTO $OrderProductDTO */
            $OrderProductDTO = $event->getData();

            if(is_null($OrderProductDTO))
            {
                return;
            }

            $Session = $this->request->getSession();

            $card = $OrderProductDTO->getCard();
            $quantity = $card['product_quantity'];

            /** Если количество изменилось в БОЛЬШУЮ сторону - обновляем и добавляем новый резерв */

            if($this->newTotal > $this->total)
            {
                /**
                 * Если продукт изменился - нельзя обновить на увеличение
                 */

                if($card['event'] !== $card['current_event'])
                {
                    $Session
                        ->getFlashBag()
                        ->add('Ошибка обновления количества',
                            sprintf('Стоимость товара %s или его наличие изменилось! Нельзя увеличить количество по новой цене либо на несуществующее наличие.', $card['product_name'])
                        );

                    $event->getForm()->addError(new FormError('Ошибка обновления количества'));

                    return;
                }

                $total = $this->newTotal - $this->total;


                /**
                 * Если доступное количество недостаточно для резерва
                 */

                if($total > $quantity)
                {
                    $Session
                        ->getFlashBag()
                        ->add('Ошибка обновления количества ',
                            sprintf('Недостаточное количество продукции %s для резерва', $card['product_name'])
                        );

                    $event->getForm()->addError(new FormError('Ошибка обновления количества'));

                    return;
                }


                //                $this->AddProductQuantity
                //                    ->forEvent($OrderProductDTO->getProduct())
                //                    ->forOffer($OrderProductDTO->getOffer())
                //                    ->forVariation($OrderProductDTO->getVariation())
                //                    ->forModification($OrderProductDTO->getModification())
                //                    ->addQuantity(false)
                //                    ->addReserve($total)
                //                    ->update();

                return;
            }

            /**
             * Если количество изменилось в МЕНЬШУЮ сторону - обновляем и снимаем резерв
             */

            if($this->total > $this->newTotal)
            {
                $total = $this->total - $this->newTotal;

                /** Снимаем резерв в карточке */
                //                $this->SubProductQuantity
                //                    ->forEvent($OrderProductDTO->getProduct())
                //                    ->forOffer($OrderProductDTO->getOffer())
                //                    ->forVariation($OrderProductDTO->getVariation())
                //                    ->forModification($OrderProductDTO->getModification())
                //                    ->subQuantity(false)
                //                    ->subReserve($total)
                //                    ->update();
            }
        });


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderProductDTO::class,
            'attr' => ['class' => 'order-basket']
        ]);
    }

}
