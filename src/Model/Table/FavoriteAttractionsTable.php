<?php
namespace App\Model\Table;

use App\Model\Entity\FavoriteAttraction;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FavoriteAttractions Model
 *
 * @property \App\Model\Table\AttractionsTable&\Cake\ORM\Association\BelongsTo $Attractions
 * @property \App\Model\Table\AgentUsersTable&\Cake\ORM\Association\BelongsTo $AgentUsers
 *
 * @method \App\Model\Entity\FavoriteAttraction get($primaryKey, $options = [])
 * @method \App\Model\Entity\FavoriteAttraction newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\FavoriteAttraction[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FavoriteAttraction|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\FavoriteAttraction saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\FavoriteAttraction patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\FavoriteAttraction[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\FavoriteAttraction findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FavoriteAttractionsTable extends Table
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

        $this->setTable('favorite_attractions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Attractions', [
            'foreignKey' => 'attraction_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('AgentUsers', [
            'foreignKey' => 'agent_user_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('attraction_id')
            ->notEmptyString('attraction_id', null, 'create')
            ->maxLength('attraction_id', 36);

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
        $rules->add($rules->existsIn(['attraction_id'], 'Attractions'));
        $rules->add($rules->existsIn(['agent_user_id'], 'AgentUsers'));

        return $rules;
    }

    /**
     * Toggle favorite attraction
     *
     * @param string $attraction_id
     * @param string $agent_user_id
     * @return FavoriteAttraction|null
     */
    public function toggle(string $attraction_id, string $agent_user_id): ?FavoriteAttraction {
        $data = ['attraction_id' => $attraction_id, 'agent_user_id' => $agent_user_id];
        $favorite_attraction = $this->find()->where($data)->first();
        if ($favorite_attraction) {
            $this->delete($favorite_attraction);
            $favorite_attraction->id = null;
            return $favorite_attraction;
        }
        $favorite_attraction = $this->newEntity($data);
        if ($this->save($favorite_attraction)) {
            return $favorite_attraction;
        }

        return null;
    }
}
