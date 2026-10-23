<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersIpaddressTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersIpaddressTable Test Case
 */
class UsersIpaddressTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersIpaddressTable
     */
    public $UsersIpaddress;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.UsersIpaddress',
        'app.Users'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('UsersIpaddress') ? [] : ['className' => UsersIpaddressTable::class];
        $this->UsersIpaddress = TableRegistry::getTableLocator()->get('UsersIpaddress', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->UsersIpaddress);

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
