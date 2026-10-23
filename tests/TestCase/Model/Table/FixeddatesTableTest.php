<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FixeddatesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FixeddatesTable Test Case
 */
class FixeddatesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FixeddatesTable
     */
    public $Fixeddates;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Fixeddates',
        'app.Tickets',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Fixeddates') ? [] : ['className' => FixeddatesTable::class];
        $this->Fixeddates = TableRegistry::getTableLocator()->get('Fixeddates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Fixeddates);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
