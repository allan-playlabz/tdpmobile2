<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StripeRecordsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\StripeRecordsTable Test Case
 */
class StripeRecordsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\StripeRecordsTable
     */
    public $StripeRecords;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.StripeRecords',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('StripeRecords') ? [] : ['className' => StripeRecordsTable::class];
        $this->StripeRecords = TableRegistry::getTableLocator()->get('StripeRecords', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->StripeRecords);

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
