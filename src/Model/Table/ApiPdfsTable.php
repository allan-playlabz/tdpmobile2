<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ApiPdfs Model
 *
 * @property \App\Model\Table\PurchasedTicketSkusTable&\Cake\ORM\Association\BelongsTo $PurchasedTicketSkus
 * @property \App\Model\Table\TicketSkusTable&\Cake\ORM\Association\BelongsTo $TicketSkus
 *
 * @method \App\Model\Entity\ApiPdf get($primaryKey, $options = [])
 * @method \App\Model\Entity\ApiPdf newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\ApiPdf[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ApiPdf|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ApiPdf saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ApiPdf patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ApiPdf[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\ApiPdf findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ApiPdfsTable extends Table
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

        $this->setTable('api_pdfs');
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
            ->scalar('filename')
            ->maxLength('filename', 255)
            ->requirePresence('filename', 'create')
            ->notEmptyFile('filename');

        $validator
            ->scalar('pdf_filename')
            ->maxLength('pdf_filename', 255)
            ->allowEmptyFile('pdf_filename');

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
        // $rules->add($rules->existsIn(['ticket_sku_id'], 'Tickets'));

        return $rules;
    }
}
