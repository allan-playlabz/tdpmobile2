<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AgentTopupsFixture
 */
class AgentTopupsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'binaryuuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'agent_id' => ['type' => 'binaryuuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'amount' => ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'image' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'topup_date' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'approved_by' => ['type' => 'binaryuuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'status' => ['type' => 'string', 'length' => null, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
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
                'id' => '',
                'agent_id' => '',
                'amount' => 1,
                'image' => 'Lorem ipsum dolor sit amet',
                'topup_date' => '2019-11-28',
                'approved_by' => '',
                'status' => 'Lorem ipsum dolor sit amet',
                'created' => '2019-11-28 01:23:45',
                'modified' => '2019-11-28 01:23:45'
            ],
        ];
        parent::init();
    }
}
