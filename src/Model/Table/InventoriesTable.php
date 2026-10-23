<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Inventories Model
 *
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\BelongsTo $Tickets
 * @property \App\Model\Table\OrdersTable&\Cake\ORM\Association\BelongsTo $Orders
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Inventory get($primaryKey, $options = [])
 * @method \App\Model\Entity\Inventory newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Inventory[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Inventory|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Inventory saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Inventory patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Inventory[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Inventory findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class InventoriesTable extends Table
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

        $this->setTable('inventories');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Tickets', [
            'foreignKey' => 'ticket_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Orders', [
            'foreignKey' => 'order_id'
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
            ->integer('quantity')
            ->allowEmptyString('quantity');

        $validator
            ->integer('sold')
            ->allowEmptyString('sold');

        $validator
            ->integer('stock')
            ->allowEmptyString('stock');

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
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['ticket_id'], 'Tickets'));

        return $rules;
    }

    public function purchase($ticket_id, $qty,$user_id,$order_id) {
        $last_inventory = $this->find()->where(['ticket_id'=>$ticket_id])->order(['created'=>'DESC','id'=>'DESC'])->first();
        $new_inventory = $this->newEntity(['ticket_id'=>$ticket_id,'type'=>'sold','quantity'=>-$qty,'user_id'=>$user_id,'order_id'=>$order_id]);
        if ($last_inventory) {
            $new_inventory->sold = intval($last_inventory->sold) + intval($qty);
            if ($last_inventory->current_quantity === null) {
                $new_inventory->stock = null;
                $new_inventory->current_quantity = null;
            } else {
                $new_inventory->stock = intval($last_inventory->stock) - intval($qty);
                $new_inventory->current_quantity = $last_inventory->current_quantity;
            }
        } 
        
        if ($this->save($new_inventory)) {
            return true;
        }
        return false;
    }
}
