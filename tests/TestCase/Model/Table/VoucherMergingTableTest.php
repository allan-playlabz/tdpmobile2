<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\VoucherMergingTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;

class VoucherMergingTableTest extends TestCase
{
    use AppDataTrait;

    protected array $fixtures = ['app.VoucherMerging', 'app.Agents'];

    private VoucherMergingTable $VoucherMerging;

    protected function setUp(): void
    {
        parent::setUp();
        $this->VoucherMerging = $this->getTableLocator()->get('VoucherMerging');
    }

    public function testIsJobExists(): void
    {
        $this->insertRow('voucher_merging', ['agent_id' => self::AGENT_A, 'order_ref_id' => 'REF1', 'type' => 'zip', 'status' => 'pending']);

        $this->assertTrue($this->VoucherMerging->isJobExists('REF1', self::AGENT_A));
        $this->assertFalse($this->VoucherMerging->isJobExists('REF1', self::AGENT_B));
        $this->assertFalse($this->VoucherMerging->isJobExists('REF2', self::AGENT_A));
    }
}
