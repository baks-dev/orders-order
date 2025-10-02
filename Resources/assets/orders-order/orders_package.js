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
    let orderpackageWarehouse = document.getElementById("package_orders_form_profile");

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
        let total = parseInt(document.getElementById(`package_orders_form_products_${key}_total`).value);
        let stock = parseInt(document.getElementById(`package_orders_form_products_${key}_stock`).value);

        let submitButton = document.getElementById("package_orders_form_package");

        if(total > stock && false === submitButton.classList.contains("disabled"))
        {
            submitButton.classList.add("disabled");
            return false;
        }

        if(total <= stock && true === submitButton.classList.contains("disabled"))
        {
            submitButton.classList.remove("disabled");
            submitButton.disabled = false;
        }
    }

    /* Имя формы */
    let forms = document.forms.package_orders_form;

    /* событие отправки формы */
    forms.addEventListener("submit", function(event)
    {
        event.preventDefault();
        submitModalForm(forms);
        return true;
    });

    return true;
}

function changeObjectPackageWarehouse()
{
    /* Создаём объект класса XMLHttpRequest */
    const requestModalName = new XMLHttpRequest();

    requestModalName.responseType = "document";


    /* Имя формы */
    let PackageOrderForm = document.forms.package_orders_form;
    disabledElementsForm(PackageOrderForm);


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

            /** Изменияем список целевых складов */
            // new NiceSelect(document.getElementById('package_order_form_warehouse'), {
            //     searchable: true,
            //     id: 'select2-package_order_form_warehouse'
            // });

            initOrderPackage();
        }

        return false;
    });

    requestModalName.send(formData);

}