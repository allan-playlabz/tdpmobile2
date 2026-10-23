<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\I18n\Time;
use Cake\Core\Configure;
use PHPQRcode\QRcode;
use Cake\Event\Event;
use Cake\Routing\Router;
use Exception;
/**
 * Orders Controller
 *
 * @property \App\Model\Table\OrdersTable $Orders
 *
 * @method \App\Model\Entity\Order[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class OrdersController extends AppController
{
  public function initialize() {
    parent::initialize();
    $this->Auth->allow(['sps']);
    $this->loadComponent('ShoppingCart');
    $this->loadComponent('Mailer');
    $this->loadComponent('TicketsHelper');
    $this->loadComponent('S3');
    $this->loadModel('Tickets');
    $this->loadModel('Agents');
    $this->loadModel('AgentUsers');
    $this->loadModel('Vouchers');
    $this->loadModel('GeneratedCodes');
    $this->loadModel('SettlementAgents');
    $this->loadModel('CreditLogs');
    $this->loadModel('Attractions');
    $this->loadModel('Fixeddates');
    $this->loadModel('Connections');
  }

    public function addToCart() {
      $this->autoRender = false;
      if ($this->request->is('ajax')) {
        if ($this->request->getData('product_id')) {
          $product = $this->Products->findById($this->request->getData('product_id'))->first();
          if (!$product) {
            return $this->_ajaxResponse('product_not_found');
          }
          $qty = intval($this->request->getData('qty'));
          if ($qty > 1) {
            $this->ShoppingCart->add($product->id, $qty);
            return $this->_ajaxResponse('success',['qty'=> $qty,'price'=>$product->price,'id'=>$product->id,'title'=>$product->title,'slug'=>$product->slug,'image'=>$product->image,'session'=>$this->request->getSession()->read('ShoppingCart')]);
          }
          $this->ShoppingCart->add($product->id,1);
          return $this->_ajaxResponse('success',['qty'=> 1,'price'=>$product->price,'id'=>$product->id,'title'=>$product->title,'slug'=>$product->slug,'image'=>$product->image,'session'=>$this->request->getSession()->read('ShoppingCart')]);
        }
      }
    }

    public function subtractQuantity() {
      $this->autoRender = false;
      if ($this->request->is('ajax')) {
        if ($this->request->getData('product_id')) {
          $product = $this->Products->findById($this->request->getData('product_id'))->first();
          if (!$product) {
            return $this->_ajaxResponse('product_not_found');
          }
          $qty = intval($this->request->getData('qty'));
          if ($qty < -1) {
            $this->ShoppingCart->add($product->id, $qty);
            return $this->_ajaxResponse('success',['qty'=> $qty,'session'=>$this->request->getSession()->read('ShoppingCart')]);
          }
          $this->ShoppingCart->add($product->id,-1);
          return $this->_ajaxResponse('success',['qty'=> -1,'session'=>$this->request->getSession()->read('ShoppingCart')]);
        }
      }
    }

    public function removeFromCart() {
      $this->autoRender = false;
      if ($this->request->is('ajax')) {

        if ($this->request->getData('product_id')) {

          $product = $this->Products->findById($this->request->getData('product_id'))->first();
          if (!$product) {
            return $this->_ajaxResponse('product_not_found');
          }
          $this->ShoppingCart->remove($product->id);
          return $this->_ajaxResponse('success',['qty'=> 0]);
        }
        return $this->_ajaxResponse('product_not_found');
      }
    }

    public function cart() {
      $ticket_list = $this->ShoppingCart->getAll();
      $tickets_max_quantity = [];
      if (!empty($ticket_list)) {
        $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'])->toArray();
        if (!empty($tickets)) {
          foreach ($tickets as $key => $ticket) {
            if (isset($ticket_list[$ticket->id]) && is_array($ticket_list[$ticket->id])) {
              foreach ($ticket_list[$ticket->id] as $datetime => $qty) {
                $_ticket_id = $ticket->id;
                if ($ticket->attraction->pricing_type == 'settlement') {
                  $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$ticket->attraction,$this->Auth->user());
                  if ($ticket) {
                    $default_max_qty =  Configure::read('Default.max_order_quantity');
                    $tickets_max_quantity[$ticket->id] = $ticket->stock && $ticket->stock < $default_max_qty ? $ticket->stock : $default_max_qty;
                  }
                  else {
                    $this->ShoppingCart->remove($_ticket_id);
                  }
                }
                else {
                  $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->Auth->user(),$datetime);
                  if ($ticket) {
                    $tickets_max_quantity[$ticket->id] = $this->TicketsHelper->getTicketAvailableQty($ticket,$datetime);
                  }
                  else {
                    $this->ShoppingCart->remove($_ticket_id);
                  }
                }
              }
            }
          }
        }
        $this->set(compact('ticket_list','tickets','tickets_max_quantity'));
      }
      if ($this->request->is(['post'])) {
        $error = 0;
        $total_qty = 0;
        if ($this->request->getData('quantity')) {
          foreach ($this->request->getData('quantity') as $key_ticket_id => $date_ticket) {
            $ticket = $this->Tickets->find()->where(['Tickets.id'=>$key_ticket_id])->contain(['Attractions'])->first();
            foreach ($date_ticket as $date => $qty) {
              $total_qty += $qty;
              if ($ticket) {
                if ($ticket->attraction->pricing_type == 'settlement') {
                  if ($qty > ($ticket->stock !== null ? $ticket->stock : Configure::read('Default.max_order_quantity'))) {
                    $this->Flash->error('Sorry you have exceeded available '.$ticket->name .' tickets.');
                    $error++;
                  }
                }
                else {
                  if ($qty > $this->TicketsHelper->getTicketAvailableQty($ticket,$date)) {
                    $this->Flash->error('Sorry you have exceeded available '.$ticket->name .' tickets.');
                    $error++;
                  }
                }
                if (!empty($ticket->min_quantity) && $qty < $ticket->min_quantity) {
                  $this->Flash->error('Minimum quantity for '.$ticket->name .' should be '.$ticket->min_quantity.'.');
                  $error++;
                }
              }
            }
          }
        }

        if (!$error && $total_qty > Configure::read('Default.max_order_quantity')) {
          $this->Flash->error('Cannot exceed max allowed quantity ('.Configure::read('Default.max_order_quantity').')');
          return $this->redirect(['action'=>'cart']);
        }
        if (!$error) {
          $this->ShoppingCart->updateQuantity($this->request->getData('quantity'));
          if (!$this->_checkAvailableVouchers(null,$this->ShoppingCart->getAll())) {
            return $this->redirect(['action'=>'cart']);
          }

          return $this->redirect(['action'=>'checkout']);
        }
      }
    }

    public function checkout() {
      $data = []

      $data = $this->_buildData($ticket_list);
      $header = $this->_buildHeaders($data);
      $response = $http->post('tdpapiv2.localhost/order/create',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);

      /* begin old checkout */
      $order = $this->Orders->newEntity();
      // if ($this->request->getSession()->check('order_id')) {
      //   $existing_order = $this->Orders->find()->where(['id'=>$this->request->getSession()->read('order_id')])->first();
      //   if ($existing_order) {
      //     $order = $existing_order;
      //     $this->Orders->OrderItems->deleteAll(['order_id'=>$existing_order->id]);
      //   }
      // }
      $ticket_list = $this->ShoppingCart->getAll();
      $max_quantity = 0;

      if (!empty($ticket_list)) {
        $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'=>['Merchants']])->toArray();
        if (!empty($tickets)) {
          foreach ($tickets as $key => $ticket) {
            if (isset($ticket_list[$ticket->id]) && is_array($ticket_list[$ticket->id])) {
              foreach ($ticket_list[$ticket->id] as $datetime => $qty) {
                if ($ticket->attraction->pricing_type == 'settlement') {
                  $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$ticket->attraction,$this->Auth->user(),$datetime);
                }
                else {
                  $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->Auth->user(),$datetime);
                }

                if ($max_quantity < $qty) {
                  $max_quantity = $qty;
                }
              }
            }
          }
        }
        $this->set(compact('ticket_list','max_quantity','tickets'));
      }
      else {
        return $this->redirect(['controller'=>'Attractions','action'=>'index']);
      }

      if ($this->request->is(['post','put','patch'])) {
        if (!empty($ticket_list)) {
          $order->order_items = [];
          $order->subtotal = 0;
          $multiple_redemption = [];
          $idx = 0;
          $ticket_total = [];
          foreach ($ticket_list as $key_ticket_id => $date_ticket) {
            $ticket = $this->Tickets->find()->where(['Tickets.id'=>$key_ticket_id])->contain(['Attractions'=>['Merchants']])->first();
            if (!isset($ticket_total[$ticket->id])) {
              $ticket_total[$ticket->id] = 0;
            }
            $ticket_total[$ticket->id] += $qty;

            foreach ($date_ticket as $date => $qty) {
              $order_item = $this->Orders->OrderItems->newEntity(['ticket_id'=>$ticket->id]);
              $order_item->qty = $qty;
              if ($ticket) {
                $_ticket_id = $ticket->id;
                if ($ticket->attraction->pricing_type == 'settlement') {
                  $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$ticket->attraction,$this->Auth->user());
                  if ($ticket &&  ($qty > ($ticket->stock !== null ? $ticket->stock : Configure::read('Default.max_order_quantity')))) {
                    $this->Flash->error('Sorry you have exceeded available '.$ticket->name .' tickets.');
                    $order->setError('ticket_availability','Sorry you have exceeded available '.$ticket->name .' tickets.');
                  }
                  elseif (!$ticket) {
                    $this->ShoppingCart->remove($_ticket_id);
                  }
                }
                else {
                  $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->Auth->user(),$date);
                  if ($ticket && ($qty > $this->TicketsHelper->getTicketAvailableQty($ticket,$date))) {
                    $this->Flash->error('Sorry you have exceeded available '.$ticket->name .' tickets.');
                    $order->setError('ticket_availability','Sorry you have exceeded available '.$ticket->name .' tickets.');
                  }
                  elseif (!$ticket) {
                    $this->ShoppingCart->remove($_ticket_id);
                  }
                }
                if ($ticket) {
                  $order_item->price = $ticket->price;
                  $order_item->total = $ticket->price * $qty;
                  if (!empty($ticket->min_quantity) && $qty < $ticket->min_quantity) {
                    $this->Flash->error('Minimum quantity for '.$ticket->name .' should be '.$ticket->min_quantity.'.');
                    $order->setError('ticket_min_quantity','Minimum quantity for '.$ticket->name .' should be '.$ticket->min_quantity.'.');
                  }
                }
              }


              if ($ticket) {
                if ($ticket->attraction->pricing_type != 'settlement') {
                  // $order_data['order_data'][$idx]['date_of_visit'] = $date;

                  if ($ticket->attraction->pricing_type =='fdatetime') {
                    $datetime = Time::createFromFormat('d M Y H:i',$date);
                    $order_item->date_of_visit = $datetime->format('Y-m-d');
                    $order_item->time_of_visit = $datetime->format('H:i:s');
                  }
                  else {
                    $datetime = Time::createFromFormat('d M Y',$date);
                    $order_item->date_of_visit = $datetime->format('Y-m-d');
                  }
                  
                }
                /* save ticket info to order item */
                $ticket_info = [
                  'attraction_name'=>$ticket->attraction->title,
                  'ticket_name'=>$ticket->name,
                  'ticket_type'=>$ticket->type,
                  'merchant_name'=>$ticket->attraction->merchant->name,
                  'merchant_id'=>$ticket->attraction->merchant_id
                ];
                if ($ticket->attraction->pricing_type == 'settlement') {
                  $ticket_info['redemption_start_date']= $ticket->redemption_start_date->format('d M Y h:ia');
                  $ticket_info['redemption_end_date']= $ticket->redemption_end_date->format('d M Y h:ia');
                  if ($ticket->cost_price) {
                    $ticket_info['cost_price'] = $ticket->cost_price;
                  }
                } else {
                  $fixed_date = $this->TicketsHelper->getFixeddate($ticket,$this->Auth->user(),$date);
                  
                  if ($fixed_date && $fixed_date->cost_price) {
                    $ticket_info['cost_price'] = $fixed_date->cost_price;
                  } else {
                    $ticket_info['cost_price'] = $ticket->cost_price;
                  }
                }
                
                if (!empty($ticket->flexi_options)) {
                  $flexi_options = json_decode($ticket->flexi_options,true);
                  if (!empty($flexi_options['valid_for'])) {
                    $ticket_info['flexi_valid_for'] = intval($flexi_options['valid_for']);
                  }
                }
                $order_item->ticket_info = json_encode($ticket_info);
                /* end save ticket info to order item */
                
                $order_data = [];
                if ($this->request->getData('passport_number.'.$ticket->id.'.'.$date)) {
                  for ($idx=0; $idx < count($this->request->getData('passport_number.'.$ticket->id.'.'.$date)); $idx++) {
                    if ($ticket->require_travellers_info) {

                      if ($this->request->getData('passport_number.'.$ticket->id.'.'.$date.'.'.$idx)) {
                        $order_data['required_info'][$idx]['passport_number'] = $this->request->getData('passport_number.'.$ticket->id.'.'.$date.'.'.$idx);
                      }
                      if ($this->request->getData('passport_expiry.'.$ticket->id.'.'.$date.'.'.$idx)) {
                        $order_data['required_info'][$idx]['passport_expiry'] = $this->request->getData('passport_expiry.'.$ticket->id.'.'.$date.'.'.$idx);
                      }
                      if ($this->request->getData('passport_first_name.'.$ticket->id.'.'.$date.'.'.$idx)) {
                        $order_data['required_info'][$idx]['passport_first_name'] = $this->request->getData('passport_first_name.'.$ticket->id.'.'.$date.'.'.$idx);
                      }
                      if ($this->request->getData('passport_last_name.'.$ticket->id.'.'.$date.'.'.$idx)) {
                        $order_data['required_info'][$idx]['passport_last_name'] = $this->request->getData('passport_last_name.'.$ticket->id.'.'.$date.'.'.$idx);
                      }
                      if ($this->request->getData('date_of_birth.'.$ticket->id.'.'.$date.'.'.$idx)) {
                        $order_data['required_info'][$idx]['date_of_birth'] = $this->request->getData('date_of_birth.'.$ticket->id.'.'.$date.'.'.$idx);
                      }
                      if ($this->request->getData('nationality.'.$ticket->id.'.'.$date.'.'.$idx)) {
                        $order_data['required_info'][$idx]['nationality'] = $this->request->getData('nationality.'.$ticket->id.'.'.$date.'.'.$idx);
                      }
                      if ($this->request->getData('remarks.'.$ticket->id.'.'.$date.'.'.$idx)) {
                        $order_data['required_info'][$idx]['remarks'] = $this->request->getData('remarks.'.$ticket->id.'.'.$date.'.'.$idx);
                      }
                    }
                  }
                }
                if ($ticket && $ticket->attraction->type == 'transfer') {
                  $extra_fields = Configure::read('Default.land_transfer_details');
                  if (!empty($extra_fields) && is_array($extra_fields)) {
                    foreach ($extra_fields as $extra_field_name => $extra_field) {
                      if ($this->request->getData($extra_field_name.'.'.$ticket->id)) {
                        if ($extra_field_name != 'pickup_time') {
                          $order_data['transfer_details'][$extra_field_name] = $this->request->getData($extra_field_name.'.'.$ticket->id);
                        }
                        else {
                          $order_data['transfer_details'][$extra_field_name] = $this->request->getData($extra_field_name.'.'.$ticket->id) ? implode(':',$this->request->getData($extra_field_name.'.'.$ticket->id)) : '';
                        }

                      }
                    }
                    if ($ticket->attraction->subtype == Configure::read('Default.transfer_subtypes.2')) {
                      foreach ($extra_fields as $extra_field_name => $extra_field) {
                        if ($this->request->getData($extra_field_name.'.'.$ticket->id)) {
                          if ($extra_field_name != 'pickup_time') {
                            $order_data['return_transfer_details'][$extra_field_name] = $this->request->getData('return_'.$extra_field_name.'.'.$ticket->id);
                          }
                          else {
                            $order_data['return_transfer_details'][$extra_field_name] = $this->request->getData('return_'.$extra_field_name.'.'.$ticket->id) ? implode(':',$this->request->getData('return_'.$extra_field_name.'.'.$ticket->id)) : '';
                          }
                        }
                      }
                    }
                  }
                }
                if (!empty($this->request->getData('multiple_redemption.'.$ticket->id.'.'.$date))) {
                    $order_data['multiple_redemption'] = true;
                }
                if (!empty($order_data)) {
                  $order_item->order_data = json_encode($order_data);
                }
                $order->order_items[] = $order_item;
                $order->subtotal += $order_item->total;
              }
            }

            // if (!$ticket) {
            //   $this->ShoppingCart->remove($key_ticket_id);
            // }
            // elseif (!$ticket->fixed_date_ticket && intval($ticket_total[$ticket->id]) > $this->TicketsHelper->getTicketAvailableQty($ticket)) {
            //   $this->log('Sorry you have exceeded available '.$ticket->name .' tickets.'.$ticket_total[$ticket->id] .':'.($ticket->quantity - $ticket->quantity_sold ),'debug');
            //   $this->Flash->error('Sorry you have exceeded available '.$ticket->name .' tickets.');
            //   $order->setError('ticket_availability','Sorry you have exceeded available tickets.');
            // }
          }
          if ($this->request->getData('first_name')) {

            $order = $this->Orders->patchEntity($order,$this->request->getData());

            $checkout_info = [
              'tracking_no'=>$this->request->getData('tracking_no'),
              'first_name'=>$this->request->getData('first_name'),
              'last_name'=>$this->request->getData('last_name'),
              'nric_passport_no'=>$this->request->getData('nric_passport_no'),
              'email'=>$this->request->getData('email'),
              'mobile'=>$this->request->getData('mobile')
            ];

            foreach ($checkout_info as $key => $value) {
              if ($key == 'email') {
                if (trim($value) == '' || !filter_var($value,FILTER_VALIDATE_EMAIL)) {
                  $order->setError($key,'Must be a valid email.');
                }
              } elseif ($key != 'tracking_no') {
                if (trim($value) == '') {
                  $order->setError($key,'Required');
                }
              }
            }

            $order->customer_name = $this->request->getData('first_name') . ' '. $this->request->getData('last_name');
            $order->tracking_no = $this->request->getData('tracking_no');
            $order->total = $order->subtotal;
            $order->checkout_info = json_encode($checkout_info);
            $order->status = 'pending';
            $order->agent_user_id = $this->Auth->user('id');
            if ($this->Orders->save($order)) {
              $this->request->getSession()->write('order_id',$order->id);
              if (!empty($multiple_redemption)) {
                $this->request->getSession()->write('multiple_redemption',$multiple_redemption);
              }
              return $this->redirect(['action'=>'confirmation']);
            }
            else {
              // debug($order->getErrors());
              $this->Flash->error('There is an error in the submitted data. Please check and try again.');
            }
          }
        }

        // if (empty($order->getErrors())) {
        //   $this->request->getSession()->write('order',$order);
        //   return $this->redirect(['action'=>'checkout2']);
        // }
      }
      // $this->loadModel('Agents');
      // $agent = $this->Agents->find()->where(['id'=>$this->Auth->user('agent_id')])->first();
      $agent = $this->Auth->user();

      $this->set(compact('order','agent'));
    }

    private function _updateOrderPrice($order,$tickets) {
      $order->subtotal = 0;
      if (!empty($tickets)) {
        foreach ($tickets as $key => $ticket) {
          if (!empty($order->order_items) && !empty($ticket)) {
            foreach ($order->order_items as $key => $order_item) {
              if ($order_item->ticket_id == $ticket->id) {
                $order_item->price = $ticket->price;
                $order_item->total = $ticket->price * $order_item->qty;
                $order->subtotal = $order_item->total;
              }
            }
          }
        }
      }
      return $order;
    }

    public function confirmation() {
      if ($this->request->is(['post','put','patch'])) {
        $sorted_data = $this->request->getData();
        sort($sorted_data);
        $request_nonce = md5(json_encode($sorted_data).date('YmdHis').$this->request->getParam('_csrfToken'));
        if ($this->request->getSession()->read('request_nonce') == $request_nonce) {
          return $this->redirect($this->request->getRequestTarget());
        } else {
          $this->request->getSession()->delete('request_nonce');
        }
        $this->request->getSession()->write('request_nonce',$request_nonce);
      }

      if (!$this->request->getSession()->check('order_id')) {
        return $this->redirect(['controller'=>'Attractions','action'=>'index']);
      }
      $order = $this->Orders->find()->contain(['OrderItems'=>function($q) {
        return $q->contain(['Vouchers','Tickets'=>['Attractions']])->order(['OrderItems.id'=>'ASC']);
      }])->where(['id'=>$this->request->getSession()->read('order_id')])->epilog('FOR UPDATE')->first();
      if (!$order) {
        return $this->redirect(['controller'=>'Attractions','action'=>'index']);
      }
      if (!$this->_xcheckOrderCart($order)) {
        $this->Flash->error('Your shopping cart has change! Please re-enter the information needed.');
        return $this->redirect(['action'=>'checkout']);
      }
      $total = 0;
      if ($this->request->is(['post','put','patch','ajax'])) {
        if ($this->request->getData('pay_by')) {

          // $order = $this->Orders->patchEntity($order,$this->request->getData());
          $order->agent_user_id = $this->Auth->user('id');
          if ($this->request->getData('pay_by') == 'credits') {
            $order->convinience_fee = 0;
            $order->total = $order->subtotal;
            if (!$this->_xcheckOrderCart($order)) {
              $this->Flash->error('Your shopping cart has change! Please re-enter the information needed.');
              return $this->redirect(['action'=>'checkout']);
            }
            if (!$this->_checkAvailableVouchers($order)) {
              $this->Flash->error('No vouchers available this time. Please contact admin.');
              $order->setError('voucher','No vouchers available this time. Please contact admin.');
              return $this->redirect(['action'=>'confirmation']);
            }

            $transaction_done = false;
            $attraction_tickets = [];
            $agent = null;
            $order->api_vouchers = [];
            $error_code = null;
            $error_description = null;
            try {
            
              $transaction_done = $this->Orders->getConnection()->transactional(function() use ($order,$attraction_tickets,$error_code,$error_description) {
                if ($order->status == 'pending') {
                  if ($this->_deductCredits($order)) {
                    $order->payment_method = 'credits';
                    $order->status = 'done';
                    if ($this->Orders->save($order)) {
                      $createVoucher = $this->_createVouchers($order);
                      if ($createVoucher !== true) {
                        $error_code = 5100;
                        $error_description = 'Unable to allocate voucher codes.';
                        $this->log(json_encode(['exception'=>$createVoucher,'order_ref_id'=>$order->order_ref_id]),'error');
                        return $error_code;
                      }
                      $external_purchase = $this->_externalPurchase($order);
                      if (!is_array($external_purchase) && $external_purchase !== true) {
                        if ($external_purchase != '8004') {
                          return false;
                        } else { // set order status to in progress and dont send email
                          $order->status = 'in_progress';
                        }
                      } else {
                        $order->api_vouchers = $external_purchase;
                      }

                      return true;
                    }
                    else {
                      $this->Flash->error('Error saving order');
                      throw new Exception("Error saving order");
                    }
                  } else {
                    $this->Flash->error('Error deducting credits');
                    throw new Exception("Unable to deduct credits");
                  }
                } else {
                  return true;
                }
              });

              if ($transaction_done) {
                $agent = $this->Agents->find()->where(['id'=>$this->Auth->user('agent_id')])->first();
                if ($agent) {
                  $auth_user = $this->Auth->user();
                  $auth_user['credits'] = $agent->credits;
                  $this->Auth->setUser($auth_user);
                }
              }
            } catch (Exception $e) {
              $this->Flash->error('There is an error processing this order. Please contact admin and provide error code 5101.');
              $this->log(json_encode(['exception'=>$e->getMessage(),'order_ref_id'=>$order->order_ref_id]),'error');
              $this->Mailer->mail('Order','exception_email','Exception Occured',Configure::read('Default.admin_email'),['exception'=>$e->getMessage(),'cause'=>'Order '.$order->order_ref_id],null,Configure::read('Default.exception_emails'));
            }

            if ($transaction_done) {
              /* send customer instant confirmation tickets --- originally this is inside transactional */
              try {
                $ticket_vouchers = !empty($order->ticket_vouchers) ? $order->ticket_vouchers : [];
                if (!empty($order->api_vouchers)) {
                  $ticket_vouchers = array_merge($ticket_vouchers,$order->api_vouchers);
                }
                /* Only send email to customer if status is done */
                if ($order->status == 'done') {
                  $this->_emailCustomer($order,$ticket_vouchers,$agent);
                }
              } catch (Exception $e) {
                $this->Flash->error('Please check your email for the tickets. If you do not receive the email, please notify the administrator.');
                $this->log($e,'error');
              }
              // if (!empty($order->ticket_vouchers)) {
              //    $this->log($order->ticket_vouchers,'debug');
              // }
              
              foreach ($order->order_items as $key => $order_item) {
                if (empty($attraction_tickets[$order_item->ticket->attraction->id])) {
                  $attraction_tickets[$order_item->ticket->attraction->id] = ['tickets'=>[],'attraction'=>$order_item->ticket->attraction];
                }
                $attraction_tickets[$order_item->ticket->attraction->id]['tickets'][] = $order_item;
              }
              if (!empty($attraction_tickets)) {
                foreach ($attraction_tickets as $key => $attraction_ticket) {
                  foreach ($attraction_ticket['tickets'] as $key => $_ticket) {
                    $this->Orders->OrderItems->loadInto($attraction_ticket['tickets'][$key],['Vouchers']);
                  }
                  try {
                    $this->_emailBooking($order,$attraction_ticket);
                  } catch (Exception $e) {
                    $this->log($e,'error');
                  }
                }
              }

              $this->request->getSession()->delete('order');
              // $this->request->getSession()->delete('multiple_redemption');
              $this->ShoppingCart->clear();
              return $this->redirect(['action'=>'thankYou']);
            } else {
              if ($error_code) {
                $this->Flash->error('Unable to complete purchase. Please contact admin and provide error code '.$error_code.'.');
              } else {
                $this->Flash->error('Unable to complete purchase.');
              }
              
            }
            
          }
          else {
            if ($this->request->getData('pay_by') == 'credit_card') {
              return $this->redirect(['action'=>'executeStripe']);
            }
            if ($this->request->is('ajax')) {
              $this->autoRender = false;
              if ($this->Orders->save($order)) {
                return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>200,'data'=>['order_id'=>$order->id]]));
              }
              else {
                return $this->response->withStatus(400)->withStringBody(json_encode(['status'=>200,'data'=>['errors'=>$order->getErrors()]]));
              }
            }
            else {
              // debug($this->request->getData());
              $this->request->getSession()->delete('order');
              $this->ShoppingCart->clear();
              return $this->redirect(['action'=>'thankYou']);
            }
          }
        }
        $this->request->getSession()->write('order_id',$order->id);
      }

      $this->set(compact('order'));
    }

    public function executeStripe() {
      $this->viewBuilder()->setlayout('empty');
      $order = $this->Orders->find()->contain(['OrderItems'=>['Tickets'=>['Attractions']]])->where(['id'=>$this->request->getSession()->read('order_id')])->first();
        if (!$order) {
          $this->Flash->error('Order not found!');
          return $this->redirect(['/']);
        }

      $line_items = [];

      $subtotal = 0;
      if (!empty($order->order_items)) {
        foreach ($order->order_items as $key => $order_item) {
          $line_items[] = [
            'name' => $order_item->ticket->attraction->title. ' '.$order_item->ticket->name . ($order_item->ticket->attraction->type =='transfer' ? ' - '.Configure::read('Default.transfer_subtypes.'.$order_item->ticket->attraction->subtype) : ''),
            'description' => $order_item->ticket->sku_id,
            'amount' => round($order_item->price * 100),
            'currency' => 'sgd',
            'quantity' => $order_item->qty,
          ];
          $subtotal += $order_item->total;
        }
      }

      // $convinience_fee  =  ceil($order->total * 3.5 + 100); //stripe requires sending in cents
      $convinience_fee  =  ceil($subtotal * 3.5 + 100); //stripe requires sending in cents
      $order->convinience_fee = round(($convinience_fee / 100),2,PHP_ROUND_HALF_UP);
      $order->total = $subtotal + $order->convinience_fee;
      if ($order->convinience_fee > 0) {
        $line_items[] = [
            'name' =>'Convenience Fee',
            'amount' => $convinience_fee,
            'currency' => 'sgd',
            'quantity' => 1,
          ];
      }
      \Stripe\Stripe::setApiKey(Configure::read('Stripe.secret_key'));

      $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'success_url' => Router::url(['action'=>'thankYou'],['fullBase'=>true]),
        'cancel_url' => Router::url(['action'=>'confirmation'],['fullBase'=>true]),
      ]);
      if ($session) {
        $order->pi_id = $session->id;
        $order->payment_method = 'credit_card';
        if ($this->Orders->save($order)) {
          $session_id = $session->id;
          $this->set('session_id',$session_id);
          $this->set(compact('order'));
        } else {
          $this->Flash->error('Cannot complete order.');
        return $this->redirect(['action'=>'confirmation']);
        }
        
      }
      else {
        $this->Flash->error('Failed to create stripe payment session.');
        return $this->redirect(['action'=>'confirmation']);
      }
    }

    private function _getcsid() {
      $order = $this->Orders->find()->contain(['OrderItems'=>['Tickets'=>['Attractions']]])->where(['id'=>$this->request->getSession()->read('order_id')])->first();
      if (!$order) {
        return null;
      }
      if (!$this->_checkAvailableVouchers($order)) {
        $order->setError('voucher','No vouchers available this time. Please contact admin.');
        $this->Flash->error('No vouchers available this time. Please contact admin.');
        return null;
      }
      if (!$this->_xcheckOrderCart($order)) {
        $this->Flash->error('Your shopping cart has change! Please re-enter the information needed.');
        $order->setError('order_items','Your shopping cart has change! Please re-enter the information needed.');
        return null;
      }
      if (!empty($order->getErrors())) {
        return null;
      }
      $line_items = [];

      $subtotal = 0;
      if (!empty($order->order_items)) {
        foreach ($order->order_items as $key => $order_item) {
          $line_items[] = [
            'name' => $order_item->ticket->attraction->title. ' '.$order_item->ticket->name . ($order_item->ticket->attraction->type =='transfer' ? ' - '.Configure::read('Default.transfer_subtypes.'.$order_item->ticket->attraction->subtype) : ''),
            'description' => $order_item->ticket->sku_id,
            'amount' => round($order_item->price * 100),
            'currency' => 'sgd',
            'quantity' => $order_item->qty,
          ];
          $subtotal += $order_item->total;
        }
      }

      // $convinience_fee  =  ceil($order->total * 3.5 + 100); //stripe requires sending in cents
      $convinience_fee  =  ceil($subtotal * 3.5 + 100); //stripe requires sending in cents
      $order->convinience_fee = round(($convinience_fee / 100),2,PHP_ROUND_HALF_UP);
      $order->total = $subtotal + $order->convinience_fee;
      if ($order->convinience_fee > 0) {
        $line_items[] = [
            'name' =>'Convenience Fee',
            'amount' => $convinience_fee,
            'currency' => 'sgd',
            'quantity' => 1,
          ];
      }
      \Stripe\Stripe::setApiKey(Configure::read('Stripe.secret_key'));

      $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'success_url' => Router::url(['action'=>'thankYou'],['fullBase'=>true]),
        'cancel_url' => Router::url(['action'=>'confirmation'],['fullBase'=>true]),
      ]);
      if ($session) {
        $order->pi_id = $session->id;
        $order->payment_method = 'credit_card';
        if ($this->Orders->save($order)) {
          return $order->pi_id;
        }
        return null;
      }
      else {
        return null;
      }
    }

    public function getcsid() {
      $order = $this->Orders->find()->contain(['OrderItems'=>['Tickets'=>['Attractions']]])->where(['id'=>$this->request->getSession()->read('order_id')])->first();
      if (!$order) {
        return $this->response->withStatus(404);
      }
      if (!$this->_checkAvailableVouchers($order)) {
        $order->setError('voucher','No vouchers available this time. Please contact admin.');
        return $this->response->withStatus(400)->withStringBody(json_encode(['status'=>400,'message'=>'Not enough vouchers. Please contact administrator.']));
      }
      if (!$this->_xcheckOrderCart($order)) {
        $this->Flash->error('Your shopping cart has change! Please re-enter the information needed.');
        $order->setError('order_items','Your shopping cart has change! Please re-enter the information needed.');
        return $this->response->withStatus(400)->withStringBody(json_encode(['status'=>400,'message'=>'Cart changed.']));
      }
      if (!empty($order->getErrors())) {
        return $this->response->withStatus(400)->withStringBody(json_encode(['status'=>400,'message'=>'Invalid order.']));
      }
      $line_items = [];

      $subtotal = 0;
      if (!empty($order->order_items)) {
        foreach ($order->order_items as $key => $order_item) {
          $line_items[] = [
            'name' => $order_item->ticket->attraction->title. ' '.$order_item->ticket->name . ($order_item->ticket->attraction->type =='transfer' ? ' - '.Configure::read('Default.transfer_subtypes.'.$order_item->ticket->attraction->subtype) : ''),
            'description' => $order_item->ticket->sku_id,
            'amount' => round($order_item->price * 100),
            'currency' => 'sgd',
            'quantity' => $order_item->qty,
          ];
          $subtotal += $order_item->total;
        }
      }

      // $convinience_fee  =  ceil($order->total * 3.5 + 100); //stripe requires sending in cents
      $convinience_fee  =  ceil($subtotal * 3.5 + 100); //stripe requires sending in cents
      $order->convinience_fee = round(($convinience_fee / 100),2,PHP_ROUND_HALF_UP);
      $order->total = $subtotal + $order->convinience_fee;
      if ($order->convinience_fee > 0) {
        $line_items[] = [
            'name' =>'Convenience Fee',
            'amount' => $convinience_fee,
            'currency' => 'sgd',
            'quantity' => 1,
          ];
      }
      \Stripe\Stripe::setApiKey(Configure::read('Stripe.secret_key'));

      $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'success_url' => Router::url(['action'=>'thankYou'],['fullBase'=>true]),
        'cancel_url' => Router::url(['action'=>'confirmation'],['fullBase'=>true]),
      ]);
      if ($session) {
        $order->pi_id = $session->id;
        $order->payment_method = 'credit_card';
        if ($this->Orders->save($order)) {
          return $this->response->withStringBody(json_encode(['status'=>200,'data'=>$session->id]))->withStatus(200);
        }
        return $this->response->withStatus(402);
      }
      else {
        return $this->response->withStatus(400);
      }
    }

    public function thankYou() {
      $order_id = $this->request->getSession()->read('order_id');
      $order = $this->Orders->find()->where(['Orders.id'=>$order_id])->contain(['OrderItems'=>['Tickets'=>['Attractions'],'Vouchers'],'AgentUsers'=>['Agents']])->first();
      $ticket_list = $this->ShoppingCart->getAll();
      $total = 0;
      if (!empty($ticket_list)) {
        $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'])->toArray();
        foreach ($tickets as $key => $ticket) {
          if ($ticket->attraction->pricing_type == 'settlement') {
            $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$ticket->attraction,$this->Auth->user());
          }
          else {
            $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->Auth->user());
          }
        }

        $this->set(compact('tickets','ticket_list'));
      }
      $this->request->getSession()->delete('order_id');
      $this->request->getSession()->delete('order');
      $this->request->getSession()->delete('multiple_redemption');
              $this->ShoppingCart->clear();
      if ($order && $order->status == 'done') {
        $this->set(compact('order'));
      }
    }

    public function sps() {
     // stripe payment succeeded
        // secret key in dashboard
      \Stripe\Stripe::setApiKey(Configure::read('Stripe.secret_key'));

      $endpoint_secret = Configure::read('Stripe.signing_secret'); // signing secret in dashboard

      $payload = @file_get_contents('php://input');
      $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
      $event = null;
      try {
          $event = \Stripe\Webhook::constructEvent(
              $payload, $sig_header, $endpoint_secret
          );
      } catch(\UnexpectedValueException $e) {
          // Invalid payload
          return $this->response->withStatus(400);
      } catch(\Stripe\Exception\SignatureVerificationException $e) {
          // Invalid signature
          return $this->response->withStatus(400);
      }
      $this->loadModel('StripeRecords');
      $stripe_record = $this->StripeRecords->newEntity(['data'=>json_encode($event),'pi_id'=>!empty($event->data->object) ? $event->data->object->id : null]);
      $this->StripeRecords->save($stripe_record);
      // Handle the event
      switch ($event->type) {
          // case 'payment_intent.succeeded':
          //     $paymentIntent = $event->data->object; // contains a StripePaymentIntent
          //     $this->_handlePaymentIntentSucceeded($paymentIntent);
          //     break;
          // case 'payment_method.attached':
          //     $paymentMethod = $event->data->object; // contains a StripePaymentMethod
          //     $this->log($paymentMethod,'debug');
          //     // handlePaymentMethodAttached($paymentMethod);
          //     break;
          case 'checkout.session.completed':
              $session = $event->data->object; // contains a StripePaymentIntent
              $this->_handlePaymentIntentSucceeded($session);
              break;
          // ... handle other event types
          default:
              // Unexpected event type
              return $this->response->withStatus(400);
      }


      return $this->response->withStatus(200);
    }


    private function _handlePaymentIntentSucceeded($session) {
      $order = $this->Orders->find()->where(['pi_id'=>$session->id])
        ->contain(['OrderItems'=>['Tickets'=>['Attractions']]]);

      $this->log($order,'debug');
      $this->log($session->id,'debug');
      $order = $order->epilog('FOR UPDATE')->first();
      $total_paid = 0;
      if (!empty($session->display_items)) {
        foreach ($session->display_items as $key => $display_item) {
          $total_paid += $display_item->amount * $display_item->quantity;
        }
      }
      $total_paid = $total_paid /100;
      if ($order && $total_paid != $order->total) {
        if ($order) {
          $this->Mailer->mail('Order','payment_discrepancy','Payment Amount Discrepancy',Configure::read('Default.cebu_air_email'),['total_paid'=>$total_paid,'order'=>$order]);
          $this->log('Payment Discrepancy '.$total_paid .'!='. $order->total. ' ' .json_encode($session),'info');
          return;
        }
      }
      if ($order && $order->status != 'done') {
        $order->status = 'done';
        if ($this->_checkAvailableVouchers($order)) {
          try {
            $transaction_done = $this->Orders->getConnection()->transactional(function() use ($order) {
              if ($this->Orders->save($order)) {
                if (!$this->_createVouchers($order)) {
                  return false;
                }
                $external_purchase = $this->_externalPurchase($order);
                if (!is_array($external_purchase) && $external_purchase !== true) {
                  if ($external_purchase != 8004) {
                    return false;
                  } 
                } else {
                  $order->api_vouchers = $external_purchase;
                }
                $attraction_tickets = [];
                foreach ($order->order_items as $key => $order_item) {
                  if (empty($attraction_tickets[$order_item->ticket->attraction->id])) {
                    $attraction_tickets[$order_item->ticket->attraction->id] = ['tickets'=>[],'attraction'=>$order_item->ticket->attraction];
                  }
                  $attraction_tickets[$order_item->ticket->attraction->id]['tickets'][] = $order_item;
                }
                /*$ticket_vouchers = !empty($order->ticket_vouchers) ? $order->ticket_vouchers : [];
                if (!empty($order->api_vouchers)) {
                  $ticket_vouchers = array_merge($ticket_vouchers,$order->api_vouchers);
                }*/
                if (!empty($attraction_tickets)) {
                  foreach ($attraction_tickets as $key => $attraction_ticket) {
                    foreach ($attraction_ticket['tickets'] as $key => $_ticket) {
                      $this->Orders->OrderItems->loadInto($attraction_ticket['tickets'][$key],['Vouchers']);
                    }
                    try {
                      $this->_emailBooking($order,$attraction_ticket);
                    } catch (Exception $e) {
                      $this->log('Email Booking Error','debug');
                      $this->log($e,'debug');
                    }
                  }
                }
                return true;
              }
              else {
                $this->log($order->getErrors(),'debug');
              }
            });
          } catch (Exception $e) {
            $order->status =  'rejected';
            $this->Orders->save($order);
            $ex_no = \Cake\Utility\Text::uuid();
            $this->Mailer->mail('Order','credit_card_failed','Stripe Payment Failed',Configure::read('Default.cebu_air_email'),['order'=>$order,'ex_no'=>$ex_no,'order_total'=>$order->total],null,null);
            $this->log($ex_no,'debug');
            $this->log($e,'debug');
          }
        } else {
          $order->status =  'rejected';
          $this->Orders->save($order);
          $this->Mailer->mail('Order','not_enough_vouchers','Not Enough Vouchers',Configure::read('Default.cebu_air_email'),['order'=>$order],null,null);
          $this->log('Not enough vouchers','debug');
        }
      }
    }

    public function ipn(){
      $this->autoRender = false;
      if ($this->request->is('post')) {
        $DEBUG = 0;
        // Set to 0 once you're ready to go live
        $USE_SANDBOX = Configure::read('Settings.paypal_sandbox');
        // Read POST data
        // reading posted data directly from $_POST causes serialization
        // issues with array data in POST. Reading raw POST data from input stream instead.
        $raw_post_data = file_get_contents('php://input');
        $this->loadModel('PaypalRecords');
        $pr =$this->PaypalRecords->newEntity();
        $pr->data = htmlEntities($raw_post_data, ENT_QUOTES);
        $this->PaypalRecords->save($pr);

        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
          $keyval = explode('=', $keyval);
          if (count($keyval) == 2)
            $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
          $get_magic_quotes_exists = true;
        }
        foreach ($myPost as $key => $value) {
          if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
            $value = urlencode(stripslashes($value));
          } else {
            $value = urlencode($value);
          }
          $req .= "&$key=$value";
        }
        // Post IPN data back to PayPal to validate the IPN data is genuine
        // Without this step anyone can fake IPN data
        if ($USE_SANDBOX == true) {
          $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        } else {
          $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
        }
        $ch = curl_init($paypal_url);
        if ($ch == FALSE) {
          return FALSE;
        }
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        if ($DEBUG == true) {
          curl_setopt($ch, CURLOPT_HEADER, 1);
          curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        }
        // CONFIG: Optional proxy configuration
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        // Set TCP timeout to 30 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        // CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
        // of the certificate as shown below. Ensure the file is readable by the webserver.
        // This is mandatory for some environments.
        //$cert = __DIR__ . "./cacert.pem";
        //curl_setopt($ch, CURLOPT_CAINFO, $cert);
        $res = curl_exec($ch);
        /* if (curl_errno($ch) != 0) // cURL error
          {
          if($DEBUG == true) {
          // error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
          }
          curl_close($ch);
          exit;
          } else {
          // Log the entire HTTP response if debug is switched on.
          if($DEBUG == true) {
          // error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
          // error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
          }
          curl_close($ch);
          } */
        // Inspect IPN validation result and act accordingly
        // Split response headers and payload, a better way for strcmp
        $tokens = explode("\r\n\r\n", trim($res));
        $res = trim(end($tokens));
        if (strcmp($res, "VERIFIED") == 0) {
          // check whether the payment_status is Completed
          // check that txn_id has not been previously processed
          // check that receiver_email is your PayPal email
          // check that payment_amount/payment_currency are correct
          // process payment and mark item as paid.
          // assign posted variables to local variables
          $item_number = $_POST['item_number'];
          $payment_status = $_POST['payment_status'];
          $payment_amount = $_POST['mc_gross'];
          $payment_currency = $_POST['mc_currency'];
          $txn_id = $_POST['txn_id'];
          $content = '';
          if ($payment_currency == 'SGD' && $payment_status == 'Completed') {
            $options = ['id' => $item_number];
            $order = $this->Orders->find()
                    ->where($options)
                    ->contain(['OrderItems'=>['Tickets']])
                    ->first();
            $content .= $item_number . ' ';
            $content .= 'Completed ';
            $addon = $order->addon;
            if ($order != null) {
              $fee = $order->total;
              $content .= 'txn exist ';
              if ($payment_amount == $fee) {
                $content .= $fee . ' ';
                $order->status = 'done';
                $pr->txn_id = $txn_id;
                $pr->order_id = $order->id;
                $this->PaypalRecords->save($pr);
                $transaction_done = $this->Orders->getConnection()->transactional(function() use ($order) {
                  if ($this->Orders->save($order)) {
                    if (!$this->_createVouchers($order)) {
                      return false;
                    }
                    return true;
                  }
                  else {
                    //order not saved
                  }
                });
              }
            }
          }
          //$receiver_email = $_POST['receiver_email'];
          //$payer_email = $_POST['payer_email'];
          if ($DEBUG == true) {
              // error_log(date('[Y-m-d H:i e] '). "Verified IPN: $req ". PHP_EOL, 3, LOG_FILE);
          }
        } else if (strcmp($res, "INVALID") == 0) {
          // log for manual investigation
          // Add business logic here which deals with invalid IPN messages
          if ($DEBUG == true) {
              // error_log(date('[Y-m-d H:i e] '). "Invalid IPN: $req" . PHP_EOL, 3, LOG_FILE);
          }
        }
        // Delete Registration Session Data
        echo 'end';
        exit;
      } else {
          return $this->redirect(array('controller' => 'sites', 'action' => 'edit'));
      }
      return;
    }

    private function _deductCredits($order) { //transactional
      // codes to deduct credit to agent
      if ($this->Auth->user()) {
        $agent = $this->Agents->find()->where(['id'=>$this->Auth->user('agent_id')])->epilog('FOR UPDATE')->first();
        if (!$agent) {
          $this->Flash->error('Login to use credits to pay.');
          throw new Exception("Unable to deduct credits");
        }
        if ($agent->credits >= $order->total) {
          $agent->credits -= $order->total;
          if ($this->Agents->save($agent)) {
            if ($this->CreditLogs->credit($agent->id,$order)) {
              return true;
            }
            else {
              $this->Flash->error('There was an error deducting the credits to your account. Please contact administrator.');
              throw new Exception("Unable to deduct credits");
            }
            // to confirm if agent_user should be registerd to credit logs
          }
          else {
            $this->Flash->error('There was an error deducting the credits to your account. Please contact administrator.');
            throw new Exception("Unable to deduct credits");
          }
        }
        else {
          $this->Flash->error('You only have '.$agent->credits.' left. Please top up credits or choose another payment method.');
          throw new Exception("Unable to deduct credits");
        }
      }
      else {
        $this->Flash->message('Please login to use credits to pay.');
        throw new Exception("Unable to deduct credits");
      }
      throw new Exception("Unable to deduct credits");
    }

    private function _checkAvailableVouchers($order = null, $ticket_list = null) {
      $is_voucher_enough = true;
      $now = Time::now();
      $total_pdf_needed = [];
      $total_barcode_needed = [];
      $total_gc_needed = [];
      if ($order && $order->order_items) {
        foreach ($order->order_items as $key => $order_item) {
          $ticket = $this->Tickets->find()->where(['Tickets.id'=>$order_item->ticket_id])->contain(['Attractions','ChildTickets'=>['Attractions']])->first();

          if ($ticket) {
            $_tickets = [$ticket];
            if (!empty($ticket->child_tickets)) {
              $_tickets = $ticket->child_tickets;
            }
            foreach ($_tickets as $key => $_ticket) {
              if ($_ticket->voucher_type =='pdf') {
                if (!isset($total_pdf_needed[$_ticket->id])) {
                  $total_pdf_needed[$_ticket->id] = 0;
                }
                $total_pdf_needed[$_ticket->id] += $order_item->qty;
                $pdf_count = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now]);
                $pdf_count = $pdf_count->count();
                if ($pdf_count < $total_pdf_needed[$_ticket->id]) {
                  $is_voucher_enough = false;
                }
              }
              elseif ($_ticket->voucher_type =='barcode') {
                if (!isset($total_barcode_needed[$_ticket->id])) {
                  $total_barcode_needed[$_ticket->id] = 0;
                }
                $total_barcode_needed[$_ticket->id] += $order_item->qty;
                $barcode_count = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$_ticket->sku_id,'status'=>'new','validity_start_date <='=>$now,'validity_end_date >='=>$now ])->count();
                if ($barcode_count < $total_barcode_needed[$_ticket->id]) {
                  $is_voucher_enough = false;
                }
              }
              elseif ($_ticket->voucher_type =='generated') {
                if (!isset($total_gc_needed[$_ticket->id])) {
                  $total_gc_needed[$_ticket->id] = 0;
                }
                $total_gc_needed[$_ticket->id] += $order_item->qty;
                $gc__ticket = $this->Tickets->find()->where(['id'=>$_ticket->id])->first();
                if ($_ticket->stock !== null && $_ticket->stock < $total_gc_needed[$_ticket->id]) {
                  $this->log($_ticket->sku_id,'debug');
                  $is_voucher_enough = false;
                }
              }
            } //end foreach
          }
          else {
            $is_voucher_enough = false;
          }
        }
      }
      elseif (!empty($ticket_list)) {
        foreach ($ticket_list as $ticket_id => $date_qty) {
          $ticket_total_qty = 0;
          foreach ($date_qty as $date => $qty) {
            $ticket_total_qty += $qty;
          }
          $ticket = $this->Tickets->find()->where(['Tickets.id'=>$ticket_id])->contain(['Attractions','ChildTickets'=>['Attractions']])->first();

          if ($ticket) {
            $_tickets = [$ticket];
            if (!empty($ticket->child_tickets)) {
              $_tickets = $ticket->child_tickets;
            }
            foreach ($_tickets as $key => $_ticket) {
              if ($_ticket->voucher_type =='pdf') {
                if (!isset($total_pdf_needed[$_ticket->id])) {
                  $total_pdf_needed[$_ticket->id] = 0;
                }
                $total_pdf_needed[$_ticket->id] += $ticket_total_qty;
                $pdf_count = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now])->count();
                if ($pdf_count < $total_pdf_needed[$_ticket->id]) {
                  $this->Flash->error('Not enough stock for '.$ticket->attraction->title.' - '.$ticket->name.'. Only '.$pdf_count.' left. Please call administrator. ' . Configure::read('Default.admin_contact'));
                  $is_voucher_enough = false;
                }
              }
              elseif ($_ticket->voucher_type =='barcode') {
                if (!isset($total_barcode_needed[$_ticket->id])) {
                  $total_barcode_needed[$_ticket->id] = 0;
                }
                $total_barcode_needed[$_ticket->id] += $ticket_total_qty;
                $barcode_count = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$_ticket->sku_id,'status'=>'new','validity_start_date <='=>$now,'validity_end_date >='=>$now ])->count();
                if ($barcode_count < $total_barcode_needed[$_ticket->id]) {
                  $this->Flash->error('Not enough stock for '.$ticket->attraction->title.' - '.$ticket->name.'. Only '.$barcode_count.' left. Please call administrator. ' . Configure::read('Default.admin_contact'));
                  $is_voucher_enough = false;
                }
              }
              elseif ($_ticket->voucher_type =='generated') {
                if (!isset($total_gc_needed[$_ticket->id])) {
                  $total_gc_needed[$_ticket->id] = 0;
                }
                $total_gc_needed[$_ticket->id] += $ticket_total_qty;
                $gc__ticket = $this->Tickets->find()->where(['id'=>$_ticket->id])->first();
                if ($_ticket->stock !== null && $_ticket->stock < $total_gc_needed[$_ticket->id]) {
                  $this->Flash->error('Not enough stock for '.$ticket->attraction->title.' - '.$ticket->name.'. Only '.($_ticket->stock).' left. Please call administrator. ' . Configure::read('Default.admin_contact'));
                  $is_voucher_enough = false;
                }
              }
            } //end foreach
          }
          else {
            $is_voucher_enough = false;
          }
        }
      }
      return $is_voucher_enough;
    }

    private function _createVouchers($order) {
      $now = Time::now();
      if ($order && $order->order_items) {
        $ticket_vouchers = [];
        $non_instant_vouchers = [];
        $api_purchases = 0;
        foreach ($order->order_items as $key => $order_item) {
          if ($order_item->ticket->api_purchase) {
            $api_purchases++;
            continue;
          }
          $order_item_data = json_decode($order_item->order_data);
          $ticket_info = json_decode($order_item->ticket_info,true);
          $required_info = !empty($order_item_data->required_info) ? $order_item_data->required_info : [];
            $vouchers = [];
            $ticket = $this->Tickets->find()->where(['Tickets.id'=>$order_item->ticket_id])->contain(['Attractions','ChildTickets'=>['Attractions']])->first();
            $flexi_options = null;
            if (!empty($ticket->flexi_options)) {
              $flexi_options = json_decode($ticket->flexi_options);
            }

            // Ensure ticket quantity is sufficient
            if ($this->Tickets->consume($ticket->id,$ticket->voucher_type,$ticket->attraction->pricing_type,$ticket->stock,$order_item->qty,$order_item->date_of_visit,$order_item->time_of_visit) !== true) {
              return 'Ticket insuficient';
            }

            for ($i = 0 ; $i< $order_item->qty; $i++) {
              /*BEGIN PACKAGE TICKETS*/
              if ($ticket && !empty($ticket->child_tickets)) {
                $package_voucher = $this->Vouchers->newEntity();
                $package_voucher->ticket_name = $ticket->name;
                $package_voucher->order_item_id = $order_item->id;
                $len = 10;
                $code = $this->_getRandomGC($ticket->attraction,$ticket,$len,true);
                while($this->Vouchers->find()->where(['code_id'=>$code])->count() && $ctr++ < 1000) {
                    $code = $this->_getRandomGC($ticket->attraction,$ticket,$len,true);
                }
                $pk_generated_code = $this->GeneratedCodes->newEntity(['code'=>$code,'ticket_id'=>$ticket->id,'status'=>'purchased','redeemable'=>1]);
                
                $pk_generated_code->redeemable = count($ticket->child_tickets);
                $pk_generated_code->sold = true;
                if (!empty($ticket->flexi_options)) {
                  if ($flexi_options) {
                    $pk_generated_code->redeemable = $flexi_options->choose;
                  }
                }
                $package_voucher->voucher_type = "generated";
                $package_voucher->code_id = $code;
                $package_code = $code;
                $ticket_overwrites_total = 0;
                $package_voucher->created = Time::now();
                if (!$this->GeneratedCodes->save($pk_generated_code)) {
                  return "Error saving generated code";
                }

                foreach ($ticket->child_tickets as $key => $child_ticket) {
                  if ($child_ticket->api_purchase) {
                    $api_purchases++;
                    continue;
                  }
                  $voucher = $this->Vouchers->newEntity();
                  $voucher->created = Time::now();
                  $voucher->ticket_name = $ticket->name;
                  $voucher->order_item_id = $order_item->id;
                  // $child_ticket->quantity_sold += 1;
                  /* deduct for package tickets  to confirm for flexi */
                  if ($this->Tickets->consume($child_ticket->id,$child_ticket->voucher_type,$child_ticket->attraction->pricing_type,$child_ticket->stock,1,$order_item->date_of_visit,$order_item->time_of_visit) !== true) {
                    return 'Ticket insuficient';
                  }
                  if (!$this->Tickets->Inventories->purchase($child_ticket->id,1,$order->agent_user_id,$order->id)) {
                    return 'Error saving inventory';
                  }
                  /* end deduct for package tickets */
                  if ($child_ticket->voucher_type == 'generated' || $child_ticket->voucher_type == null) {
                    /*GENERATE CODE*/
                    
                    $len = 10;
                    $code = $this->_getRandomGC($ticket->attraction,$ticket,$len);
                    if (!$child_ticket->generated_code) {
                      $ctr = 0;
                      while($this->Vouchers->find()->where(['code_id'=>$code])->count() && $ctr++ < 1000) {
                          $code = $this->_getRandomGC($ticket->attraction,$ticket,$len);
                      }
                      $generated_code = $this->GeneratedCodes->newEntity(['code'=>$code,'ticket_id'=>$child_ticket->id,'status'=>'purchased','redeemable'=>1,'ticket_sku_id'=>$child_ticket->sku_id]);
                      // if ($ticket->multiple_redemption) {

                      //   if (!empty($order_item_data->multiple_redemption)) {
                      //     $generated_code->redeemable = $order_item->qty;
                      //     $ticket->generated_code = $generated_code;
                      //   }
                      // }

                      // if (!empty($ticket->flexi_options)) {
                      //   $flexi_options = json_decode($ticket->flexi_options);
                      //   if ($flexi_options) {
                      //     $generated_code->redeemable = $flexi_options->choose;
                      //   }
                      // }
                      $voucher->voucher_type = "generated";
                      $voucher->generated_code = $generated_code;
                      $voucher->code_id = $code;
                      $generated_code->package_code = $package_code;
                      $generated_code->sold = true;
                      if (empty($ticket->flexi_options)) { //if flexi dont give out the individual voucher
                        $voucher->ticket_name = $child_ticket->name;
                        if ($ticket->single_pdf) {
                          $voucher->single_pdf = $order_item->id.'_'.$i;
                          $voucher->order_qty_idx = $i;
                        }
                        if ($ticket->instant_confirmation) {
                          $ticket_vouchers[] = $voucher;
                        } else {
                          $non_instant_vouchers[] = $voucher;
                        }
                        $vouchers[] = $voucher;
                      }
                      if (!$this->GeneratedCodes->save($generated_code)) {
                        return "Error saving generated code";
                      }
                      if (empty($ticket->flexi_options)) {
                        if (!empty($required_info) && !empty($required_info[$i])) {
                          try {
                            $this->_createPdf($order,$voucher,$child_ticket,$order_item,$required_info[$i]);
                          } catch (Exception $e) {
                            $this->_handleException($e);
                          }
                        }
                        else {
                          try {
                            $this->_createPdf($order,$voucher,$child_ticket,$order_item);
                          } catch (Exception $e) {
                            $this->_handleException($e);
                          }
                        }
                      }
                    }
                    else {
                      $code = $child_ticket->generated_code->code;
                    }
                    if (!empty($ticket_info) && empty($ticket_info[$code])) {
                      $child_ticket_info = [
                        'attraction_name'=>$child_ticket->attraction->title,
                        'ticket_name'=>$child_ticket->name,
                        'ticket_type'=>$child_ticket->type
                      ];
                      if ($ticket->attraction->pricing_type == 'settlement') {
                        $child_ticket_info['redemption_start_date']= !empty($child_ticket->redemption_start_date) ? $child_ticket->redemption_start_date->format('d M Y h:ia') : '';
                        $child_ticket_info['redemption_end_date']= !empty($child_ticket->redemption_end_date) ? $child_ticket->redemption_end_date->format('d M Y h:ia') : '';
                      }
                      if ($child_ticket->cost_price) {
                        $child_ticket_info['cost_price'] = $child_ticket->cost_price;
                      }
                      $ticket_info[$code] = $child_ticket_info;
                    }
                  // $vouchers[] = $voucher;  
                  }
                  elseif ($child_ticket->voucher_type =='pdf') {
                    $pdf = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$child_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now])->order(['Pdfs.created'=>'asc','Pdfs.id'=>'asc'])->epilog('FOR UPDATE')->first();
                    if ($pdf) {
                      $voucher->voucher_type = "pdf";
                      $voucher->code_id = $pdf->id;
                      $voucher->ticket_name = $child_ticket->name;
                      $voucher->pdf_filename = $pdf->filename;
                      $pdf->purchased_ticket_sku_id = $child_ticket->sku_id;
                      $pdf->status = 'purchased';
                      $pdf->sold = true;
                      if (!$this->Tickets->Pdfs->save($pdf)) {
                        return "Error saving pdf";
                      }
                      $orig_filename = explode('/',$pdf->filename);
                      for ($pdf_loop=0;$pdf_loop<count($orig_filename);$pdf_loop++){
                        $orig_filename[$pdf_loop] = urlencode($orig_filename[$pdf_loop]);
                      }
                      $encoded_filename = implode("/", $orig_filename);
                      $pdf_filename = 'vouchers/'.$voucher->created->format('Y/md').'/'.$voucher->code_id.'.pdf';
                      /* need copy object instead */
                      $copy = $this->S3->copyObject([
                          'CopySource' => $encoded_filename,
                          'Key' => $pdf_filename,
                      ]);
                      if ($copy) {
                        $this->S3->deleteObject($pdf->encoded_filename);
                      }
                      if (!empty($ticket_info) && empty($ticket_info[$code])) {
                        $child_ticket_info = [
                          'attraction_name'=>$child_ticket->attraction->title,
                          'ticket_name'=>$child_ticket->name,
                          'ticket_type'=>$child_ticket->type
                        ];
                        if ($child_ticket->cost_price) {
                          $child_ticket_info['cost_price'] = $child_ticket->cost_price;
                        }
                        if ($ticket->attraction->pricing_type == 'settlement') {
                          $child_ticket_info['redemption_start_date']= !empty($child_ticket->redemption_start_date) ? $child_ticket->redemption_start_date->format('d M Y h:ia') : '';
                          $child_ticket_info['redemption_end_date']= !empty($child_ticket->redemption_end_date) ? $child_ticket->redemption_end_date->format('d M Y h:ia') : '';
                        }
                        $ticket_info[$pdf->id] = $child_ticket_info;
                      }
                      if ($ticket->single_pdf) {
                        $voucher->single_pdf = $order_item->id.'_'.$i;
                        $voucher->order_qty_idx = $i;
                      }
                      if ($ticket->instant_confirmation) {
                        $ticket_vouchers[] = $voucher;
                      } else {
                        $non_instant_vouchers[] = $voucher;
                      }
                      $vouchers[] = $voucher;

                    }
                    else {
                      return "No available pdf";
                    }
                  }
                  else {
                    $barcode = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$child_ticket->sku_id,'status'=>'new','validity_start_date <='=>$now,'validity_end_date >='=>$now ])->order(['Barcodes.created'=>'asc','Barcodes.id'=>'asc'])->epilog('FOR UPDATE')->first();
                    if ($barcode) {
                      $voucher->voucher_type = "barcode";
                      $voucher->code_id = $barcode->code;
                      $voucher->ticket_name = $child_ticket->name;
                      $voucher->barcode = $barcode;
                      // $vouchers[] = $voucher;
                      if (!empty($required_info) && !empty($required_info[$i])) {
                        try {
                          $this->_createPdf($order,$voucher,$child_ticket,$order_item,$required_info[$i]);
                        } catch (Exception $e) {
                          $this->_handleException($e);
                        }
                      }
                      else {
                        try {
                          $this->_createPdf($order,$voucher,$child_ticket,$order_item);
                        } catch (Exception $e) {
                          $this->_handleException($e);
                        }
                        
                      }
                      $barcode->purchased_ticket_sku_id = $ticket->sku_id;
                      $barcode->status = 'purchased';
                      $barcode->sold = true;
                      if (!$this->Tickets->Barcodes->save($barcode)) {
                        return "Error saving barcode";
                      }
                      if (!empty($ticket_info) && empty($ticket_info[$code])) {
                        $child_ticket_info = [
                          'attraction_name'=>$child_ticket->attraction->title,
                          'ticket_name'=>$child_ticket->name,
                          'ticket_type'=>$child_ticket->type
                        ];
                        if ($child_ticket->cost_price) {
                          $child_ticket_info['cost_price'] = $child_ticket->cost_price;
                        }
                        if ($ticket->attraction->pricing_type == 'settlement') {
                          $child_ticket_info['redemption_start_date']= !empty($child_ticket->redemption_start_date) ? $child_ticket->redemption_start_date->format('d M Y h:ia') : '';
                          $child_ticket_info['redemption_end_date']= !empty($child_ticket->redemption_end_date) ? $child_ticket->redemption_end_date->format('d M Y h:ia') : '';
                        }
                        $ticket_info[$barcode->code] = $child_ticket_info;
                      }
                      $barcode->package_code = $package_code;
                      if ($ticket->single_pdf) {
                        $voucher->single_pdf = $order_item->id.'_'.$i;
                        $voucher->order_qty_idx = $i;
                      }
                      if ($ticket->instant_confirmation) {
                        $ticket_vouchers[] = $voucher;
                      } else {
                        $non_instant_vouchers[] = $voucher;
                      }
                      $vouchers[] = $voucher;
                    }
                    else {
                      $this->log('No barcode found for '.$child_ticket->sku_id,'debug');
                      return "No available barcode";
                    }
                  }
                  $ticket_info = $this->_addChildTicketMerchant($ticket_info,$child_ticket);
                }
                if (!empty($ticket->flexi_options)) {
                  if ($flexi_options) {
                    if ($ticket->instant_confirmation) {
                      $ticket_vouchers[] = $package_voucher;
                    } else {
                      $non_instant_vouchers[] = $package_voucher;
                    }
                    $vouchers[] = $package_voucher;
                    if (!empty($required_info) && !empty($required_info[$i])) {
                      try {
                        $this->_createPdf($order,$package_voucher,$ticket,$order_item,$required_info[$i]);
                      } catch (Exception $e) {
                        $this->_handleException($e);
                      }
                    }
                    else {
                      try {
                        $this->_createPdf($order,$package_voucher,$ticket,$order_item);
                      } catch (Exception $e) {
                        $this->_handleException($e);
                      }
                    }
                  }
                }
              }
              /* END PACKAGE TICKETS */
              /* BEGIN ATTRACTION TICKETS */
              else {
                $voucher = $this->Vouchers->newEntity();
                $voucher->created = Time::now();
                $voucher->ticket_name = $ticket->name;
                $voucher->order_item_id = $order_item->id;
                if ($ticket->voucher_type == 'generated' || $ticket->voucher_type == null) {
                  /*GENERATE CODE*/
                  $len = 10;
                  $code = $this->_getRandomGC($ticket->attraction,$ticket,$len);
                  if (!$ticket->generated_code) {
                    $ctr = 0;
                    while($this->Vouchers->find()->where(['code_id'=>$code])->count() && $ctr++ < 1000) {
                        $code = $this->_getRandomGC($ticket->attraction,$ticket,$len);
                    }
                    $generated_code = $this->GeneratedCodes->newEntity(['code'=>$code,'ticket_id'=>$ticket->id,'status'=>'purchased','redeemable'=>1,'ticket_sku_id'=>$ticket->sku_id]);
                    $generated_code->sold = true;
                    if ($ticket->multiple_redemption) {

                      if (!empty($order_item_data->multiple_redemption)) {
                        $generated_code->redeemable = $order_item->qty;
                        $ticket->generated_code = $generated_code;
                      }
                    }
                    elseif (!empty($ticket->flexi_options)) {
                      $flexi_options = json_decode($ticket->flexi_options);
                      if ($flexi_options) {
                        $generated_code->redeemable = $flexi_options->choose;
                      }
                    }
                    $voucher->voucher_type = "generated";
                    $voucher->generated_code = $generated_code;
                    $voucher->code_id = $code;
                    if ($ticket->instant_confirmation) {
                      $ticket_vouchers[] = $voucher;
                    } else {
                      $non_instant_vouchers[] = $voucher;
                    }
                    $vouchers[] = $voucher;

                    if (!$this->GeneratedCodes->save($generated_code)) {
                      return "Error saving generated code";
                    }
                    if (!empty($required_info) && !empty($required_info[$i])) {
                      try {
                        $this->_createPdf($order,$voucher,$ticket,$order_item,$required_info[$i]);
                      } catch (Exception $e) {
                        $this->_handleException($e);
                      }
                    }
                    else {
                      try {
                        $this->_createPdf($order,$voucher,$ticket,$order_item);
                      } catch (Exception $e) {
                        $this->_handleException($e);
                      }
                    }
                  }
                  else {
                    $code = $ticket->generated_code->code;
                  }
                
                  // $vouchers[] = $voucher;
                  
                }
                elseif ($ticket->voucher_type =='pdf') {
                  $pdf = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$ticket->sku_id,'status'=>'new','validity_end_date >='=>$now])->order(['Pdfs.created'=>'asc','Pdfs.id'=>'asc'])->epilog('FOR UPDATE')->first();
                  if ($pdf) {
                    $voucher->voucher_type = "pdf";
                    $voucher->code_id = $pdf->id;
                    // $vouchers[] = $voucher;
                    $pdf->purchased_ticket_sku_id = $ticket->sku_id;
                    $pdf->status = 'purchased';
                    $pdf->sold = true;
                    if (!$this->Tickets->Pdfs->save($pdf)) {
                      return "Error saving pdf";
                    }
                    // $dir = new Folder(WWW_ROOT.'order_files'.DS.$order->id, true, 0755);
                    // Download the file to the order_files folder
                    // if(file_put_contents( $dir->path.DS.$voucher->code_id.'.pdf',file_get_contents(Configure::read('TDP.app_root').'/webroot/'.$pdf->filename))) {
                    //     // $pdf_file->delete();
                    // }
                    $voucher->pdf_filename = $pdf->filename;
                    $orig_filename = explode('/',$pdf->filename);
                    for ($pdf_loop=0;$pdf_loop<count($orig_filename);$pdf_loop++){
                      $orig_filename[$pdf_loop] = urlencode($orig_filename[$pdf_loop]);
                    }
                    $encoded_filename = implode("/", $orig_filename);
                    $pdf_filename = explode('/', $pdf->filename);
                    $pdf_filename = 'vouchers/'.$voucher->created->format('Y/md').'/'.$voucher->code_id.'.pdf';
                    /* need copy object instead */
                    $copy_filename = $this->S3->copyObject([
                        'CopySource' => $encoded_filename,
                        'Key' => $pdf_filename,
                    ]);
                    if ($copy_filename) {
                      $this->S3->deleteObject($encoded_filename);
                      $pdf->filename = $copy_filename;
                      if (!$this->Tickets->Pdfs->save($pdf)) {
                        return "Error saving pdf";
                      }
                    }
                    if ($ticket->instant_confirmation) {
                      $ticket_vouchers[] = $voucher;
                    } else {
                      $non_instant_vouchers[] = $voucher;
                    }
                    $vouchers[] = $voucher;
                  }
                  else {
                    return "No available pdf";
                  }
                }
                else {
                  $barcode = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$ticket->sku_id,'status'=>'new','validity_start_date <='=>$now,'validity_end_date >='=>$now ])->order(['Barcodes.created'=>'asc','Barcodes.id'=>'asc'])->epilog('FOR UPDATE')->first();
                  if ($barcode) {
                    $voucher->voucher_type = "barcode";
                    $voucher->code_id = $barcode->code;
                    $voucher->barcode = $barcode;
                    
                    // $vouchers[] = $voucher;
                    
                    $barcode->purchased_ticket_sku_id = $ticket->sku_id;
                    $barcode->status = 'purchased';
                    $barcode->sold = true;
                    
                    if (!$this->Tickets->Barcodes->save($barcode)) {
                      return "Error saving barcode";
                    }
                    if (!empty($required_info) && !empty($required_info[$i])) {
                      try {
                        $this->_createPdf($order,$voucher,$ticket,$order_item,$required_info[$i]);
                      } catch (Exception $e) {
                        $this->_handleException($e);
                      }
                    }
                    else {
                      try {
                        $this->_createPdf($order,$voucher,$ticket,$order_item);
                      } catch (Exception $e) {
                        $this->_handleException($e);
                      }
                    }
                    if ($ticket->instant_confirmation) {
                      $ticket_vouchers[] = $voucher;
                    } else {
                      $non_instant_vouchers[] = $voucher;
                    }
                    $vouchers[] = $voucher;
                  }
                  else {
                    // mail self for no pdf voucher
                    return "No available barcode";
                  }
                }
                if (!$this->Tickets->save($ticket)) {
                  return "Error saving ticket";
                }
              }
              /* END ATTRACTION TICKETS */
            }

            if (!$this->Tickets->Inventories->purchase($ticket->id,$order_item->qty,$order->agent_user_id,$order->id)) {
              return 'Error saving inventory';
            }
            

            $order_item->ticket_info = json_encode($ticket_info);
            if (!$this->Orders->OrderItems->save($order_item)) {
              return "Cannot save order (ticket_info).";
            }

            if (!empty($vouchers)) {
              if ($this->Vouchers->saveMany($vouchers)) {
                // $this->Flash->success('Created Vouchers');
              }
              else {
                foreach ($vouchers as $key => $ev) {
                  if (!empty($ev->getErrors())) {
                    $this->log(['voucher'=>$ev,'order_ref_id'=>$order->order_ref_id],'debug');
                  }
                }
                return "Error saving vouchers";
              }
            }
            /* email instant confirmation */
        }

        if (!empty($ticket_vouchers) || !empty($non_instant_vouchers)) {
          if (!empty($ticket_vouchers)) {
            $order->ticket_vouchers = $ticket_vouchers;
            /* change sending of email to after transaction */
            // $this->_emailCustomer($order,$ticket_vouchers);
          }
          if (!empty($non_instant_vouchers)) {
            // send to owner (cebu air)
            $attachments = [];
            foreach ($non_instant_vouchers as $key => $voucher) {
              $result = $this->S3->getObject('vouchers/'.$voucher->created->format('Y/md').'/'.$voucher->code_id.'.pdf');
              $attachment = [];
              if (!empty($result)) {
                $attachment = [
                      'data' =>  $result,
                      'mimetype' => 'application/pdf'
                  ];
                if (empty($attachments[$voucher->code_id]) && !empty($attachment) && !empty($voucher->code_id)) {
                  $filename = $voucher->ticket_name.'_'.$voucher->code_id.'.pdf';
                  if ($voucher->voucher_type == 'barcode') {
                    $filename = $voucher->ticket_name.'_'.'B'.$voucher->code_id.'.pdf';
                  }
                  if (empty($attachments[$filename]) && !empty($attachment) && !empty($voucher->code_id)) {
                    
                    $attachments[$filename] = $attachment;
                  }
                }
              }
            }
            $checkout_info = $order->checkout_info;
            if (!is_array($checkout_info)) {
              $checkout_info = json_decode($checkout_info,true);
            }
            $this->Mailer->mail('Order','new_order','New Booking Request (Order Reference '.$order->order_ref_id.') at Cebu Air Travel & Tours Pte Ltd',Configure::read('Default.cebu_air_email'),['checkout_info'=>$checkout_info,'order'=>$order,'ticket'=>$ticket,'vouchers'=>$vouchers],null,null,$attachments);
          }
        } else {
          if ($api_purchases == 0) {
            return "No vouchers created";
          }
        }
        return true;
      }
    }

    private function _createPdf($order, $voucher,$ticket,$order_item,$traveller_data = null) {
      $CakePdf = new \CakePdf\Pdf\CakePdf();
      if (!empty($ticket->voucher_template) && in_array($ticket->voucher_template, Configure::read('Default.voucher_templates')) ) {
        $CakePdf->template($ticket->voucher_template, 'pdf');
      }
      else {
        $CakePdf->template('default_voucher', 'pdf');
        $CakePdf->margin(15, 20, 25, 20);
      }

      $barcode = '';
      if ($ticket->voucher_code_type == 'barcode') {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = '<img src="data:image/png;base64,' . base64_encode($generator->getBarcode($voucher->code_id, $generator::TYPE_CODE_128,1,50)) . '">';
      }
      else {
        ob_start();
        QRcode::png($voucher->code_id);
        // \PHPQRCode\QRcode::png('contents of the QR code', null, 'H', 8, 2);
        $barcode = ob_get_contents();
        ob_end_clean();
        $barcode = '<img src="data:image/png;base64,' .base64_encode($barcode).'" />';
      }
      $ticket_redemption_start_date = $ticket->redemption_start_date;
      $ticket_redemption_end_date = $ticket->redemption_end_date;
      if ($ticket->attraction->pricing_type != 'settlement') {
        $ticket_redemption_start_date = !empty($order_item->date_of_visit) ? ($order_item->date_of_visit->format('F d, Y').(!empty($order_item->time_of_visit) ? $order_item->time_of_visit->format(' h:i a') : '')) : '';
        $ticket_redemption_end_date = null;
      }

      $flexi_options = !empty($ticket->flexi_options) ? json_decode($ticket->flexi_options,true) : null;
      $flexi_ticket_validity = !empty($flexi_options) && !empty($flexi_options['valid_for']) ? 'Voucher is valid for <b>'.$flexi_options['valid_for'].' days</b> from date of first use.' : '';
      
      $CakePdf->viewVars([
        'order'=>$order,
        'voucher'=>$voucher,
        'attraction_title'=>$ticket->attraction->title,
        'attraction_title_chinese'=>$ticket->attraction->title_chinese,
        'attraction_type'=>$ticket->attraction->type,
        'ticket_name'=>$ticket->name,
        'ticket_description'=>$ticket->description,
        'ticket_terms_and_conditions'=>$ticket->terms_and_conditions,
        'ticket_redemption_start_date'=> $ticket_redemption_start_date,
        'flexi_ticket_validity'=>$flexi_ticket_validity,
        'ticket_redemption_end_date'=>$ticket_redemption_end_date,
        'ticket_voucher_note'=>$ticket->voucher_note,
        'barcode'=>$barcode,
        'traveller_data'=>$traveller_data,
        'order_item'=>$order_item]);
      
      // $pdf = $CakePdf->output();
      // $dir = new Folder(WWW_ROOT.'order_files'.DS.$order->id, true, 0755);
      // $pdf = $CakePdf->write($dir->path.DS. $voucher->code_id.'.pdf');
      $pdf_filename = 'vouchers/'.$voucher->created->format('Y/md').'/'.$voucher->code_id.'.pdf';
      $this->S3->putObjectAsStream($pdf_filename,$CakePdf->output(),'application/pdf');
      // $email_data['attachment'] = $pdf;
    }

    private function _xcheckOrderCart($order) {
      $ticket_list = $this->ShoppingCart->getAll();
      $expected_order_items = 0;
      if (!empty($ticket_list)) {
        foreach ($ticket_list as $_ticket_id => $_datetime) {

          $expected_order_items += count($_datetime);
        }
      }
      if ($expected_order_items != count($order->order_items)) {
        return false;
      }


      if (!empty($ticket_list) && !empty($order->order_items)) {
        $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'])->toArray();
        if (!empty($tickets)) {
          foreach ($tickets as $key => $ticket) {
            if (isset($ticket_list[$ticket->id]) && is_array($ticket_list[$ticket->id])) {
              foreach ($ticket_list[$ticket->id] as $datetime => $qty) {
                if ($ticket->attraction->pricing_type == 'settlement') {
                  $order_item = $this->Orders->OrderItems->find()->where(['ticket_id'=>$ticket->id,'order_id'=>$order->id,'qty'=>$qty])->first();
                  if (!$order_item) {
                    return false;
                  }
                }
                else {
                  if (strpos($datetime, ':') !== false) {
                    $dt = Time::createFromFormat('d M Y H:i',$datetime);
                    $order_item = $this->Orders->OrderItems->find()->where(['ticket_id'=>$ticket->id,'order_id'=>$order->id,'qty'=>$qty,'date_of_visit'=>$dt->format('Y-m-d'),'time_of_visit'=>$dt->format('H:i:s')])->first();
                    if (!$order_item) {
                      return false;
                    }
                  }
                  else {
                    $dt = Time::createFromFormat('d M Y',$datetime);
                    $order_item = $this->Orders->OrderItems->find()->where(['ticket_id'=>$ticket->id,'order_id'=>$order->id,'qty'=>$qty,'date_of_visit'=>$dt->format('Y-m-d')])->first();
                    if (!$order_item) {
                      return false;
                    }
                  }
                }
              }
            }
          }
        }
        else {
          return true;
        }
      }
      return true;
    }

    private function _getRandomGC($attraction,$ticket, $len,$is_package_voucher = false) {
        $digits = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';//abcdefghijklmnopqrstuvwxyz
        $max = 35;//strlen($digits) -1;
        // $code = '';
        // $prefix = ['VC','PK'];
        $code = '';
        if ($attraction->type == 'single') {
          $code = 'VC'; //voucher code
        }
        elseif ($attraction->type =='package') {
          if (!empty($ticket->flexi_options)) {
            $code = 'FX'; //flexi voucher code
          }
          elseif ($is_package_voucher) {
            $code = 'PK'; //package voucher code
          }
          else {
            $code = 'CV'; //child voucher code
          }
        }
        for ($i=0; $i < $len ; $i++) {
            $code .= $digits[mt_rand(0,$max)];
        }
        return $code;
    }

    private function _emailCustomer($order,$vouchers,$agent) {
      $checkout_info = $order->checkout_info;
      if (!is_array($order->checkout_info)) {
        $checkout_info = json_decode($order->checkout_info,true);
      }

      if (!empty($checkout_info['email']) && filter_var(trim($checkout_info['email']),FILTER_VALIDATE_EMAIL)) {
        $attachments = [];
        $vouchers_to_merge = [];
        foreach ($vouchers as $key => $voucher) {
          if ($voucher->single_pdf) {
            if (empty($vouchers_to_merge[$voucher->single_pdf])) {
              $vouchers_to_merge[$voucher->single_pdf] = [$voucher];
            } else {
              $vouchers_to_merge[$voucher->single_pdf][] = $voucher;
            }
          } else {
            $result = $this->S3->getObject('vouchers/'.$voucher->created->format('Y/md').'/'.$voucher->code_id.'.pdf');
            $attachment = [];
            if (!empty($result)) {
              $attachment = [               
                    'data' =>  $result,
                    'mimetype' => 'application/pdf'
                ];
                
              $filename = $voucher->ticket_name.'_'.$voucher->code_id.'.pdf';
              if ($voucher->voucher_type == 'barcode') {
                $filename = $voucher->ticket_name.'_'.'B'.$voucher->code_id.'.pdf';
              }
              if (empty($attachments[$filename]) && !empty($attachment) && !empty($voucher->code_id)) {
                
                $attachments[$filename] = $attachment;
              }
            }
          }
        }
        
        if (!empty($vouchers_to_merge)) {
          foreach ($vouchers_to_merge as $order_item_id => $_vouchers) {
            $filenames = [];
            $merged_filename = [];
            $attachment_filename = '';
            foreach ($_vouchers as $key => $_voucher) {
              $filename = $_voucher->code_id.'.pdf';
              // $merged_filename[] = $_voucher->code_id;
              if ($attachment_filename != '') {
                $attachment_filename .= '_';
              }
              if ($_voucher->voucher_type == 'barcode') {
                $attachment_filename .= $_voucher->ticket_name.'_B'.$_voucher->code_id;
              } else {
                $attachment_filename .= $_voucher->ticket_name.'_'.$_voucher->code_id;
              }
              
              if ($_voucher->voucher_type =='pdf' && !empty($_voucher->pdf_filename)) {
                  $filename = explode('/',$_voucher->pdf_filename);
                  $filename = end($filename);
                  $filename = preg_replace('/[^\.a-zA-Z0-9]*/','', $filename);
              }
              $file = $this->S3->getObject('vouchers/'.$_voucher->created->format('Y/md').'/'.$_voucher->code_id.'.pdf');
              if ($file) {
                  $filename = TMP.'merged_pdf'.DS.$filename;
                  $success = file_put_contents($filename, $file->__toString());
                  $filenames[] = $filename;
              }
            }

            $merged_filename = $order_item_id;
            $merge_output_file = $this->_pdfMerge($filenames,$merged_filename);
            $full_output_filename = WWW_ROOT. 'files/pdf/'.$merge_output_file;
            $s3_filename = $order_item_id;
            sleep(2);
            
            $this->S3->putObject('vouchers/'.$voucher->created->format('Y/md').'/'.$s3_filename.'.pdf',$full_output_filename);
            $attachment = [];
              $attachment = [               
                    'file' =>  $full_output_filename,
                    'mimetype' => 'application/pdf'
                ];
                
              $attachment_filename .= '.pdf';
              if (empty($attachments[$attachment_filename])) {
                $attachments[$attachment_filename] = $attachment;
              }
            
          }
        }
        $agent = $this->Agents->find()->where(['id'=>$this->Auth->user('agent_id')])->first();
        $bcc = null;
        if ($agent && $agent->email != $checkout_info['email']) {
          $bcc = $agent->email;
        }
        $email_from = !empty($agent) && !empty($agent->email_displayname) ? $agent->email_displayname : 'SG Attractions';
        $this->Mailer->mail('Purchase','new_order','Successfully purchased items (Order Reference '.$order->order_ref_id.') at '.$email_from,$checkout_info['email'],['checkout_info'=>$checkout_info,'order'=>$order,'email_from'=>$email_from],null,$bcc,$attachments);
      }
    }

    private function _emailBooking($order,$attraction_ticket) {
      $emails = json_decode($attraction_ticket['attraction']->email_booking_to,true);
      if (!empty($emails)) {
        foreach ($emails as $key => $email) {
          if (filter_var(trim($email),FILTER_VALIDATE_EMAIL)) {
            $this->Mailer->mail('New Booking','ticket_purchased','New Booking Request (Order Reference '.$order->order_ref_id.') at Cebu Air Travel & Tours Pte Ltd',$email,['order'=>$order,'attraction'=>$attraction_ticket['attraction'],'tickets'=>$attraction_ticket['tickets']]);
          }
        }
      }
      elseif ($attraction_ticket['attraction']->type == 'transfer') {
        $merchant = $this->Attractions->Merchants->find()->where(['id'=>$attraction_ticket['attraction']->merchant_id])->first();
        if ($merchant && $merchant->email) {
          if (filter_var(trim($merchant->email),FILTER_VALIDATE_EMAIL)) {
            $this->Mailer->mail('New Booking','ticket_purchased','New Booking Request (Order Reference '.$order->order_ref_id.') at Cebu Air Travel & Tours Pte Ltd',$merchant->email,['order'=>$order,'attraction'=>$attraction_ticket['attraction'],'tickets'=>$attraction_ticket['tickets']]);
          }
        }
      }
    }

    private function _addChildTicketMerchant($ticket_info,$child_ticket) {
      if (empty($ticket_info[$child_ticket->sku_id])) {
        $child_ticket_merchant = $this->Tickets->Attractions->Merchants->find()->where(['id'=>$child_ticket->attraction->merchant_id])->first();
        if (!empty($child_ticket_merchant)) {
          $ticket_info[$child_ticket->sku_id] = ['merchant_id'=>$child_ticket->attraction->merchant_id,'merchant_name'=>$child_ticket_merchant->name];
        }
      }
      return $ticket_info;
    }

    private function _handleException($e) {
      $this->log($e,'error');
    }

    private function _externalPurchase($order) {
      $to_purchase = [];
      $connections = [];
      foreach ($order->order_items as $key => $order_item) {
        if ($order_item->ticket->api_purchase && !empty($order_item->ticket->connection_id)) {
          $connection = null;
          if (!isset($connections[$order_item->ticket->connection_id])) {
            $connection = $this->Connections->find()->where(['id'=>$order_item->ticket->connection_id])->first();
            $connections[$order_item->ticket->connection_id] = $connection;
          } else {
            $connection = $connections[$order_item->ticket->connection_id];
          }
          
          if ($connection) {
            if (empty($to_purchase[$order_item->ticket->connection_id])) {
              $to_purchase[$order_item->ticket->connection_id] = [];
            }
            $to_purchase[$order_item->ticket->connection_id][] = $order_item;
            // if (!$this->Tickets->query()->update()->set(['quantity_sold = quantity_sold + '.$order_item->qty])->where(['id'=>$order_item->ticket->id])->execute()) {
            //   throw new Exception("Error saving ticket");
            // } // save the quantity sold to tickets
            if (!$this->Tickets->Inventories->purchase($order_item->ticket->id,$order_item->qty,$order->agent_user_id,$order->id)) {
              throw new Exception('Error saving ticket');
            }
          }
        } elseif ($order_item->ticket->type == 'complex') { // for package
          $ticket = $this->Tickets->find()->where(['id'=>$order_item->ticket_id])->contain(['ChildTickets'=>function ($q) {
            return $q->where(['api_purchase'=>true]);
          }])->first();
          $child_tickets = $ticket->child_tickets;
          if (!empty($child_tickets)) {
            foreach ($child_tickets as $key => $child_ticket) {
              if ($child_ticket->api_purchase && !empty($child_ticket->connection_id)) {
                $connection = null;
                if (!isset($connections[$child_ticket->connection_id])) {
                  $connection = $this->Connections->find()->where(['id'=>$child_ticket->connection_id])->first();
                  $connections[$child_ticket->connection_id] = $connection;
                } else {
                  $connection = $connections[$child_ticket->connection_id];
                }
                
                if ($connection) {
                  if (empty($to_purchase[$child_ticket->connection_id])) {
                    $to_purchase[$child_ticket->connection_id] = [];
                  }
                  $to_purchase[$child_ticket->connection_id][] = $order_item;
                  // if (!$this->Tickets->query()->update()->set(['quantity_sold = quantity_sold + '.$order_item->qty])->where(['id'=>$child_ticket->id])->execute()) {
                  //   throw new Exception("Error saving ticket");
                  // } // save the quantity sold to tickets
                  if (!$this->Tickets->Inventories->purchase($child_ticket->id,$order_item->qty,$order->agent_user_id,$order->id)) {
                    throw new Exception('Error saving ticket');
                  }
                }
              }
            }
          }
          
        }
      }
      
      if (!empty($to_purchase)) {
        foreach ($to_purchase as $connection_id => $order_items) {
          $connection = $connections[$connection_id];
          $connection_function = '_'.$connection->function_name.'Purchase';
          return $this->$connection_function($order,$order_items,$connection);
        }
        return false;
      }
      return true;
    }

    private function _rwsPurchase($order,$order_items,$connection) {

      $this->loadModel('SupplierRws');
      $purchase_result = $this->SupplierRws->purchase($order,$order_items,$connection);
      // debug($purchase_result);exit;
      if (!empty($purchase_result)) {
        
        if (is_array($purchase_result)) {
          $vouchers = [];
          $updated_order_items = $purchase_result;
          foreach ($updated_order_items as $oi_key => $order_item) {
            $oi_vouchers = [];
            if ($order_item->ticket->single_pdf/* && $order_item->qty > 1*/) {
              foreach ($order_item->vouchers as $key => $oiv) {
                if ($oiv->connection_id == $connection->id) {
                  $oi_vouchers[] = $oiv;
                  $vouchers[] = $oiv;
                }
              }
              if (!empty($oi_vouchers)) {
                for ($i=0; $i < $order_item->qty; $i++) { 
                  if (!empty($oi_vouchers[$i])) {
                    foreach ($vouchers as $oiv_key => $oiv) {
                      if ($oiv->code_id == $oi_vouchers[$i]->code_id) {
                        $vouchers[$oiv_key]->single_pdf = $order_item->id.'_'.$i;
                      }
                    }
                  }
                }
              }
            } else {
              $vouchers = array_merge($vouchers, $order_item->vouchers);
            }
          }
          if (!$this->Orders->OrderItems->saveMany($updated_order_items)) {          
            $this->log($updated_order_items,'debug');
          }
          if (!empty($vouchers)) {
            return $vouchers;
            // if (!$this->Vouchers->saveMany($vouchers)) {
            //   $this->log(json_encode(['error_vouchers'=>$vouchers]),'debug');
            //   throw new Exception('Error saving vouchers.');
            // }
          }else {
            $this->log('No vouchers created. Order '.$order->order_ref_id);
          }
        } else {
          $error_ref = explode('-',$purchase_result);
          if (!empty($error_ref[0])) {
            if ($error_ref[0] == '8004') {
              $this->Flash->success('We are working for your order. We will send the result to your email once done. Thank you.');
              return $error_ref[0];
            } else {
              $this->Flash->error('Error: '.$purchase_result.'. Please try again or contact admin and provide the error code.');
              return $error_ref[0];
            }
          } else {
            return false;
          }
        }
      } else {
        throw new Exception("Items not purchased");
      }
      return false;
    }

    private function _onefgPurchase($order,$order_items,$connection) {
      $this->loadModel('SupplierOnefg');
      $vouchers = [];
      foreach ($order_items as $key => $order_item) {
        $purchase_result = $this->SupplierOnefg->purchase($order,[$order_item],$connection);
        if (!empty($purchase_result)) {
          if (is_array($purchase_result)) {
            $updated_order_items = $purchase_result;
            foreach ($updated_order_items as $oi_key => $order_item) {
              $oi_vouchers = [];
              if ($order_item->ticket->single_pdf/* && $order_item->qty > 1*/) {
                foreach ($order_item->vouchers as $key => $oiv) {
                  if ($oiv->connection_id == $connection->id) {
                    $oi_vouchers[] = $oiv;
                    $vouchers[] = $oiv;
                  }
                }
                if (!empty($oi_vouchers)) {
                  for ($i=0; $i < $order_item->qty; $i++) { 
                    if (!empty($oi_vouchers[$i])) {
                      foreach ($vouchers as $oiv_key => $oiv) {
                        if ($oiv->code_id == $oi_vouchers[$i]->code_id) {
                          $vouchers[$oiv_key]->single_pdf = $order_item->id.'_'.$i;
                        }
                      }
                    }
                  }
                }
              } else {
                $vouchers = array_merge($vouchers, $order_item->vouchers);
              }
            }
          // foreach ($updated_order_items as $oi_key => $order_item) {
          //   if (!empty($order_item->api_vouchers) && empty($order_item->ticket->flexi_options)) {
          //     $api_vouchers = $order_item->api_vouchers;
          //     if (!is_array($api_vouchers)) {
          //       $api_vouchers = json_decode($api_vouchers,true);
          //     }
          //     if (!empty($api_vouchers)) {
          //       $qty_key = 0;
          //       foreach ($api_vouchers as $key => $api_code) {
          //         $voucher = $this->Vouchers->newEntity();
          //         $voucher->created = Time::now();
          //         $voucher->voucher_type = 'api';
          //         $voucher->ticket_name = $order_item->ticket->name;
          //         $voucher->order_item_id = $order_item->id;
          //         $voucher->code_id = $api_code;
          //         if ($order_item->qty > 1) {
          //           $voucher->order_qty_idx = $qty_key;
          //         }
          //         if ($order_item->ticket->single_pdf) {
          //           $voucher->single_pdf = $order_item->id.'_'.$qty_key;
          //           $voucher->order_qty_idx = $qty_key;
          //         }
          //         $vouchers[] = $voucher;
          //         $qty_key++;
          //       }
          //     }
          //   }
          // }
            if (!$this->Orders->OrderItems->saveMany($updated_order_items)) {          
              $this->log($updated_order_items,'debug');
            }
            
          } else {
            $error_ref = explode('-',$purchase_result);
            if (!empty($error_ref[0])) {
              $this->Flash->error('Error: '.$purchase_result.'. Please try again or contact admin and provide the error code.');
              return $error_ref[0];
            } else {
              return false;
            }
          }
        } else {
          throw new Exception("Items not purchased");
        }
      }
      if (!empty($vouchers)) {
        return $vouchers;
        // if (!$this->Vouchers->saveMany($vouchers)) {
        //   $this->log(json_encode(['error_vouchers'=>$vouchers]),'debug');
        //   throw new Exception('Error saving vouchers.');
        // }
      }else {
        $this->log('No vouchers created. Order '.$order->order_ref_id,'debug');
      }
      
    }

   /* private function _emailCustomerApi($order,$order_items) {
      $checkout_info = $order->checkout_info;
      if (!is_array($order->checkout_info)) {
        $checkout_info = json_decode($order->checkout_info,true);
      }

      if (!empty($checkout_info['email']) && filter_var(trim($checkout_info['email']),FILTER_VALIDATE_EMAIL)) {
        $attachments = [];
        $vouchers_to_merge = [];
        foreach ($order_items as $key => $order_item) {
          if (!empty($order_item->api_vouchers)) {
            // $vouchers = $order_item->api_vouchers;
            // if (!is_array($vouchers)) {
              $vouchers = json_decode($order_item->api_vouchers);
            // }
            if (!empty($vouchers)) {
              foreach ($vouchers as $key => $code) {
                $result = $this->S3->getObject('vouchers/'.$order_item->created->format('Y/md').'/'.$code.'.pdf');
                // $this->log($result,'debug');
                $attachment = [];
                if (!empty($result)) {
                  $attachment = [               
                        'data' =>  $result,
                        'mimetype' => 'application/pdf'
                    ];
                  $filename = $order_item->ticket->name.'_'.$code.'.pdf';
                  if (empty($attachments[$filename]) && !empty($attachment)) {
                    $attachments[$filename] = $attachment;
                  }
                }
              }
            }
          }
        }
        $this->log($attachments,'debug');
        $agent = $this->Agents->find()->where(['id'=>$this->Auth->user('agent_id')])->first();
        $bcc = null;
        if ($agent && $agent->email != $checkout_info['email']) {
          $bcc = $agent->email;
        }
        $this->Mailer->mail('Purchase','new_order','Successfully purchased items (Order Reference '.$order->order_ref_id.') at SG Attractions',$checkout_info['email'],['checkout_info'=>$checkout_info,'order'=>$order],null,$bcc,$attachments);
      }
    }*/

    public function removeTicket($ticket_id = null,$date = null) {
        $this->request->allowMethod(['post', 'delete']);
        $ticket = $this->Tickets->find()->where(['id'=>$ticket_id])->first();
        if ($ticket) {
          $this->ShoppingCart->remove($ticket->id,$date);
        }
        return $this->redirect($this->referer());
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
      $conditions = ['agent_user_id'=>$this->Auth->user('id')];
        $conditions['status']  = 'done';
        if ($this->request->getQuery('order_ref_id')) {
            $conditions['order_ref_id like'] = $this->request->getQuery('order_ref_id');
        }

        if ($this->request->getQuery('status') && $this->request->getQuery('status') != 'all') {
            $conditions['status'] = $this->request->getQuery('status');
        } elseif ($this->request->getQuery('status') != 'all') {
          $conditions['status IN'] = ['done','in_progress','cancelled','refunded'];
        }
        if ($this->request->getQuery('from')) {
          $from = Time::createFromFormat('d M Y', $this->request->getQuery('from'));
          $from->hour(0);
          $from->minute(0);
          $from->second(0);
          $to = Time::now();
          if ($this->request->getQuery('to')) {
            $to = Time::createFromFormat('d M Y', $this->request->getQuery('to'));
          }
          $to->hour(23);
          $to->minute(59);
          $to->second(59);
          $conditions['created >='] = $from;
          $conditions['created <='] = $to;
        }
        if ($this->request->getQuery('status') && $this->request->getQuery('status') != 'all') {
            $conditions['status'] = $this->request->getQuery('status');
        }
        $this->paginate = [
          'conditions' => $conditions,
          'contain'=>['OrderItems'=>['Tickets']],
          'order'=>['created'=>'desc']
        ];
        $query = $this->Orders;
        $orders = $this->paginate($query);

        $this->set(compact('orders'));
    }

    /**
     * View method
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $order = $this->Orders->get($id, [
            'contain' => ['Agents', 'OrderItems'=>function($q) {
              return $q->contain(['Vouchers'=>function($vq) {
                return $vq->order(['order_qty_idx'=>'ASC']);
              },'Tickets'=>['Attractions']])->order(['OrderItems.id'=>'ASC']);
            }],
            'conditions'=>['agent_user_id'=>$this->Auth->user('id')]
        ]);

        $this->set('order', $order);
    }

    private function __convert_to_csv($input_array, $output_file_name, $delimiter) {
        /** open raw memory as file, no need for temp files */
        $temp_memory = fopen('php://memory', 'w');
        /** loop through array  */
        foreach ($input_array as $line) {
          // debug($line);
          /** default php csv handler * */
          fputcsv($temp_memory, $line, $delimiter);
        }
        /** rewrind the "file" with the csv lines * */
        fseek($temp_memory, 0);
        /** modify header to be downloadable csv file * */
        header('Content-Type: application/csv');
        header('Content-Disposition: attachement; filename="' .
                $output_file_name . '";');
        /** Send file to browser for download */
        fpassthru($temp_memory); 
    }

    private function _pdfMerge($files, $ref_id) {
        /*ghostsript*/
        $folder = new \Cake\Filesystem\Folder(WWW_ROOT. 'files/pdf',true,0775);
        $output_file = $ref_id.'.pdf';
        
        if (Configure::read('ENVI') == 'DEV') {
            #$cmd = "/usr/local/bin/gs -q -dNOPAUSE -dBATCH -dAutoRotatePages=1 -sPAPERSIZE=legal -sDEVICE=pdfwrite -sOutputFile=$output_file ";
            // $cmd = "/usr/local/bin/gs";
            $cmd = '\"program files"\gs\gs9.52\bin\gswin64';
            // $cmd = "/program files/gs/gs9.52/bin/gswin64";
        } else {
            #$cmd = "gs -dNOPAUSE -dBATCH -dAutoRotatePages=1 -sPAPERSIZE=legal -sDEVICE=pdfwrite -sOutputFile=$output_file ";
            $cmd = "gs";
            // $cmd = "/program files/gs/gs9.52/bin/gswin64";
        }
        $full_output_file = WWW_ROOT. 'files/pdf/'.$ref_id.'.pdf';
        $cmd .= " -sDEVICE=pdfwrite -dNOPAUSE -dQUIET -dBATCH -sOutputFile=$full_output_file ";
        // debug($files);
        foreach($files as $file) {
            $cmd .= $file." ";
        }
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $p_string = "start {$cmd}";
                pclose(popen($p_string, "r"));
            } else {
                exec($cmd);
            }
        } catch (Exception $exc) {
            echo 'Error: '.$exc;
        }
        return $output_file;
    }

    private function _buildHeaders($data) {
      $header = [];
      $header['Date'] = date('Y-m-d H:i:s');
      $header['X-CA-User'] = $this->Auth->user('email');
      $header['X-Ca-Nonce'] = \Cake\Utility\Text::uuid();
      $header['X-Ca-SignatureMethod'] = 'HmacSHA256';
      $header['X-CA-UserSecret'] = hash('SHA256', $this->Auth->user('email').$this->Auth->user('id'));
      $textToSign = $this->request->getHeader('X-CA-User')."\n".
            $this->request->getHeader('Date')."\n".
            $this->request->getHeader('X-CA-SignatureMethod')."\n".
            $this->request->getHeader('X-CA-Nonce');
      $signature = base64_encode(hash_hmac('SHA256', $textToSign, $secret_key, true));
      $header['Authorization'] = 'Bearer '.$signature;
      return $header;
    }

    private function _buildData($ticket_list) {
      $data = [
        'tracking_no' => $this->request->getData('tracking_no') ? : date('YmdHis').$this->request->getHeader('X-CA-Nonce'),
        'customer_name' => $this->request->getData('customer_name'),

      ];
      foreach ($ticket_list as $key_ticket_id => $date_ticket) {
        $ticket = $this->Tickets->find()->where(['Tickets.id'=>$key_ticket_id])->contain(['Attractions'=>['Merchants']])->first();
        if (!isset($ticket_total[$ticket->id])) {
          $ticket_total[$ticket->id] = 0;
        }
        $ticket_total[$ticket->id] += $qty;
        $no = 1;
        foreach ($date_ticket as $date => $qty) {
          $data['item_sku_id_'.$no] = $ticket->sku_id;
          $data['item_quantity_'.$no] = $qty;


          if ($ticket) {
            if ($ticket->attraction->pricing_type != 'settlement') {
              // $order_data['order_data'][$idx]['date_of_visit'] = $date;

              if ($ticket->attraction->pricing_type =='fdatetime') {
                $datetime = Time::createFromFormat('d M Y H:i',$date);
                $data['item_booking_date_'.$no] = $datetime->format('Y-m-d H:i:s');
              }
              else {
                $datetime = Time::createFromFormat('d M Y',$date);
                $data['item_booking_date_'.$no] = $datetime->format('Y-m-d')
              }
              
            }
            
            $order_data = [];
            if ($this->request->getData('passport_number.'.$ticket->id.'.'.$date)) {
              for ($idx=0; $idx < count($this->request->getData('passport_number.'.$ticket->id.'.'.$date)); $idx++) {
                if ($ticket->require_travellers_info) {

                  if ($this->request->getData('passport_number.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data['required_info'][$idx]['passport_number'] = $this->request->getData('passport_number.'.$ticket->id.'.'.$date.'.'.$idx);
                  }
                  if ($this->request->getData('passport_expiry.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data['required_info'][$idx]['passport_expiry'] = $this->request->getData('passport_expiry.'.$ticket->id.'.'.$date.'.'.$idx);
                  }
                  if ($this->request->getData('passport_first_name.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data['required_info'][$idx]['passport_first_name'] = $this->request->getData('passport_first_name.'.$ticket->id.'.'.$date.'.'.$idx);
                  }
                  if ($this->request->getData('passport_last_name.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data['required_info'][$idx]['passport_last_name'] = $this->request->getData('passport_last_name.'.$ticket->id.'.'.$date.'.'.$idx);
                  }
                  if ($this->request->getData('date_of_birth.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data['required_info'][$idx]['date_of_birth'] = $this->request->getData('date_of_birth.'.$ticket->id.'.'.$date.'.'.$idx);
                  }
                  if ($this->request->getData('nationality.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data['required_info'][$idx]['nationality'] = $this->request->getData('nationality.'.$ticket->id.'.'.$date.'.'.$idx);
                  }
                  if ($this->request->getData('remarks.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data['required_info'][$idx]['remarks'] = $this->request->getData('remarks.'.$ticket->id.'.'.$date.'.'.$idx);
                  }
                }
              }
            }
            if ($ticket && $ticket->attraction->type == 'transfer') {
              $extra_fields = Configure::read('Default.land_transfer_details');
              if (!empty($extra_fields) && is_array($extra_fields)) {
                foreach ($extra_fields as $extra_field_name => $extra_field) {
                  if ($this->request->getData($extra_field_name.'.'.$ticket->id)) {
                    if ($extra_field_name != 'pickup_time') {
                      $order_data['transfer_details'][$extra_field_name] = $this->request->getData($extra_field_name.'.'.$ticket->id);
                    }
                    else {
                      $order_data['transfer_details'][$extra_field_name] = $this->request->getData($extra_field_name.'.'.$ticket->id) ? implode(':',$this->request->getData($extra_field_name.'.'.$ticket->id)) : '';
                    }

                  }
                }
                if ($ticket->attraction->subtype == Configure::read('Default.transfer_subtypes.2')) {
                  foreach ($extra_fields as $extra_field_name => $extra_field) {
                    if ($this->request->getData($extra_field_name.'.'.$ticket->id)) {
                      if ($extra_field_name != 'pickup_time') {
                        $order_data['return_transfer_details'][$extra_field_name] = $this->request->getData('return_'.$extra_field_name.'.'.$ticket->id);
                      }
                      else {
                        $order_data['return_transfer_details'][$extra_field_name] = $this->request->getData('return_'.$extra_field_name.'.'.$ticket->id) ? implode(':',$this->request->getData('return_'.$extra_field_name.'.'.$ticket->id)) : '';
                      }
                    }
                  }
                }
              }
            }
            if (!empty($this->request->getData('multiple_redemption.'.$ticket->id.'.'.$date))) {
                $order_data['multiple_redemption'] = true;
            }
            if (!empty($order_data)) {
              $data['item_order_data_'.$no] = $order_data;
            }
            $order->subtotal += $order_item->total;
          }
        }

      }
    }

}
