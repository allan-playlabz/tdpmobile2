<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Exception;


/**
 * Tickets Model
 *
 * @property \App\Model\Table\AttractionsTable&\Cake\ORM\Association\BelongsTo $Attractions
 * @property \App\Model\Table\SkusTable&\Cake\ORM\Association\BelongsTo $Skus
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\BelongsTo $ParentTickets
 * @property \App\Model\Table\GeneratedCodesTable&\Cake\ORM\Association\HasMany $GeneratedCodes
 * @property \App\Model\Table\OrderItemsTable&\Cake\ORM\Association\HasMany $OrderItems
 * @property \App\Model\Table\SettlementAgentsTable&\Cake\ORM\Association\HasMany $SettlementAgents
 * @property \App\Model\Table\SettlementsTable&\Cake\ORM\Association\HasMany $Settlements
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\HasMany $ChildTickets
 *
 * @method \App\Model\Entity\Ticket get($primaryKey, $options = [])
 * @method \App\Model\Entity\Ticket newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Ticket[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Ticket|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Ticket saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Ticket patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Ticket[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Ticket findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TicketsTable extends Table
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

        $this->setTable('tickets');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Attractions', [
            'foreignKey' => 'attraction_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('ParentTickets', [
            'className' => 'Tickets',
            'foreignKey' => 'parent_id'
        ]);
        $this->belongsToMany('ChildTickets', [
            'className'=>'Tickets',
            'foreignKey' => 'package_ticket_id',
            'targetForeignKey' => 'ticket_id',
            'joinTable' => 'packages_tickets'
        ]);
        $this->hasMany('GeneratedCodes', [
            'foreignKey' => 'ticket_sku_id'
        ]);
        $this->hasMany('Pdfs', [
            'foreignKey' => 'ticket_sku_id'
        ]);
        $this->hasMany('Barcodes', [
            'foreignKey' => 'ticket_sku_id'
        ]);
        $this->hasMany('OrderItems', [
            'foreignKey' => 'ticket_id'
        ]);
        $this->hasMany('SettlementAgents', [
            'foreignKey' => 'ticket_id'
        ]);
        $this->hasMany('Settlements', [
            'foreignKey' => 'ticket_id'
        ]);

        $this->hasMany('Inventories', [
            'foreignKey' => 'ticket_id'
        ]);
        // $this->hasMany('ChildTickets', [
        //     'className' => 'Tickets',
        //     'foreignKey' => 'parent_id'
        // ]);
        $this->hasMany('Fixeddates', [
            'foreignKey' => 'ticket_id'
        ]);
        $this->belongsToMany('Agents', [
            'through'=>'FixeddatesAgents',
            'foreignKey' => 'ticket_id',
            'targetForeignKey'=>'agent_id'
        ]);
        $this->hasMany('FixeddatesAgents', [
            'foreignKey' => 'ticket_id'
        ]);
        $this->hasMany('Fdatesettlements', [
            'foreignKey' => 'ticket_id'
        ]);
        $this->hasMany('FdatesettlementsAgents', [
            'foreignKey' => 'ticket_id'
        ]);

        $this->hasMany('PackagesTickets', [
            'foreignKey' => 'ticket_id'
        ]);

        $this->belongsTo('Connections', [
            'foreignKey' => 'connection_id',
            'joinType' => 'LEFT'
        ]);

        $this->hasOne('TicketTripTypes', [
            'foreignKey' => 'ticket_id',
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
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        $validator
            ->scalar('terms_and_conditions')
            ->requirePresence('terms_and_conditions', 'create')
            ->notEmptyString('terms_and_conditions');

        $validator
            ->notEmptyString('sequence');

        $validator
            ->dateTime('publish_start_date')
            ->requirePresence('publish_start_date', 'create')
            ->notEmptyDateTime('publish_start_date');

        $validator
            ->dateTime('publish_end_date')
            ->requirePresence('publish_end_date', 'create')
            ->notEmptyDateTime('publish_end_date');

        $validator
            ->numeric('price')
            ->allowEmptyString('price');

        $validator
            ->allowEmptyString('release_period');

        $validator
            ->dateTime('redemption_start_date')
            ->requirePresence('redemption_start_date', 'create')
            ->notEmptyDateTime('redemption_start_date');

        $validator
            ->dateTime('redemption_end_date')
            ->requirePresence('redemption_end_date', 'create')
            ->notEmptyDateTime('redemption_end_date');

        $validator
            ->boolean('fixed_date_ticket')
            ->allowEmptyString('fixed_date_ticket');

        $validator
            ->scalar('fixed_date_info')
            ->allowEmptyString('fixed_date_info');

        $validator
            ->allowEmptyString('slot_allotment');

        $validator
            ->boolean('instant_confirmation')
            ->allowEmptyString('instant_confirmation');

        $validator
            ->allowEmptyString('quantity');

        $validator
            ->scalar('voucher_note')
            ->allowEmptyString('voucher_note');

        $validator
            ->scalar('type')
            ->allowEmptyString('type');

        $validator
            ->scalar('flexi_options')
            ->allowEmptyString('flexi_options');

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

        return $rules;
    }

    public function consume($ticket_id,$voucher_type,$pricing_type,$stock,$qty,$date = null,$time=null) {
      // Ensure subticket quantity is sufficient
      $for_update_ticket = $this->find()->where(['Tickets.id'=>$ticket_id])->epilog('FOR UPDATE')->first();
      if ($for_update_ticket) {
        if ($for_update_ticket->stock !== null && $for_update_ticket->stock < $qty) {
          return "Not enough ticket stock";
        }
      } else {
        return "Not enough ticket stock";
      }
      if ($pricing_type == 'fdate') {
        $fixeddate = $this->Fixeddates->find()->where(['ticket_id'=>$ticket_id,'date'=>$date])->epilog('FOR UPDATE')->first();
        if ($fixeddate) {
          if ($fixeddate->stock !== null && $fixeddate->stock < $qty) {
            return "Not enough ticket stock";
          }
          $fixeddate->quantity_sold += $qty;
          if ($fixeddate->stock !== null) {
            $fixeddate->stock -= $qty;
          }
          if (!$this->Fixeddates->save($fixeddate)) {
              return "Error saving fixdate";
          }
        }
      }
      if ($pricing_type == 'fdatetime') {
        $fixeddate = $this->Fixeddates->find()->where(['ticket_id'=>$ticket_id,'date'=>$date,'time'=>$time])->epilog('FOR UPDATE')->first();
        if ($fixeddate) {

          if ($fixeddate->stock !== null && $fixeddate->stock < $qty) {
            return "Not enough ticket stock";
          }
          $fixeddate->quantity_sold += $qty;
          if ($fixeddate->stock !== null) {
            $fixeddate->stock -= $qty;
          }
          if (!$this->Fixeddates->save($fixeddate)) {
              return "Error saving fixdate";
          }
        }
      }
      $for_update_ticket->quantity_sold += $qty;
      if ($for_update_ticket->stock !== null) {
        $for_update_ticket->stock -= $qty;
      }
      if (!$this->save($for_update_ticket)) {
          return "Error saving ticket";
      }
      \Cake\Log\Log::write('debug',$for_update_ticket->sku_id.' '.$for_update_ticket->quantity_sold);
      return true;
    }

    public function getReturnTicket($ticket_id) {
        $ticket = $this->find()->matching('TicketTripTypes', function($q) use ($ticket_id) {
            return $q->where(['linked_ticket_id' => $ticket_id, 'TicketTripTypes.type' => 'return']);
            
        })->contain(['TicketTripTypes'])->first();
        if ($ticket) {
            return $ticket;
        }

        return null;
    }

    public function isValidReturnTicket($ticket_id, $return_ticket_id) {
        $return_ticket_ids = $this->TicketTripTypes->find('list', ['valueField' => 'ticket_id', 'type' => 'return'])->where(['linked_ticket_id' => $ticket_id])->toArray();

        if (!empty($return_ticket_ids)) {
            return in_array($return_ticket_id, $return_ticket_ids);
        }

        return false;
    }
}
