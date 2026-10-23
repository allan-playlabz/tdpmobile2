<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PasswordResetsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PasswordResetsTable Test Case
 */
class PasswordResetsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PasswordResetsTable
     */
    public $PasswordResets;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.PasswordResets'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('PasswordResets') ? [] : ['className' => PasswordResetsTable::class];
        $this->PasswordResets = TableRegistry::getTableLocator()->get('PasswordResets', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->PasswordResets);

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
