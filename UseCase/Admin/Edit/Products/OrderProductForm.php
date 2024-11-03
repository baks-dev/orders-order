<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderProductForm extends AbstractType
{
    private int|false $total = false;

    public function __construct(
        private readonly RequestStack $request,
        private readonly AddProductQuantityInterface $AddProductQuantity,
        private readonly SubProductQuantityInterface $SubProductQuantity,

    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('price', Price\OrderPriceForm::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event): void {

            /** @var OrderProductDTO $OrderProductDTO */
            $OrderProductDTO = $event->getData();

            if($OrderProductDTO)
            {
                if(false === $this->total)
                {
                    $this->total = $OrderProductDTO->getPrice()->getTotal();
                }

                if(false !== $this->total && $this->total !== $OrderProductDTO->getPrice()->getTotal())
                {
                    $Session = $this->request->getSession();

                    /** Если количество изменилось в БОЛЬШУЮ сторону - проверяем доступное наличие и обновляем резерв */
                    if($OrderProductDTO->getPrice()->getTotal() > $this->total)
                    {
                        $card = $OrderProductDTO->getCard();

                        if($card['event'] !== $card['current_event'])
                        {
                            $Session = $this->request->getSession();

                            $Session
                                ->getFlashBag()
                                ->add('Ошибка обновления количества',
                                    sprintf('Стоимость товара %s или его наличие изменилось! Нельзя увеличить количество по новой цене либо на несуществующее наличие.', $card['product_name'])
                                );

                            return;
                        }

                        // Разница в новом количестве
                        $newTotal = $OrderProductDTO->getPrice()->getTotal() - $this->total;

                        $quantity = $card['product_quantity'];

                        $update = false;

                        /**
                         * Проверяем, что нового резерва будет достаточно
                         */

                        if($quantity >= $newTotal)
                        {
                            /** Если количество изменилось в БОЛЬШУЮ сторону - обновляем и добавляем новый резерв */

                            $update = $this->AddProductQuantity
                                ->forEvent($OrderProductDTO->getProduct())
                                ->forOffer($OrderProductDTO->getOffer())
                                ->forVariation($OrderProductDTO->getVariation())
                                ->forModification($OrderProductDTO->getModification())
                                ->addQuantity(false)
                                ->addReserve($newTotal)
                                ->update();
                        }
                        else
                        {
                            /** Присваиваем предыдущее значение и уведомляем пользователя, что нет необходимого наличия */
                            $OrderProductDTO->getPrice()->setTotal($this->total);
                        }

                        if(false === $update)
                        {
                            $Session
                                ->getFlashBag()
                                ->add('Ошибка обновления количества ',
                                    sprintf('Недостаточное количество продукции %s для резерва', $card['product_name'])
                                );
                        }
                    }

                    /** Если количество изменилось в МЕНЬШУЮ сторону - обновляем и снимаем резерв */
                    if($this->total > $OrderProductDTO->getPrice()->getTotal())
                    {
                        $newTotal = $this->total - $OrderProductDTO->getPrice()->getTotal();

                        /** Снимаем резерв в карточке */
                        $this->SubProductQuantity
                            ->forEvent($OrderProductDTO->getProduct())
                            ->forOffer($OrderProductDTO->getOffer())
                            ->forVariation($OrderProductDTO->getVariation())
                            ->forModification($OrderProductDTO->getModification())
                            ->subQuantity(false)
                            ->subReserve($newTotal)
                            ->update();
                    }
                }

            }
        });


        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event): void {

            /** @var OrderProductDTO $OrderProductDTO */
            $OrderProductDTO = $event->getData();

            $card = $OrderProductDTO->getCard();

            $newTotal = $OrderProductDTO->getPrice()->getTotal() - $this->total;
            $quantity = $card['product_quantity'];

            /** Если фактическое наличие недостаточно для резерва */
            if($newTotal > $quantity)
            {
                $event->getForm()->addError(new FormError('Ошибка обновления количества'));
            }

            if(false !== $this->total && $this->total >= $OrderProductDTO->getPrice()->getTotal())
            {
                return;
            }

            /** Если событие продукта (цена, описание, либо наличие) изменилось - запрещаем изменять на повышение */
            if($card['event'] !== $card['current_event'])
            {
                $event->getForm()->addError(new FormError('Ошибка обновления количества'));
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
