<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * RecommendedAttractions Model
 *
 * @property \App\Model\Table\AttractionsTable&\Cake\ORM\Association\BelongsTo $Attractions
 *
 * @method \App\Model\Entity\RecommendedAttraction get($primaryKey, $options = [])
 * @method \App\Model\Entity\RecommendedAttraction newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\RecommendedAttraction[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\RecommendedAttraction[]|\Cake\Datasource\ResultSetInterface saveMany($entities, $options = [])
 */
class RecommendedAttractionsTable extends Table
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

        $this->setTable('recommended_attractions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Attractions', [
            'foreignKey' => 'attraction_id',
            'joinType' => 'INNER',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('display_order')
            ->notEmptyString('display_order');

        return $validator;
    }
}
