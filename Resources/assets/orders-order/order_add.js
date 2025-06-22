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

async function changeObjectCategory(forms)
{
    disabledElementsForm(forms);

    document.getElementById("preProduct")?.classList.add("d-none");
    document.getElementById("preOffer")?.classList.add("d-none");
    document.getElementById("preVariation")?.classList.add("d-none");
    document.getElementById("preModification")?.classList.add("d-none");

    const data = new FormData(forms);
    data.delete(forms.name + "[_token]");

    await fetch(forms.action, {
        method : forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
        credentials : "same-origin", // include, *same-origin, omit
        headers : {
            "X-Requested-With" : "XMLHttpRequest",
        },
        redirect : "follow", // manual, *follow, error
        referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body : data, // body data type must match "Content-Type" header
    }).then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        }).then((data) =>
        {

            if(data)
            {

                const parser = new DOMParser();
                const result = parser.parseFromString(data, "text/html");

                let preProduct = result.getElementById("preProduct");

                document.getElementById("preProduct").replaceWith(preProduct);

                preProduct ?
                    document?.getElementById("product")?.replaceWith(preProduct) :
                    preProduct.innerHTML = "";

                /** SELECT2 */
                let replacer = document.getElementById(forms.name + "_preProduct_preProduct");
                replacer && replacer.type !== "hidden" ? preProduct.classList.remove("d-none") : null;

                /** Событие на изменение модификации */
                if(replacer)
                {

                    if(replacer.tagName === "SELECT")
                    {
                        new NiceSelect(replacer, {searchable : true});

                        let focus = document.getElementById(forms.name + "_preProduct_preProduct_select2");
                        focus ? focus.click() : null;
                    }
                }

                /** сбрасываем зависимые поля */
                let preOffer = document.getElementById("preOffer");
                preOffer ? preOffer.innerHTML = "" : null;

                /** сбрасываем зависимые поля */
                let preVariation = document.getElementById("preVariation");
                preVariation ? preVariation.innerHTML = "" : null;

                let preModification = document.getElementById("preModification");
                preModification ? preModification.innerHTML = "" : null;


                if(replacer)
                {

                    replacer.addEventListener("change", function()
                    {
                        changeObjectProduct(forms);
                        return false;
                    });
                }
            }

            enableElementsForm(forms);
        });
}


async function changeObjectProduct(forms)
{
    disabledElementsForm(forms);

    document.getElementById("preOffer")?.classList.add("d-none");
    document.getElementById("preVariation")?.classList.add("d-none");
    document.getElementById("preModification")?.classList.add("d-none");

    const data = new FormData(forms);
    data.delete(forms.name + "[_token]");

    await fetch(forms.action, {
        method : forms.method, // *GET, POST, PUT, DELETE, etc.
        cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
        credentials : "same-origin", // include, *same-origin, omit
        headers : {
            "X-Requested-With" : "XMLHttpRequest",
        },

        redirect : "follow", // manual, *follow, error
        referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body : data, // body data type must match "Content-Type" header
    })
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        }).then((data) =>
        {

            if(data)
            {

                const parser = new DOMParser();
                const result = parser.parseFromString(data, "text/html");


                let preOffer = result.getElementById("preOffer");
                preOffer ? document.getElementById("preOffer").replaceWith(preOffer) : preOffer.innerHTML = "";

                if(preOffer)
                {

                    /** SELECT2 */

                    let replaceOfferId = forms.name + "_preProduct_preOffer";

                    let replacer = document.getElementById(replaceOfferId);
                    replacer && replacer.type !== "hidden" ? preOffer.classList.remove("d-none") : null;

                    if(replacer.tagName === "SELECT")
                    {
                        new NiceSelect(replacer, {searchable : true});

                        let focus = document.getElementById(forms.name + "_preProduct_preOffer_select2");
                        focus ? focus.click() : null;

                    }
                    else
                    {
                        selectTotal(document.getElementById(forms.name + "_preProduct_preProduct"));
                    }

                }


                /** сбрасываем зависимые поля */
                let preVariation = document.getElementById("preVariation");
                preVariation ? preVariation.innerHTML = "" : null;

                let preModification = document.getElementById("preModification");
                preModification ? preModification.innerHTML = "" : null;


                /** Событие на изменение торгового предложения */
                let offerChange = document.getElementById(forms.name + "_preProduct_preOffer");

                if(offerChange)
                {

                    offerChange.addEventListener("change", function()
                    {
                        changeObjectOffer(forms);
                        return false;
                    });
                }
            }

            enableElementsForm(forms);
        });
}


async function changeObjectOffer(forms)
{
    disabledElementsForm(forms);

    document.getElementById("preVariation")?.classList.add("d-none");
    document.getElementById("preModification")?.classList.add("d-none");

    const data = new FormData(forms);
    data.delete(forms.name + "[_token]");


    await fetch(forms.action, {
        method : forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
        credentials : "same-origin", // include, *same-origin, omit
        headers : {
            "X-Requested-With" : "XMLHttpRequest",
        },

        redirect : "follow", // manual, *follow, error
        referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body : data, // body data type must match "Content-Type" header
    }).then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        }).then((data) =>
        {

            if(data)
            {

                const parser = new DOMParser();
                const result = parser.parseFromString(data, "text/html");

                let preVariation = result.getElementById("preVariation");

                if(preVariation)
                {

                    document.getElementById("preVariation").replaceWith(preVariation);

                    /** SELECT2 */

                    let replacer = document.getElementById(forms.name + "_preProduct_preVariation");
                    replacer && replacer.type !== "hidden" ? preVariation.classList.remove("d-none") : null;

                    if(replacer)
                    {

                        if(replacer.tagName === "SELECT")
                        {
                            new NiceSelect(replacer, {searchable : true});

                            let focus = document.getElementById(forms.name + "_preProduct_preVariation_select2");
                            focus ? focus.click() : null;

                            replacer.addEventListener("change", function()
                            {
                                changeObjectVariation(forms);
                                return false;
                            });
                        }
                        else
                        {
                            selectTotal(document.getElementById(forms.name + "_preProduct_preOffer"));
                        }

                    }

                }

                let preModification = document.getElementById("preModification");
                preModification ? preModification.innerHTML = "" : null;

            }

            enableElementsForm(forms);
        });
}


async function changeObjectVariation(forms)
{

    disabledElementsForm(forms);

    document.getElementById("preModification")?.classList.add("d-none");

    const data = new FormData(forms);
    data.delete(forms.name + "[_token]");

    await fetch(forms.action, {
        method : forms.method, // *GET, POST, PUT, DELETE, etc.
        cache : "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
        credentials : "same-origin", // include, *same-origin, omit
        headers : {
            "X-Requested-With" : "XMLHttpRequest",
        },
        redirect : "follow", // manual, *follow, error
        referrerPolicy : "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body : data, // body data type must match "Content-Type" header
    }).then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        }).then((data) =>
        {

            if(data)
            {

                const parser = new DOMParser();
                const result = parser.parseFromString(data, "text/html");

                let preModification = result.getElementById("preModification");

                if(preModification)
                {

                    document.getElementById("preModification").replaceWith(preModification);

                    /** SELECT2 */
                    let replacer = document.getElementById(forms.name + "_preProduct_preModification");
                    replacer && replacer.type !== "hidden" ? preModification.classList.remove("d-none") : null;

                    /** Событие на изменение модификации */
                    if(replacer)
                    {

                        if(replacer.tagName === "SELECT")
                        {
                            new NiceSelect(replacer, {searchable : true});

                            let focus = document.getElementById(forms.name + "_preProduct_preModification_select2");
                            focus ? focus.click() : null;

                            replacer.addEventListener("change", function()
                            {
                                selectTotal(this);
                                return false;
                            });

                        }
                        else
                        {
                            selectTotal(document.getElementById(forms.name + "_preProduct_preVariation"));
                        }
                    }
                }
            }

            enableElementsForm(forms);
        });
}


function selectTotal(element)
{
    let index = element.selectedIndex;

    let preProduct_preTotal = document.getElementById("add_to_order_form_preProduct_preTotal");

    preProduct_preTotal.setAttribute("max", element.options[index].dataset.max);
    
    /* Проверить стоимость */
    let $price = element.options[index].getAttribute("data-product-price");
    let $objectSubmit = document.getElementById("add_to_order_form_order_add");

    if($price === '0')
    {
        $objectSubmit.classList.add('disabled');

        /* TOAST */
        let header = "Добавить продукцию в заказ";
        
        let $errorFormHandler = "{ \"type\":\"danger\" , " +
            "\"header\":\"" + header + "\"  , " +
            "\"message\" : \"У данного продукта не указана цена\" }";

        createToast(JSON.parse($errorFormHandler));
    }
    else
    {
        if(true === $objectSubmit.classList.contains('disabled'))
        {
            $objectSubmit.classList.remove('disabled');
        }
    }


    setTimeout(function()
    {
        let focusTotal = document.getElementById("add_to_order_form_preProduct_preTotal");
        focusTotal.value = "";
        focusTotal ? focusTotal.focus() : null;
    }, 0);
}


async function submitAddToOrderForm(forms)
{
    let $errorFormHandler = null;

    let header = "Добавить продукцию в заказ";


    let $preTotal = document.getElementById("add_to_order_form_preProduct_preTotal");
    let $TOTAL = $preTotal.value * 1;

    let $totalMax = $preTotal.getAttribute("max");

    if($TOTAL === undefined || $TOTAL < 1 || $TOTAL > $totalMax)
    {

        if($TOTAL === undefined)
        {
            $errorFormHandler = "{ \"type\":\"danger\" , " +
                "\"header\":\"" + header + "\"  , " +
                "\"message\" : \"Ошибка при заполнение количества\" }";
        }

        if($TOTAL > $totalMax)
        {
            $errorFormHandler = "{ \"type\":\"danger\" , " +
                "\"header\":\"" + header + "\"  , " +
                "\"message\" : \"На складе продукции отстутсвует необходимое количество\" }";
        }

        if($TOTAL < 1)
        {
            $errorFormHandler = "{ \"type\":\"danger\" , " +
                "\"header\":\"" + header + "\"  , " +
                "\"message\" : \"Не указано количество продукции\" }";
        }

    }

    let $category = document.getElementById("add_to_order_form_preProduct_category");

    if($category.value.length === 0)
    {

        $errorFormHandler = "{ \"type\":\"danger\" , " +
            "\"header\":\"" + header + "\"  , " +
            "\"message\" : \"" + $category.options[0].textContent + "\" }";

        createToast(JSON.parse($errorFormHandler));

        return false;
    }

    let $preProduct = document.getElementById("add_to_order_form_preProduct_preProduct");

    if($preProduct && $preProduct.value.length === 0)
    {

        $errorFormHandler = "{ \"type\":\"danger\" , " +
            "\"header\":\"" + header + "\"  , " +
            "\"message\" : \"" + $preProduct.options[0].textContent + "\" }";

    }

    let $preOffer = document.getElementById("add_to_order_form_preProduct_preOffer");

    if($preOffer)
    {
        if($preOffer.tagName === "SELECT" && $preOffer.value.length === 0)
        {

            $errorFormHandler = "{ \"type\":\"danger\" , " +
                "\"header\":\"" + header + "\"  , " +
                "\"message\" : \"" + $preOffer.options[0].textContent + "\" }";
        }
    }

    let $preVariation = document.getElementById("add_to_order_form_preProduct_preVariation");

    if($preVariation)
    {
        if($preVariation.tagName === "SELECT" && $preVariation.value.length === 0)
        {

            $errorFormHandler = "{ \"type\":\"danger\" , " +
                "\"header\":\"" + header + "\"  , " +
                "\"message\" : \"" + $preVariation.options[0].textContent + "\" }";
        }
    }

    let $preModification = document.getElementById("add_to_order_form_preProduct_preModification");

    if($preModification)
    {
        if($preModification.tagName === "SELECT" && $preModification.value.length === 0)
        {

            $errorFormHandler = "{ \"type\":\"danger\" , " +
                "\"header\":\"" + header + "\"  , " +
                "\"message\" : \"" + $preModification.options[0].textContent + "\" }";
        }
    }


    /* Выводим сообщение об ошибке заполнения */

    if($errorFormHandler)
    {
        createToast(JSON.parse($errorFormHandler));
        return false;
    }


    const data = new FormData(forms);

    const elementFormName = "edit_order_form";

    let elementCount = document.querySelectorAll("[id*=\"item_edit_order_form_product\"]").length;
    let prototype = document.getElementById("order-product-list").getAttribute("data-prototype");
    let inputs = document.getElementById(elementFormName + "_product").getAttribute("data-prototype");

    prototype = prototype.replaceAll("__product__", elementCount);
    inputs = inputs.replaceAll("__product__", elementCount);

    let category = data.get(forms.name + "[preProduct][category]");
    let product = data.get(forms.name + "[preProduct][preProduct]");
    let offer = data.get(forms.name + "[preProduct][preOffer]");
    let variation = data.get(forms.name + "[preProduct][preVariation]");
    let modification = data.get(forms.name + "[preProduct][preModification]");
    let $totalAmount = data.get(forms.name + "[preProduct][preTotal]");

    let categoryOption = document.querySelector("#" + forms.name + "_preProduct_category [value=\"" + category + "\"]");
    let productOption = document.querySelector("#" + forms.name + "_preProduct_preProduct [value='" + product + "']");
    let offerOption = offer === null ? null : document.querySelector("#add_to_order_form_preProduct_preOffer [value='" + offer + "']");
    let variationOption = variation === null ? null : document.querySelector("#add_to_order_form_preProduct_preVariation [value='" + variation + "']");
    let modificationOption = modification === null ? null : document.querySelector("#add_to_order_form_preProduct_preModification [value='" + modification + "']");

    let categoryUrl = productOption.getAttribute("data-category-url");
    let productUrl = productOption.getAttribute("data-product-url");
    let productFullUrl = "/catalog/" + categoryUrl + "/" + productUrl;

    let productName = productOption.getAttribute("data-name");
    let productArticle = productOption.getAttribute("data-product-article");
    let productPrice = productOption.getAttribute("data-product-price");
    let productCurrency = productOption.getAttribute("data-product-currency");
    let $totalAmountMax = productOption.getAttribute("data-max");

    let discount = categoryOption.getAttribute("data-discount");

    let offerValue = offerOption === null ? null : offerOption.getAttribute("data-offer-value");
    let offerPostfix = offerOption === null ? null : offerOption.getAttribute("data-offer-postfix");
    if(offerValue !== null)
    {
        let offerReference = offerOption.getAttribute("data-offer-reference");
        productFullUrl += "/" + offerValue;
        productName += "<br><small class='text-muted text-capitalize'>" + offerReference + ":</small> " + offerValue;
        productArticle = offerOption.getAttribute("data-product-article");
        productPrice = offerOption.getAttribute("data-product-price");
        productCurrency = offerOption.getAttribute("data-product-currency");
        $totalAmountMax = offerOption.getAttribute("data-max");
    }

    let variationValue = variationOption === null ? null : variationOption.getAttribute("data-variation-value");
    let variationPostfix = variationOption === null ? null : variationOption.getAttribute("data-variation-postfix");
    if(variationValue !== null)
    {
        let variationReference = variationOption.getAttribute("data-variation-reference");
        productFullUrl += "/" + variationValue;
        productName += "<br><small class='text-muted text-capitalize'>" + variationReference + ":</small> " + variationValue;
        productArticle = variationOption.getAttribute("data-product-article");
        productPrice = variationOption.getAttribute("data-product-price");
        productCurrency = variationOption.getAttribute("data-product-currency");
        $totalAmountMax = variationOption.getAttribute("data-max");
    }

    let modificationValue = modificationOption === null ? null : modificationOption.getAttribute("data-modification-value");
    let modificationPostfix = modificationOption === null ? null : modificationOption.getAttribute("data-modification-postfix");
    if(modificationValue !== null)
    {
        let modificationReference = modificationOption.getAttribute("data-modification-reference");
        productFullUrl += "/" + modificationValue;
        productName += "<br><small class='text-muted text-capitalize'>" + modificationReference + ":</small> " + modificationValue;

        productArticle = modificationOption.getAttribute("data-product-article");
        productPrice = modificationOption.getAttribute("data-product-price");
        productCurrency = modificationOption.getAttribute("data-product-currency");
        $totalAmountMax = modificationOption.getAttribute("data-max");
    }

    let postfix = modificationPostfix === null ? variationPostfix === null ? offerPostfix === null ? null : offerPostfix : variationPostfix : modificationPostfix;
    if(postfix !== null)
    {
        productFullUrl += "/" + postfix;
    }

    prototype = prototype.replaceAll("__product_url__", productFullUrl);

    let productImagePath = productOption.getAttribute("data-image-path");
    let offerImagePath = offerOption === null ? null : offerOption.getAttribute("data-image-path");
    let variationImagePath = variationOption === null ? null : variationOption.getAttribute("data-image-path");
    let modificationImagePath = modificationOption === null ? null : modificationOption.getAttribute("data-image-path");
    let imagePath = productImagePath === null ? modificationImagePath === null ? variationImagePath === null ? offerImagePath === null ? "/assets/img/blank.svg" : offerImagePath : variationImagePath : modificationImagePath : productImagePath;
    imagePath = "url('" + imagePath + "')";

    prototype = prototype.replaceAll("__image_path__", imagePath);

    if(offerPostfix !== null)
    {
        productName += " " + offerPostfix;
    }

    if(variationPostfix !== null)
    {
        productName += " " + variationPostfix;
    }

    if(modificationPostfix !== null)
    {
        productName += " " + modificationPostfix;
    }

    let $productMinPrice = productPrice - (productPrice * discount / 100);

    prototype = prototype.replaceAll("__product_name__", productName);

    prototype = prototype.replaceAll("__product_article__", productArticle);

    prototype = prototype.replaceAll("__product_price__", productPrice === "" ? 1 : productPrice / 100);

    let $totalPrice = productPrice * $totalAmount / 100;
    let $formattedPrice = new Intl.NumberFormat($locale, {
        style : "currency",
        currency : productCurrency === "rur" ? "RUB" : productCurrency,
        maximumFractionDigits : 0,
    }).format($totalPrice);
    prototype = prototype.replaceAll("__product_price_formatted__", $formattedPrice);

    let $minPrice = $productMinPrice === 0 ? 1 : $productMinPrice / 100;
    let $formattedMinPrice = new Intl.NumberFormat($locale, {
        style : "currency",
        currency : productCurrency === "rur" ? "RUB" : productCurrency,
        maximumFractionDigits : 0,
    }).format($minPrice);

    prototype = prototype.replaceAll("__product_min_price__", $minPrice);
    prototype = prototype.replaceAll("__product_min_price_formatted__", $formattedMinPrice);

    prototype = prototype.replaceAll("__product_currency__", productCurrency);

    prototype = prototype.replaceAll("__product_discount__", discount);

    prototype = prototype.replaceAll("__product_total__", $totalAmount);
    prototype = prototype.replaceAll("__product_total_max__", $totalAmountMax);


    // Заново вешаем слушатели событий для работы с кнопками уменьшения/увеличения количества
    document.querySelector("#order-product-list tbody").innerHTML += prototype + inputs;

    /** Заполняем поля во вставленном элементе */
    document.getElementById(elementFormName + "_product_" + elementCount + "_product").setAttribute("value", product);
    document.getElementById(elementFormName + "_product_" + elementCount + "_offer").setAttribute("value", offer);
    document.getElementById(elementFormName + "_product_" + elementCount + "_variation").setAttribute("value", variation);
    document.getElementById(elementFormName + "_product_" + elementCount + "_modification").setAttribute("value", modification);

    total();

    /** Уменьшаем число продукции */
    document.querySelectorAll(".minus").forEach(function(btn)
    {
        btn.addEventListener("click", function()
        {
            let inpt = document.getElementById(this.dataset.id).value;

            let result = parseFloat(inpt.replace(",", "."));
            result = result - (this.dataset.step ? this.dataset.step * 1 : 1);

            if(result <= 0)
            {
                return;
            }

            document.getElementById(this.dataset.id).value = result;

            /** Пересчет Суммы */
            orderSum(result, this.dataset.id);

            /** Персчет всего количество */
            total();

        });
    });


    document.querySelectorAll(".total").forEach(function(input)
    {
        setTimeout(function initCounter()
        {
            if(typeof orderCounter.debounce == "function")
            {
                /** Событие на изменение количество в ручную */
                input.addEventListener("input", orderCounter.debounce(1000));
                return;
            }

            setTimeout(initCounter, 100);

        }, 100);
    });


    /** Событие на изменение стоимости */
    document.querySelectorAll(".price").forEach(function(input)
    {
        setTimeout(function initPrice()
        {
            if(typeof orderCounter.debounce == "function")
            {
                /** Событие на изменение стимости в ручную */
                input.addEventListener("input", orderCounter.debounce(1000));
                return;
            }

            setTimeout(initPrice, 100);

        }, 100);
    });

    /** Увеличиваем число продукции */
    document.querySelectorAll(".plus").forEach(function(btn)
    {
        btn.addEventListener("click", function()
        {
            let inpt = document.getElementById(this.dataset.id);

            let result = parseFloat(inpt.value.replace(",", "."));
            result = result + (this.dataset.step ? this.dataset.step * 1 : 1);

            if(inpt.dataset.max && result > inpt.dataset.max)
            {
                return;
            }

            document.getElementById(this.dataset.id).value = result;

            /** Пересчет Суммы */
            orderSum(result, this.dataset.id);

            /** Персчет всего количество */
            total();
        });
    });

    document.querySelectorAll(".copy").forEach(el =>
    {
        el.addEventListener("click", () =>
        {
            navigator.clipboard.writeText(el.dataset.copy).then(() =>
            {
                let $successSupplyToast = "{ \"type\":\"success\" , " +
                    "\"header\":\"Копирование\"  , " +
                    "\"message\" : \"Результат успешно скопирован в буфер обмена\" }";

                createToast(JSON.parse($successSupplyToast));

                el.classList.add("opacity-25");

                setTimeout(() =>
                {
                    el.classList.remove("opacity-25");
                }, 500);

            }).catch(err =>
            {
                console.log("Something went wrong", err);
            });
        });
    });

    let $deleteButtons = document.querySelectorAll(".delete-product");

    $deleteButtons.forEach(($button) => {
        $button.addEventListener("click", function($e)
            {
                $e.preventDefault();

                let $row = $e.currentTarget.getAttribute("data-row");

                deleteElement($row);
            }
        );
    });

    /* Закрываем модальное окно */
    let myModalEl = document.querySelector("#modal");
    let modal = bootstrap.Modal.getInstance(myModalEl);
    modal.hide();
}


setTimeout(function() {
    const object_submit = document.getElementById("add_to_order_form_order_add");

    if(object_submit)
    {
        object_submit.addEventListener("click", function()
        {
            let form = this.closest("form");

            submitAddToOrderForm(form);
        });
    }

    const object_category = document.getElementById("add_to_order_form_preProduct_category");

    if(object_category)
    {
        object_category.addEventListener("change", function()
        {
            let form = this.closest("form");
            changeObjectCategory(form);
            return false;
        });

        if(object_category.tagName === "SELECT")
        {
            new NiceSelect(object_category, {searchable : true});
        }
    }

    const object_product = document.getElementById("add_to_order_form_preProduct_preProduct");

    if(object_product)
    {
        let focus = document.getElementById("add_to_order_form_preProduct_preProduct_select2");

        focus ? focus.click() : null;

        object_product.addEventListener("change", function()
        {
            let form = this.closest("form");
            changeObjectProduct(form);
            return false;
        });

        if(object_product.tagName === "SELECT")
        {
            new NiceSelect(object_product, {searchable : true});
        }
    }
}, 2)





