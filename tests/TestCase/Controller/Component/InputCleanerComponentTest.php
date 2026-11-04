<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\InputCleanerComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class InputCleanerComponentTest extends TestCase
{
    private function clean(array $data, array $config = [], string $action = 'checkout'): array
    {
        $request = new ServerRequest(['post' => $data, 'params' => ['action' => $action]]);
        $controller = new Controller($request);
        $component = new InputCleanerComponent(new ComponentRegistry($controller), $config);
        $component->startup(new Event('Controller.startup', $controller));

        return $controller->getRequest()->getData();
    }

    public function testTrimsAndStripsTagsRecursively(): void
    {
        $data = $this->clean([
            'name' => '  <b>Jane</b> ',
            'guests' => [['note' => ' <script>x</script>hi ']],
            'qty' => 3,
        ]);

        $this->assertSame(['name' => 'Jane', 'guests' => [['note' => 'xhi']], 'qty' => 3], $data);
    }

    public function testIgnoredFieldsAreTrimmedButKeepTags(): void
    {
        $data = $this->clean(['html' => ' <p>Hi</p> ', 'name' => '<i>Jo</i>'], ['ignore_fields' => ['html']]);

        $this->assertSame(['html' => '<p>Hi</p>', 'name' => 'Jo'], $data);
    }

    public function testIgnoredActionsAreUntouched(): void
    {
        $data = $this->clean(['name' => ' <b>Jane</b> '], ['ignore_actions' => ['checkout']]);

        $this->assertSame(['name' => ' <b>Jane</b> '], $data);
    }

    public function testEmptyDataIsANoOp(): void
    {
        $this->assertSame([], $this->clean([]));
    }
}
