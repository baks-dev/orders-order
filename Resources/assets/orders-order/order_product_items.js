/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

executeFunc(function editOrderProductItems()
{
    let productItems = document.querySelectorAll("[data-group=\"product-items\"]");

    if(typeof productItems === "undefined" || productItems === null)
    {
        return false;
    }

    productItems.forEach(initHsH22s6NM);

    return true;
});


/** Изменяем цену в каждой единице продукта */
function initHsH22s6NM(item)
{
    const changePriceBtn = item.querySelectorAll("[data-action*=\"price\"]");

    changePriceBtn.forEach(changeOrderProductItemPriceByClick);

    const changePriceInput = item.querySelectorAll(".item-price");

    changePriceInput.forEach(changeOrderProductItemPriceByInput);

    const itemDiscounts = item.querySelectorAll(".item-discount");

    itemDiscounts.forEach(changeOrderProductItemPriceByDiscount);

    const deletItems = item.querySelectorAll(".delete-el");

    deletItems.forEach(deleteOrderProductItem);
}


/** Удаление элементов по кнопке */
function deleteOrderProductItem(btn)
{
    btn.addEventListener("click", function(event)
    {
        const itemForDelete = document.getElementById(this.dataset.id);

        if(!itemForDelete)
        {
            return;
        }

        const totalInput = document.getElementById(btn.dataset.total);

        /** Блок со всеми единицами продукта */
        let items = itemForDelete.parentNode;

        /** Не даем удалить элемент, если их общее количество меньше значения в data атрибуте input с количеством */

        if((parseInt(items.dataset.itemsCount)) <= 1)
        {
            let dangerToast = "{ \"type\":\"danger\" , " +
                "\"header\":\"Ошибка при изменении заказа\"  , " +
                `"message" : "Удалите продукт из заказа" }`;

            createToast(JSON.parse(dangerToast));

            return;
        }

        // @TODO пока не используется
        /** Вставка прототипа удаленного элемента */
        //addOrderProductDeletedItem(this, itemForDelete)

        if(itemForDelete)
        {
            const itemPrice = itemForDelete.querySelector("#" + itemForDelete.id + "_price_price");
            itemForDelete.remove();

            /** Элементы после удаления */
            let itemsAfterDel = items.querySelectorAll("[data-item=\"product-item\"]");

            const newCount = itemsAfterDel.length;
            items.setAttribute("data-items-count", newCount);

            const productTotal = document.getElementById(itemPrice.dataset.total);
            productTotal.value = newCount;

            modifyOrderProductTotal(itemPrice);

            /** Изменение количества в заголовке */
            //if(false === items.parentNode.classList.contains('accordion-body'))
            //{
            //    return;
            //}
            //
            //const accordion = items.parentNode.parentNode
            //
            //const itemHeader = accordion.parentNode.querySelector('.items-header');
            //
            //let itemHeaderCount = itemHeader.querySelector('[data-items-total]');
            //itemHeaderCount.setAttribute('data-items-total', newCount);
            //
            //itemHeaderCount.innerHTML = newCount;
        }
    });
}

/** Изменение цены продукта путем применения скидки */
function changeOrderProductItemPriceByDiscount(input)
{
    input.addEventListener("input", function(event)
    {
        const discount = this.value * -1;

        let price = document.getElementById(this.dataset.target);
        let current_price = price.dataset.price;

        let product_price = parseFloat(current_price);

        price.value = product_price - product_price / 100 * discount;

        modifyOrderProductTotal(price);
    });
}


/** Изменение цены продукта вводом в поле */
function changeOrderProductItemPriceByInput(input)
{
    let timer;

    input.addEventListener("input", function(event)
    {
        clearTimeout(timer);

        timer = setTimeout(() =>
        {
            modifyOrderProductTotal(this);
        }, 1000);

    });
}


/** Изменение цены продукта вводом по кнопкам */
function changeOrderProductItemPriceByClick(btn)
{
    btn.addEventListener("click", function(event)
    {
        let inpt = document.getElementById(this.dataset.id);
        let inptValue = inpt.value;

        const action = btn.dataset.action;

        if(action !== "price-plus" && action !== "price-minus")
        {
            return;
        }

        let result = parseFloat(inptValue.replace(",", "."));

        if(action === "price-minus")
        {
            result = result - (this.dataset.step ? this.dataset.step * 1 : 1);

            if(result <= 0)
            {
                return;
            }
        }

        if(action === "price-plus")
        {
            result = result + (this.dataset.step ? this.dataset.step * 1 : 1);
        }

        let price = document.getElementById(this.dataset.id);
        price.value = result;

        modifyOrderProductTotal(price);
    });
}


/** Изменение общей цены за продукты */
function modifyOrderProductTotal(el)
{
    let product_summ = document.getElementById("summ_" + el.dataset.total);

    /** Блок с единицами продукта */
    const items = document.getElementById(el.dataset.item + "-items");

    let items_summ = null;

    if(items)
    {
        /**  */
        items.querySelectorAll(".item-price").forEach(function(el)
        {
            let result = parseFloat(el.value.replace(",", "."));
            items_summ += result;
        });
    }

    let result_product_sum = new Intl.NumberFormat($locale, {
        style : "currency",
        currency : product_summ.dataset.currency,
        maximumFractionDigits : 0,
    }).format(items_summ);

    product_summ.innerText = result_product_sum;
    product_summ.setAttribute("data-total", items_summ);

    let total_sum = null;

    let product_list = document.getElementById("order-product-list");

    product_list.querySelectorAll(".total-sum").forEach(function(el)
    {
        let result = parseFloat(el.dataset.total);
        total_sum += result;
    });

    let total_product_sum = document.getElementById("total_product_sum");
    let total_list = document.getElementById("total_all_sum");

    let total_summ_format = new Intl.NumberFormat($locale, {
        style : "currency",
        currency : "RUB",
        maximumFractionDigits : 0,
    }).format(total_sum);

    total_product_sum.innerText = total_summ_format;
    total_list.innerText = total_summ_format;
}

/** Вставка прототипа удаленного элемента */
function addOrderProductDeletedItem(btn, el)
{
    /** Вставляем в прототип индекс элемента */
    let prototypeContent = document.getElementById(btn.dataset.itemsDeletedPrototype).innerText;
    prototypeContent = prototypeContent.replace(/__deleted-item__/g, btn.dataset.itemsIndex);

    /** Блок с удаленными единицами */
    let deletedItems = document.getElementById(btn.dataset.itemsDeleted);

    const template = document.createElement("template");
    template.innerHTML = prototypeContent.trim();

    /** Присваивает идентификаторы из одного элемента в другой */
    for(let input of template.content.children)
    {

        /**
         * ID
         */
        if(input.id.endsWith("_id"))
        {
            const itemId = el.querySelector("#" + el.id + "_id");

            if(itemId)
            {
                input.value = itemId.dataset.itemsId.trim();
            }
        }


        /**
         * Продукт
         */
        if(input.id.endsWith("_product"))
        {
            const itemProduct = el.querySelector("#" + el.id + "_product");

            if(itemProduct)
            {
                input.value = itemProduct.dataset.itemsProduct.trim();
            }
        }

        /**
         * Константа
         */
        if(input.id.endsWith("_const"))
        {
            const itemConst = el.querySelector("#" + el.id + "_const");

            if(itemConst)
            {
                input.value = itemConst.dataset.itemsConst.trim();
            }
        }
    }

    /** Вставляем элемент в коллекцию для удаления */
    const fragment = template.content.cloneNode(true);
    deletedItems.appendChild(fragment);
}

//const observer = new MutationObserver(() => {
//    console.log('Новый текст:', product_summ.textContent);
//});
//
//
//observer.observe(product_summ, {
//    childList: true,       // наблюдать за добавлением/удалением потомков
//    subtree: true,         // наблюдать также за потомками
//    characterData: true,   // наблюдать за изменениями текста
//    attributes: true,      // наблюдать за изменениями атрибутов
//    attributeOldValue: true // сохранять старое значение атрибута
//});
