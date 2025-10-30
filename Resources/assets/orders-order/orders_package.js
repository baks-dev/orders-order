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

executeFunc(initOrderPackage);

function initOrderPackage()
{
    const modal = document.getElementById("modal");
    const frm = modal.querySelector("form");

    if(false === listenerObjectPackageWarehouse(frm))
    {
        return false;
    }

    frm.addEventListener(
        "submit",
        function(event)
        {
            event.preventDefault();
            submitModalForm(frm);
            return false;
        },
        {
            once : true,
        });

    return true;
}


function listenerObjectPackageWarehouse(frm)
{
    let orderpackageWarehouse = document.getElementById(`${frm.name}_profile`);

    if(orderpackageWarehouse === null)
    {
        return false;
    }

    orderpackageWarehouse.addEventListener("change", changeObjectPackageWarehouse, false);

    // Подсчёт продуктов в форме
    let productsCount = document.querySelectorAll(".product-total").length;

    // Блокируем кнопку сабмита, если на складе недостаточно остатка
    for(let key = 0; key < productsCount; key++)
    {
        let total = parseInt(document.getElementById(`${frm.name}_products_${key}_total`).value);
        let stock = parseInt(document.getElementById(`${frm.name}_products_${key}_stock`).value);

        let submitButton = document.getElementById(`${frm.name}_package`);

        if(total > stock && false === submitButton.classList.contains("disabled"))
        {
            submitButton.classList.add("disabled");
            //submitButton.disabled = true;
        }

        if(total <= stock && true === submitButton.classList.contains("disabled"))
        {
            submitButton.classList.remove("disabled");
            submitButton.disabled = false;
        }
    }

    /** Вешаем события но новые кнопки */
    modal.querySelectorAll("[data-bs-target=\"#modal\"]").forEach(function(item, i, arr)
    {
        modalLink(item);
    });


    return true;
}


function changeObjectPackageWarehouse()
{
    /* Создаём объект класса XMLHttpRequest */
    const requestModalName = new XMLHttpRequest();

    requestModalName.responseType = "document";

    const modal = document.getElementById("modal");
    const PackageOrderForm = modal.querySelector("form");

    disabledElementsForm(PackageOrderForm);

    /** Удаляем токен из отпарвки данных */
    let formData = new FormData(PackageOrderForm);
    formData.delete(PackageOrderForm.name + "[_token]");

    requestModalName.open(PackageOrderForm.getAttribute("method"), PackageOrderForm.getAttribute("action"), true);

    /* Указываем заголовки для сервера */
    requestModalName.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    /* Получаем ответ от сервера на запрос*/
    requestModalName.addEventListener("readystatechange", function()
    {
        enableElementsForm(PackageOrderForm);

        /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
        if(requestModalName.readyState === 4 && requestModalName.status === 200)
        {

            let result = requestModalName.response.getElementById("modal-body");

            result.querySelectorAll("[data-select=\"select2\"]").forEach(function(item)
            {
                new NiceSelect(item, {searchable : true});
            });

            document.getElementById("modal-body").replaceWith(result);

            listenerObjectPackageWarehouse(PackageOrderForm);
        }

        return false;
    });

    requestModalName.send(formData);
}