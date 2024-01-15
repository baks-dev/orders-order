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



userProfileType = document.getElementById('new_order_form_usr_userProfile_type');

if (userProfileType)
{
    userProfileType.addEventListener('change', function (event) {

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


document.querySelectorAll('input[name="new_order_form[usr][payment][payment]"]').forEach(function (userPayment) {
    userPayment.addEventListener('change', function (event) {
        let forms = this.closest('form');
        submitPaymentForm(forms);
        return false;
    });
});

document.querySelectorAll('input[name="new_order_form[usr][delivery][delivery]"]').forEach(function (userPayment) {
    userPayment.addEventListener('change', function (event) {
        let forms = this.closest('form');
        submitDeliveryForm(forms);
        return false;
    });
});

document.querySelectorAll('select.change_region_field').forEach(function (userRegion) {
    userRegion.addEventListener('change', function (event) {
        let forms = this.closest('form');
        submitRegionForm(forms, userRegion.id);
        return false;
    });
});

async function submitDeliveryForm(forms) {


    console.log('submitDeliveryForm');

    const data = new FormData(forms);
    data.delete(forms.name+'[_token]');


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
        .then((response) => {

            if (response.status !== 200) {
                return false;
            }

            return response.text();
        })

        .then((data) => {

            if (data) {


                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                let user_delivery = doc.getElementById('user_delivery');
                document.getElementById('user_delivery').replaceWith(user_delivery);

                /** Пересобираем поля для способа дотсавки */
                document.querySelectorAll('input[name="new_order_form[usr][delivery][delivery]"]').forEach(function (user_delivery) {
                    user_delivery.addEventListener('change', function (event) {

                        let forms = this.closest('form');
                        submitDeliveryForm(forms);
                        return false;
                    });
                });


                document.querySelectorAll('select.change_region_field').forEach(function (userRegion) {
                    userRegion.addEventListener('change', function (event) {
                        let forms = this.closest('form');
                        submitRegionForm(forms, userRegion.id);
                        return false;
                    });
                });

                /** Делаем перерасчет */


                /** Пересобирваем tooltip */
                var tooltipTriggerList = [].slice.call(user_delivery.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });



                /** Персчет всего количество */
                //total();


                /** Сбрасываем значения геолокации */
                document.querySelector('[data-latitude]').value = '';
                document.querySelector('[data-longitude]').value = '';


                limitOxMvRIBczY = 100;

                setTimeout(function OxMvRIBczY() {

                    console.log('initAdddress');
                    console.log(typeof initAdddress);

                    if (typeof initAdddress == 'function') {
                        initAdddress();
                        return;
                    }

                    if (limitOxMvRIBczY > 1000) { return; }

                    limitOxMvRIBczY = limitOxMvRIBczY * 2;

                    setTimeout(OxMvRIBczY, limitOxMvRIBczY);

                }, 100);

                /** Определяем поле с адресом */
                //initAdddress();

            }
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitRegionForm(forms, id) {


    console.log('submitRegionForm');

    const data = new FormData(forms);
    data.delete(forms.name+'[_token]');


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
        .then((response) => {

            if (response.status !== 200) {
                return false;
            }

            return response.text();
        })

        .then((data) => {

            if (data) {


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

                setTimeout(function ZJzxDhmvtC() {

                    if (typeof initAdddress == 'function') {
                        initAdddress();
                        return;
                    }

                    if (limitZJzxDhmvtC > 1000) { return; }

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

async function submitPaymentForm(forms) {

    console.log('submitPaymentForm');

    const data = new FormData(forms);
    data.delete(forms.name+'[_token]');

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
        .then((response) => {

            if (response.status !== 200) {
                return false;
            }

            return response.text();
        })

        .then((data) => {

            if (data) {


                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                let user_payment = doc.getElementById('user_payment');
                document.getElementById('user_payment').replaceWith(user_payment);


                document.querySelectorAll('input[name="new_order_form[usr][payment][payment]"]').forEach(function (user_payment) {
                    user_payment.addEventListener('change', function (event) {

                        let forms = this.closest('form');
                        submitPaymentForm(forms);
                        return false;
                    });
                });


                /** Пересобираем поля для способа оплаты */

                /** Пересобирваем tooltip */
                var tooltipTriggerList = [].slice.call(user_payment.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

            }
        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}

async function submitProfileForm(forms) {

    //console.log('submitProfileForm');

    const data = new FormData(forms);
    data.delete(forms.name+'[_token]');

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
        .then((response) => {

            if (response.status !== 200) {
                return false;
            }

            return response.text();

        })

        .then((data) => {


            if (data) {

                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                /** Блок профиля пользователя */
                let user_profile = doc.getElementById('user_profile');
                document.getElementById('user_profile').replaceWith(user_profile);


                userProfileType = document.getElementById('new_order_form_usr_userProfile_type');

                userProfileType.addEventListener('change', function (event) {

                    let forms = this.closest('form');
                    submitProfileForm(forms);
                    return false;
                });


                /** Блок способа оплаты */
                let user_payment = doc.getElementById('user_payment');
                document.getElementById('user_payment').replaceWith(user_payment);


                /** Пересобираем события способа оплаты */
                document.querySelectorAll('input[name="new_order_form[usr][payment][payment]"]').forEach(function (userPayment) {
                    userPayment.addEventListener('change', function (event) {
                        let replaceId = 'user_profile';
                        let forms = this.closest('form');
                        submitPaymentForm(forms);
                        return false;
                    });
                });

                /** Блок способа Добавки */
                let user_delivery = doc.getElementById('user_delivery');
                document.getElementById('user_delivery').replaceWith(user_delivery);

                /** Пересобираем поля для способа дотсавки */
                document.querySelectorAll('input[name="new_order_form[usr][delivery][delivery]"]').forEach(function (user_delivery) {
                    user_delivery.addEventListener('change', function (event) {

                        let forms = this.closest('form');
                        submitDeliveryForm(forms);
                        return false;
                    });
                });


                //total();

                document.querySelectorAll('select.change_region_field').forEach(function (userRegion) {
                    userRegion.addEventListener('change', function (event) {
                        let forms = this.closest('form');
                        submitRegionForm(forms, userRegion.id);
                        return false;
                    });
                });


                /** Пересобирваем tooltip */
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}


if (document.readyState == 'loading') {
    document.addEventListener('DOMContentLoaded', initFormDraft);
} else {
    initFormDraft();
}

function initFormDraft() {

    let form = document.querySelector('#modal form');  //getElementById('modal');

    if (typeof form == 'undefined')
    {
        return;
    }

    let name = form.name;

    input = form.querySelector('#'+name+'_price_total'); //basket.getElementById('order_product_form_price_total');

    if (input) {

        /** Событие на изменение количество в ручную */
        input.addEventListener('input', orderModalCounter.debounce(300));

        /** Счетчик  */
        form.querySelector('#plus').addEventListener('click', () => {

            let price_total = form.querySelector('#'+name+'_price_total');
            let result = price_total.value * 1;
            let max = price_total.dataset.max * 1;

            if (result < max) {
                result = result + 1;
                form.querySelector('#'+name+'_price_total').value = result;
                orderModalSum(result);
            }

        });


        form.querySelector('#minus').addEventListener('click', () => {
            let price_total = form.querySelector('#'+name+'_price_total');
            let result = price_total.value * 1;

            if (result > 1) {
                result = result - 1
                form.querySelector('#'+name+'_price_total').value = result;
                orderModalSum(result);
            }
        });

        //return;
    }


    function orderModalCounter() {

        let result = this.value * 1;
        let max = this.dataset.max * 1;

        if (result > max) {
            form.querySelector('#summ_'+name+'_price_total').value = max;
            result = max;
        }

        orderModalSum(result);
    }

    function orderModalSum(result) {

        let product_summ = form.querySelector('#summ_'+name+'_price_total');

        let result_product_sum = result * product_summ.dataset.price;

        if (product_summ.dataset.discount) {
            result_product_sum = result_product_sum - (result_product_sum / 100 * product_summ.dataset.discount);
        }

        result_product_sum = result_product_sum / 100;
        result_product_sum = new Intl.NumberFormat($lang, {
            style: 'currency',
            currency: product_summ.dataset.currency,
            maximumFractionDigits: 0
        }).format(result_product_sum);
        product_summ.innerText = result_product_sum;

    }
}


