<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CreditLogsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CreditLogsTable Test Case
 */
class CreditLogsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CreditLogsTable
     */
    public $CreditLogs;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.CreditLogs',
        'app.Agents',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('CreditLogs') ? [] : ['className' => CreditLogsTable::class];
        $this->CreditLogs = TableRegistry::getTableLocator()->get('CreditLogs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->CreditLogs);

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
