<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class Option extends Entity
{
    protected $_accessible = [
        'name' => true,
        'value' => true,
        'autoload' => true,
        'created' => true,
        'modified' => true,
    ];
}
