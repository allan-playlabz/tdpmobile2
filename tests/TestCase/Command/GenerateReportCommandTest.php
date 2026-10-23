<?php
namespace App\Test\TestCase\Command;

use App\Command\GenerateReportCommand;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\GenerateReportCommand Test Case
 *
 */
class GenerateReportCommandTest extends TestCase
{
    use \Cake\Console\TestSuite\ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }
    /**
     * Test buildOptionParser method
     *
     * @return void
     */
    public function testBuildOptionParser()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
