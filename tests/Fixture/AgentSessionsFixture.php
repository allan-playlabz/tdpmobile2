<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AgentSessionsFixture
 */
class AgentSessionsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'string', 'length' => 40, 'fixed' => true, 'null' => false, 'default' => null, 'collate' => 'ascii_bin', 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => 'CURRENT_TIMESTAMP', 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => 'CURRENT_TIMESTAMP', 'comment' => '', 'precision' => null],
        'data' => ['type' => 'binary', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'expires' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'agent_user_id' => ['type' => 'binaryuuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
            'agent_user_idx' => ['type' => 'index', 'columns' => ['agent_user_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd
    /**
     * Init method
     *
     * @return void
     */
    public function init()
    {
        $this->records = [
            [
                'id' => '32863ff5-7244-42cf-ad3a-5be03a93216e',
                'created' => '2020-07-23 01:47:44',
                'modified' => '2020-07-23 01:47:44',
                'data' => 'Lorem ipsum dolor sit amet',
                'expires' => 1,
                'agent_user_id' => ''
            ],
        ];
        parent::init();
    }
}
