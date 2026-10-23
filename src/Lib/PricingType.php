<?php

namespace App\Lib;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * PricingType class
 * Library pricing type of attraction or ticket
 */
class PricingType
{
    public const TWO_WAY_FDATETIME = 'two_way_fdatetime';
    public const SETTLEMENT = 'settlement';
    public const FDATE = 'fdate';
    public const FDATETIME = 'fdatetime';
}
