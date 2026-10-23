<?php

namespace App\Util;

use Cake\Log\Log;
use Exception;

/**
 * Rounder class
 * utility class for rounding up values
 */
class Rounder
{
    /**
     * Rounds a decimal value up
     *
     * @param float $value
     * @param int $precision
     * @return float
     */
    public static function roundUpCent($value) {
        $pow = 100;
        return bcdiv(ceil(bcmul($pow, $value, 4)), $pow, 2);
    }
}