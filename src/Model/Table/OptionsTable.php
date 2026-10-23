<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OptionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('options');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): \Cake\Validation\Validator
    {
        $validator
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('value')
            ->allowEmptyString('value');

        $validator
            ->boolean('autoload')
            ->allowEmptyString('autoload');

        return $validator;
    }

    /**
     * Get an option value by name
     *
     * @param string $name Option name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getValueByName($name, $default = null)
    {
        $option = $this->find()
            ->where(['name' => $name])
            ->first();

        if ($option) {
            return $option->value;
        }

        return $default;
    }
}
