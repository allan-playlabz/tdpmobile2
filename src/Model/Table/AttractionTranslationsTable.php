<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AttractionTranslations Model
 *
 * @property \App\Model\Table\AttractionsTable&\Cake\ORM\Association\BelongsTo $Attractions
 *
 * @method \App\Model\Entity\AttractionTranslation get($primaryKey, $options = [])
 * @method \App\Model\Entity\AttractionTranslation newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\AttractionTranslation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AttractionTranslation|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AttractionTranslation saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AttractionTranslation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AttractionTranslation[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\AttractionTranslation findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AttractionTranslationsTable extends Table
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

        $this->setTable('attraction_translations');
        $this->setDisplayField('title');
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('language')
            ->maxLength('language', 10)
            ->requirePresence('language', 'create')
            ->notEmptyString('language');

        $validator
            ->scalar('title')
            ->maxLength('title', 100)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('description')
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        $validator
            ->scalar('terms_and_conditions')
            ->requirePresence('terms_and_conditions', 'create')
            ->notEmptyString('terms_and_conditions');

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
        $rules->add($rules->existsIn(['attraction_id'], 'Attractions'));

        return $rules;
    }

    /**
     * Finds attraction translation
     *
     * @param string $attraction_id
     * @param string $language
     * @return AttractionTrasnlation
     */
    public function findTranslation($attraction_id, $language) {
        $attraction_translation = $this->find()->where([
            'attraction_id' => $attraction_id,
            'language' => $language,
        ]);
        $attraction_translation = $attraction_translation->first();
        return $attraction_translation;
    }
}
