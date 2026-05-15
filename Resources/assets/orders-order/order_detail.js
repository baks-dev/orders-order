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

executeFunc(function executeOrderCanvasSublitForm()
{
    const myOffcanvas = document.getElementById("offcanvas");
    const forms = myOffcanvas.querySelector("form");

    if(typeof forms !== "object")
    {
        return false;
    }

    submitOrderCanvasForm(forms);

    return true;
});

async function submitOrderCanvasForm(forms)
{
    let order_submit_btn = document.getElementById(forms.name + "_order");

    if(typeof order_submit_btn == "object")
    {
        forms.addEventListener("submit", function(event)
        {
            event.preventDefault();
            disabledElementsForm(forms);

            const data = new FormData(forms);

            fetch(forms.action, {
                method : forms.method, // *GET, POST, PUT, DELETE, etc.
                //mode: 'same-origin', // no-cors, *cors, same-origin
                cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
                credentials : "same-origin", // include, *same-origin, omit
                headers : {
                    // 'X-Requested-With': 'XMLHttpChange'
                    "X-Requested-With" : "XMLHttpRequest",
                },
                redirect : "follow", // manual, *follow, error
                referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
                body : data, // body data type must match "Content-Type" header
            })

                /** Выводим сообщение в случае ошибки */
                //.then((response) => response)
                .then((response) =>
                {
                    if(!response.ok)
                    { // проверяем, успешен ли ответ (статус 200-299)
                        throw new Error(`Ошибка HTTP: ${response.status}`);
                    }

                    return response.json(); // парсим JSON

                })


                /** Блокируем заказ в случае успешной отправки формы */
                .then(data =>
            {
                /** Показываем сообщение об успешном изменении заказа */
                createToast(data);

                /** Блокируем карточку товара в канбане */
                const order_card = document.getElementById(forms.dataset.id);

                if(order_card)
                {
                    const label = order_card.querySelector(`label[for="${forms.dataset.posting}"]`);

                    let draggable = order_card.querySelector("[class*=\"draggable\"]");

                    if(draggable !== null)
                    {
                        /** Блокируем draggable */
                        let draggableMove = draggable.querySelector(".bi-arrows-move");
                        draggableMove.classList.add("d-none");

                        let draggableLock = draggable.querySelector(".bi-ban");
                        draggableLock.classList.remove("d-none");

                        /** Скрываем чекбокс выбора карточки */
                        label?.classList.add("fade");

                        draggable.classList.remove("draggable-handle");
                        draggable.classList.add("draggable-lock");
                    }
                }

                var myOffcanvas = document.getElementById("offcanvas");

                if(myOffcanvas)
                {
                    let bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(myOffcanvas);
                    bsOffcanvas.hide();
                }

                // работаем с данными
            })

                /** Ошибка запроса */
                .catch(error =>
            {

                let $successSupplyToast = "{ \"type\":\"danger\" , " +
                    "\"header\":\"Ошибка при обновлении заказа\"  , " +
                    "\"message\" : \"Повторите попытку позже либо обратитесь к администратору ресурса за дополнительной информацией\" }";

                createToast(JSON.parse($successSupplyToast));

                var myOffcanvas = document.getElementById("offcanvas");

                if(myOffcanvas)
                {
                    let bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(myOffcanvas);
                    bsOffcanvas.hide();
                }
            });
        });

    }


}
