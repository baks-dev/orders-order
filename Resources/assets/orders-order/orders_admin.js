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
    var $elementDeliveryDate = document.querySelector('input[name*="[usr][delivery][deliveryDate]"]');

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

        //forms.action = '/basket';

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

        document.getElementById(this.dataset.id).value = result;

        /** Пересчет Суммы */
        orderSum(result, this.dataset.id);

        /** Персчет всего количество */
        total();

    });
});


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
            /** Событие на изменение стимости в ручную */
            input.addEventListener('input', orderCounter.debounce(1000));
            return;
        }

        setTimeout(initPrice, 100);

    }, 100);

});


/** Увеличиваем число продукции */
document.querySelectorAll('.plus').forEach(function(btn)
{

    btn.addEventListener('click', function(event)
    {
        let inpt = document.getElementById(this.dataset.id);

        let result = parseFloat(inpt.value.replace(",", "."));
        result = result + (this.dataset.step ? this.dataset.step * 1 : 1);

        //let inpt = document.getElementById(this.dataset.id);
        //let result = inpt.value * 1 + 1;

        console.log(inpt.dataset.max);

        if(inpt.dataset.max && result > inpt.dataset.max)
        {
            return;
        }

        document.getElementById(this.dataset.id).value = result;

        /** Пересчет Суммы */
        orderSum(result, this.dataset.id);

        /** Персчет всего количество */
        total();

    });
});

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
}

function orderSum(result, id)
{
    let product_total = document.getElementById(id);
    let product_summ = document.getElementById('summ_' + id);

    if(product_summ)
    {
        let result_product_sum = (result * product_total.dataset.price) / 100;

        result_product_sum = new Intl.NumberFormat($locale, {
            style: 'currency',
            currency : product_total.dataset.currency === "RUR" ? "RUB" : product_total.dataset.currency,
            maximumFractionDigits: 2
        }).format(result_product_sum);

        product_summ.innerText = result_product_sum;
    }
}

function total()
{
    let result_total = 0;
    let currency = null;

    document.querySelectorAll('.total').forEach(function(total)
    {
        // зменение в поле количество

        const price_id = total.id.replace(/price_total/g, "price_price");
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
        //let price = total.dataset.price * 1;
        currency = total.dataset.currency === "RUR" ? "RUB" : total.dataset.currency;
        const discount = total.dataset.discount * 1;

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

    if(delivery && delivery.dataset.price)
    {
        result_total = delivery.dataset.price * 1 + result_total;
    }

    result_all_sum = new Intl.NumberFormat($locale, {
        style: 'currency',
        currency: currency,
        maximumFractionDigits: 0
    }).format(result_total);

    let total_all_sum = document.getElementById('total_all_sum');
    if(total_all_sum)
    { total_all_sum.innerText = result_all_sum; }

}


document.querySelectorAll('.delete-product').forEach(function(btn)
{
    btn.addEventListener('click', function(event)
    {
        event.preventDefault();
        submitLink(btn.href, btn.dataset.id);
    });
});


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
                var doc = parser.parseFromString(data, 'text/html');

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
                var tooltipTriggerList = [].slice.call(user_delivery.querySelectorAll('[data-bs-toggle="tooltip"]'))
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
                    { return; }

                    limitOxMvRIBczY = limitOxMvRIBczY * 2;

                    setTimeout(OxMvRIBczY, limitOxMvRIBczY);

                }, 100);

                /** Определяем поле с адресом */
                //initAdddress();

                initDatepicker();

            }
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitRegionForm(forms, id)
{


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
                var doc = parser.parseFromString(data, 'text/html');

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
                    { return; }

                    limitZJzxDhmvtC = limitZJzxDhmvtC * 2;

                    setTimeout(ZJzxDhmvtC, limitZJzxDhmvtC);

                }, 100);

            }
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitPaymentForm(forms)
{


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
                var doc = parser.parseFromString(data, 'text/html');

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
                var tooltipTriggerList = [].slice.call(user_payment.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

            }
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitOrderForm(forms)
{

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
                var doc = parser.parseFromString(data, 'text/html');

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
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                initDatepicker();
            }

        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}
