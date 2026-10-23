<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * GeneratedCodesFixture
 */
class GeneratedCodesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'binaryuuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'code' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'ticket_id' => ['type' => 'binaryuuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'status' => ['type' => 'string', 'length' => null, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'redeemable' => ['type' => 'tinyinteger', 'length' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'redeemed' => ['type' => 'tinyinteger', 'length' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
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
                'code' => 'Lorem ipsum dolor sit amet',
                'ticket_id' => '',
                'status' => 'Lorem ipsum dolor sit amet',
                'redeemable' => 1,
                'redeemed' => 1,
                'created' => '2019-11-22 13:40:27',
                'modified' => '2019-11-22 13:40:27'
            ],
        ];
        parent::init();
    }
}
