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
    ru : {btnAdd : "В корзину", btnAddede : "В корзине"},
    en : {btnAdd : "Add to cart", btnAddede : "In the basket"},
};

function addOrder(event)
{
    let forms = this.closest("form");
    event.preventDefault();
    submitModalForm(forms);
    return false;
}

document.querySelectorAll(".order-basket").forEach(function(forms)
{
    const btn = forms.querySelector("button[type=\"submit\"]");

    if(btn)
    {
        const originalHandler = addOrder.bind(btn);

        btn.addEventListener("click", function(event) {
            originalHandler(event);

            setTimeout(() => {
                const basketLink = document.createElement('a');
                basketLink.href = '/basket';
                basketLink.className = 'btn btn-success d-flex align-items-center justify-content-center py-2 fw-bold rounded-3 w-100 mb-4';
                basketLink.style.width = 'fit-content';
                basketLink.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart-check-fill" viewBox="0 0 16 16">
                        <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m-1.646-7.646-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L8 8.293l2.646-2.647a.5.5 0 0 1 .708.708"/>
                    </svg>
                `;

                btn.parentNode.replaceChild(basketLink, btn);

            }, 500);
        });
    }
});

executeFunc(initDatepicker);

function initDatepicker()
{
    var $elementDeliveryDate = document.querySelector("input[name*=\"[usr][delivery][deliveryDate]\"]");

    if($elementDeliveryDate === null)
    {
        return false;
    }

    if(typeof MCDatepicker !== "object")
    {
        return false;
    }

    const [day, month, year] = $elementDeliveryDate.value.split(".");
    $selectedDate = new Date(+year, month - 1, +day);

    let currentDate = new Date;
    const nextDay = new Date(currentDate.setDate(currentDate.getDate() + 1));

    currentDate = new Date;
    const limitDay = new Date(currentDate.setDate(currentDate.getDate() + 7));

    MCDatepicker.create({
        el : "#" + $elementDeliveryDate.id,
        bodyType : "modal",
        autoClose : false,
        closeOndblclick : true,
        closeOnBlur : false,
        customOkBTN : "OK",
        customClearBTN : datapickerLang[$locale].customClearBTN,
        customCancelBTN : datapickerLang[$locale].customCancelBTN,
        firstWeekday : datapickerLang[$locale].firstWeekday,
        dateFormat : "DD.MM.YYYY",
        customWeekDays : datapickerLang[$locale].customWeekDays,
        customMonths : datapickerLang[$locale].customMonths,
        selectedDate : $selectedDate,
        minDate : nextDay,
        maxDate : limitDay,
    });
    return true;
}

function resolve(forms)
{
    if(forms === false)
    {
        return;
    }

    if(forms.name === "order_product_form")
    {
        $userbasket = document.getElementById("user_basket");

        if($userbasket)
        {
            $userbasket.classList.remove("d-none");
            $counter = $userbasket.innerText * 1;
            $userbasket.innerText = $counter + 1;
        }

        let btn = forms.querySelector("button[type=\"submit\"]");
        btn.classList.replace("btn-primary", "btn-outline-primary");
        btn.querySelector("span.basket-text").innerText = basketLang[$locale].btnAddede;
        btn.removeEventListener("click", addOrder, false);
        btn.addEventListener("click", function(event)
        {
            event.preventDefault();
            window.location.href = "/basket";
            return false;
        });
    }
}

document.querySelectorAll(".minus").forEach(function(btn)
{
    btn.addEventListener("click", function(event)
    {
        let inpt = document.getElementById(this.dataset.id);

        if(inpt)
        {
            /* Получить Шаг увеличения/уменьшения кол-ва */
            let step = inpt.dataset.step * 1;

            let result = inpt.value * 1;

            /* Результат складывается из значения - Шаг */
            result = result - step;

            if(result <= 0)
            {
                return;
            }

            document.getElementById(this.dataset.id).value = result;
            orderSum(result, this.dataset.id);
            total();
        }

    });
});

document.querySelectorAll(".total").forEach(function(input)
{
    executeFunc(function initCounter()
    {
        if(typeof orderCounter.debounce !== "function")
        {
            return false;
        }

        input.addEventListener("input", orderCounter.debounce(300));

        return true;
    });


    //setTimeout(function initCounter()
    //{
    //    if(typeof orderCounter.debounce == "function")
    //    {
    //        input.addEventListener("input", orderCounter.debounce(300));
    //        return;
    //    }
    //    setTimeout(initCounter, 100);
    //}, 100);
});

document.querySelectorAll(".plus").forEach(function(btn)
{
    btn.addEventListener("click", function(event)
    {
        let inpt = document.getElementById(this.dataset.id);

        if(inpt)
        {

            /* Получить Шаг увеличения/уменьшения кол-ва */
            let step = inpt.dataset.step * 1;

            /* Результат складывается из значения + Шаг */
            let result = inpt.value * 1 + step;

            let max = inpt.dataset.max * 1;

            if(result > inpt.dataset.max)
            { return; }

            document.getElementById(this.dataset.id).value = result;
            orderSum(result, this.dataset.id);
            total();

        }
    });
});

function orderCounter()
{

    /* Шаг увеличения/уменьшения кол-ва в форме оформления заказа */
    let step = this.dataset.step * 1;

    /* Значение */
    let result = this.value * 1;
    if(result === 0)
    {
        result = step
        this.value = step;
    }

    /* Максимальное */
    let max = this.dataset.max * 1

    let remainder = this.value % step

    /* Скорректировать значение если указано значение не кратное step */
    if(remainder !== 0)
    {
        /* Если поль-ль указал значение меньше шага, то задать значение равное шагу */
        if(result < step)
        {
            this.value = step
        }
        /* Иначе указать значение с учетом остатка */
        if(result > step)
        {
            this.value = result - remainder
        }
    }

    /* Если поль-ль указал значение больше максимального */
    if(result > max)
    {
        this.value = max
    }

    orderSum(result, this.id);
    total();
}

function orderSum(result, id)
{
    let product_summ = document.getElementById("summ_" + id);

    if(product_summ)
    {
        let result_product_sum = result * product_summ.dataset.price;
        if(product_summ.dataset.discount)
        {
            result_product_sum = result_product_sum - result_product_sum / 100 * product_summ.dataset.discount;
        }

        result_product_sum = result_product_sum / 100;
        result_product_sum = new Intl.NumberFormat($locale, {
            style : "currency",
            currency : product_summ.dataset.currency === "RUR" ? "RUB" : product_summ.dataset.currency,
            maximumFractionDigits : 0,
        }).format(result_product_sum);
        product_summ.innerText = result_product_sum;
    }
}

function total()
{
    let result_total = 0;
    let currency = null;

    // Кол-во единиц товара
    let total_count_sum = 0;
    document.querySelectorAll(".total").forEach(function(total)
    {
        let total_value = total.value * 1;
        let price = total.dataset.price * 1;
        currency = total.dataset.currency === "RUR" ? "RUB" : total.dataset.currency;
        let discount = total.dataset.discount * 1;

        if(total_value)
        {
            let result_total_value = total_value * price;

            if(discount)
            {
                result_total_value = result_total_value - result_total_value / 100 * discount;
            }

            result_total = result_total + result_total_value;

            // увеличить кол-во единиц товара
            total_count_sum += total_value;
        }
    });

    // Изменить общее кол-во единиц товаров на странице корзины
    let total_count_result = document.querySelector(".total-count");
    total_count_result ? total_count_result.innerText = total_count_sum : false;


    result_total = result_total / 100;

    result_product_sum = new Intl.NumberFormat($locale, {
        style : "currency",
        currency : currency ? currency : "RUB",
        maximumFractionDigits : 0,
    }).format(result_total);

    let total_result = document.getElementById("total_result");

    if(total_result)
    {
        total_result.innerText = result_product_sum;
    }

    let total_product_sum = document.getElementById("total_product_sum");

    if(total_product_sum)
    {
        total_product_sum.innerText = result_product_sum;
    }

    let delivery = document.querySelector("input[name*=\"[users][delivery][delivery]\"][checked=\"checked\"]");

    if(delivery && delivery.dataset.price)
    {
        result_total = delivery.dataset.price * 1 + result_total;
    }

    result_all_sum = new Intl.NumberFormat($locale, {
        style : "currency",
        currency : currency ? currency : "RUB",
        maximumFractionDigits : 0,
    }).format(result_total);

    let total_all_sum = document.getElementById("total_all_sum");

    if(total_all_sum)
    {
        total_all_sum.innerText = result_all_sum;
    }

    return true;
}
document.querySelectorAll(".delete-product").forEach(function(btn)
{
    btn.addEventListener("click", function(event)
    {
        event.preventDefault();

        const itemId = btn.dataset.id;
        const item = document.getElementById(itemId);

        if(item)
        {
            item.classList.add("fade-delete");

            setTimeout(function()
            {
                item.remove();

                const remainingItems = document.querySelectorAll(".delete-product");
                if(remainingItems.length === 0)
                {
                    location.reload();
                }

                total();
            }, 500);
        }
        submitLink(btn.href, btn.dataset.id);
        // setTimeout(total, 1e3);
    });
});

function success(id)
{
    document.getElementById(id)?.remove();
}

document.querySelectorAll("input[name*=\"[usr][userProfile][type]\"]").forEach(function(userProfileType)
{
    userProfileType.addEventListener("change", function(event)
    {
        let forms = this.closest("form");
        submitOrderForm(forms);
        return false;
    });
});

document.querySelectorAll("input[name*=\"[usr][payment][payment]\"]").forEach(function(userPayment)
{
    userPayment.addEventListener("change", function(event)
    {
        let forms = this.closest("form");
        submitPaymentForm(forms);
        return false;
    });
});

document.querySelectorAll("input[name*=\"[usr][delivery][delivery]\"]").forEach(function(userPayment)
{
    userPayment.addEventListener("change", function(event)
    {
        let forms = this.closest("form");
        submitDeliveryForm(forms);
        return false;
    });
});

document.querySelectorAll("select.change_region_field").forEach(function(userRegion)
{
    userRegion.addEventListener("change", function(event)
    {
        let forms = this.closest("form");
        submitRegionForm(forms, userRegion.id);
        return false;
    });
});

async function submitDeliveryForm(forms)
{
    const data = new FormData(forms);
    data.delete(forms.name + "[_token]");

    await fetch(forms.action, {
        method : forms.method,
        cache : "no-cache",
        credentials : "same-origin",
        headers : {"X-Requested-With" : "XMLHttpRequest"},
        redirect : "follow",
        referrerPolicy : "no-referrer",
        body : data,
    }).then(response =>
    {
        if(response.status !== 200)
        {return false;}
        return response.text();
    }).then(data =>
    {
        if(data)
        {
            var parser = new DOMParser;
            var doc = parser.parseFromString(data, "text/html");
            let user_delivery = doc.getElementById("user_delivery");

            document.getElementById("user_delivery").replaceWith(user_delivery);

            document.querySelectorAll("input[name*=\"[usr][delivery][delivery]\"]").forEach(function(user_delivery)
            {
                user_delivery.addEventListener("change", function(event)
                {
                    let forms = this.closest("form");
                    submitDeliveryForm(forms);
                    return false;
                });
            });

            document.querySelectorAll("select.change_region_field").forEach(function(userRegion)
            {
                userRegion.addEventListener("change", function(event)
                {
                    let forms = this.closest("form");
                    submitRegionForm(forms, userRegion.id);
                    return false;
                });
            });

            var tooltipTriggerList = [].slice.call(user_delivery.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));

            tooltipTriggerList.map(function(tooltipTriggerEl)
            {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            total();

            document.querySelector("[data-latitude]").value = "";
            document.querySelector("[data-longitude]").value = "";

            executeFunc(function OxMvRIBczY()
            {
                if(typeof initAdddress !== "function")
                {
                    return false;
                }

                initAdddress();
                return true;
            })

            /*setTimeout(function OxMvRIBczY()
             {
             if(typeof initAdddress == "function")
             {
             initAdddress();
             return;
             }
             if(limitOxMvRIBczY > 1e3)
             {
             return;
             }
             limitOxMvRIBczY = limitOxMvRIBczY * 2;
             setTimeout(OxMvRIBczY, limitOxMvRIBczY);
             }, 100)*/;

            initDatepicker();
        }
    });

    return false;
}

async function submitRegionForm(forms, id)
{
    const data = new FormData(forms);
    data.delete(forms.name + "[_token]");

    await fetch(forms.action, {
        method : forms.method,
        cache : "no-cache",
        credentials : "same-origin",
        headers : {"X-Requested-With" : "XMLHttpRequest"},
        redirect : "follow",
        referrerPolicy : "no-referrer",
        body : data,
    }).then(response =>
    {
        if(response.status !== 200)
        {
            return false;
        }

        return response.text();

    }).then(data =>
    {
        if(data)
        {
            var parser = new DOMParser;
            var doc = parser.parseFromString(data, "text/html");
            let callId = id.replace(/_region/g, "_call");
            let call = doc.getElementById(callId);

            document.getElementById(callId).replaceWith(call);
            document.querySelector("[data-latitude]").value = "";
            document.querySelector("[data-longitude]").value = "";

            executeFunc(function ZJzxDhmvtC()
            {
                if(typeof initAdddress !== "function")
                {
                    return false;
                }

                initAdddress();
                return true;

            });

            let delivery_call = document.querySelector("#" + forms.name + "_usr_delivery_field_0_value_call");

            if(delivery_call)
            {
                new NiceSelect(delivery_call, {searchable : true});
            }




            //limitZJzxDhmvtC = 100;
            //
            //setTimeout(function ZJzxDhmvtC()
            //{
            //    if(typeof initAdddress == "function")
            //    {
            //        initAdddress();
            //        return;
            //    }
            //    if(limitZJzxDhmvtC > 1e3)
            //    {return;}
            //    limitZJzxDhmvtC = limitZJzxDhmvtC * 2;
            //    setTimeout(ZJzxDhmvtC, limitZJzxDhmvtC);
            //}, 100);
        }
    });

    return false;
}

async function submitPaymentForm(forms)
{
    const data = new FormData(forms);
    data.delete(forms.name + "[_token]");

    await fetch(forms.action, {
        method : forms.method,
        cache : "no-cache",
        credentials : "same-origin",
        headers : {"X-Requested-With" : "XMLHttpRequest"},
        redirect : "follow",
        referrerPolicy : "no-referrer",
        body : data,
    }).then(response =>
    {
        if(response.status !== 200)
        {
            return false;
        }
        return response.text();
    }).then(data =>
    {
        if(data)
        {
            var parser = new DOMParser;
            var doc = parser.parseFromString(data, "text/html");
            let user_payment = doc.getElementById("user_payment");

            document.getElementById("user_payment").replaceWith(user_payment);

            document.querySelectorAll("input[name*=\"[usr][payment][payment]\"]").forEach(function(user_payment)
            {
                user_payment.addEventListener("change", function(event)
                {
                    let forms = this.closest("form");
                    submitPaymentForm(forms);
                    return false;
                });
            });

            var tooltipTriggerList = [].slice.call(user_payment.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));

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
    data.delete(forms.name + "[_token]");

    await fetch(forms.action, {
        method : forms.method,
        cache : "no-cache",
        credentials : "same-origin",
        headers : {"X-Requested-With" : "XMLHttpRequest"},
        redirect : "follow",
        referrerPolicy : "no-referrer",
        body : data,
    }).then(response =>
    {
        if(response.status !== 200)
        {
            return false;
        }

        return response.text();

    }).then(data =>
    {
        if(data)
        {
            var parser = new DOMParser;
            var doc = parser.parseFromString(data, "text/html");
            let user_profile = doc.getElementById("user_profile");

            document.getElementById("user_profile").replaceWith(user_profile);
            let user_payment = doc.getElementById("user_payment");
            document.getElementById("user_payment").replaceWith(user_payment);

            document.querySelectorAll("input[name*=\"[usr][payment][payment]\"]").forEach(function(userPayment)
            {
                userPayment.addEventListener("change", function(event)
                {
                    let replaceId = "user_profile";
                    let forms = this.closest("form");
                    submitPaymentForm(forms);
                    return false;
                });
            });

            let user_delivery = doc.getElementById("user_delivery");

            document.getElementById("user_delivery").replaceWith(user_delivery);

            document.querySelectorAll("input[name*=\"[usr][delivery][delivery]\"]").forEach(function(user_delivery)
            {
                user_delivery.addEventListener("change", function(event)
                {
                    let forms = this.closest("form");
                    submitDeliveryForm(forms);
                    return false;
                });
            });

            total();

            document.querySelectorAll("select.change_region_field").forEach(function(userRegion)
            {
                userRegion.addEventListener("change", function(event)
                {
                    let forms = this.closest("form");
                    submitRegionForm(forms, userRegion.id);
                    return false;
                });
            });

            var tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));

            tooltipTriggerList.map(function(tooltipTriggerEl)
            {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            initDatepicker();
        }
    });

    return false;
}