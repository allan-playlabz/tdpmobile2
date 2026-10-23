<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ApiPdfsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ApiPdfsTable Test Case
 */
class ApiPdfsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ApiPdfsTable
     */
    public $ApiPdfs;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.ApiPdfs',
        'app.PurchasedTicketSkus',
        'app.TicketSkus'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('ApiPdfs') ? [] : ['className' => ApiPdfsTable::class];
        $this->ApiPdfs = TableRegistry::getTableLocator()->get('ApiPdfs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->ApiPdfs);

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
