<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FdateOverrideRate Entity
 *
 * @property int $id
 * @property int $fdatesettlement_id
 * @property int $fdate_id
 * @property float $rate
 * @property float $rebate
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Fdatesettlement $fdatesettlement
 * @property \App\Model\Entity\Fixeddate $fixeddate
 */
class FdateOverrideRate extends Entity
{
    protected $_accessible = [
        'fdatesettlement_id' => true,
        'fdate_id' => true,
        'rate' => true,
        'rebate' => true,
        'created' => true,
        'modified' => true,
        'fdatesettlement' => true,
        'fixeddate' => true
    ];
}
