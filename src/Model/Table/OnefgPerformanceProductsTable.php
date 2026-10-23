<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * OnefgPerformanceProducts Model
 *
 * @method \App\Model\Entity\OnefgPerformanceProduct get($primaryKey, $options = [])
 * @method \App\Model\Entity\OnefgPerformanceProduct newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\OnefgPerformanceProduct[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OnefgPerformanceProduct|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OnefgPerformanceProduct saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OnefgPerformanceProduct patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\OnefgPerformanceProduct[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\OnefgPerformanceProduct findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OnefgPerformanceProductsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('onefg_performance_products');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): \Cake\Validation\Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('product_ak')
            ->maxLength('product_ak', 100)
            ->requirePresence('product_ak', 'create')
            ->notEmptyString('product_ak');

        $validator
            ->scalar('product_name')
            ->maxLength('product_name', 255)
            ->allowEmptyString('product_name');

        $validator
            ->scalar('product_description')
            ->maxLength('product_description', 255)
            ->allowEmptyString('product_description');

        $validator
            ->scalar('performance_ak')
            ->maxLength('performance_ak', 100)
            ->requirePresence('performance_ak', 'create')
            ->notEmptyString('performance_ak');

        $validator
            ->date('performance_date')
            ->allowEmptyDate('performance_date');

        $validator
            ->time('performance_time')
            ->allowEmptyTime('performance_time');

        $validator
            ->scalar('seat_category_description')
            ->maxLength('seat_category_description', 255)
            ->allowEmptyString('seat_category_description');

        $validator
            ->integer('availability')
            ->allowEmptyString('availability');

        $validator
            ->numeric('price')
            ->allowEmptyString('price');

        return $validator;
    }

    public function add($data) {
        $query = $this->query();
        $query
        ->insert(['product_ak','product_name','product_description', 'performance_ak', 'performance_date','performance_time','seat_category_description','availability','price', 'created','modified'])
        ->values($data);
        $query->epilog('ON DUPLICATE KEY UPDATE `product_name` = VALUES(`product_name`),`product_description` = VALUES(`product_description`),`price` = VALUES(`price`),`availability` = VALUES(`availability`), `modified` = NOW()');
        $query->execute();
        return true;
    }
}
