<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Attractions Model
 *
 * @property \App\Model\Table\MerchantsTable&\Cake\ORM\Association\BelongsTo $Merchants
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @property \App\Model\Table\SkusTable&\Cake\ORM\Association\BelongsTo $Skus
 * @property \App\Model\Table\AttractionMediasTable&\Cake\ORM\Association\HasMany $AttractionMedias
 * @property \App\Model\Table\AttractionTranslationsTable&\Cake\ORM\Association\HasMany $AttractionTranslations
 * @property \App\Model\Table\SettlementAgentsTable&\Cake\ORM\Association\HasMany $SettlementAgents
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\HasMany $Tickets
 *
 * @method \App\Model\Entity\Attraction get($primaryKey, $options = [])
 * @method \App\Model\Entity\Attraction newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Attraction[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Attraction|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Attraction saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Attraction patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Attraction[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Attraction findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AttractionsTable extends Table
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

        $this->setTable('attractions');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Merchants', [
            'foreignKey' => 'merchant_id'
        ]);
        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER'
        ]);
        $this->hasMany('AttractionMedias', [
            'foreignKey' => 'attraction_id',
            'order'=>['display_order'=>'asc']
        ]);
        $this->hasMany('AttractionTranslations', [
            'foreignKey' => 'attraction_id'
        ]);
        $this->hasMany('SettlementAgents', [
            'foreignKey' => 'attraction_id'
        ]);
        $this->hasMany('Tickets', [
            'foreignKey' => 'attraction_id',
            'order'=>['sequence'=>'asc']
        ]);
        $this->hasOne('FavoriteAttractions', [
            'foreignKey' => 'attraction_id'
        ]);
        $this->belongsTo('Cities', [
            'foreignKey' => 'city_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsToMany('Tags', [
            'joinTable' => 'attractions_tags',
            'foreignKey' => 'attraction_id',
            'targetForeignKey' => 'tag_id',
        ]);
        $this->hasOne('RecommendedAttractions', [
            'foreignKey' => 'attraction_id'
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
            ->scalar('title')
            ->maxLength('title', 100)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('note_for_reseller')
            ->allowEmptyString('note_for_reseller');

        $validator
            ->allowEmptyString('display_position');

        $validator
            ->scalar('featured_image')
            ->maxLength('featured_image', 255)
            ->allowEmptyFile('featured_image');

        $validator
            ->scalar('description')
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        $validator
            ->scalar('terms_and_conditions')
            ->requirePresence('terms_and_conditions', 'create')
            ->notEmptyString('terms_and_conditions');

        $validator
            ->scalar('status')
            ->allowEmptyString('status');

        $validator
            ->boolean('print_as_qr_code')
            ->allowEmptyString('print_as_qr_code');

        $validator
            ->scalar('gallery')
            ->allowEmptyString('gallery');

        $validator
            ->scalar('type')
            ->allowEmptyString('type');

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
        $rules->add($rules->existsIn(['merchant_id'], 'Merchants'));
        $rules->add($rules->existsIn(['category_id'], 'Categories'));

        return $rules;
    }
}
