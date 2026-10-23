<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SettlementAgentsFixture
 */
class SettlementAgentsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'attraction_id' => ['type' => 'binaryuuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'ticket_id' => ['type' => 'binaryuuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'settlement_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'agent_id' => ['type' => 'binaryuuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
            'settlement_idx' => ['type' => 'index', 'columns' => ['settlement_id'], 'length' => []],
            'agent_idx' => ['type' => 'index', 'columns' => ['agent_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci'
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
                'id' => 1,
                'attraction_id' => '',
                'ticket_id' => '',
                'settlement_id' => 1,
                'agent_id' => '',
                'created' => '2019-11-26 07:19:13',
                'modified' => '2019-11-26 07:19:13'
            ],
        ];
        parent::init();
    }
}
