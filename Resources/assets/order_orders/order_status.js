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

order_form_status = document.getElementById('order_form_status');

if(order_form_status)
{
    order_form_status.addEventListener('change', function(event)
    {
        changeObjectOrderStatus(this.dataset.order, this.value);

        let btn = document.getElementById('order_form_order');
        btn.setAttribute('disabled', 'disabled');

    });

}

/* вешаем события на модальные ссылки */
document.querySelectorAll('.modal-link')
    .forEach(function(item, i, arr)
    {
        modalLink(item);
    });


function changeObjectOrderStatus(id, status)
{

    fetch('/admin/order/' + status + '/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(function(response)
        {
            return response.status === 200 ? response.text() : false;
        })
        .then(function(html)
        {

            /** Если писутствует прелоад-форма статуса - показываем в модальном окне  */
            if(html)
            {
                const modal = document.getElementById('modal');
                document.getElementById('modal').innerHTML = html;
                new bootstrap.Modal(modal).show();

                /** Инициируем LAZYLOAD  */
                modal.addEventListener('shown.bs.modal', function(event)
                {

                    let lazy = document.createElement('script');
                    lazy.src = '/assets/js/lazyload.min.js?v={{ version }}';
                    document.head.appendChild(lazy);

                });

                modal.querySelectorAll('form').forEach(function(forms)
                {

                    /* событие отправки формы */
                    forms.addEventListener('submit', function(event)
                    {
                        event.preventDefault();
                        submitModalForm(forms);
                        return false;
                    });
                });

                return;
            }

            submitLink('/admin/order/status/' + status + '/' + id);

            // if (order_form_status)
            // {
            //     location.reload();
            // }


        }).catch(function(err)
    {
        // There was an error
        console.warn('Something went wrong.', err);
    });
}


function resolve()
{

    location.reload();
}