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

executeFunc(share);

function share()
{
    const shareButton = document.getElementById("shareButton");

    if(!shareButton)
    {
        console.error("Кнопка не найдена!");
        return;
    }

    if(navigator.share)
    {
        shareButton.addEventListener("click", function()
        {
            navigator.share({
                title : document.title,
                text : "Посмотри это!",
                url : window.location.href.split('?')[0] + '?share=' + this.getAttribute('data-shared'),
            }).catch(error => console.error("Ошибка: ", error));
        });

    }
    else
    {
        shareButton.addEventListener("click", function()
        {
            const url = window.location.href.split('?')[0] + '?share=' + this.getAttribute('data-shared');
            navigator.clipboard.writeText(url).then(() =>
            {

                let toastMessage = JSON.stringify({
                    type : "success",
                    header : "Копирование",
                    message : "Ссылка успешно скопирована в буфер обмена",
                });

                createToast(JSON.parse(toastMessage));
            }).catch(error => console.error("Ошибка при копировании: ", error));
        });
    }

    return true;
 
}