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

basketLang = {
    'ru': {
        btnAdd: 'В корзину',
        btnAddede: 'В корзине',
    },
    'en': {
        btnAdd: 'Add to cart',
        btnAddede: 'In the basket',
    }
}

function addOrder(event)
{
    let forms = this.closest('form');
    event.preventDefault();
    submitModalForm(forms);
    return false;
}

document.querySelectorAll('.order-basket').forEach(function(forms)
{
    const btn = forms.querySelector('button[type="submit"]');

    if(btn)
    {
        btn.addEventListener('click', addOrder);
    }
});

initDatepicker();

function initDatepicker()
{
    let $elementDeliveryDate = document.querySelector('input[name*="[usr][delivery][deliveryDate]"]');

    if($elementDeliveryDate)
    {
        let JrKZvcNyRepeat = 100;

        setTimeout(function JrKZvcNy()
        {

            if(JrKZvcNyRepeat >= 1000)
            { return; }

            if(typeof MCDatepicker === 'object')
            {
                const [day, month, year] = $elementDeliveryDate.value.split('.');
                $selectedDate = new Date(+year, month - 1, +day);

                let currentDate = new Date();
                const nextDay = new Date(currentDate.setDate(currentDate.getDate()));

                currentDate = new Date();
                const limitDay = new Date(currentDate.setDate(currentDate.getDate() + 7));

                MCDatepicker.create({
                    el: '#' + $elementDeliveryDate.id,
                    bodyType: 'modal',
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
                    selectedDate: $selectedDate,
                    minDate: nextDay,
                    maxDate: limitDay,
                });

                return;
            }

            JrKZvcNyRepeat = JrKZvcNyRepeat * 2;
            setTimeout(JrKZvcNy, 100);

        }, 100);
    }
}

function resolve(forms)
{

    if(forms !== false && forms.name === 'order_product_form')
    {

        /** Увеличиваем бейдж корзины */
        $userbasket = document.getElementById('user_basket');

        if($userbasket)
        {
            $userbasket.classList.remove('d-none');

            $counter = $userbasket.innerText * 1;
            $userbasket.innerText = $counter + 1;
        }

        /** Меняем кнопку submit */

        let btn = forms.querySelector('button[type="submit"]');

        btn.classList.replace('btn-primary', 'btn-outline-primary');
        btn.querySelector('span.basket-text').innerText = basketLang[$locale].btnAddede;

        btn.removeEventListener('click', addOrder, false);

        btn.addEventListener('click',

            function(event)
            {
                event.preventDefault();
                window.location.href = "/basket";
                return false;
            }
        );
    }
}

/** Уменьшаем число продукции */
document.querySelectorAll('.minus').forEach(function(btn)
{

    btn.addEventListener('click', function(event)
    {
        let inpt = document.getElementById(this.dataset.id).value;

        let result = parseFloat(inpt.replace(",", "."));
        result = result - (this.dataset.step ? this.dataset.step * 1 : 1);

        if(result <= 0)
        {
            return;
        }

        let price = document.getElementById(this.dataset.id)
        price.value = result;

        let productItems = document.getElementById(price.dataset.productGroup + '-items')

        /** При изменении количества продукта - добавляем единицу продукта */
        if(price.id.endsWith('total'))
        {
            let item = productItems.querySelector('[data-item="product-item"]');
            let del = item.querySelector('.delete-el');

            del.click()
        }

        /** При изменении цены продукта - изменяем цену в каждой единице продукта */
        if(price.id.endsWith('price'))
        {
            modifyOrderProductItemPrice(productItems, result)
        }

        /** Пересчет Суммы */
        orderSum(result, this.dataset.id);

        /** Персчет всего количество */
        total();

    });
});

/** Увеличиваем число продукции */
document.querySelectorAll('.plus').forEach(function(btn)
{
    btn.addEventListener('click', function(event)
    {
        let inpt = document.getElementById(this.dataset.id);

        let result = parseFloat(inpt.value.replace(",", "."));
        result = result + (this.dataset.step ? this.dataset.step * 1 : 1);

        if(inpt.dataset.max && result > inpt.dataset.max)
        {
            return;
        }

        inpt.value = result;

        /** Коллекция единиц конкретного продукта */
        let orderProductItems = document.getElementById(inpt.dataset.productGroup + '-items')

        /** При изменении количества продукта - добавляем единицу продукта */
        if(inpt.id.endsWith('total'))
        {
            addOrderProductItem(orderProductItems)
        }

        /** При изменении цены продукта - изменяем цену в каждой единице продукта */
        if(inpt.id.endsWith('price'))
        {
            modifyOrderProductItemPrice(orderProductItems, result)
        }

        /** Пересчет Суммы */
        orderSum(result, this.dataset.id);

        /** Персчет всего количество */
        total();

    });
});

/** Добавляем единицу продукта */
function addOrderProductItem(items)
{
    if(items)
    {
        let productItems = items.querySelectorAll('[data-item="product-item"]');

        /** Минимальный индекс в коллекции элем енотов */
        const maxIndex = Math.max(
            ...Array.from(productItems)
                .map(el => parseInt(el.dataset.itemIndex))
        );

        let prototype = document.getElementById(items.id + '-prototype');

        let prototypeContent = prototype.innerText;
        prototypeContent = prototypeContent.replace(/__item__/g, maxIndex + 1);

        const template = document.createElement('template');
        template.innerHTML = prototypeContent.trim();
        const inputElement = template.content.firstElementChild;

        const inputElementPrice = inputElement.querySelector('#' + inputElement.id + '_price_price');
        inputElementPrice.value = inputElementPrice.dataset.price

        //items.append(inputElement)
        prototype.after(inputElement)

        /** Элементы после вставки */
        const itemCountAfterAdd = items.querySelectorAll('[data-item="product-item"]')

        items.setAttribute('data-items-count', itemCountAfterAdd.length);

        initHsH22s6NM(items)
    }
}

/** Изменяем цену и скидку в каждой единице продукта */
function modifyOrderProductItemPrice(items, price_value, discount_value = null)
{
    if(items)
    {
        /** Изменяем цену в каждой единице продукта */
        items.querySelectorAll('.item-price').forEach(function(price_input)
        {
            let itemMin = price_input.getAttribute('min');

            if(price_value < parseInt(itemMin))
            {
                return
            }

            price_input.value = price_value;
        });

        /** Изменяем скидку в каждой единице продукта */
        if(null !== discount_value)
        {
            items.querySelectorAll('.item-discount').forEach(function(discount_input)
            {
                discount_input.value = discount_value * -1;
            });
        }
    }
}

/** Событие на изменение количества */
document.querySelectorAll('.total').forEach(function(input)
{
    setTimeout(function initCounter()
    {
        if(typeof orderCounter.debounce == 'function')
        {
            /** Событие на изменение количество в ручную */
            input.addEventListener('input', orderCounter.debounce(1000));
            return;
        }

        setTimeout(initCounter, 100);

    }, 100);

});

/** Событие на изменение стоимости */
document.querySelectorAll('.price').forEach(function(input)
{
    setTimeout(function initPrice()
    {
        if(typeof orderCounter.debounce == 'function')
        {
            /** Событие на изменение стоимости в ручную */
            input.addEventListener('input', orderCounter.debounce(1000));
            return;
        }

        setTimeout(initPrice, 100);

    }, 100);

});

/** Суммирует цену с учетом количества */
function orderSum(result, id)
{
    let product_total = document.getElementById(id);
    let product_summ = document.getElementById('summ_' + id);

    if(product_summ)
    {
        let result_product_sum = (result * product_total.dataset.price) / 100;

        result_product_sum = new Intl.NumberFormat($locale, {
            style: 'currency',
            currency: product_total.dataset.currency === "RUR" ? "RUB" : product_total.dataset.currency,
            maximumFractionDigits: 2
        }).format(result_product_sum);

        product_summ.innerText = result_product_sum;

    }
}

function total(id = null)
{
    let result_total = 0;
    let currency = null;

    document.querySelectorAll('.total').forEach(function(total)
    {
        // изменение в поле количество

        const price_id = id ?? total.id.replace(/price_total/g, "price_price");

        const input_price = document.getElementById(price_id);

        let price = parseFloat(total.dataset.price.replace(",", "."));

        if(input_price)
        {
            price = parseFloat(input_price.value.replace(",", ".")) * 100;
            total.dataset.price = price;

            let minimal = parseFloat(input_price.getAttribute('min')) * 100;

            /** Делаем проверку, что сумма не менше допустимой */
            if(minimal > price)
            {
                price = minimal;
                total.dataset.price = minimal;
                input_price.value = minimal / 100;

                let $successSupplyToast = '{ "type":"danger" , ' +
                    '"header":"Ошибка при изменении стоимости"  , ' +
                    '"message" : "Нельзя указать стоимость товара в заказе ниже минимально допустимой!" }';

                createToast(JSON.parse($successSupplyToast));

            }

            /** Делаем перерасчет суммы продукции */
            orderSum(total.value, total.id);

        }


        const total_value = total.value * 1;
        currency = total.dataset.currency === "RUR" ? "RUB" : total.dataset.currency;

        if(total_value)
        {
            let result_total_value = total_value * price;
            result_total = result_total + result_total_value;
        }

    });


    result_total = result_total / 100;

    result_product_sum = new Intl.NumberFormat($locale, {
        style: 'currency',
        currency: currency,
        maximumFractionDigits: 0
    }).format(result_total);


    let total_result = document.getElementById('total_result');

    if(total_result)
    {
        total_result.innerText = result_product_sum;
    }


    let total_product_sum = document.getElementById('total_product_sum');

    if(total_product_sum)
    {
        total_product_sum.innerText = result_product_sum;
    }


    /** пересчитываем доставку */
    let delivery = document.querySelector('input[name*="[users][delivery][delivery]"][checked="checked"]');

    let service_sum = document.getElementById('service_sum');

    /** С учетом стоимости услуг */
    if(service_sum)
    {
        let service_sum_parse = service_sum.textContent.replace(/[^\d]/g, "");
        let service_sum_int = parseInt(service_sum_parse, 10);

        result_total = result_total + service_sum_int;
    }

    if(delivery && delivery.dataset.price)
    {
        result_total = delivery.dataset.price * 1 + result_total;
    }

    let result_all_sum = new Intl.NumberFormat($locale, {
        style: 'currency',
        currency: currency,
        maximumFractionDigits: 0
    }).format(result_total);

    let total_all_sum = document.getElementById('total_all_sum');
    if(total_all_sum)
    { total_all_sum.innerText = result_all_sum; }

}

function orderCounter()
{
    let result = this.value * 1;
    let max = this.dataset.max * 1;

    if(result < 1)
    {
        document.getElementById(this.id).value = 1;
        result = 1;
    }


    if(result > max)
    {
        document.getElementById(this.id).value = max;
        result = max;
    }

    orderSum(result, this.id);

    total();


    /** Коллекция единиц конкретного продукта */
    let orderProductItems = document.getElementById(this.dataset.productGroup + '-items')

    /** Изменение количества продукта */
    if(this.id.endsWith('total'))
    {

        let items = orderProductItems.querySelectorAll('[data-item="product-item"]');

        if(this.value < items.length)
        {
            console.log(' ->', 'удаляем элементы')
            const diff = items.length - this.value

            for(let i = 0; i < diff; i++)
            {
                let item = orderProductItems.querySelector('[data-item="product-item"]');
                item.remove()
            }

            let itemsCountAfter = orderProductItems.querySelectorAll('[data-item="product-item"]');
            orderProductItems.setAttribute('data-items-count', itemsCountAfter.length)
        }


        if(this.value > items.length)
        {
            console.log(' ->', 'добавляем элементы')

            const diff = this.value - items.length

            for(let i = 0; i < diff; i++)
            {
                addOrderProductItem(orderProductItems)
            }
        }
    }

    /** Изменение цены продукта */
    if(this.id.endsWith('price'))
    {
        modifyOrderProductItemPrice(orderProductItems, this.value)
    }
}

document.querySelectorAll(".delete-product").forEach(function($button)
{
    $button.addEventListener("click", function($e)
    {
        $e.preventDefault();

        let $row = $e.currentTarget.getAttribute("data-row");

        deleteElement($row);
    });
});

function deleteElement($row)
{
    let $elemCount = document.querySelectorAll(".delete-product").length;

    if($elemCount < 2)
    {
        /* TOAST */
        let header = "Редактирование заказа";

        let $errorFormHandler = "{ \"type\":\"danger\" , " +
            "\"header\":\"" + header + "\"  , " +
            "\"message\" : \"В заказе должен быть хотя бы один продукт\" }";

        createToast(JSON.parse($errorFormHandler));

        return;
    }


    document.getElementById($row).remove();

    document.getElementById('item_' + $row).nextSibling.nextSibling.remove() // удаляем единицы продукции // @TODO
    document.getElementById('item_' + $row).nextSibling.remove();
    document.getElementById('item_' + $row).remove();

    total();
}

function success(id)
{
    (document.getElementById(id))?.remove();
}

document.querySelectorAll('input[name*="[usr][userProfile][type]"]').forEach(function(userProfileType)
{
    userProfileType.addEventListener('change', function(event)
    {
        let forms = this.closest('form');
        submitOrderForm(forms);
        return false;
    });
});

document.querySelectorAll('input[name*="[usr][payment][payment]"]').forEach(function(userPayment)
{
    userPayment.addEventListener('change', function(event)
    {
        let forms = this.closest('form');
        submitPaymentForm(forms);
        return false;
    });
});

document.querySelectorAll('input[name*="[usr][delivery][delivery]"]').forEach(function(userPayment)
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
});

async function submitDeliveryForm(forms)
{
    const data = new FormData(forms);

    const remove = [
        forms.name + "[_token]",
        forms.name + "[usr][delivery][field]",
    ];

    remove.forEach(pattern =>
    {
        [...data.keys()].filter(key => key.startsWith(pattern)).forEach(key => data.delete(key));
    });


    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })
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
                let parser = new DOMParser();
                let doc = parser.parseFromString(data, 'text/html');

                let user_delivery = doc.getElementById('user_delivery');
                document.getElementById('user_delivery').replaceWith(user_delivery);

                /** Пересобираем поля для способа дотсавки */
                document.querySelectorAll('input[name*="[usr][delivery][delivery]"]').forEach(function(user_delivery)
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
                let tooltipTriggerList = [].slice.call(user_delivery.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });


                /** Персчет всего количество */
                total();


                /** Сбрасываем значения геолокации */
                document.querySelector('[data-latitude]').value = '';
                document.querySelector('[data-longitude]').value = '';


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

                initDatepicker();

            }
        });


    return false;
}

async function submitRegionForm(forms, id)
{


    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');


    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })
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
                let parser = new DOMParser();
                let doc = parser.parseFromString(data, 'text/html');

                let callId = id.replace(/_region/g, '_call');
                let call = doc.getElementById(callId);

                document.getElementById(callId).replaceWith(call);

                /** Сбрасываем значения геолокации */
                document.querySelector('[data-latitude]').value = '';
                document.querySelector('[data-longitude]').value = '';


                /** Определяем поле с адресом */

                limitZJzxDhmvtC = 100;

                setTimeout(function ZJzxDhmvtC()
                {

                    if(typeof initAdddress == 'function')
                    {
                        initAdddress();
                        return;
                    }

                    if(limitZJzxDhmvtC > 1000)
                    {
                        return;
                    }

                    limitZJzxDhmvtC = limitZJzxDhmvtC * 2;

                    setTimeout(ZJzxDhmvtC, limitZJzxDhmvtC);

                }, 100);

            }
        });


    return false;
}

async function submitPaymentForm(forms)
{
    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })
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
                let parser = new DOMParser();
                let doc = parser.parseFromString(data, 'text/html');

                let user_payment = doc.getElementById('user_payment');
                document.getElementById('user_payment').replaceWith(user_payment);

                document.querySelectorAll('input[name*="[usr][payment][payment]"]').forEach(function(user_payment)
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
                let tooltipTriggerList = [].slice.call(user_payment.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });


    return false;
}

async function submitOrderForm(forms)
{
    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })
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
                let parser = new DOMParser();
                let doc = parser.parseFromString(data, 'text/html');

                /** Блок профиля пользователя */
                let user_profile = doc.getElementById('user_profile');
                document.getElementById('user_profile').replaceWith(user_profile);


                /** Блок способа оплаты */
                let user_payment = doc.getElementById('user_payment');
                document.getElementById('user_payment').replaceWith(user_payment);


                /** Пересобираем события способа оплаты */
                document.querySelectorAll('input[name*="[usr][payment][payment]"]').forEach(function(userPayment)
                {
                    userPayment.addEventListener('change', function(event)
                    {
                        let replaceId = 'user_profile';
                        let forms = this.closest('form');
                        submitPaymentForm(forms);
                        return false;
                    });
                });

                /** Блок способа дотсавки */
                let user_delivery = doc.getElementById('user_delivery');
                document.getElementById('user_delivery').replaceWith(user_delivery);

                /** Пересобираем поля для способа дотсавки */
                document.querySelectorAll('input[name*="[usr][delivery][delivery]"]').forEach(function(user_delivery)
                {
                    user_delivery.addEventListener('change', function(event)
                    {

                        let forms = this.closest('form');
                        submitDeliveryForm(forms);
                        return false;
                    });
                });

                /** Персчет всего количество */
                total();

                document.querySelectorAll('select.change_region_field').forEach(function(userRegion)
                {
                    userRegion.addEventListener('change', function(event)
                    {
                        let forms = this.closest('form');
                        submitRegionForm(forms, userRegion.id);
                        return false;
                    });
                });


                /** Пересобирваем tooltip */
                let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                initDatepicker();
            }
        });

    return false;
}

/** Скидка заказа */
var order_discount = document.querySelector('#edit_order_form_discount');

order_discount.addEventListener('input', function()
{
    const discount = this.value;

    let products_discounts = document.querySelectorAll('.product-discount, .item-discount').forEach(function(product_discount)
    {
        if(product_discount.disabled == false)
        {
            product_discount.value = discount;
            product_discount.dispatchEvent(new Event('input'));
        }
    });

});

/** Скидка товара */
var product_discounts = document.querySelectorAll('.product-discount');

product_discounts.forEach(function(product_discount)
{
    product_discount.addEventListener('input', function(event)
    {
        const discount = this.value * -1;

        /* Родительский td */
        let td = this.closest('td');

        /* найти элемент с ценой в родельской td */
        let price = td.querySelector('.price');
        let current_price = price.dataset.price;

        /* Сделать расчет скидки товара */
        let product_price = parseFloat(current_price);
        let discount_product_price = product_price - product_price / 100 * discount // TODO

        /* Изменить значение input поля Цены товара */
        price.value = discount_product_price;

        /** Изменяем цену и скидку в каждой единице продукта */
        modifyOrderProductItemPrice(document.getElementById(price.dataset.target + '-items'), price.value, discount)

        /* Пересчетать всего */
        total();
    })
})
