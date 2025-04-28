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

basket = document.querySelector('#modal');  //getElementById('modal');

modal_form = null;

basket.addEventListener('shown.bs.modal', function(event)
{

    executeFunc(function initModalBasket()
    {
        modal_form = basket.querySelector('form');

        if(!modal_form)
        {
            return false;
        }

        let input = basket.querySelector('#' + modal_form.name + '_price_total'); //basket.getElementById('order_product_form_price_total');

        if(input)
        {
            /** Событие на изменение количество в ручную */
            input.addEventListener('input', orderModalCounter.debounce(300));

            /** Счетчик  */
            basket.querySelector('#plus').addEventListener('click', () =>
            {
                let price_total = basket.querySelector('#' + modal_form.name + '_price_total');
                let result = price_total.value * 1;
                let max = price_total.dataset.max * 1;

                if(result < max)
                {
                    result = result + 1;
                    basket.querySelector('#' + modal_form.name + '_price_total').value = result;
                    orderModalSum(result);
                }

            });


            basket.querySelector('#minus').addEventListener('click', () =>
            {
                let price_total = basket.querySelector('#' + modal_form.name + '_price_total');
                let result = price_total.value * 1;

                if(result > 1)
                {
                    result = result - 1
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
    let result = this.value * 1;
    let max = this.dataset.max * 1;


    if(result > max)
    {
        basket.querySelector('#' + modal_form.name + '_price_total').value = max;
        result = max;
    }

    orderModalSum(result);

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
        currency : product_summ.dataset.currency === "RUR" ? "RUB" : product_summ.dataset.currency,
        maximumFractionDigits: 0
    }).format(result_product_sum);
    product_summ.innerText = result_product_sum;

}

