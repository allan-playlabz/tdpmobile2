<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Barcodes Model
 *
 * @property \App\Model\Table\PurchasedTicketSkusTable&\Cake\ORM\Association\BelongsTo $PurchasedTicketSkus
 * @property \App\Model\Table\TicketSkusTable&\Cake\ORM\Association\BelongsTo $TicketSkus
 *
 * @method \App\Model\Entity\Barcode get($primaryKey, $options = [])
 * @method \App\Model\Entity\Barcode newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Barcode[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Barcode|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Barcode saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Barcode patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Barcode[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Barcode findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BarcodesTable extends Table
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

        $this->setTable('barcodes');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Tickets', [
            'foreignKey' => 'ticket_sku_id',
            'bindingKey'=>'sku_id',
            'joinType' => 'LEFT'
        ]);
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('package_code')
            ->maxLength('package_code', 100)
            ->allowEmptyString('package_code');

        $validator
            ->scalar('code')
            ->maxLength('code', 255)
            ->requirePresence('code', 'create')
            ->notEmptyString('code')
            ->add('code', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->date('validity_start_date')
            ->requirePresence('validity_start_date', 'create')
            ->notEmptyDate('validity_start_date');

        $validator
            ->date('validity_end_date')
            ->requirePresence('validity_end_date', 'create')
            ->notEmptyDate('validity_end_date');

        $validator
            ->scalar('status')
            ->notEmptyString('status');

        $validator
            ->boolean('sold')
            ->allowEmptyString('sold');

        $validator
            ->dateTime('redeemed_datetime')
            ->allowEmptyDateTime('redeemed_datetime');

        $validator
            ->scalar('custom_data')
            ->maxLength('custom_data', 500)
            ->allowEmptyString('custom_data');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->isUnique(['code']));

        return $rules;
    }
}
