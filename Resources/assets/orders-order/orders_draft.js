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


/** Добавить перемещение продукции со склада на склад */


async function changeObjectCategory(forms)
{
    disabledElementsForm(forms);

    document.getElementById('preProduct')?.classList.add('d-none');
    document.getElementById('preOffer')?.classList.add('d-none');
    document.getElementById('preVariation')?.classList.add('d-none');
    document.getElementById('preModification')?.classList.add('d-none');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');

                let preProduct = result.getElementById('preProduct');

                document.getElementById('preProduct').replaceWith(preProduct);

                preProduct ?
                    document
                        ?.getElementById('product')
                        ?.replaceWith(preProduct) :
                    preProduct.innerHTML = '';

                /** SELECT2 */
                let replacer = document.getElementById(forms.name + '_preProduct_preProduct');
                replacer && replacer.type !== 'hidden' ? preProduct.classList.remove('d-none') : null;

                /** Событие на изменение модификации */
                if(replacer)
                {

                    if(replacer.tagName === 'SELECT')
                    {
                        new NiceSelect(replacer, {searchable: true});

                        let focus = document.getElementById(forms.name + '_preProduct_preProduct_select2');
                        focus ? focus.click() : null;
                    }
                }

                /** сбрасываем зависимые поля */
                let preOffer = document.getElementById('preOffer');
                preOffer ? preOffer.innerHTML = '' : null;

                /** сбрасываем зависимые поля */
                let preVariation = document.getElementById('preVariation');
                preVariation ? preVariation.innerHTML = '' : null;

                let preModification = document.getElementById('preModification');
                preModification ? preModification.innerHTML = '' : null;


                if(replacer)
                {

                    replacer.addEventListener('change', function(event)
                    {
                        changeObjectProduct(forms);
                        return false;
                    });
                }
            }

            enableElementsForm(forms);
        });
}


async function changeObjectProduct(forms)
{
    disabledElementsForm(forms);

    document.getElementById('preOffer')?.classList.add('d-none');
    document.getElementById('preVariation')?.classList.add('d-none');
    document.getElementById('preModification')?.classList.add('d-none');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');


                let preOffer = result.getElementById('preOffer');
                preOffer ? document.getElementById('preOffer').replaceWith(preOffer) : preOffer.innerHTML = '';

                if(preOffer)
                {

                    /** SELECT2 */

                    let replaceOfferId = forms.name + '_preProduct_preOffer';

                    let replacer = document.getElementById(replaceOfferId);
                    replacer && replacer.type !== 'hidden' ? preOffer.classList.remove('d-none') : null;

                    if(replacer.tagName === 'SELECT')
                    {
                        new NiceSelect(replacer, {searchable: true});

                        let focus = document.getElementById(forms.name + '_preProduct_preOffer_select2');
                        focus ? focus.click() : null;

                    } else
                    {
                        selectTotal(document.getElementById(forms.name + '_preProduct_preProduct'))
                    }

                }


                /** сбрасываем зависимые поля */
                let preVariation = document.getElementById('preVariation');
                preVariation ? preVariation.innerHTML = '' : null;

                let preModification = document.getElementById('preModification');
                preModification ? preModification.innerHTML = '' : null;


                /** Событие на изменение торгового предложения */
                let offerChange = document.getElementById(forms.name + '_preProduct_preOffer');

                if(offerChange)
                {

                    offerChange.addEventListener('change', function(event)
                    {
                        changeObjectOffer(forms);
                        return false;
                    });
                }


                // return;
                //
                //
                // /** Изменияем список целевых складов */
                // let warehouse = result.getElementById('targetWarehouse');
                //
                //
                // document.getElementById('targetWarehouse').replaceWith(warehouse);
                // document.getElementById('new_order_form_targetWarehouse').addEventListener('change', changeObjectWarehause, false);
                //
                // new NiceSelect(document.getElementById('new_order_form_targetWarehouse'), {
                //     searchable: true,
                //     id: 'select2-' + replaceId
                // });

            }

            enableElementsForm(forms);
        });
}


async function changeObjectOffer(forms)
{
    disabledElementsForm(forms);

    document.getElementById('preVariation')?.classList.add('d-none');
    document.getElementById('preModification')?.classList.add('d-none');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');


    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');


                let preVariation = result.getElementById('preVariation');

                if(preVariation)
                {

                    document.getElementById('preVariation').replaceWith(preVariation);

                    /** SELECT2 */

                    let replacer = document.getElementById(forms.name + '_preProduct_preVariation');
                    replacer && replacer.type !== 'hidden' ? preVariation.classList.remove('d-none') : null;

                    if(replacer)
                    {

                        if(replacer.tagName === 'SELECT')
                        {
                            new NiceSelect(replacer, {searchable: true});

                            let focus = document.getElementById(forms.name + '_preProduct_preVariation_select2');
                            focus ? focus.click() : null;

                            replacer.addEventListener('change', function(event)
                            {
                                changeObjectVariation(forms);
                                return false;
                            });
                        } else
                        {
                            selectTotal(document.getElementById(forms.name + '_preProduct_preOffer'))
                        }

                    }

                } else
                {

                    let targetWarehouse = result.getElementById('targetWarehouse');

                    if(targetWarehouse)
                    {

                        document.getElementById('targetWarehouse').replaceWith(targetWarehouse);

                        /** SELECT2 */
                        let replacerWarehouse = document.getElementById(forms.name + '_preProduct_targetWarehouse');
                        replacer.addEventListener('change', changeObjectWarehause, false);

                        if(replacerWarehouse && replacerWarehouse.tagName === 'SELECT')
                        {
                            new NiceSelect(replacerWarehouse, {searchable: true});
                        }
                    }
                }


                let preModification = document.getElementById('preModification');
                preModification ? preModification.innerHTML = '' : null;


            }

            enableElementsForm(forms);
        });
}


async function changeObjectVariation(forms)
{

    disabledElementsForm(forms);

    document.getElementById('preModification')?.classList.add('d-none');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');


    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');


                let preModification = result.getElementById('preModification');

                if(preModification)
                {

                    document.getElementById('preModification').replaceWith(preModification);

                    /** SELECT2 */
                    let replacer = document.getElementById(forms.name + '_preProduct_preModification');
                    replacer && replacer.type !== 'hidden' ? preModification.classList.remove('d-none') : null;

                    /** Событие на изменение модификации */
                    if(replacer)
                    {

                        if(replacer.tagName === 'SELECT')
                        {
                            new NiceSelect(replacer, {searchable: true});

                            let focus = document.getElementById(forms.name + '_preProduct_preModification_select2');
                            focus ? focus.click() : null;

                            replacer.addEventListener('change', function(event)
                            {
                                selectTotal(this)
                                return false;
                            });

                        } else
                        {
                            selectTotal(document.getElementById(forms.name + '_preProduct_preVariation'))
                        }
                    }
                }
            }

            enableElementsForm(forms);
        });
}


function selectTotal(element)
{
    let index = element.selectedIndex;

    let preProduct_preTotal = document.getElementById('new_order_form_preProduct_preTotal');

    preProduct_preTotal.setAttribute('max', element.options[index].dataset.max);

    // preProduct_preTotal.addEventListener('input', addProductOrder.debounce(3000));

    setTimeout(function()
    {
        let focusTotal = document.getElementById('new_order_form_preProduct_preTotal');
        focusTotal.value = '';
        focusTotal ? focusTotal.focus() : null;
    }, 0);
}


var collectionProductOrder = new Map();

function addProductOrder()
{
    document.getElementById('preOffer')?.classList.add('d-none');
    document.getElementById('preVariation')?.classList.add('d-none');
    document.getElementById('preModification')?.classList.add('d-none');

    /* Блок для новой коллекции КАТЕГОРИИ */
    let $blockCollectionProducts = document.getElementById('collectionProductOrder');

    /* Добавляем новую коллекцию */
    //$addButtonStock.addEventListener('click', function () {

    let $errorFormHandler = null;

    let header = 'Добавить продукцию в заказ';


    let $preTotal = document.getElementById('new_order_form_preProduct_preTotal');
    let $TOTAL = $preTotal.value * 1;

    let $totalMax = $preTotal.getAttribute('max');

    if($TOTAL === undefined || $TOTAL < 1 || $TOTAL > $totalMax)
    {

        if($TOTAL === undefined)
        {
            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"' + header + '"  , ' +
                '"message" : "Ошибка при заполнение количество" }';
        }

        if($TOTAL > $totalMax)
        {
            $warninfFormHandler = '{ "type":"danger" , ' +
                '"header":"' + header + '"  , ' +
                '"message" : "На складе продукции отстутсвует необходимое количество" }';


            createToast(JSON.parse($warninfFormHandler));

        }

        if($TOTAL < 1)
        {
            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"' + header + '"  , ' +
                '"message" : "Не указано количество продукции" }';
        }


    }


    //let $targetWarehouse = document.getElementById('new_order_form_targetWarehouse');
    //if($targetWarehouse.value.length === 0)
    //{
    //
    //    $errorFormHandler = '{ "type":"danger" , ' +
    //        '"header":"Добавить лист закупки продукции"  , ' +
    //        '"message" : "' + $targetWarehouse.options[0].textContent + '" }';
    //
    //}

    //let $destinationWarehouse = document.getElementById('new_order_form_destinationWarehouse');
    //if($destinationWarehouse.value.length === 0)
    //{
    //
    //    $errorFormHandler = '{ "type":"danger" , ' +
    //        '"header":"Добавить лист закупки продукции"  , ' +
    //        '"message" : "' + $destinationWarehouse.options[0].textContent + '" }';
    //
    //}


    let $preProduct = document.getElementById('new_order_form_preProduct_preProduct');

    if($preProduct.value.length === 0)
    {

        $errorFormHandler = '{ "type":"danger" , ' +
            '"header":"' + header + '"  , ' +
            '"message" : "' + $preProduct.options[0].textContent + '" }';

    }


    let $preOffer = document.getElementById('new_order_form_preProduct_preOffer');

    if($preOffer)
    {
        if($preOffer.tagName === 'SELECT' && $preOffer.value.length === 0)
        {

            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"' + header + '"  , ' +
                '"message" : "' + $preOffer.options[0].textContent + '" }';
        }
    }


    let $preVariation = document.getElementById('new_order_form_preProduct_preVariation');

    if($preVariation)
    {
        if($preVariation.tagName === 'SELECT' && $preVariation.value.length === 0)
        {

            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"' + header + '"  , ' +
                '"message" : "' + $preVariation.options[0].textContent + '" }';
        }
    }

    let $preModification = document.getElementById('new_order_form_preProduct_preModification');
    if($preModification)
    {
        if($preModification.tagName === 'SELECT' && $preModification.value.length === 0)
        {

            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"' + header + '"  , ' +
                '"message" : "' + $preModification.options[0].textContent + '" }';
        }
    }


    //if($targetWarehouse.value === $destinationWarehouse.value)
    //{
    //    $errorFormHandler = '{ "type":"danger" , ' +
    //        '"header":"' + header + '"  , ' +
    //        '"message" : "Внутренее перемещение! Выберите другой склад назначения либо отгрузки" }';
    //}

    let mapKey = $preProduct ? $preProduct.value : '';
    mapKey += $preOffer ? $preOffer.value : '';
    mapKey += $preVariation ? $preVariation.value : '';
    mapKey += $preModification ? $preModification.value : '';

    if(collectionProductOrder.has(mapKey))
    {
        $errorFormHandler = '{ "type":"danger" , ' +
            '"header":"' + header + '"  , ' +
            '"message" : "Продукция уже добавлена в список" }';
    }


    /*
    if(collectionProductOrder.size >= 5)
    {
        $errorFormHandler = '{ "type":"danger" , ' +
            '"header":"' + header + '"  , ' +
            '"message" : "Количество в заявке временно ограничено до 5 позиций! Сохраните активную и добавьте новую." }';
    }
     */

    /* Выводим сообщение об ошибке заполнения */

    if($errorFormHandler)
    {
        createToast(JSON.parse($errorFormHandler));
        return false;
    }

    /* получаем прототип коллекции  */
    let $addButtonStock = document.getElementById('new_order_form_addProduct');

    let newForm = $addButtonStock.dataset.prototype;
    let index = $addButtonStock.dataset.index * 1;

    /* Замена '__name__' в HTML-коде прототипа
     вместо этого будет число, основанное на том, сколько коллекций */
    newForm = newForm.replace(/__product__/g, index);
    //newForm = newForm.replace(/__FIELD__/g, index);


    /* Вставляем новую коллекцию */
    let stockDiv = document.createElement('div');

    stockDiv.classList.add('item-collection-product');
    stockDiv.classList.add('w-100');
    stockDiv.innerHTML = newForm;
    $blockCollectionProducts.append(stockDiv);


    let productIndex = $preProduct.selectedIndex;
    let $productName = '<b>' + $preProduct.options[$preProduct.selectedIndex].dataset.name + '</b>';


    //let targetWarehouseIndex = $targetWarehouse.selectedIndex;
    //let $targetWarehouseName = $targetWarehouse.options[targetWarehouseIndex].dataset.name;
    //
    //let destinationWarehouseIndex = $destinationWarehouse.selectedIndex;
    //let $destinationWarehouseName = $destinationWarehouse.options[destinationWarehouseIndex].textContent;

    //let variationIndex = $preVariation.selectedIndex;
    let $variationName = $preVariation?.tagName === 'SELECT' ? '<small class="text-muted">' + document.querySelector('label[for="' + $preVariation.id + '"]').textContent + '</small> <b>' + $preVariation.options[$preVariation.selectedIndex].dataset.name + '</b>' : '';

    //let modificationIndex = $preModification.selectedIndex;
    let $modificationName = $preModification?.tagName === 'SELECT' ? '<small class="text-muted">' + document.querySelector('label[for="' + $preModification.id + '"]').textContent + '</small> <b>' + $preModification.options[$preModification.selectedIndex].dataset.name + '</b>' : '';

    //let offerIndex = $preOffer.selectedIndex;
    let $offerName = $preOffer?.tagName === 'SELECT' ? '<small class="text-muted">' + document.querySelector('label[for="' + $preOffer.id + '"]').textContent + '</small> <b>' + $preOffer.options[$preOffer.selectedIndex].dataset.name + '</b>' : '';


    let $productTextBlock = stockDiv.querySelector('#product-text-' + index);
    $productTextBlock.innerHTML = $productName + ' ' + $offerName + ' ' + $variationName + ' ' + $modificationName + '&nbsp; : &nbsp;<b>' + $TOTAL + ' шт.</b>';

    /** Заполняем значения скрытых элементо */

    let $product = stockDiv.querySelector('#new_order_form_product_' + index + '_product');

    let $offer = stockDiv.querySelector('#new_order_form_product_' + index + '_offer');

    let $variation = stockDiv.querySelector('#new_order_form_product_' + index + '_variation');

    let $modification = stockDiv.querySelector('#new_order_form_product_' + index + '_modification');

    let $total = stockDiv.querySelector('#new_order_form_product_' + index + '_price_total')


    //$warehouse.value = $targetWarehouse.value;
    //$destination.value = $destinationWarehouse.value;

    $product.value = $preProduct ? $preProduct.value : '';
    $offer.value = $preOffer ? $preOffer.value : '';
    $variation.value = $preVariation ? $preVariation.value : '';
    $modification.value = $preModification ? $preModification.value : '';
    $total.value = $preTotal.value;

    /* Удаляем при клике колекцию СЕКЦИЙ */
    stockDiv.querySelector('.del-item-product').addEventListener('click', function()
    {
        this.closest('.item-collection-product').remove();
        index = $addButtonStock.dataset.index * 1;
        $addButtonStock.dataset.index = (index - 1).toString();

        collectionProductOrder.delete(mapKey);

    });

    /* Увеличиваем data-index на 1 после вставки новой коллекции */
    $addButtonStock.dataset.index = (index + 1).toString();

    /* Обнуляем количество в перформе */
    $preTotal.value = null;

    collectionProductOrder.set(mapKey, $total.value);

    document.getElementById('new_order_form_preProduct_preOffer_select2')?.remove();
    document.querySelector('label[for="new_order_form_preProduct_preOffer"]')?.remove();

    document.getElementById('new_order_form_preProduct_preVariation_select2')?.remove();
    document.querySelector('label[for="new_order_form_preProduct_preVariation"]')?.remove();

    document.getElementById('new_order_form_preProduct_preModification_select2')?.remove();
    document.querySelector('label[for="new_order_form_preProduct_preModification"]')?.remove();

    setTimeout(() =>
    {
        document.getElementById('new_order_form_preProduct_preProduct_select2').click();
    }, 100);

}


userProfileType = document.getElementById('new_order_form_usr_userProfile_type');

if(userProfileType)
{
    userProfileType.addEventListener('change', function(event)
    {
        let forms = this.closest('form');

        submitProfileForm(forms);
        return false;
    });
}


// document.querySelectorAll('input[name="new_order_form[usr][userProfile][type]"]').forEach(function (userProfileType) {
//     userProfileType.addEventListener('change', function (event) {
//
//         let forms = this.closest('form');
//         submitProfileForm(forms);
//         return false;
//     });
// });


document.querySelectorAll('input[name="new_order_form[usr][payment][payment]"]').forEach(function(userPayment)
{
    userPayment.addEventListener('change', function(event)
    {
        let forms = this.closest('form');
        submitPaymentForm(forms);
        return false;
    });
});

document.querySelectorAll('input[name="new_order_form[usr][delivery][delivery]"]').forEach(function(userPayment)
{
    userPayment.addEventListener('change', function(event)
    {
        let forms = this.closest('form');
        submitDeliveryForm(forms);
        return false;
    });
});

document.querySelectorAll('select.change_region_field').forEach(function(userRegion)
{
    userRegion.addEventListener('change', function(event)
    {
        let forms = this.closest('form');
        submitRegionForm(forms, userRegion.id);
        return false;
    });

    new NiceSelect(userRegion);

});

async function submitDeliveryForm(forms)
{
    disabledElementsForm(forms);

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            // 'X-Requested-With': 'XMLHttpChange'
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();
        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                let user_delivery = doc.getElementById('user_delivery');
                document.getElementById('user_delivery').replaceWith(user_delivery);

                /** Пересобираем поля для способа дотсавки */
                document.querySelectorAll('input[name="new_order_form[usr][delivery][delivery]"]').forEach(function(user_delivery)
                {
                    user_delivery.addEventListener('change', function(event)
                    {

                        let forms = this.closest('form');
                        submitDeliveryForm(forms);
                        return false;
                    });
                });


                document.querySelectorAll('select.change_region_field').forEach(function(userRegion)
                {
                    userRegion.addEventListener('change', function(event)
                    {
                        let forms = this.closest('form');
                        submitRegionForm(forms, userRegion.id);
                        return false;
                    });

                });

                /** Делаем перерасчет */


                /** Пересобирваем tooltip */
                var tooltipTriggerList = [].slice.call(user_delivery.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });


                /** Инициируем календарь */
                document.querySelectorAll('.js-datepicker').forEach((datepicker) =>
                {

                    let $elementDate = document.getElementById(datepicker.id).value;
                    const [day, month, year] = $elementDate.split('.');
                    $selectedDate = new Date(+year, month - 1, +day);


                    //let nextDay = new Date(currentDate.setDate(currentDate.getDate()));
                    let nextDay = new Date();

                    let currentDate = new Date();
                    let limitDay = new Date(currentDate.setDate(currentDate.getDate() + 7));

                    MCDatepicker.create({
                        el: '#' + datepicker.id,
                        bodyType: 'modal', // ‘modal’, ‘inline’, or ‘permanent’.
                        autoClose: false,
                        closeOndblclick: true,
                        closeOnBlur: false,
                        customOkBTN: 'OK',
                        customClearBTN: datapickerLang[$locale].customClearBTN,
                        customCancelBTN: datapickerLang[$locale].customCancelBTN,
                        firstWeekday: datapickerLang[$locale].firstWeekday,
                        dateFormat: 'DD.MM.YYYY',
                        customWeekDays: datapickerLang[$locale].customWeekDays,
                        customMonths: datapickerLang[$locale].customMonths,
                        selectedDate: $selectedDate === 'Invalid Date' ? new Date() : $selectedDate,
                        minDate: nextDay,
                        maxDate: limitDay,
                    });
                });


                /** Сбрасываем значения геолокации */
                document.querySelector('[data-latitude]').value = '';
                document.querySelector('[data-longitude]').value = '';


                let dataUserAddress = document.querySelectorAll('[data-address]');

                if(dataUserAddress)
                {
                    dataUserAddress.forEach(function(area)
                    {
                        area.value = '';
                    });
                }


                limitOxMvRIBczY = 100;

                setTimeout(function OxMvRIBczY()
                {

                    if(typeof initAdddress == 'function')
                    {

                        initAdddress();
                        return;
                    }

                    if(limitOxMvRIBczY > 1000)
                    {
                        return;
                    }

                    limitOxMvRIBczY = limitOxMvRIBczY * 2;

                    setTimeout(OxMvRIBczY, limitOxMvRIBczY);

                }, 100);

                /** Определяем поле с адресом */
                //initAdddress();


            }

            enableElementsForm(forms);
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitRegionForm(forms, id)
{

    disabledElementsForm(forms);

    //console.log('submitRegionForm');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');


    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            // 'X-Requested-With': 'XMLHttpChange'
            'X-Requested-With': 'XMLHttpRequest'
        },


        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();
        })

        .then((data) =>
        {

            if(data)
            {


                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                let callId = id.replace(/_region/g, '_call');
                let call = doc.getElementById(callId);


                document.getElementById(callId).replaceWith(call);


                /** Сбрасываем значения геолокации */
                document.querySelector('[data-latitude]').value = '';
                document.querySelector('[data-longitude]').value = '';


                /** Определяем поле с адресом */

                executeFunc(function ordersDraftAddres()
                {
                    if(typeof initAdddress !== 'function')
                    {
                        return false;
                    }

                    initAdddress();

                    return true;
                });

                //limitZJzxDhmvtC = 100;
                //
                //setTimeout(function ZJzxDhmvtC()
                //{
                //
                //    if(typeof initAdddress == 'function')
                //    {
                //        initAdddress();
                //        return;
                //    }
                //
                //    if(limitZJzxDhmvtC > 1000)
                //    {
                //        return;
                //    }
                //
                //    limitZJzxDhmvtC = limitZJzxDhmvtC * 2;
                //
                //    setTimeout(ZJzxDhmvtC, limitZJzxDhmvtC);
                //
                //}, 100);

            }

            enableElementsForm(forms);
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitPaymentForm(forms)
{

    disabledElementsForm(forms);

    //console.log('submitPaymentForm');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            // 'X-Requested-With': 'XMLHttpChange'
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();
        })

        .then((data) =>
        {

            if(data)
            {


                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                let user_payment = doc.getElementById('user_payment');
                document.getElementById('user_payment').replaceWith(user_payment);


                document.querySelectorAll('input[name="new_order_form[usr][payment][payment]"]').forEach(function(user_payment)
                {
                    user_payment.addEventListener('change', function(event)
                    {

                        let forms = this.closest('form');
                        submitPaymentForm(forms);
                        return false;
                    });
                });


                /** Пересобираем поля для способа оплаты */

                /** Пересобирваем tooltip */
                var tooltipTriggerList = [].slice.call(user_payment.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

            }

            enableElementsForm(forms);
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitProfileForm(forms)
{

    disabledElementsForm(forms);

    //console.log('submitProfileForm');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            // 'X-Requested-With': 'XMLHttpChange'
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {


            if(data)
            {

                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                /** Блок профиля пользователя */
                let user_profile = doc.getElementById('user_profile');
                document.getElementById('user_profile').replaceWith(user_profile);


                userProfileType = document.getElementById('new_order_form_usr_userProfile_type');

                if(userProfileType.tagName === 'SELECT')
                {
                    new NiceSelect(userProfileType);
                }

                userProfileType.addEventListener('change', function(event)
                {
                    let forms = this.closest('form');
                    submitProfileForm(forms);
                    return false;
                });


                /** Блок продукции */
                let user_products = doc.getElementById('user_products');
                document.getElementById('user_products').replaceWith(user_products);


                var object_category = document.getElementById('new_order_form_preProduct_category');

                if(object_category)
                {
                    //object_product.addEventListener('change', changeObjectProduct, false);
                    object_category.addEventListener('change', function(event)
                    {
                        let forms = this.closest('form');
                        changeObjectCategory(forms);
                        return false;
                    });


                    if(object_category.tagName === 'SELECT')
                    {
                        new NiceSelect(object_category, {searchable: true});
                    }

                    //let $addButtonStock = document.getElementById('new_order_form_addProduct');
                    //
                    //if($addButtonStock)
                    //{
                    //    $addButtonStock.addEventListener('click', addProductOrder, false);
                    //}
                }


                var object_product = document.getElementById('new_order_form_preProduct_preProduct');

                if(object_product)
                {
                    let focus = document.getElementById('new_order_form_preProduct_preProduct_select2');
                    focus ? focus.click() : null;


                    //object_product.addEventListener('change', changeObjectProduct, false);
                    object_product.addEventListener('change', function(event)
                    {
                        let forms = this.closest('form');
                        changeObjectProduct(forms);
                        return false;
                    });


                    if(object_product.tagName === 'SELECT')
                    {
                        new NiceSelect(object_product, {searchable: true});
                    }

                    let $addButtonStock = document.getElementById('new_order_form_addProduct');

                    if($addButtonStock)
                    {
                        $addButtonStock.addEventListener('click', addProductOrder, false);
                    }
                }


                /** Блок способа оплаты */
                let user_payment = doc.getElementById('user_payment');
                document.getElementById('user_payment').replaceWith(user_payment);


                /** Пересобираем события способа оплаты */
                document.querySelectorAll('input[name="new_order_form[usr][payment][payment]"]').forEach(function(userPayment)
                {
                    userPayment.addEventListener('change', function(event)
                    {
                        let replaceId = 'user_profile';
                        let forms = this.closest('form');
                        submitPaymentForm(forms);
                        return false;
                    });
                });

                /** Блок способа Добавки */
                let user_delivery = doc.getElementById('user_delivery');
                document.getElementById('user_delivery').replaceWith(user_delivery);

                /** Пересобираем поля для способа доставки */
                document.querySelectorAll('input[name="new_order_form[usr][delivery][delivery]"]').forEach(function(user_delivery)
                {
                    user_delivery.addEventListener('change', function(event)
                    {

                        let forms = this.closest('form');
                        submitDeliveryForm(forms);
                        return false;
                    });
                });


                //total();

                document.querySelectorAll('select.change_region_field').forEach(function(userRegion)
                {
                    userRegion.addEventListener('change', function(event)
                    {
                        let forms = this.closest('form');
                        submitRegionForm(forms, userRegion.id);
                        return false;
                    });
                });


                /** Пересобираем tooltip */
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                /** Перезапускаем lazyload */
                let lazy = document.createElement('script');
                lazy.src = '/assets/js/lazyload.min.js';
                document.head.appendChild(lazy);

                /** Инициируем календарь */
                document.querySelectorAll('.js-datepicker').forEach((datepicker) =>
                {

                    let $elementDate = document.getElementById(datepicker.id).value;
                    const [day, month, year] = $elementDate.split('.');
                    $selectedDate = new Date(+year, month - 1, +day);


                    // let nextDay = new Date(currentDate.setDate(currentDate.getDate()));
                    let nextDay = new Date();

                    let currentDate = new Date();
                    let limitDay = new Date(currentDate.setDate(currentDate.getDate() + 7));

                    MCDatepicker.create({
                        el: '#' + datepicker.id,
                        bodyType: 'modal', // ‘modal’, ‘inline’, or ‘permanent’.
                        autoClose: false,
                        closeOndblclick: true,
                        closeOnBlur: false,
                        customOkBTN: 'OK',
                        customClearBTN: datapickerLang[$locale].customClearBTN,
                        customCancelBTN: datapickerLang[$locale].customCancelBTN,
                        firstWeekday: datapickerLang[$locale].firstWeekday,
                        dateFormat: 'DD.MM.YYYY',
                        customWeekDays: datapickerLang[$locale].customWeekDays,
                        customMonths: datapickerLang[$locale].customMonths,
                        selectedDate: $selectedDate === 'Invalid Date' ? nextDay : $selectedDate,
                        minDate: nextDay, //  Огранчииваем указаной датой (на завтра)
                        maxDate: limitDay, // ограничиваем 7 дней
                    });
                });

            }

            enableElementsForm(forms);
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}


//if(document.readyState === 'loading')
//{
//    document.addEventListener('DOMContentLoaded', initFormDraft);
//} else
//{
//    initFormDraft();
//}


//function initFormDraft()
//{
//
//    let form = document.querySelector('#modal form');  //getElementById('modal');
//
//    if(typeof form == 'undefined' || !form)
//    {
//        return false;
//    }
//
//    //console.log(form);
//
//    let name = form.name;
//
//    input = form.querySelector('#' + name + '_price_total'); //basket.getElementById('order_product_form_price_total');
//
//    if(input)
//    {
//
//        /** Событие на изменение количество в ручную */
//        input.addEventListener('input', orderModalCounter.debounce(300));
//
//
//        /** Счетчик  */
//        form.querySelector('#plus').addEventListener('click', () =>
//        {
//
//            let price_total = form.querySelector('#' + name + '_price_total');
//            let result = price_total.value * 1;
//            let max = price_total.dataset.max * 1;
//
//            if(result < max)
//            {
//                result = result + 1;
//                form.querySelector('#' + name + '_price_total').value = result;
//                orderModalSum(result);
//            }
//
//        });
//
//
//        form.querySelector('#minus').addEventListener('click', () =>
//        {
//            let price_total = form.querySelector('#' + name + '_price_total');
//            let result = price_total.value * 1;
//
//            if(result > 1)
//            {
//                result = result - 1
//                form.querySelector('#' + name + '_price_total').value = result;
//                orderModalSum(result);
//            }
//        });
//
//        //return;
//    }
//
//
//    function orderModalCounter()
//    {
//
//        console.log(this);
//
//        let result = this.value * 1;
//        let max = this.dataset.max * 1;
//
//        if(result > max)
//        {
//            form.querySelector('#' + name + '_price_total').value = max;
//            form.querySelector('#summ_' + name + '_price_total').value = max;
//            result = max;
//        }
//
//        orderModalSum(result);
//    }
//
//    function orderModalSum(result)
//    {
//
//        let product_summ = form.querySelector('#summ_' + name + '_price_total');
//
//        let result_product_sum = result * product_summ.dataset.price;
//
//        if(product_summ.dataset.discount)
//        {
//            result_product_sum = result_product_sum - (result_product_sum / 100 * product_summ.dataset.discount);
//        }
//
//        if(product_summ.dataset.currency)
//        {
//            result_product_sum = result_product_sum / 100;
//            result_product_sum = new Intl.NumberFormat($locale, {
//                style: 'currency',
//                currency: product_summ.dataset.currency,
//                maximumFractionDigits: 0
//            }).format(result_product_sum);
//            product_summ.innerText = result_product_sum;
//        }
//    }
//
//
//}


//executeFunc(initFormDraft);
