<?php
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
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 *
 */

namespace BaksDev\Orders\Order\Twig;

use BaksDev\Contacts\Region\Repository\ContactCallDetail\ContactCallDetailInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class OrderProductsExtension extends AbstractExtension
{
    private ContactCallDetailInterface $callDetail;

    public function __construct(ContactCallDetailInterface $callDetail)
    {
        $this->callDetail = $callDetail;
    }

	public function getFunctions() : array
	{
	    return [
	    	new TwigFunction('order_products', [$this, 'content'], ['needs_environment' => true, 'is_safe' => ['html']]),
	    	new TwigFunction('order_products_render', [$this, 'render'], ['needs_environment' => true, 'is_safe' => ['html']]),
	    	new TwigFunction('order_products_template', [$this, 'template'], ['needs_environment' => true, 'is_safe' => ['html']]),
	    ];
	}

	public function content(Environment $twig, string $value) : string
	{
	    dump('order_products');
	    dd($value);

	    //	    $data = $this->callDetail->fetchContactCallDetailById(new ContactsRegionCallUid($value));
//
	    //	    try
	    //	    {
	    //	        return $twig->render('@Template/ContactsRegion/content.html.twig', ['value' => $data]);
	    //	    } catch(LoaderError $loaderError)
	    //	    {
	    //	        return $twig->render('@ContactsRegion/choice/content.html.twig', ['value' => $data]);
	    //	    }
	}

	public function render(Environment $twig, $value) : string
	{
	    dd('order_products_render');

	    //	    try
	    //	    {
	    //	        return $twig->render('@Template/ContactsRegion/render.html.twig', ['value' => $value]);
	    //	    }
	    //	    catch(LoaderError $loaderError)
	    //	    {
	    //	        return $twig->render('@ContactsRegion/choice/render.html.twig', ['value' => $value]);
	    //	    }
	}

	public function template(Environment $twig, $value) : string
	{
	    dd('order_products_template');

	    //	    try
	    //	    {
	    //	        return $twig->render('@Template/ContactsRegion/template.html.twig', ['value' => $value]);
	    //	    }
	    //	    catch(LoaderError $loaderError)
	    //	    {
	    //	        return $twig->render('@ContactsRegion/choice/template.html.twig', ['value' => $value]);
	    //	    }
	}
}
