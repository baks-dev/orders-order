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

executeFunc(function initBasketService()
{
    if(typeof MCDatepicker === 'object')
    {
        baksetinitDatapickerOnLoad()
    }
});

/** Валидация пустых значений даты и периода при нажатии "Оформить заказ" */
const order_form_button = document.getElementById('order_form_order');

order_form_button.addEventListener('click', function(event)
{

    /* Получить все услуги */
    const services = document.querySelectorAll('.services .service-item');

    let count_invalid = 0;
    /** Валидация дат, добавление/удаление класса invalid-feedback в зависимости от того отмечен ли чекбокс */
    for(let index = 0; index < services.length; index++)
    {
        let service_date = document.getElementById('order_form_serv_' + index + '_date');

        if(service_date.hasAttribute('required') && service_date.value.length === 0)
        {
            service_date.classList.add('is-invalid');

        }
        else
        {
            service_date.classList.remove('is-invalid');
        }

        if(count_invalid)
        {
            event.preventDefault();
            return false;
        }
    }

    /** Валидация периода, добавление/удаление класса invalid-feedback в зависимости от того отмечен ли чекбокс */
    for(let index = 0; index < services.length; index++)
    {
        let service_period = document.getElementById('order_form_serv_' + index + '_period');

        if(service_period.hasAttribute('required') && service_period.value.length === 0)
        {
            service_period.classList.add('is-invalid');
        } else
        {
            service_period.classList.remove('is-invalid');
        }

        if(count_invalid)
        {
            event.preventDefault();
            return false;
        }
    }

})


/** Обработчик чекбоксов - выбор услуг */
const services_checkboxes = document.querySelectorAll('.service_select')

services_checkboxes.forEach(function(services_checkbox)
{
    services_checkbox.addEventListener('click', function()
    {
        /** Если чекбокс услеги не отмечен */
        if(this.checked === false)
        {

            /* Скрыть коллекцию */
            document.getElementById('order_form_service_' + this.dataset.index).classList.add('d-none')

            /* Сделать необязательным дату */
            document.getElementById('order_form_serv_' + this.dataset.index + '_date').required = false;

            /* Скрыть услугу в блоке Итого */
            const service_summary = document.getElementById('service_summary-' + this.dataset.index);
            if(service_summary)
            {
                service_summary.remove();
            }

            /* Найти period select по id и сбросить его значение */
            let period_select = document.getElementById('order_form_serv_' + this.dataset.index + '_period')

            if(period_select !== 'undefined')
            {
                period_select.selectedIndex = 0;
            }

            /* Сделать необязательным период */
            document.getElementById('order_form_serv_' + this.dataset.index + '_period').required = false;

        } else
        {

            /* Показать коллекцию */
            document.getElementById('order_form_service_' + this.dataset.index).classList.remove('d-none')

            /* Сделать обязательным дату */
            document.getElementById('order_form_serv_' + this.dataset.index + '_date').required = true;

            /* Сделать обязательным период */
            document.getElementById('order_form_serv_' + this.dataset.index + '_period').required = true;

        }
    })
});

/** Инициализация datapicker */
function baksetinitDatapickerOnLoad()
{

    const services = document.querySelectorAll('.services .service-item');

    for(let index = 0; index < services.length; index++)
    {

        const id = 'order_form_serv_' + index + '_date'

        const datepicker = MCDatepicker.create({
            el: '#' + id,
            minDate: new Date(),
            bodyType: 'modal',
            autoClose: false,
            closeOndblclick: true,
            closeOnBlur: false,
            customOkBTN: 'OK',
            customClearBTN: datapickerLang[$locale].customClearBTN,
            customCancelBTN: datapickerLang[$locale].customCancelBTN,
            firstWeekday: datapickerLang[$locale].firstWeekday,
            dateFormat: 'DD.MM.YYYY',
            customWeekDays: datapickerLang[$locale].customWeekDays,
            customMonths: datapickerLang[$locale].customMonths,
        });


        /* Обработчик события выбора даты */
        datepicker.onSelect(function(date, formatedDate)
        {

            const order_form = document.forms.order_form

            /* Получить периоды по выбранной дате */
            changeBasketServicePeriod(order_form, index, formatedDate);

            document.getElementById('order_form_serv_' + index + '_date').classList.remove('is-invalid');
        });
    }


}

/** Изменить период в зав-ти от даты */
async function changeBasketServicePeriod(form, index = 0, formatedDate)
{
    const data = new FormData(form);

    /* Удаляем токен из формы */
    data.delete(form.name + '[_token]');

    /* Удаляем поле, которое каждый раз подгружается динамически */
    data.delete(form.name + '[order_form_serv_0_period]');

    await fetch(form.action, {
        method: form.method,
        cache: 'no-cache',
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
        body: data,
    })

        .then((response) =>
        {
            if(response.status !== 200)
            {
                return false;
            }

            return response.text();
        })
        .then((data) =>
        {
            if(data)
            {
                const result = parseFormData(data);

                /** Заменить период значением из result */
                const period_id = 'order_form_serv_' + index + '_period';
                document.getElementById(period_id).replaceWith(result.getElementById(period_id))


                /** Получить все периоды */
                const services_periods = document.querySelectorAll('.service-period');

                /* Для каждого периода навестить обработчик для вставки в блок Итого */
                services_periods.forEach(function(item)
                {

                    item.addEventListener('change', function()
                    {

                        const selectedOption = item.options[item.selectedIndex];

                        setServiceSummary(formatedDate, selectedOption.text, index)

                        if(item.value)
                        {
                            document.getElementById('order_form_serv_' + index + '_period').classList.remove('is-invalid');
                        }
                    })

                })

            }
        })

}

/** При выборе периода указать услугу в блоке Итого */
function setServiceSummary(date, period_value, index)
{

    /* Сформировать значение по услуге для вывода в блоке Итого */
    let service_summary = document.getElementById('service_summary-' + index)

    let service_price = document.getElementById('order_form_serv_' + index + '_money')
    let service_name = document.getElementById('order_form_serv_' + index + '_name')

    if(service_summary)
    {
        service_summary.remove()
    }

    if(!service_summary)
    {
        service_summary = document.createElement('div');
        service_summary.setAttribute('id', 'service_summary-' + index)
        service_summary.classList.add('mb-2');
    }

    //service_summary.textContent = period.dataset.name + '(от ' +  service_price.value +   ')' + ": " + date + " на " + period_value
    service_summary.textContent = service_name.value + ' (от ' + service_price.value + ' р)';

    const total_result_submit = document.querySelector('#selected-services');

    const service_selected = document.getElementById('order_form_serv_' + index + '_selected')
    const service_period = document.getElementById('order_form_serv_' + index + '_period')


    if(service_selected.checked === true && service_period.value)
    {
        /* Поместить новый элемент выше "Оформить заказ" */
        total_result_submit.after(service_summary);
    }

}

function parseFormData(data)
{
    const parser = new DOMParser();
    return parser.parseFromString(data, 'text/html');
}