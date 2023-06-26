/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

/** Обновляем offcanvas событиями orders_basket */
var myOffcanvas = document.getElementById('offcanvas');
myOffcanvas.addEventListener('show.bs.offcanvas', function (event) {
    var head= document.getElementsByTagName('head')[0];

    var ordersScript = document.createElement('script');
    ordersScript.src= '/assets/order_orders/orders_basket.min.js';
    head.appendChild(ordersScript);

});




function getToken(url, ctx) {
    return new Promise((resolve, reject) => {
        fetch(url, {
            method: 'POST',
            headers: new Headers({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(ctx)
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`Unexpected status code ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                resolve(data.token);
            })
            .catch(err => {
                reject(err);
            });
    });
}




setTimeout(function P8X1I2diQ4() {

    if(typeof Droppable === 'object')
    {

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

// Handle drag start event -- more info: https://shopify.github.io/draggable/docs/class/src/Draggable/DragEvent/DragEvent.js~DragEvent.html
        droppable.on("drag:start", (e) => {
            //droppableOrigin = e.originalSource.getAttribute("data-kt-draggable-level");
            //console.log('drag:start');
            document.body.style.overflow = 'hidden';
        });




// Handle drag over event -- more info: https://shopify.github.io/draggable/docs/class/src/Draggable/DragEvent/DragEvent.js~DragOverEvent.html
        droppable.on("drag:over", (e) => {

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



        droppable.on("drag:stop", async (e) => {

            // удалить весь перетаскиваемый занятый предел
            containers.forEach(c => {
                c.classList.remove("draggable-dropzone--occupied");
            });

            // Удалить оповещение с ограниченным доступом
            //restrcitedWrapper.classList.remove("bg-light-danger");
            //console.log(e);

            document.body.style.overflow = 'auto';

            let level = e.sourceContainer.getAttribute("data-status");
            let id = e.originalSource.id; // getAttribute("data-order");

            if (e.sourceContainer.getAttribute("data-status") !== droppableLevel && droppableRestrict !== 'restricted')
            {

                //console.log(toDroppable.getAttribute("data-status"));

                //fetch('https://bundles.baks.dev/admin/payment/delete/01871a93-9e6f-72f0-a853-7279d5c954654')
                //fetch('https://bundles.baks.dev/admin/payment/delete/01871ddd-d8ae-7fb4-9c38-9e6be9803198')
                fetch('/admin/order/'+droppableLevel+'/'+id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (response) {
                        return response.status === 200 ? response.text() : false;
                    })
                    .then(function (html) {

                        /** Если писутствует прелоад-форма статуса - показываем в модальном окне  */
                        if (html)
                        {
                            const modal = document.getElementById('modal');
                            document.getElementById('modal').innerHTML = html;
                            new bootstrap.Modal(modal).show();

                            /** Инициируем форму упаковки заказа  */
                            modal.addEventListener('shown.bs.modal', function (event){
                                if (typeof initOrderPackage  == "function")
                                {
                                    initOrderPackage();
                                }
                            });

                            modal.querySelectorAll('form').forEach(function (forms) {

                                /* событие отправки формы */
                                forms.addEventListener('submit', function (event) {
                                    event.preventDefault();
                                    submitModalForm(forms);
                                    return false;
                                });
                            });

                            return;
                        }

                        submitLink( '/admin/order/status/'+droppableLevel+'/'+id)

                    }).catch(function (err) {
                    // There was an error
                    console.warn('Something went wrong.', err);
                });






                // https://bundles.baks.dev/login

                ///let response = await fetch('https://bundles.baks.dev/admin/payment/delete/01871a93-9e6f-72f0-a853-7279d5c90ea9');





// if (response.ok) {
//
//
//     const modal = document.getElementById('modal');
//     document.getElementById('modal').innerHTML = '4564'
//     //var myModal = new bootstrap.Modal(modal);
//     //myModal.toggle();
//
//
//
//
//
//     //console.log(response.text());
//
//     // если HTTP-статус в диапазоне 200-299
//   // получаем тело ответа (см. про этот метод ниже)
//   //let json = await response.json();
// } else {
//   alert("Ошибка HTTP: " + response.status);
// }


                // const modal = new bootstrap.Modal('#modal', {
                //   keyboard: false
                // })
                //
                // modal.show();



                console.log(level+' => '+droppableLevel);
                //console.log(id);

                /** Отправляем запрос на изменение статуса */
                //submitLink( '/admin/order/status/'+droppableLevel+'/'+id)

            }

        });






// Handle drop event -- https://shopify.github.io/draggable/docs/class/src/Droppable/DroppableEvent/DroppableEvent.js~DroppableDroppedEvent.html

        droppable.on("droppable:dropped", (e) => {

            toDroppable = e.dropzone;


            //const isRestricted = e.dropzone.closest('[data-draggable-level="restricted"]');

            //const isRestricted = e.dropzone.closest('[data-kt-draggable-level="restricted"]');
            // Detect if drop container is restricted
            // if (isRestricted) {
            //     // Check if dragged element has permission level
            //     if (droppableOrigin !== "admin") {
            //         restrcitedWrapper.classList.add("bg-light-danger");
            //         e.cancel();
            //     }
            // }

            //e.cancel();

            //console.log(e);

            droppableLevel =  e.dropzone.getAttribute("data-status");
            droppableRestrict =  e.dropzone.getAttribute("data-level");

            if (droppableRestrict === 'restricted')
            {
                e.cancel();
            }

        });


        return;
    }

    setTimeout(P8X1I2diQ4, 100);

}, 100);

