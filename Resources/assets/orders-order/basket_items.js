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

/** Изменение кол-ва в форме добавления в корзину на странице товара  */

document.querySelectorAll('.img-product').forEach((el) => el.addEventListener('click', () => document.getElementById('img-product').style.backgroundImage = el.style.backgroundImage));

/* Счетчик c учетом шага увеличения/уменьшения кол-ва в форме оформления заказа  */
document.getElementById("plus")?.addEventListener("click", () =>
{
    let price_total = document.getElementById("public_order_product_form_price_total");

    /* Получить Шаг увеличения/уменьшения кол-ва */
    let step = price_total.dataset.step * 1;

    /* Результат складывается из значения + Шаг */
    let result = price_total.value * 1 + step;

    let max = price_total.dataset.max * 1;

    if(result <= max)
    {
        document.getElementById("public_order_product_form_price_total").value = result;
    }

});

/* C учетом шага увеличения/уменьшения кол-ва в форме оформления заказа */
document.getElementById("minus")?.addEventListener("click", () =>
{

    let price_total = document.getElementById("public_order_product_form_price_total");

    /* Шаг увеличения/уменьшения кол-ва */
    let step = price_total.dataset.step * 1;

    let result = price_total.value * 1;

    if(result > 1)
    {
        result = result - step;

        if(result <= 0)
        {
            return;
        }

        document.getElementById("public_order_product_form_price_total").value = result;

    }
});


/* Обработка изменения кол-ва вручную  */
const product_price_total = document.getElementById("public_order_product_form_price_total");

product_price_total.addEventListener("input", orderProductCounter.debounce(500));

function orderProductCounter()
{
    /* Шаг увеличения/уменьшения кол-ва */
    let step = this.dataset.step * 1;

    /* Значение */
    let total = this.value * 1;

    /* Если указали 0 */
    if(total === 0)
    {
        total = step;
        this.value = step;
    }

    /* Максимальное */
    let max = this.dataset.max * 1

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
}