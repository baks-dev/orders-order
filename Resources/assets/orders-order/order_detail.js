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

    /* вешаем события на OFFCANVAS */
    document.querySelectorAll(".offcanvas-link").forEach(function(item, i, arr)
    {
        item.addEventListener("click", function()
        {
            offcanvasEventLinkDetail(item);
        });
    });

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
            console.log("Ajax submit сработал, обычный не запущен");
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
        }, {once : true});

    }
}

async function offcanvasEventLinkDetail(offcanvas)
{
    /** Показываем прелоад */
    let modal = document.getElementById("modal");
    //modal.innerHTML = "<div class=\"modal-dialog modal-dialog-centered\"><div class=\"d-flex justify-content-center w-100\"><div class=\"spinner-border text-light\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div></div></div>";

    modal.innerHTML = "<div class=\"modal-dialog modal-dialog-centered modal-xl\">\n" +
        "        <div class=\"modal-content border-0 bg-transparent shadow-none\">\n" +
        "            <div class=\"d-flex justify-content-center w-100\">\n" +
        "                <button type=\"button\" class=\"btn btn-link w-100\" style=\"min-height: 500px;\" data-bs-dismiss=\"modal\">\n" +
        "                    <div class=\"spinner-border text-light\" role=\"status\">\n" +
        "                        <span class=\"visually-hidden\">Loading...</span>\n" +
        "                    </div>\n" +
        "                </button>\n" +
        "            </div>\n" +
        "        </div>\n" +
        "    </div>";


    bootstrap.Modal.getOrCreateInstance(modal).show();

    //const data = new FormData(forms);

    await fetch(offcanvas.dataset.href, {
        method : "GET", // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
        credentials : "same-origin", // include, *same-origin, omit
        headers : {
            "X-Requested-With" : "XMLHttpRequest",
        }, redirect : "follow", // manual, *follow, error
        referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        //body: data // body data type must match "Content-Type" header
    }).then((response) =>
    {
        if(response.status === 302)
        {
            window.location.reload();
        }

        /** Закрываем прелоад */
        bootstrap.Modal.getOrCreateInstance(modal).hide();

        if(response.status !== 200)
        {
            return false;
        }

        return response.text();

    }).then((data) =>
    {
        if(data)
        {
            var myOffcanvas = document.getElementById("offcanvas");

            if(myOffcanvas === null)
            {
                console.log("Элемент с идентификатором id=\"offcanvas\" не найден");

                /**
                 <div class="offcanvas offcanvas-start"
                 tabindex="-1"
                 id="offcanvas"
                 style="--bs-offcanvas-width: 850px;">
                 </div>
                 */

                return;
            }

            let bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(myOffcanvas);

            myOffcanvas.innerHTML = data;

            bsOffcanvas.show();

            /** Обновляем Preload */

            let lazy = document.createElement("script");
            lazy.src = "/assets/" + $version + "/js/lazyload.min.js?v=" + Date.now();
            document.head.appendChild(lazy);

            myOffcanvas.addEventListener("hidden.bs.offcanvas", event =>
            {
                myOffcanvas.innerHTML = "";
            });

            bindBootstrapTooltip();

        }

        /** Закрываем прелоад */
        setTimeout(function()
        {
            bootstrap.Modal.getOrCreateInstance(modal).hide();
        }, 300);

    });

    return false;
}
