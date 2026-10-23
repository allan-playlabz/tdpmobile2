<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Merchants Model
 *
 * @property \App\Model\Table\AttractionsTable&\Cake\ORM\Association\HasMany $Attractions
 * @property \App\Model\Table\LandTransfersTable&\Cake\ORM\Association\HasMany $LandTransfers
 * @property \App\Model\Table\MerchantUsersTable&\Cake\ORM\Association\HasMany $MerchantUsers
 *
 * @method \App\Model\Entity\Merchant get($primaryKey, $options = [])
 * @method \App\Model\Entity\Merchant newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Merchant[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Merchant|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Merchant saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Merchant patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Merchant[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Merchant findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MerchantsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('merchants');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Attractions', [
            'foreignKey' => 'merchant_id'
        ]);
        $this->hasMany('LandTransfers', [
            'foreignKey' => 'merchant_id'
        ]);
        $this->hasMany('MerchantUsers', [
            'foreignKey' => 'merchant_id'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('contact_number')
            ->maxLength('contact_number', 15)
            ->allowEmptyString('contact_number');

        $validator
            ->scalar('business_description')
            ->maxLength('business_description', 500)
            ->requirePresence('business_description', 'create')
            ->notEmptyString('business_description');

        $validator
            ->scalar('contact_person_name')
            ->maxLength('contact_person_name', 100)
            ->requirePresence('contact_person_name', 'create')
            ->notEmptyString('contact_person_name');

        $validator
            ->scalar('contact_person_designation')
            ->maxLength('contact_person_designation', 100)
            ->requirePresence('contact_person_designation', 'create')
            ->notEmptyString('contact_person_designation');

        $validator
            ->scalar('contact_person_number')
            ->maxLength('contact_person_number', 45)
            ->requirePresence('contact_person_number', 'create')
            ->notEmptyString('contact_person_number');

        $validator
            ->scalar('contact_person_email')
            ->maxLength('contact_person_email', 100)
            ->allowEmptyString('contact_person_email');

        $validator
            ->scalar('merchant_website')
            ->maxLength('merchant_website', 255)
            ->allowEmptyString('merchant_website');

        $validator
            ->scalar('status')
            ->allowEmptyString('status');

        $validator
            ->boolean('online_only')
            ->allowEmptyString('online_only');

        $validator
            ->scalar('signature')
            ->allowEmptyString('signature');

        $validator
            ->scalar('voucher_template')
            ->maxLength('voucher_template', 45)
            ->allowEmptyString('voucher_template');

        $validator
            ->scalar('voucher_code_type')
            ->allowEmptyString('voucher_code_type');

        $validator
            ->boolean('multiple_redemption')
            ->allowEmptyString('multiple_redemption');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['email']));

        return $rules;
    }
}
