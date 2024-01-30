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

namespace BaksDev\Orders\Order\UseCase\Admin\New\User\Delivery\Field;

use BaksDev\Contacts\Region\Form\ContactRegionChoice\ContactRegionFieldForm;
use BaksDev\Core\Services\Fields\FieldsChoice;
use BaksDev\Delivery\Repository\DeliveryByTypeProfileChoice\DeliveryByTypeProfileChoiceInterface;
use BaksDev\Delivery\Repository\FieldByDeliveryChoice\FieldByDeliveryChoiceInterface;
use BaksDev\Delivery\Type\Field\DeliveryFieldUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class OrderDeliveryFieldForm extends AbstractType
{
	
	private FieldsChoice $fieldsChoice;
    private DeliveryByTypeProfileChoiceInterface $deliveryFields;

    public function __construct(
        DeliveryByTypeProfileChoiceInterface $deliveryFields,
		FieldsChoice $fieldsChoice,
	)
	{
		$this->fieldsChoice = $fieldsChoice;
        $this->deliveryFields = $deliveryFields;
    }


	public function buildForm(FormBuilderInterface $builder, array $options) : void
	{
		$builder->add('field', HiddenType::class);
		
		$builder->get('field')->addModelTransformer
		(
			new CallbackTransformer(
				function($field) {
					return $field instanceof DeliveryFieldUid ? $field->getValue() : $field;
				},
				function($field) {
					return new DeliveryFieldUid($field);
				}
			)
		);


		//$builder->add('value', HiddenType::class, ['required' => false]);


        $builder->add
        (
            'value',
            ContactRegionFieldForm::class,
            //[
//                'label' => $DeliveryField->getAttr(),
//                'help' => $DeliveryField->getOption(),
//                'required' => $DeliveryField->getRequired(),
//                'constraints' => $DeliveryField->getRequired() ? [new NotBlank()] : [],
            //]
        );


		$builder->addEventListener(
			FormEvents::PRE_SET_DATA,
			function(FormEvent $event) {
				
				/* @var OrderDeliveryFieldDTO $data */
				$data = $event->getData();
				$form = $event->getForm();

				if($data)
				{
					/** @var DeliveryFieldUid $DeliveryField */
					$DeliveryField = $data->getField();
					
					if($DeliveryField->getType())
					{
						$fieldType = $this->fieldsChoice->getChoice($DeliveryField->getType());

						$form->add
						(
							'value',
							$fieldType->form(),
							[
								'label' => $DeliveryField->getAttr(),
								'help' => $DeliveryField->getOption(),
								'required' => $DeliveryField->getRequired(),
								'constraints' => $DeliveryField->getRequired() ? [new NotBlank()] : [],
							]
						);
					}
				}
			}
		);



        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                /** @var OrderDeliveryFieldDTO $data */

                $data = $event->getData();
                $form = $event->getForm()->getParent();

                //if($data?->getField())
                //{
                    //$fieldType = $this->deliveryFields->fetchDeliveryByProfile($data->getField());

                    //dd($fieldType);


                // 018d5184-d11f-71fc-84ae-2aa64c7aea98


                // 018d5175-8b4a-7420-9a57-a7c1ce62f3f2
                //dd($data);


//                    $form->add
//                    (
//                        'value',
//                        ContactRegionFieldForm::class,
//                        [
//                            'label' => '11111111',
//                            'help' => '2222222222',
//                            'required' => false
//                        ]
//                    );

                //}


                //$form->add('field', self::class, ['label' => false]);

                    //dump('POST_SUBMIT '.self::class);
                    //dd($data);



            }
        );

	}
	
	
	public function configureOptions(OptionsResolver $resolver) : void
	{
		$resolver->setDefaults([
			'data_class' => OrderDeliveryFieldDTO::class,
		]);
	}
	
}