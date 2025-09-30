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


var containers = document.querySelectorAll(".draggable-zone");

// Массив для хранения выбранных заказов
let selectedOrders = new Set();

const form = document.forms.order_delivery_filter_form;
form.addEventListener('change', () => { setTimeout(() => { form.submit(); }, 300); });


function getToken(url, ctx)
{
    return new Promise((resolve, reject) =>
    {
        fetch(url, {
            method: 'POST',
            headers: new Headers({'Content-Type': 'application/json'}),
            body: JSON.stringify(ctx)
        })
            .then(res =>
            {
                if(!res.ok)
                {
                    throw new Error(`Unexpected status code ${res.status}`);
                }
                return res.json();
            })
            .then(data =>
            {
                resolve(data.token);

            })
            .catch(err =>
            {
                reject(err);
            });
    });
}


executeFunc(function P8X1I2diQ4()
{

    if(typeof Droppable !== 'object' || typeof bootstrap !== 'object')
    {
        return false;
    }

    // Добавляем обработчики для чекбоксов
    function initCheckboxHandlers() {
        const checkboxes = document.querySelectorAll('.draggable input[type="checkbox"]');
        console.log('Найдено чекбоксов:', checkboxes.length);

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const orderId = this.closest('.draggable').id;
                const draggableElement = this.closest('.draggable');

                console.log('Чекбокс изменен для заказа:', orderId, 'Checked:', this.checked);

                if (this.checked) {
                    selectedOrders.add(orderId);
                    draggableElement.classList.add('selected-order');
                    console.log('Заказ добавлен в selectedOrders:', orderId);
                } else {
                    selectedOrders.delete(orderId);
                    draggableElement.classList.remove('selected-order');
                    console.log('Заказ удален из selectedOrders:', orderId);
                }

                console.log('Текущий список selectedOrders:', Array.from(selectedOrders));

                // Визуальное выделение выбранных карточек
                updateSelectedOrdersVisuals();
            });
        });
    }

    // Функция для обновления визуального состояния выбранных карточек
    function updateSelectedOrdersVisuals() {
        const allDraggables = document.querySelectorAll('.draggable');

        allDraggables.forEach(draggable => {
            const orderId = draggable.id;
            const draggableHandle = draggable.querySelector('.draggable-handle');

            if (selectedOrders.has(orderId)) {
                draggable.style.opacity = '0.8';
                draggable.style.transform = 'scale(0.98)';
                draggable.style.boxShadow = '0 0 0 2px #007bff';

                // Если есть выделенные карточки, включаем перетаскивание только для них
                if (draggableHandle) {
                    draggableHandle.style.pointerEvents = 'auto';
                    draggableHandle.style.opacity = '1';
                }
            } else {
                draggable.style.opacity = '';
                draggable.style.transform = '';
                draggable.style.boxShadow = '';

                // Если есть выделенные карточки, отключаем перетаскивание для невыделенных
                if (draggableHandle && selectedOrders.size > 0) {
                    draggableHandle.style.pointerEvents = 'none';
                    draggableHandle.style.opacity = '0.3';
                    draggable.style.opacity = '0.5';
                } else if (draggableHandle) {
                    // Если нет выделенных карточек, включаем перетаскивание для всех
                    draggableHandle.style.pointerEvents = 'auto';
                    draggableHandle.style.opacity = '1';
                }
            }
        });
    }

    // Функция для создания визуального индикатора множественного перетаскивания
    function createMultipleDragIndicator(count) {
        const indicator = document.createElement('div');
        indicator.className = 'multiple-drag-indicator';
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

    const modal = document.getElementById('modal');
    const modal_bootstrap = bootstrap.Modal.getOrCreateInstance(modal);

    var droppable = new Droppable.default(containers, {
        draggable: ".draggable",
        dropzone: ".draggable-zone",
        handle: ".draggable .draggable-handle",
        mirror: {
            //appendTo: selector,
            appendTo: "body",
            constrainDimensions: true
        }
    });

    // Define draggable element variable for permissions level
    let droppableOrigin;
    let droppableLevel;
    let droppableRestrict;

    let toDroppable;
    let isDraggingSelected = false;
    let draggedOrderIds = [];

    // Handle drag start event -- more info: https://shopify.github.io/draggable/docs/class/src/Draggable/DragEvent/DragEvent.js~DragEvent.html
    droppable.on("drag:start", (e) =>
    {
        document.body.style.overflow = 'hidden';

        const draggedOrderId = e.originalSource.id;

        console.log('Drag start для заказа:', draggedOrderId);
        console.log('Выбранные заказы:', Array.from(selectedOrders));
        console.log('Размер выбранных:', selectedOrders.size);

        // Проверяем, является ли перетаскиваемый элемент частью выделенных
        if (selectedOrders.has(draggedOrderId) && selectedOrders.size > 1) {
            isDraggingSelected = true;
            draggedOrderIds = Array.from(selectedOrders);

            console.log('Групповое перетаскивание активировано для:', draggedOrderIds);

            // Добавляем индикатор множественного перетаскивания
            const indicator = createMultipleDragIndicator(selectedOrders.size);
            e.originalSource.appendChild(indicator);

            // Делаем все выбранные элементы полупрозрачными во время перетаскивания
            selectedOrders.forEach(orderId => {
                const element = document.getElementById(orderId);
                if (element && element !== e.originalSource) {
                    element.style.opacity = '0.5';
                    element.style.transition = 'opacity 0.2s ease';
                }
            });
        } else {
            isDraggingSelected = false;
            draggedOrderIds = [draggedOrderId];
            console.log('Одиночное перетаскивание для:', draggedOrderId);
        }
    });


    // Handle drag over event -- more info: https://shopify.github.io/draggable/docs/class/src/Draggable/DragEvent/DragEvent.js~DragOverEvent.html
    droppable.on("drag:over", (e) =>
    {

        //console.log('drag:over');


        // const isRestricted = e.overContainer.closest('[data-kt-draggable-level="restricted"]');
        // if (isRestricted) {
        //     if (droppableOrigin !== "admin") {
        //         restrcitedWrapper.classList.add("bg-light-danger");
        //     } else {
        //         restrcitedWrapper.classList.remove("bg-light-danger");
        //     }
        // } else {
        //     restrcitedWrapper.classList.remove("bg-light-danger");
        // }

        //console.log('drag:over');
        //droppableLevel = e.overContainer.getAttribute("data-status")

        // console.log(droppableLevel);

    });

    // Handle drag stop event -- more info: https://shopify.github.io/draggable/docs/class/src/Draggable/DragEvent/DragEvent.js~DragStopEvent.html
    droppable.on("drag:stop", async (e) =>
    {
        // Удаляем индикатор множественного перетаскивания
        const indicator = e.originalSource.querySelector('.multiple-drag-indicator');
        if (indicator) {
            indicator.remove();
        }

        // Возвращаем нормальную прозрачность всем элементам
        if (isDraggingSelected) {
            selectedOrders.forEach(orderId => {
                const element = document.getElementById(orderId);
                if (element) {
                    element.style.opacity = '';
                    element.style.transition = '';
                }
            });
        }

        // удалить весь перетаскиваемый занятый предел
        containers.forEach(c =>
        {
            c.classList.remove("draggable-dropzone--occupied");
        });

        document.body.style.overflow = 'auto';

        let level = e.sourceContainer.getAttribute("data-status");

        if(e.sourceContainer.getAttribute("data-status") !== droppableLevel && droppableRestrict !== 'restricted')
        {
            // Универсальная логика для одиночного и группового перетаскивания
            let ordersToProcess = [];

            if (isDraggingSelected && draggedOrderIds.length > 1) {
                // Групповое перетаскивание
                ordersToProcess = draggedOrderIds;
                console.log(`Групповое перетаскивание ${ordersToProcess.length} заказов:`, ordersToProcess);
            } else {
                // Одиночное перетаскивание
                ordersToProcess = [e.originalSource.id];
                console.log('Одиночное перетаскивание заказа:', ordersToProcess[0]);
            }

            console.log(`Из статуса ${level} в статус ${droppableLevel}`);

            /** Включаем preload */
            modal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="d-flex justify-content-center w-100"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div></div>';
            modal_bootstrap.show();

            // Единый запрос для всех заказов
            try {
                //const requestData = {
                //    status_form: {
                //        orders: ordersToProcess,
                //    }
                //};

                let formData = new FormData();

                ordersToProcess.forEach((id, index) => {
                    formData.append(`${droppableLevel}_orders_form[orders][${index}][id]`, id)
                });

                const response = await fetch('/admin/order/' + droppableLevel, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (response.status === 302 || response.status === 404) {
                    // Отправляем уведомления для всех заказов
                    //await Promise.all(ordersToProcess.map(orderId =>
                    //    fetch('/admin/order/status/' + orderId, {
                    //        headers: {'X-Requested-With': 'XMLHttpRequest'}
                    //    })
                    //));

                    formData = new FormData();

                    ordersToProcess.forEach((id, index) => {
                        formData.append(`status_form[orders][${index}][id]`, id)
                    });

                    await fetch('/admin/order/status/' + droppableLevel, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    // Очищаем список выбранных заказов и закрываем модал
                    selectedOrders.clear();
                    updateSelectedOrdersVisuals();
                    modal_bootstrap.hide();
                    return;
                }

                if (response.status === 200) {
                    const result = await response.text();

                    // Очищаем список выбранных заказов
                    selectedOrders.clear();
                    updateSelectedOrdersVisuals();

                    //if (result.requiresForm && ordersToProcess.length === 1) {
                        // Только один заказ требует форму - показываем её
                        modal.innerHTML = result;

                        /** Инициируем LAZYLOAD */
                        let lazy = document.createElement('script');
                        lazy.src = '/assets/js/lazyload.min.js?v={{ version }}';
                        document.head.appendChild(lazy);

                        //modal.querySelectorAll('form').forEach(function(forms) {
                        //    forms.addEventListener('submit', function(event) {
                        //        event.preventDefault();
                        //        submitModalForm(forms);
                        //        return false;
                        //    });
                        //});

                    //} else {
                    //    // Обрабатываем успешные обновления
                    //    if (result.success && result.success.length > 0) {
                    //        result.success.forEach(orderId => {
                    //            const orderElement = document.getElementById(orderId);
                    //            if (orderElement) {
                    //                const targetZone = document.querySelector(`[data-status="${droppableLevel}"]`);
                    //                if (targetZone) {
                    //                    orderElement.remove();
                    //                    targetZone.appendChild(orderElement);
                    //
                    //                    // Снимаем выделение
                    //                    const checkbox = orderElement.querySelector('input[type="checkbox"]');
                    //                    if (checkbox) {
                    //                        checkbox.checked = false;
                    //                    }
                    //                    orderElement.classList.remove('selected-order');
                    //                }
                    //            }
                    //        });
                    //    }
                    //
                    //    // Закрываем модальное окно и показываем результаты
                    //    modal_bootstrap.hide();
                    //
                    //    let successCount = result.success ? result.success.length : 0;
                    //    let errorCount = result.errors ? result.errors.length : 0;
                    //    let requiresFormCount = result.requiresForm ? (result.requiresFormOrders ? result.requiresFormOrders.length : 0) : 0;
                    //
                    //    if (successCount > 0) {
                    //        let message;
                    //        if (ordersToProcess.length === 1) {
                    //            message = 'Статус заказа успешно изменен!';
                    //        } else {
                    //            message = `Успешно обновлено ${successCount} из ${ordersToProcess.length} заказов`;
                    //            if (errorCount > 0) {
                    //                message += `. ${errorCount} заказов не удалось обновить`;
                    //            }
                    //            if (requiresFormCount > 0) {
                    //                message += `. ${requiresFormCount} заказов требуют дополнительной формы`;
                    //            }
                    //        }
                    //
                    //        let toastType = 'success';
                    //        if (errorCount > 0 || requiresFormCount > 0) {
                    //            toastType = 'warning';
                    //        }
                    //
                    //        let $successOrderToast = '{ "type":"' + toastType + '" , ' +
                    //            '"header":"' + (ordersToProcess.length === 1 ? 'Статус изменен' : 'Групповое обновление') + '"  , ' +
                    //            '"message" : "' + message + '" }';
                    //        createToast(JSON.parse($successOrderToast));
                    //
                    //    } else {
                    //        let message = ordersToProcess.length === 1 ?
                    //            'Не удалось обновить статус заказа!' :
                    //            'Не удалось обновить ни одного заказа!';
                    //
                    //        let $dangerOrderToast = '{ "type":"danger" , ' +
                    //            '"header":"Ошибка"  , ' +
                    //            '"message" : "' + message + '" }';
                    //        createToast(JSON.parse($dangerOrderToast));
                    //    }
                    //}
                } else {
                    throw new Error(`Unexpected status code ${response.status}`);
                }

            } catch (error) {
                modal_bootstrap.hide();
                selectedOrders.clear();
                updateSelectedOrdersVisuals();
                console.error('Ошибка обновления:', error);

                let $dangerOrderToast = '{ "type":"danger" , ' +
                    '"header":"Ошибка сети"  , ' +
                    '"message" : "Ошибка при отправке запроса на сервер!" }';
                createToast(JSON.parse($dangerOrderToast));
            }
        }

        // Сбрасываем флаги
        isDraggingSelected = false;
        draggedOrderIds = [];
    });


    // Handle drop event -- https://shopify.github.io/draggable/docs/class/src/Droppable/DroppableEvent/DroppableEvent.js~DroppableDroppedEvent.html
    droppable.on("droppable:dropped", (e) =>
    {

        toDroppable = e.dropzone;

        droppableLevel = e.dropzone.getAttribute("data-status");
        droppableRestrict = e.dropzone.getAttribute("data-level");

        if(droppableRestrict === 'restricted')
        {
            e.cancel();
        }
    });

    return true;
});
