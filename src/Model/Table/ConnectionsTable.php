<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Connections Model
 *
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\HasMany $Tickets
 * @property \App\Model\Table\AttractionsTable&\Cake\ORM\Association\BelongsToMany $Attractions
 *
 * @method \App\Model\Entity\Connection get($primaryKey, $options = [])
 * @method \App\Model\Entity\Connection newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Connection[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Connection|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Connection saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Connection patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Connection[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Connection findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ConnectionsTable extends Table
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

        $this->setTable('connections');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Tickets', [
            'foreignKey' => 'connection_id'
        ]);
        $this->belongsToMany('Attractions', [
            'foreignKey' => 'connection_id',
            'targetForeignKey' => 'attraction_id',
            'joinTable' => 'attractions_connections'
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
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('api_url')
            ->maxLength('api_url', 255)
            ->allowEmptyString('api_url');

        $validator
            ->scalar('credentials')
            ->allowEmptyString('credentials');

        $validator
            ->scalar('function_name')
            ->maxLength('function_name', 100)
            ->allowEmptyString('function_name');

        $validator
            ->scalar('status')
            ->allowEmptyString('status');

        return $validator;
    }
}
