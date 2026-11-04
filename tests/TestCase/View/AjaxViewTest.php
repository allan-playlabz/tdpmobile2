<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\View\AjaxView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class AjaxViewTest extends TestCase
{
    public function testUsesAjaxLayoutAndContentType(): void
    {
        $view = new AjaxView(new ServerRequest(), new Response());

        $this->assertSame('ajax', $view->getLayout());
        $this->assertStringStartsWith('text/html', $view->getResponse()->getHeaderLine('Content-Type'));
    }
}
