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
const UPDATE_INTERVAL_MS = 5000; // Интервал обновления

/**
 * Функция обновления одного статуса
 */
async function updateStatus(status)
{
    try
    {
        const response = await fetch("/admin/orders_status/" + status.dataset.status, {
            method : "POST",
            cache : "no-cache",
            credentials : "same-origin",
            headers : {"X-Requested-With" : "XMLHttpRequest"},
            redirect : "follow",
            referrerPolicy : "no-referrer",
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
function startStatusUpdate(status, intervalMs)
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
        await updateStatus(status);

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
function stopStatusUpdate(statusKey)
{
    if(intervalMap[statusKey])
    {
        clearTimeout(intervalMap[statusKey].timeoutId);
        delete intervalMap[statusKey];
    }
}


const canban_statuses = document.querySelectorAll("[data-status][data-level]");

// При первом запуске обновляем все сразу
for(const status of canban_statuses)
{
    updateStatus(status);
}

// Запускаем индивидуальные интервалы для каждого статуса
for(const status of canban_statuses)
{
    startStatusUpdate(status, UPDATE_INTERVAL_MS);
}


// Опционально: остановить при уходе пользователя с вкладки
window.addEventListener("pagehide", function()
{
    Object.keys(intervalMap).forEach(key => stopStatusUpdate(key));
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
