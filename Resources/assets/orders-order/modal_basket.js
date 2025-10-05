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


basket = document.querySelector('#modal');

modal_form = null;
clicked_button = null; /** Сохраняем ссылку на нажатую кнопку */

/** Отслеживаем клики по кнопкам "Добавить в корзину" */
document.addEventListener('click', function(event)
{
    if(event.target.closest('.add-basket'))
    {
        clicked_button = event.target.closest('.add-basket');
    }
});

basket.addEventListener('shown.bs.modal', function(event)
{

    executeFunc(function initModalBasket()
    {
        modal_form = basket.querySelector('form');

        if(!modal_form)
        {
            return false;
        }

        /** Обработчик для кнопки "В корзину" в модальном окне */
        const submitButton = basket.querySelector('button[type="submit"]');
        if(submitButton)
        {
            submitButton.addEventListener('click', function(event)
            {
                /** Заменяем кнопку в карточке товара на ссылку на корзину */
                setTimeout(function()
                {
                    if(clicked_button && clicked_button.parentNode)
                    {
                        const basketLink = document.createElement('a');
                        basketLink.href = '/basket';
                        basketLink.className = 'btn btn-success d-flex align-items-center';
                        basketLink.title = 'Перейти в корзину';
                        basketLink.style.width = 'fit-content';
                        basketLink.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart-check-fill" viewBox="0 0 16 16">
                                <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m-1.646-7.646-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L8 8.293l2.646-2.647a.5.5 0 0 1 .708.708"/>
                            </svg>
                        `;

                        try
                        {
                            clicked_button.parentNode.replaceChild(basketLink, clicked_button);
                            clicked_button = null;
                        } catch(error)
                        {

                        }
                    }
                }, 1000);
            });
        }

        let input = basket.querySelector('#' + modal_form.name + '_price_total');

        if(input)
        {
            /** Событие на изменение количество в ручную */
            input.addEventListener('input', orderModalCounter.debounce(600));

            /** Счетчик  */
            basket.querySelector('#plus').addEventListener('click', () =>
            {
                let price_total = basket.querySelector('#' + modal_form.name + '_price_total');

                /* Шаг увеличения/уменьшения кол-ва в форме оформления заказа */
                let step = price_total.dataset.step * 1;

                let result = price_total.value * 1 + step;

                /* Максимальное */
                let max = price_total.dataset.max * 1;

                if(result <= max)
                {
                    basket.querySelector('#' + modal_form.name + '_price_total').value = result;
                    orderModalSum(result);
                }

            });


            basket.querySelector('#minus').addEventListener('click', () =>
            {
                let price_total = basket.querySelector('#' + modal_form.name + '_price_total');

                /* Шаг увеличения/уменьшения кол-ва в форме оформления заказа */
                let step = price_total.dataset.step * 1;

                /* Результат */
                let result = price_total.value * 1;

                if(result > 1)
                {
                    result = result - step;

                    if(result <= 0)
                    {
                        return;
                    }

                    basket.querySelector('#' + modal_form.name + '_price_total').value = result;
                    orderModalSum(result);
                }
            });

            return true;
        }

        return false;

    })

});

function orderModalCounter()
{

    /* Шаг увеличения/уменьшения кол-ва в форме оформления заказа */
    let step = this.dataset.step * 1;

    /* Значение */
    let total = this.value * 1;
    if(total === 0)
    {
        total = step;
        this.value = step;
    }

    /* Максимальное */
    let max = this.dataset.max * 1;

    let remainder = this.value % step;

    /* Скорректировать значение если указано значение не кратное step */
    if(remainder !== 0)
    {
        /* Если поль-ль указал значение меньше шага, то задать значение равное шагу */
        if(total < step)
        {
            this.value = step;
        }
        /* Иначе указать значение с учетом остатка */
        if(total > step)
        {
            this.value = total - remainder;
        }
    }

    /* Если поль-ль указал значение больше максимального */
    if(total > max)
    {
        this.value = max;
    }

    orderModalSum(total);

}

function orderModalSum(result)
{
    let product_summ = basket.querySelector('#summ_' + modal_form.name + '_price_total');

    let result_product_sum = result * product_summ.dataset.price;

    if(product_summ.dataset.discount)
    {
        result_product_sum = result_product_sum - (result_product_sum / 100 * product_summ.dataset.discount);
    }

    result_product_sum = result_product_sum / 100;
    result_product_sum = new Intl.NumberFormat($locale, {
        style: 'currency',
        currency: product_summ.dataset.currency === "RUR" ? "RUB" : product_summ.dataset.currency,
        maximumFractionDigits: 0
    }).format(result_product_sum);
    product_summ.innerText = result_product_sum;

}