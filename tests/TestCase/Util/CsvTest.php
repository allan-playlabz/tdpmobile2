<?php
declare(strict_types=1);

namespace App\Test\TestCase\Util;

use App\Util\Csv;
use Cake\TestSuite\TestCase;

class CsvTest extends TestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = 'csvtest-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $dir = TMP . 'reports' . DS . $this->userId;
        foreach (glob($dir . DS . '*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
        parent::tearDown();
    }

    public function testWriteAppendAndFinish(): void
    {
        $path = Csv::writeReport([['Order', 'Total'], ['A1', '10.00']], 'report.csv.tmp', ',', $this->userId);
        $this->assertSame(TMP . 'reports' . DS . $this->userId . DS . 'report.csv.tmp', $path);

        Csv::appendToReport([['B2', '5,50']], 'report.csv.tmp', ',', $this->userId);
        $final = Csv::finishReport('report.csv.tmp', $this->userId);

        $this->assertSame(TMP . 'reports' . DS . $this->userId . DS . 'report.csv', $final);
        $this->assertFileDoesNotExist($path);
        $this->assertSame("Order,Total\nA1,10.00\nB2,\"5,50\"\n", file_get_contents($final));
    }

    public function testWriteOverwritesExistingFile(): void
    {
        Csv::writeReport([['old']], 'r.csv', ';', $this->userId);
        $path = Csv::writeReport([['new', 'row']], 'r.csv', ';', $this->userId);

        $this->assertSame("new;row\n", file_get_contents($path));
    }
}
