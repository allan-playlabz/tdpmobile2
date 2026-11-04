<?php
declare(strict_types=1);

namespace App\Test\TestCase\Util;

use App\Util\Rounder;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RounderTest extends TestCase
{
    public static function valueProvider(): array
    {
        return [
            'exact cents unchanged' => ['10.25', '10.25'],
            'integer' => ['7', '7.00'],
            'fraction of a cent rounds up' => ['1.001', '1.01'],
            'just above a cent' => ['2.3401', '2.35'],
            'zero' => ['0', '0.00'],
            'float input' => [3.141, '3.15'],
            'negative rounds toward zero' => ['-1.009', '-1.00'],
        ];
    }

    #[DataProvider('valueProvider')]
    public function testRoundUpCent(string|float $value, string $expected): void
    {
        $this->assertSame($expected, Rounder::roundUpCent($value));
    }
}
