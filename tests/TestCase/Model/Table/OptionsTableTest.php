<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OptionsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;

class OptionsTableTest extends TestCase
{
    use AppDataTrait;

    protected array $fixtures = ['app.Options', 'app.Agents'];

    private OptionsTable $Options;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Options = $this->getTableLocator()->get('Options');
    }

    public function testGetValueByName(): void
    {
        $this->insertRow('options', ['name' => 'site_notice', 'value' => 'Maintenance tonight']);
        $this->insertRow('options', ['name' => 'empty_value', 'value' => null]);

        $this->assertSame('Maintenance tonight', $this->Options->getValueByName('site_notice'));
        $this->assertSame('Maintenance tonight', $this->Options->getValueByName('site_notice', 'fallback'));
        // An existing option wins over the default, even when its value is empty.
        $this->assertNull($this->Options->getValueByName('empty_value', 'fallback'));
        $this->assertNull($this->Options->getValueByName('missing'));
        $this->assertSame('fallback', $this->Options->getValueByName('missing', 'fallback'));
    }
}
