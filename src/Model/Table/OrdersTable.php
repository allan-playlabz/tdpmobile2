<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Mustache_Engine;
use App\Lib\Payments;
use App\Util\Rounder;
use Cake\I18n\Number;
use App\Lib\ApiCaller;
use Cake\Core\Configure;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

define ('PERMITTED_CHARS','0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
/**
 * Orders Model
 *
 * @property \App\Model\Table\OrderRevesTable&\Cake\ORM\Association\BelongsTo $OrderReves
 * @property \App\Model\Table\AgentsTable&\Cake\ORM\Association\BelongsTo $Agents
 * @property \App\Model\Table\OrderItemsTable&\Cake\ORM\Association\HasMany $OrderItems
 *
 * @method \App\Model\Entity\Order get($primaryKey, $options = [])
 * @method \App\Model\Entity\Order newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Order[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Order|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Order saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Order patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Order[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Order findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OrdersTable extends Table
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

        $this->setTable('orders');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Loggable');

        $this->belongsToMany('Agents', [
            'through'=>'AgentUsers',
            'foreignKey' => 'id',
            'targetForeignKey'=>'agent_id',
            'bindingKey'=> 'agent_user_id'
        ]);

        $this->belongsTo('AgentUsers', [
            'foreignKey' => 'agent_user_id',
            'joinType' => 'INNER'
        ]);

        $this->hasMany('OrderItems', [
            'foreignKey' => 'order_id'
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
            ->scalar('customer_name')
            ->maxLength('customer_name', 100)
            ->requirePresence('customer_name', 'create')
            ->notEmptyString('customer_name');

        $validator
            ->scalar('status')
            ->allowEmptyString('status');

        $validator
            ->numeric('subtotal')
            ->allowEmptyString('subtotal');

        $validator
            ->numeric('convinience_fee')
            ->allowEmptyString('convinience_fee');

        $validator
            ->numeric('total')
            ->allowEmptyString('total');

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
        $rules->add($rules->existsIn(['agent_id'], 'Agents'));

        return $rules;
    }

    public function afterSave(\Cake\Event\EventInterface $event, $entity, $options) {
      if (isset($entity) && $entity->isNew()) {
        $order_ref_id = $this->_getRandomChars();
        while ($this->find()->where(['order_ref_id'=>$order_ref_id])->first()) {
            $order_ref_id = $this->_getRandomChars();
        }
        $entity->order_ref_id = $order_ref_id;
        $this->save($entity);
      }
    }

    private function _getRandomChars() {
        $str = '';
        while (strlen($str) <= 15) {
            $str .= PERMITTED_CHARS[mt_rand(0,35)];
        }
        return $str;
    }

    public function setPaymentMethod(\App\Model\Entity\Order $order, $payment_method) {
        
        $payment_methods = Payments::getPaymentMethodList();
        $payment_methods[] = 'credits';
        if (!in_array($payment_method, $payment_methods)) {
            $order->setError('payment_method', 'Invalid payment method');
            return false;
        }
        $order->payment_method = $payment_method;
        if ($this->save($order, ['associated' => false])) {
            return true;
        }
       
        return false;
    }

    /**
     * Sets convenience fee based on payment method and markup
     *
     * @param \App\Model\Entity\Order $order
     * @return boolean
     */
    public function setConvenienceFee(\App\Model\Entity\Order $order) {
        
        $markup = Payments::getMarkup($order->payment_method);
        $markup_value = $markup['value'];
        $markup_type = $markup['type'];
        $markup_add = $markup['add'];
        $providers = Configure::read('Payment.providers');
        $provider = !empty($providers[$order->payment_method]) ? $providers[$order->payment_method] : null;
        $markups = [];            
        
        $convenience_fee = 0;
        if ($markup_type === 'percentage') {
            if ($provider == '2C2P') {
                $total_amount = Rounder::roundUpCent(bcmul(bcadd((string) $order->subtotal, $markup_add, 4), bcdiv(100, bcsub(100,  $markup_value, 4), 4), 4));
                $convenience_fee  = bcsub((string) $total_amount, (string) $order->subtotal, 2);
            } elseif ($provider == 'Paypal') {
                $total_amount = Rounder::roundUpCent(bcmul(bcadd((string) $order->subtotal, $markup_add, 4), bcdiv(100, bcsub(100,  $markup_value, 4), 4), 4));
                $convenience_fee  = bcsub((string) $total_amount, (string) $order->subtotal, 2);
            } elseif ($provider == 'Stripe') {
                $convenience_fee = Rounder::roundUpCent(bcadd(bcdiv(bcmul((string) $order->subtotal, $markup_value), 100, 4), $markup_add, 4));
                $total_amount = bcadd((string) $order->subtotal, $convenience_fee, 2);
            } else {
                $total_amount = $order->subtotal;
                $convenience_fee = 0;
            }
            
        } else {
            $convenience_fee = Rounder::roundUpCent(bcadd($markup_value, $markup_add, 4));
            $total_amount = bcadd($order->subtotal, $convenience_fee, 2);
        }
        
        $order->convinience_fee = $convenience_fee;
        $order->total = $total_amount;
        if ($this->save($order, ['associated' => false])) {
            return true;
        }
       
        return false;
    }

    /**
     * Sets payment id 
     *
     * @param \App\Model\Entity\Order $order
     * @param string $pi_id
     * @return boolean
     */
    public function setPaymentId(\App\Model\Entity\Order $order, $pi_id) {
      
        $order->pi_id = $pi_id;
        if ($this->save($order, ['associated' => false])) {
            return true;
        }
       
        return false;
    }

    public function createInvoice ($order_ref_id, $user) {
        $api_caller = new ApiCaller();
        $Agents = TableRegistry::getTableLocator()->get('Agents');
        $VoucherTemplates = TableRegistry::getTableLocator()->get('VoucherTemplates');
        $response = $api_caller->get('/order/details',['order_ref_id' => $order_ref_id], $user['email'], $user['id']);
        $invoice_template = $VoucherTemplates->find()->where(['type' => 'invoice'])->first();
        $agent = $Agents->find()->where(['id' => $user['agent_id']])->first();
        if (!$invoice_template) {
            return false;
        }
        $response_data = $response->getJson();
        if ($response_data['status'] != '1000' || empty($response_data['data'])) {
            return false;
        }
        $order_data = $response_data['data'];
        $invoice_table = $this->_createInvoiceTable($order_data);
        $data = [
            'invoice_no' => $order_ref_id,
            'site_name' => Configure::read('Site.name'),
            'company_name' => Configure::read('Site.company_name'),
            'company_address' => Configure::read('Site.company_address'),
            'company_contact_no' => Configure::read('Site.company_contact_no'),
            'order_ref_id' => $order_ref_id,
            'created' => $order_data['created'],
            'customer_company_name' => $agent->company_name,
            'customer_name' => $order_data['name'] ?? '',
            'payment_method' => $order_data['payment_method'] ? ucwords(Inflector::humanize($order_data['payment_method'])) : '',
            'mobile' => $order_data['mobile'] ?? '',
            'customer_email' => $order_data['email'] ?? '',
            'order_total' => Number::currency($order_data['total'] ?? 0),
            'invoice_table' => $invoice_table,
            // 'order_items' => $order_items
        ];
        $pdf = $this->_createPdf($invoice_template->template_text, $data);
        return $pdf;
    }

    private function _createPdf($template_text, $data) {
        $CakePdf = new \CakePdf\Pdf\CakePdf();        
        $CakePdf->margin(0, 0, 0, 0);
        // $CakePdf->template(''.'test_voucher', 'pdf');        
        
        $mustache = new Mustache_Engine();
        $html_text = $mustache->render($template_text, $data);
        
        return $CakePdf->output($html_text);
    }

    private function _createInvoiceTable($order_data) {
        $invoice_table = '<table style="width: 100%; line-height: inherit; text-align: left;" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr class="heading">
                        <td
                            style="padding: 5px; vertical-align: top; background: #eee; border-bottom: 1px solid #ddd; font-weight: bold;">
                            Item</td>
                        <td
                            style="padding: 5px; vertical-align: top; text-align: right; background: #eee; border-bottom: 1px solid #ddd; font-weight: bold;">
                            Price</td>
                        <td
                            style="padding: 5px; vertical-align: top; text-align: right; background: #eee; border-bottom: 1px solid #ddd; font-weight: bold;">
                            Quantity</td>
                        <td
                            style="padding: 5px; vertical-align: top; text-align: right; background: #eee; border-bottom: 1px solid #ddd; font-weight: bold;">
                            Total</td>
                    </tr>';
        foreach ($order_data['order_items'] as $order_item) {
            $invoice_table .= '<tr class="item">
            <td style="padding: 5px; vertical-align: top; border-bottom: 1px solid #eee;">'.$order_item['attraction_title'].'
            '.$order_item['ticket_name'].'</td>
            <td style="padding: 5px; vertical-align: top; text-align: right; border-bottom: 1px solid #eee;">
            '.Number::precision($order_item['price'] ?? 0, 2).'</td>
            <td style="padding: 5px; vertical-align: top; text-align: right; border-bottom: 1px solid #eee;">
            '.$order_item['quantity'].'</td>
            <td style="padding: 5px; vertical-align: top; text-align: right; border-bottom: 1px solid #eee;">
            '.Number::precision($order_item['total'] ?? 0, 2).'</td>
        </tr>';
        }
        $invoice_table .='
                    <tr class="total">
                        <td style="padding: 5px; vertical-align: top;">&nbsp;</td>
                        <td style="padding: 5px; vertical-align: top;">&nbsp;</td>
                        <td style="padding: 5px; vertical-align: top;">&nbsp;</td>
                        <td
                            style="padding: 5px; vertical-align: top; text-align: right;">
                            Convenience Fee: ' . Number::precision($order_data['convenience_fee'] ?? 0, 2) . '</td>
                    </tr>';
        $invoice_table .='
                    <tr class="total">
                        <td style="padding: 5px; vertical-align: top;">&nbsp;</td>
                        <td style="padding: 5px; vertical-align: top;">&nbsp;</td>
                        <td style="padding: 5px; vertical-align: top;">&nbsp;</td>
                        <td
                            style="padding: 5px; vertical-align: top; text-align: right; font-weight: bold;">
                            Total: ' . Number::currency($order_data['total'] ?? 0) . '</td>
                    </tr>
                </tbody>
            </table>';
            
        return $invoice_table;
    }
}
