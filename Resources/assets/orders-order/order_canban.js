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

// Объект для хранения идентификаторов интервалов для каждого статуса
const intervalMap = {};
const UPDATE_INTERVAL_MS = 60000; // Интервал обновления

const canban_statuses = document.querySelectorAll("[data-status][data-level]");

let isActiveDocument = true;


function checkTabState()
{
    const isActive = (false === document.hidden);
    //console.log(isActive ? "Вкладка активна" : "Вкладка неактивна");

    if(false === isActiveDocument && true === isActive)
    {
        //console.log("Вернулись во вкладку");

        isActiveDocument = true;

        // Обновляем статусы когда возвращаемся в окно
        for(const status of canban_statuses)
        {
            updateStatusOrderCanban(status, null);
        }

        return true;
    }

    isActiveDocument = isActive;

    return isActive;
}

// Слушаем изменения
document.addEventListener("visibilitychange", checkTabState);


executeFunc(
    function scheduleAutoSubmitOrderDeliveryFilterForm()
    {
        if(typeof formDebounce !== "function")
        {
            return false;
        }

        const form = document.forms.order_delivery_filter_form;

        if(typeof form === "undefined")
        {
            return false;
        }

        const debouncedSubmit = formDebounce(() =>
        {
            const data = new FormData(form);

            // При первом запуске обновляем все сразу
            for(const status of canban_statuses)
            {
                // Останавливаем старый интервал, если есть
                const oldInterval = intervalMap[status.dataset.status];

                if(oldInterval)
                {
                    clearInterval(oldInterval);
                }

                // Находим все элементы с классом draggable
                const draggableElements = status.querySelectorAll(".draggable");

                // Полностью удаляем каждый элемент из DOM-дерева
                draggableElements.forEach(element =>
                {
                    // 1. Задаем параметры плавности прямо в стиле элемента
                    element.style.transition = "opacity 0.5s ease, transform 0.5s ease";

                    // 2. Запускаем анимацию на следующем кадре анимации (важно для срабатывания transition)
                    requestAnimationFrame(() =>
                    {
                        element.style.opacity = "0";
                        element.style.transform = "scale(0.9)";
                    });

                });

                /** Снимаем чекбоксы с заказов */
                document.querySelectorAll("input[type=\"checkbox\"][data-status]:checked").forEach(order_checked =>
                {
                    order_checked.checked = false;
                });


                // Очищаем список выбранных заказов и закрываем модал
                selectedOrders.clear();
                //updateSelectedOrdersVisuals();


                updateStatusOrderCanban(status, data);
            }

        }, 1000);

        const inputFields = form.querySelectorAll("input, select, textarea");

        // Добавляем обработчик изменения для каждого поля ввода
        inputFields.forEach(field =>
        {
            if(field.tagName === "INPUT")
            {
                // Вешаем на все relevant события
                field.addEventListener("input", debouncedSubmit);
                field.addEventListener("keyup", debouncedSubmit);
            }

            if(field.tagName === "SELECT")
            {
                field.addEventListener("change", debouncedSubmit);
            }

            field.addEventListener("focus", (event) =>
            {
                if(idFormDebounce)
                {
                    clearTimeout(idFormDebounce);
                }

                if(lastFormDebounce)
                {
                    clearTimeout(idFormDebounce);
                }

                //debouncedSubmit();
            });

            // 2. Логика кнопки "Х" (Очистка поля и сессии)
            form.querySelectorAll(".clear-btn").forEach(btn =>
            {
                btn.addEventListener("click", (e) =>
                {
                    e.preventDefault();
                    const fieldId = btn.dataset.clear;

                    // Находим input по имени (Symfony генерирует имена вида form_name[field_name])
                    const input = form.querySelector(`#${fieldId}`);

                    if(input)
                    {
                        input.value = ""; // Очищаем поле

                        // Сбрасываем таймер авто-отправки, чтобы не было дублирования
                        if(idFormDebounce)
                        {
                            clearTimeout(idFormDebounce);
                        }

                        if(lastFormDebounce)
                        {
                            clearTimeout(idFormDebounce);
                        }

                        debouncedSubmit();
                    }
                });
            });

        });


        return true;
    });


/**
 * Функция обновления одного статуса
 */
async function updateStatusOrderCanban(status, form_data)
{

    // Останавливаем старый интервал, если есть
    const oldInterval = intervalMap[status.dataset.status];

    if(oldInterval)
    {
        clearInterval(oldInterval);
    }

    try
    {
        /** Если вкладка не активна - не обновляем */
        if(false === isActiveDocument)
        {
            //console.log("Вкладка не активна, не выполняем запрос ");
            return true;
        }

        /** Проверяем есть ли выделенные заказы */
        const ischeckboxChecked = document.querySelector("input[type=\"checkbox\"][data-status]:checked");

        if(ischeckboxChecked)
        {
            return true;
        }

        const response = await fetch("/admin/orders_status/" + status.dataset.status, {
            method : "POST",
            cache : "no-cache",
            credentials : "same-origin",
            headers : {"X-Requested-With" : "XMLHttpRequest"},
            redirect : "follow",
            referrerPolicy : "no-referrer",
            body : form_data,
        });

        if(!response.ok)
        {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.text();

        if(data)
        {
            status.innerHTML = data;

            let lazy = document.createElement("script");
            lazy.src = "/assets/" + $version + "/js/lazyload.min.js?v=" + Date.now();
            document.head.appendChild(lazy);

            executeFunc(() =>
            {
                document.querySelectorAll(".offcanvas-link").forEach(function(item)
                {
                    item.addEventListener("click", function()
                    {
                        offcanvasLink(item);
                    });

                    item.classList.remove("offcanvas-link");
                });


                var tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));

                tooltipTriggerList.map(function(tooltipTriggerEl)
                {
                    const tooltipInstance = new bootstrap.Tooltip(tooltipTriggerEl);

                    tooltipTriggerEl.addEventListener("click", event =>
                    {
                        tooltipInstance.hide();
                    });

                    tooltipTriggerEl.removeAttribute("data-bs-toggle");

                    return tooltipInstance;
                });


                // Добавляем обработчики для чекбоксов
                function initCheckboxHandlers()
                {

                    const all = document.getElementById("check-all");


                    const checkboxes = document.querySelectorAll(".draggable input[type=\"checkbox\"]");
                    //console.log("Найдено чекбоксов:", checkboxes.length);

                    checkboxes.forEach(checkbox =>
                    {

                        /** снимаем чеки при обновлении */
                        checkbox.checked = false;

                        checkbox.addEventListener("change", function()
                        {
                            const orderId = this.closest(".draggable").id;
                            const draggableElement = this.closest(".draggable");

                            /** Ограничиваем выделяемые заказы по статусу */
                            status = checkbox.dataset.status;


                            //console.log("Чекбокс изменен для заказа:", orderId, "Checked:", this.checked);

                            if(this.checked)
                            {
                                //selectCheckboxesByPrefix(checkbox.id);

                                selectedOrders.add(orderId);
                                draggableElement.classList.add("selected-order");
                                //console.log("Заказ добавлен в selectedOrders:", orderId);

                                /** Выделяем аналогичные заказы */

                                //// Извлекаем префикс (убираем последнюю часть после последнего дефиса)
                                //let checkboxId = checkbox.id;
                                //const idParts = checkboxId.split("-");
                                //idParts.pop(); // Удаляем последнюю часть
                                //const prefix = idParts.join("-");
                                //
                                //// Находим и выделяем все соответствующие checkbox
                                //const checkboxes = document.querySelectorAll(`input[type="checkbox"][id^="${prefix}"]`);
                                //
                                //checkboxes.forEach(check =>
                                //{
                                //    if(!check.checked)
                                //    {
                                //        check.checked = true;
                                //        selectedOrders.add(check.value);
                                //    }
                                //});

                            }
                            else
                            {
                                selectedOrders.delete(orderId);
                                draggableElement.classList.remove("selected-order");
                                //console.log("Заказ удален из selectedOrders:", orderId);
                            }

                            //console.log("Текущий список selectedOrders:", Array.from(selectedOrders));

                            // Визуальное выделение выбранных карточек
                            updateSelectedOrdersVisuals();
                        });
                    });

                    if(all)
                    {
                        /** снимаем чек при обновлении */
                        all.checked = false;

                        all.addEventListener("change", function(all)
                        {
                            checkboxes.forEach(checkbox =>
                            {

                                if(checkbox.dataset.status === "new")
                                {
                                    const orderId = checkbox.closest(".draggable").id;
                                    const draggableElement = checkbox.closest(".draggable");

                                    checkbox.checked = this.checked;

                                    if(this.checked)
                                    {
                                        selectedOrders.add(orderId);
                                        draggableElement.classList.add("selected-order");

                                    }
                                    else
                                    {
                                        selectedOrders.delete(orderId);
                                        draggableElement.classList.remove("selected-order");
                                    }
                                }

                            });

                            updateSelectedOrdersVisuals();
                        });
                    }

                }

                // Функция для обновления визуального состояния выбранных карточек
                function updateSelectedOrdersVisuals()
                {
                    const allDraggables = document.querySelectorAll(".draggable");

                    allDraggables.forEach(draggable =>
                    {
                        const orderId = draggable.id;
                        const draggableHandle = draggable.querySelector(".draggable-handle");
                        const draggableCheckbox = draggable.querySelector("input[type=\"checkbox\"]");


                        if(selectedOrders.has(orderId))
                        {
                            /** Показать полностью весь заказ */
                            draggable.classList.remove("opacity-50");
                            draggable.classList.replace("z-0", "z-2");

                            //draggable.classList.add("opacity-100");

                            /** Выделяем заказ рамкой */
                            draggable.style.transform = "scale(0.98)";
                            draggable.style.boxShadow = "0 0 0 2px #007bff";

                            // Если есть выделенные карточки, включаем перетаскивание только для них
                            if(draggableHandle)
                            {
                                //draggableHandle.classList.remove("d-none");
                                draggableHandle.style.pointerEvents = "auto";
                            }
                        }
                        else
                        {

                            //draggable.classList.remove("opacity-100");
                            draggable.removeAttribute("style");

                            //draggable.style.opacity = '';
                            //draggable.style.removeProperty('transform'); // transform = "";
                            //draggable.style.removeProperty('box-shadow') //.boxShadow = "";

                            // Если есть выделенные карточки, отключаем перетаскивание для невыделенных
                            if(draggableHandle)
                            {
                                if(selectedOrders.size > 0)
                                {
                                    draggable.classList.add("opacity-50"); // полупрозрачный заказ
                                    //draggableHandle.classList.add("d-none"); // скрываем кнопку перетаскивания

                                    //draggableHandle.style.pointerEvents = 'auto';
                                    draggableHandle.style.pointerEvents = "none";

                                    /** получаем элемент chekbox */
                                    if(draggableCheckbox && draggableCheckbox.dataset.status !== status)
                                    {
                                        //draggableCheckbox.classList.add("invisible");
                                        draggableCheckbox.disabled = true;
                                    }
                                }

                                if(selectedOrders.size === 0)
                                {
                                    // Если нет выделенных карточек, включаем перетаскивание для всех
                                    draggable.classList.remove("opacity-50");
                                    //draggableHandle.classList.remove("d-none");


                                    draggableHandle.style.pointerEvents = "auto";

                                    //draggableCheckbox.classList.remove("disabled");
                                    draggableCheckbox ? draggableCheckbox.disabled = false : false;

                                    status = null;
                                }

                            }

                        }
                    });
                }

                // Функция для создания визуального индикатора множественного перетаскивания
                function createMultipleDragIndicator(count)
                {
                    const indicator = document.createElement("div");
                    indicator.className = "multiple-drag-indicator";
                    indicator.style.cssText = `
            position: absolute;
            top: -10px;
            right: -10px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            z-index: 1000;
        `;
                    indicator.textContent = count;
                    return indicator;
                }

                // Инициализируем обработчики чекбоксов
                initCheckboxHandlers();


                return true;

            }, 300);
        }
    }
    catch(error)
    {
        console.error(`[Canban] Ошибка обновления статуса ${status.dataset.status}:`, error);
    }
}

/**
 * Запуск циклического обновления для одного статуса
 */
function startStatusOrderCanbanUpdate(status, intervalMs)
{

    // Останавливаем старый интервал, если есть
    const oldInterval = intervalMap[status.dataset.status];

    if(oldInterval)
    {
        clearInterval(oldInterval);
    }

    // Запускаем новый таймер: сначала выполнение fetch, затем задержка
    async function scheduleUpdate()
    {
        await updateStatusOrderCanban(status, null);

        // Планируем обновление через интервал
        const timeoutId = setTimeout(scheduleUpdate, intervalMs);

        // Сохраняем ссылки для остановки
        intervalMap[status.dataset.status] = {timeoutId, promise : Promise.resolve()};
    }

    // Запускаем с смещением (для распределения нагрузки)
    const startDelay = Math.floor(Math.random() * intervalMs); // Случайная задержка 0-5с
    setTimeout(scheduleUpdate, startDelay);
}

/**
 * Остановка обновления для конкретного статуса
 */
function stopStatusOrderCanbanUpdate(statusKey)
{
    if(intervalMap[statusKey])
    {
        clearTimeout(intervalMap[statusKey].timeoutId);
        delete intervalMap[statusKey];
    }
}


// При первом запуске обновляем все сразу
for(const status of canban_statuses)
{
    updateStatusOrderCanban(status, null);
}

// Запускаем индивидуальные интервалы для каждого статуса
for(const status of canban_statuses)
{
    startStatusOrderCanbanUpdate(status, UPDATE_INTERVAL_MS);
}


// Опционально: остановить при уходе пользователя с вкладки
window.addEventListener("pagehide", function()
{
    Object.keys(intervalMap).forEach(key => stopStatusOrderCanbanUpdate(key));
});


//
//let canban_statuses = document.querySelectorAll("[data-status][data-level]");
//
//
//canban_statuses.forEach(async status =>
//{
//
//    await fetch("/admin/orders_status/" + status.dataset.status, {
//        method : "POST", // *GET, POST, PUT, DELETE, etc.
//        //mode: 'same-origin', // no-cors, *cors, same-origin
//        cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
//        credentials : "same-origin", // include, *same-origin, omit
//        headers : {
//            "X-Requested-With" : "XMLHttpRequest",
//        },
//        redirect : "follow", // manual, *follow, error
//        referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
//        //body : data, // body data type must match "Content-Type" header
//    }).then((response) =>
//    {
//        if(response.status !== 200)
//        {
//            return false;
//        }
//
//        return response.text();
//
//    }).then((data) =>
//    {
//
//        if(data)
//        {
//            status.innerHTML = data;
//
//            executeFunc(() =>
//            {
//                /** Обновляем Preload */
//
//                let lazy = document.createElement("script");
//                lazy.src = "/assets/" + $version + "/js/lazyload.min.js?v=" + Date.now();
//                document.head.appendChild(lazy);
//
//                /* вешаем события на OFFCANVAS */
//                document.querySelectorAll(".offcanvas-link").forEach(function(item, i, arr)
//                {
//                    item.addEventListener("click", function()
//                    {
//                        offcanvasLink(item);
//                    });
//
//                    item.classList.remove("offcanvas-link");
//                });
//
//                return true;
//
//            }, 300);
//
//        }
//
//    });
//
//});
//
//
//


//
//let canban_statuses = document.querySelectorAll("[data-status][data-level]");
//
//
//canban_statuses.forEach(async status =>
//{
//
//    await fetch("/admin/orders_status/" + status.dataset.status, {
//        method : "POST", // *GET, POST, PUT, DELETE, etc.
//        //mode: 'same-origin', // no-cors, *cors, same-origin
//        cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
//        credentials : "same-origin", // include, *same-origin, omit
//        headers : {
//            "X-Requested-With" : "XMLHttpRequest",
//        },
//        redirect : "follow", // manual, *follow, error
//        referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
//        //body : data, // body data type must match "Content-Type" header
//    }).then((response) =>
//    {
//        if(response.status !== 200)
//        {
//            return false;
//        }
//
//        return response.text();
//
//    }).then((data) =>
//    {
//
//        if(data)
//        {
//            status.innerHTML = data;
//
//            executeFunc(() =>
//            {
//                /** Обновляем Preload */
//
//                let lazy = document.createElement("script");
//                lazy.src = "/assets/" + $version + "/js/lazyload.min.js?v=" + Date.now();
//                document.head.appendChild(lazy);
//
//                /* вешаем события на OFFCANVAS */
//                document.querySelectorAll(".offcanvas-link").forEach(function(item, i, arr)
//                {
//                    item.addEventListener("click", function()
//                    {
//                        offcanvasLink(item);
//                    });
//
//                    item.classList.remove("offcanvas-link");
//                });
//
//                return true;
//
//            }, 300);
//
//        }
//
//    });
//
//});
//
//
//
