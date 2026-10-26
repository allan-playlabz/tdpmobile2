<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * LogsFixture (schema from tests/schema/app.sql; rows are created by the tests)
 */
class LogsFixture extends TestFixture
{
    /**
     * @return void
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
