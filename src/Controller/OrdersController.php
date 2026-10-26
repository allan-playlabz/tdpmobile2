<?php
namespace App\Controller;

use Exception;
use Cake\Log\Log;
use Cake\I18n\DateTime;
use App\Lib\Payments;
use Cake\Event\Event;
use Cake\Http\Client;
use PHPQRcode\QRcode;
use App\Lib\Translator;
use App\Util\CatMailer;
use Cake\Core\Configure;
use Cake\Routing\Router;
use App\Filesystem\File;
use App\Filesystem\Folder;
use Cake\Utility\Inflector;
use App\Form\QuestionGroupForm;
use App\Controller\AppController;
use App\Lib\PricingType;

/**
 * Orders Controller
 *
 * @property \App\Model\Table\OrdersTable $Orders
 *
 * @method \App\Model\Entity\Order[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class OrdersController extends AppController
{
  public function initialize(): void {
    parent::initialize();
    $this->Authentication->addUnauthenticatedActions(['sps','notificationReceiver','frontendNotificationReceiver']);
    $this->loadComponent('InputCleaner');
    $this->loadComponent('ShoppingCart');
    $this->loadComponent('Mailer');
    $this->loadComponent('TicketsHelper');
    $this->loadComponent('S3');
    $this->Tickets = $this->fetchTable('Tickets');
    $this->Agents = $this->fetchTable('Agents');
    $this->AgentUsers = $this->fetchTable('AgentUsers');
    $this->Vouchers = $this->fetchTable('Vouchers');
    $this->GeneratedCodes = $this->fetchTable('GeneratedCodes');
    $this->SettlementAgents = $this->fetchTable('SettlementAgents');
    $this->CreditLogs = $this->fetchTable('CreditLogs');
    $this->Attractions = $this->fetchTable('Attractions');
    $this->Fixeddates = $this->fetchTable('Fixeddates');
    $this->Connections = $this->fetchTable('Connections');
    Payments::config();
  }

  public function beforeFilter(\Cake\Event\EventInterface $event) {
    parent::beforeFilter($event);
    $viewerBlockedActions = ['addToCart', 'subtractQuantity', 'removeFromCart', 'cart', 'checkout', 'removeTicket', 'index', 'myTickets', 'cancelVoucher'];
    if ($this->getAuthUser('role') == 'viewer' && in_array($this->request->getParam('action'), $viewerBlockedActions)) {
      $this->Flash->error(__('You are not authorized to access that location.'));
      return $this->redirect('/');
    }
  }

    public function addToCart() {
      $this->autoRender = false;
      if ($this->request->is('ajax')) {
        if ($this->request->getData('product_id')) {
          $product = $this->Products->findById($this->request->getData('product_id'))->first();
          if (!$product) {
            return $this->_ajaxResponse('product_not_found');
          }
          /* check tickets to only allow purchase of single merchant when external */
          $ticket_list = $this->ShoppingCart->getAll();
          if (!empty($ticket_list)) {
            $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'])->toArray();
            if (!empty($tickets)) {
              $connection_used = null;
              foreach ($tickets as $key => $ticket) {
                if ($ticket->connection_id) {
                  $connection_used = $ticket->connection_id;
                }
              }
              if ($connection_used && $connection_used != $product->connection_id) {
                return $this->_ajaxResponse('product_not_found');
              } else if ($connection_used == null && $product->connection_id != null) {
                return $this->_ajaxResponse('product_not_found');
              }
            }
          }
          /* end external */
          /* block duplicate supplier_api_id */
          if ($this->ShoppingCart->hasDuplicateSupplier($product->supplier_api_id, $product->attraction_id)) {
            return $this->_ajaxResponse('product_not_found');
          }
          /* end block duplicate supplier */
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
                  $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$ticket->attraction,$this->getAuthUser());
                  if ($ticket) {
                    $default_max_qty =  Configure::read('Default.max_order_quantity');
                    $tickets_max_quantity[$ticket->id] = $ticket->stock && $ticket->stock < $default_max_qty ? $ticket->stock : $default_max_qty;
                  }
                  else {
                    $this->ShoppingCart->remove($_ticket_id);
                  }
                } 
                elseif ($ticket->attraction->pricing_type == PricingType::TWO_WAY_FDATETIME) {
                  return $this->redirect(['controller' => 'Attractions', 'action' => 'checkout']);
                }
                else {
                  $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->getAuthUser(),$datetime);
                  if ($ticket->markup_value && $ticket->markup_value > 0) {
                    $ticket->price = $this->_getTicketApiPrice($ticket, $datetime);
                  }
                  if ($ticket) {
                    $tickets_max_quantity[$ticket->id] = $this->TicketsHelper->getTicketAvailableQty($ticket,$datetime);
                  }
                  else {
                    $this->ShoppingCart->remove($_ticket_id);
                  }
                }
              }
              if ($this->request->getSession()->check('language')) {
                Translator::translateTicket($this->request, $ticket);
              }
            }
          }
        }
        $this->set(compact('ticket_list','tickets','tickets_max_quantity'));
      }
      if ($this->request->is(['post'])) {
        $error = 0;
        $total_qty = 0;
        $tickets_total_quantity = [];
        if ($this->request->getData('quantity')) {
          foreach ($this->request->getData('quantity') as $key_ticket_id => $date_ticket) {
            $ticket = $this->Tickets->find()->where(['Tickets.id'=>$key_ticket_id])->contain(['Attractions'])->first();
            foreach ($date_ticket as $date => $qty) {
              $total_qty += $qty;
              if ($ticket) {
                if ($ticket->attraction->pricing_type == 'settlement') {
                  if ($qty > ($ticket->stock !== null ? $ticket->stock : Configure::read('Default.max_order_quantity'))) {
                    $this->Flash->error(__('Sorry you have exceeded available {0} tickets.', $ticket->name));
                    $error++;
                  }
                }
                else {
                  if ($qty > $this->TicketsHelper->getTicketAvailableQty($ticket,$date)) {
                    $this->Flash->error(__('Sorry you have exceeded available {0} tickets.', $ticket->name));
                    $error++;
                  }
                }
                if (!empty($ticket->min_quantity) && $qty < $ticket->min_quantity) {
                  $this->Flash->error(__('Minimum quantity for {0} should be {1}.', $ticket->name, $ticket->min_quantity));
                  $error++;
                }
                if ($ticket->max_quantity) {
                        if (!empty($tickets_total_quantity[$key_ticket_id])) {
                          $tickets_total_quantity[$key_ticket_id] += $qty;
                        } else {
                          $tickets_total_quantity[$key_ticket_id] = $qty;
                        }
                      if ($tickets_total_quantity[$ticket->id] > $ticket->max_quantity) {
                        $this->Flash->error(__('Sorry, you have exceeded max quantity {0} of ',$ticket->max_quantity) . $ticket->name);
                        $error++;
                      }
                }
              }
            }
          }
        }

        if (!$error && $total_qty > Configure::read('Default.max_order_quantity')) {
          $this->Flash->error(__('Cannot exceed max allowed quantity ({0})', Configure::read('Default.max_order_quantity')));
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

      /* begin old checkout */
      $order = $this->Orders->newEmptyEntity();
      // if ($this->request->getSession()->check('order_id')) {
      //   $existing_order = $this->Orders->find()->where(['id'=>$this->request->getSession()->read('order_id')])->first();
      //   if ($existing_order) {
      //     $order = $existing_order;
      //     $this->Orders->OrderItems->deleteAll(['order_id'=>$existing_order->id]);
      //   }
      // }
      $ticket_list = $this->ShoppingCart->getAll();
      $max_quantity = 0;
      $tickets = [];
      $two_way_ticket_prices = [];

      if (!empty($ticket_list)) {
        $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'=>['Merchants'], 'TicketTripTypes'])
        ->toArray();        
        if (!empty($tickets)) {
          if (!empty($tickets[0]->ticket_trip_type)) {
            $tickets_sort = [];
            foreach ($tickets as $ticket) {
              $tickets_sort[$ticket->ticket_trip_type->type . $ticket->name] = $ticket;
            }
            $tickets = $tickets_sort;
            ksort($tickets);
            $tickets = array_values($tickets);
          }
          foreach ($tickets as $key => $ticket) {
            if (isset($ticket_list[$ticket->id]) && is_array($ticket_list[$ticket->id])) {
              foreach ($ticket_list[$ticket->id] as $datetime => $qty) {
                if ($ticket->attraction->pricing_type == 'settlement') {
                  $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$ticket->attraction,$this->getAuthUser(),$datetime);
                } elseif ($ticket->pricing_type === PricingType::TWO_WAY_FDATETIME) {
                  if ($ticket->ticket_trip_type->type == 'return') {
                    continue;
                  }
                  $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->getAuthUser(),$datetime);
                  if (count($tickets) > 1) {
                    $return_ticket = $this->Tickets->getReturnTicket($ticket->id);
                    if (!$return_ticket) {
                      $this->Flash->error(__('Return Ticket not found.'));
                      $this->ShoppingCart->clear();
                      return $this->redirect(['controller' => 'Attractions', 'action' => 'index']);
                    }
                    $two_way_price = [];
                    if ($return_ticket) {
                      $return_datetime = array_keys($ticket_list[$return_ticket->id])[0];
                      $return_ticket = $this->Tickets->loadInto($return_ticket, ['Attractions' => ['Merchants']]);
                      $return_ticket = $this->TicketsHelper->getFixeddateResellerPrice($return_ticket,$return_ticket->attraction,$this->getAuthUser(),$return_datetime);
                      $two_way_price = $this->_getTicketApiPrice($ticket, $datetime, $return_ticket, $return_datetime);
                    }
                    if (!empty($two_way_price)) {
                      if (empty($two_way_price['return'])) {
                        $this->Flash->error(__('Not enough stock for return ticket'));
                        $this->ShoppingCart->clear();
                        return $this->redirect(['controller' => 'Attractions', 'action' => 'view', $ticket->attraction->sku_id]);
                      }
                      $ticket->price = $two_way_price['depart'];
                      $return_ticket->price = $two_way_price['return'];
                      $two_way_ticket_prices[$ticket->sku_id] = $ticket->price;
                      $two_way_ticket_prices[$return_ticket->sku_id] = $return_ticket->price;
                    } else {
                      $this->Flash->error(__('Not enough stock'));
                      $this->ShoppingCart->clear();
                      return $this->redirect(['controller' => 'Attractions', 'action' => 'index']);
                    }
                  } else {
                    $this->Flash->error(__('Not enough stock'));
                    $this->ShoppingCart->clear();
                    return $this->redirect(['controller' => 'Attractions', 'action' => 'index']);
                  }
                }
                else {
                  $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->getAuthUser(),$datetime);
                  if ($ticket->markup_value && $ticket->markup_value > 0) {
                    $ticket->price = $this->_getTicketApiPrice($ticket, $datetime);
                  }
                }
                
                if ($max_quantity < $qty) {
                  $max_quantity = $qty;
                }
              }
            }
            if ($this->request->getSession()->check('language')) {
              Translator::translateTicket($this->request, $ticket);
            }
          }
          foreach ($tickets as $key => $ticket) {
            if (!empty($two_way_ticket_prices[$ticket->sku_id])) {
              $tickets[$key]->price = $two_way_ticket_prices[$ticket->sku_id];
            }
          }
        }
        $this->set(compact('ticket_list','max_quantity','tickets'));
      }
      else {
        return $this->redirect(['controller'=>'Attractions','action'=>'index']);
      }

      $tickets_with_question_group = $this->_getTicketsWithQuestionGroup($tickets);

      if ($this->request->is(['post','put','patch'])) {
        /* new order using api */
        $http = new \Cake\Http\Client();
        $data = [];
        if ($tickets && $tickets[0]->pricing_type === PricingType::TWO_WAY_FDATETIME) {
          $data = $this->_buildTwoWayData($ticket_list, $tickets_with_question_group, $tickets, $two_way_ticket_prices);
        } else {
          $data = $this->_buildData($ticket_list, $tickets_with_question_group);
        }

        if (empty($data)) {
          return $this->redirect(['action'=>'checkout']);
        }
        $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
        $api_url = Configure::read('Api.url');
        $response = $http->post($api_url.'/order/create',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
        $errorMessage = '<h4>' . __('Booking Temporarily Unavailable for this Supplier') . '</h4><br>' .
                         __('We\'re experiencing a temporary connection issue with the ticketing system. Please try again in a few minutes.') . '<br><br>' .
                         '<strong>' . __('What to do:') . '</strong><br>' .
                         '• ' . __('Wait 2-3 minutes and try your booking again') . '<br>' .
                         '• ' . __('If the issue persists, contact our support team') . '<br>' .
                         '• ' . __('Your payment has NOT been charged') . '<br><br>' .
                         __('Support Email: {0}', Configure::read('Site.support_email')) . '<br>' .
                         __('Support Phone: {0}', Configure::read('Site.company_contact_no'));
        if ($response->isOk()) {
          $res_data = $response->getJson();
          if (is_array($res_data) && $res_data['status'] == '1000') {
            $order_ref_id = $res_data['data']['order_ref_id'];
            $order = $this->Orders->find()->where(['order_ref_id'=>$order_ref_id])->first();
            if ($order) {
              $this->request->getSession()->write('order_id',$order->id);
              return $this->redirect(['action'=>'confirmation']);
            }
          } else if (is_array($res_data) && $res_data['status'] == '5008') {
            $this->log(json_encode($res_data),'error');
            $mapped_status = $this->_getMappedStatus($res_data['status']);
            $this->Flash->error('Error: ' . $mapped_status);
          } else if (is_array($res_data) && $res_data['status'] == '5007') {
            $this->Flash->error(__('Order Tracking Number already exists. Tracking Number should be unique or empty.'));
            $order->setError('tracking_no','Duplicate Tracking Number');
          } else {
            $this->log(json_encode($res_data),'error');

            // Handle both data directly or inside response key
            $error_data = !empty($res_data['response']['data']['error']) ? $res_data['response']['data']['error'] : 
                          (!empty($res_data['data']['error']) ? $res_data['data']['error'] : null);

            if (!empty($error_data)) {
              $mapped_status = $this->_getMappedStatus($res_data['status']);
              $error_message = 'Error: ' . $mapped_status;

              // Check if error is a string or array
              if (is_string($error_data)) {
                $error_message .= '. ' . $error_data;
              } elseif (is_array($error_data)) {
                // Format array errors nicely
                $error_details = [];
                foreach ($error_data as $field => $message) {
                  $error_details[] = ucfirst(str_replace('_', ' ', $field)) . ': ' . $message;
                }
                $error_message .= '. ' . implode(', ', $error_details);
              }

              $this->Flash->error($error_message);
            } elseif (is_array($res_data) && $res_data['status'] == '8001') {
              $this->Flash->error($errorMessage, ['escape' => false]);
            } else {
              $mapped_status = $this->_getMappedStatus($res_data['status']);
              $this->Flash->error('Error: ' . $mapped_status . '. ' . $res_data['message']);
            }
          }
        } else {
          $this->log($response->getStatusCode().' ' . $response->getStringBody(),'error');
          $this->Flash->error($errorMessage, ['escape' => false]);
        }
        /* end new order using api */

      }
      // $this->Agents = $this->fetchTable('Agents');
      // $agent = $this->Agents->find()->where(['id'=>$this->getAuthUser('agent_id')])->first();
      $agent = $this->getAuthUser();

      $this->set(compact('order','agent','tickets_with_question_group'));
    }

    /**
     * @param array<Ticket> $tickets
     * @return array
     */
    private function _getTicketsWithQuestionGroup($tickets) {
        $tickets_with_question_group = [];
        $attraction_skus = [];
        $ticket_sku_ids = [];
        foreach ($tickets as $ticket) {
            $attraction_skus[] = $ticket->attraction->sku_id;
            $ticket_sku_ids[] = $ticket->sku_id;
        }
        $http = new Client();
        $api_url = Configure::read('Api.url');
        foreach ($attraction_skus as $sku_id) {
            $data = ['sku_id'=>$sku_id,'from_mobile'=> true];
            $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
            try {
                $response = $http->get($api_url . '/attraction/details', $data, ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false, 'timeout' => Configure::read('Api.timeout')]);
                if ($response->isOk()) {
                    $res_data = $response->getJson();
                    if (!empty($res_data['response']['data']['tickets'])) {
                        $attraction_tickets = $res_data['response']['data']['tickets'];
                        foreach ($attraction_tickets as $attraction_ticket) {
                            if (in_array($attraction_ticket['sku_id'], $ticket_sku_ids) && !empty($attraction_ticket['question_group'])) {
                                foreach ($tickets as $ticket) {
                                    if ($ticket->sku_id === $attraction_ticket['sku_id']) {
                                        $tickets_with_question_group[$ticket->id] = $attraction_ticket['question_group'];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $this->log($e->getMessage(),'debug');
            }
        }
        return $tickets_with_question_group;
    }

    private function _updateOrderPrice($order,$tickets) {
      $order->subtotal = 0;
      if (!empty($tickets)) {
        foreach ($tickets as $key => $ticket) {
          if (!empty($order->order_items) && !empty($ticket)) {
            foreach ($order->order_items as $key => $order_item) {
              if ($order_item->ticket_id == $ticket->id) {
                $order_item->price = $ticket->price;
                $order_item->total = bcmul((string) $ticket->price, (string) $order_item->qty, 2);
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
        $request_nonce = md5(json_encode($sorted_data) . date('YmdHis') . $this->request->getAttribute('csrfToken'));
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
        return $q->contain(['Vouchers','Tickets'=>['Attractions']])->orderBy(['OrderItems.id'=>'ASC']);
      }])->where(['id'=>$this->request->getSession()->read('order_id')])->first();
      if (!$order) {
        return $this->redirect(['controller'=>'Attractions','action'=>'index']);
      }
      if ($order->status == 'done') {
        return $this->redirect(['controller'=>'Orders','action'=>'thankYou']);
      }
      if (!$this->_xcheckOrderCart($order)) {
        $this->Flash->error(__('Your shopping cart has change! Please re-enter the information needed.'));
        return $this->redirect(['action'=>'checkout']);
      }
      $total = 0;
      if ($this->request->is(['post','put','patch','ajax'])) {
        if ($this->request->getData('pay_by')) {

          // $order = $this->Orders->patchEntity($order,$this->request->getData());
          $order->agent_user_id = $this->getAuthUser('id');
          if ($this->request->getData('pay_by') == 'credits') {
            $order->convinience_fee = 0;
            $order->total = $order->subtotal;
            if (!$this->_xcheckOrderCart($order)) {
              $this->Flash->error(__('Your shopping cart has change! Please re-enter the information needed.'));
              return $this->redirect(['action'=>'checkout']);
            }
            if (!$this->_checkAvailableVouchers($order)) {
              $this->Flash->error(__('No vouchers available this time. Please contact admin.'));
              $order->setError('voucher','No vouchers available this time. Please contact admin.');
              return $this->redirect(['action'=>'confirmation']);
            }
            /* new order using api */
            $http = new \Cake\Http\Client();
            $api_url = Configure::read('Api.url');
            $data = ['order_ref_id'=>$order->order_ref_id,'action'=>'pay','from_mobile'=> true];
            $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
            try {
              $response = $http->post($api_url.'/order/update',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
              if ($response->isOk()) {
                $res_data = $response->getJson();
                if (empty($res_data)) {
                  Log::debug(json_encode($res_data));
                  $this->Flash->error('Internal server error. There is an error processing your order. Please contact admin.');
                }
                if ($res_data['status'] == '1000' || $res_data['status'] == '5006') {
                  if (!empty($res_data['data'])) {
                    $order_ref_id = $res_data['data']['order_ref_id'];
                    $order = $this->Orders->find()->where(['order_ref_id'=>$order_ref_id])->first();
                    if ($order) {
                      $this->request->getSession()->delete('order');
                      $this->ShoppingCart->clear();
                      return $this->redirect(['action'=>'thankYou']);
                      // return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id]]]));
                    } else {
                      $updated_order = $this->Orders->find()->where(['id'=>$order->id])->first();
                      return $this->redirect(['action'=>'thankYou']);
                      // return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$updated_order->status,'order_ref_id'=>$updated_order->order_ref_id]]]));

                    }
                  } else {
                    $this->log(json_encode($res_data),'debug');
                    $this->Flash->error(__('Order not found. Please contact admin.'));
                    // return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'message'=>'Order not found.']));
                  }
                } else {
                  // Handle both data directly or inside response key
                  $error_data = !empty($res_data['response']['data']['error']) ? $res_data['response']['data']['error'] : 
                                (!empty($res_data['data']['error']) ? $res_data['data']['error'] : null);

                  if (!empty($error_data)) {
                    $mapped_status = $this->_getMappedStatus($res_data['status']);
                    $error_message = 'Error: ' . $mapped_status;

                    // Check if error is a string or array
                    if (is_string($error_data)) {
                      $error_message .= '. ' . $error_data;
                    } elseif (is_array($error_data)) {
                      // Format array errors nicely
                      $error_details = [];
                      foreach ($error_data as $field => $message) {
                        $error_details[] = ucfirst(str_replace('_', ' ', $field)) . ': ' . $message;
                      }
                      $error_message .= '. ' . implode(', ', $error_details);
                    }

                    $this->Flash->error($error_message);
                  } elseif (!empty($res_data['message'])) {
                    $mapped_status = $this->_getMappedStatus($res_data['status']);
                    $error_message = $res_data['message'] ?: '';
                    $mapped_status_text = trim(preg_replace('/^\d{4}\s*/', '', $mapped_status));
                    if ($mapped_status_text !== '' && strpos($error_message, $mapped_status_text) !== false) {
                      $this->Flash->error('Error: ' . $res_data['status'] . '. ' . $error_message);
                    } else {
                      $this->Flash->error('Error: ' . $mapped_status . '. ' . $error_message);
                    }

                  } elseif ($res_data['status'] == '503') {
                    $this->Flash->error('Error: ' . $res_data['status'] . '. Service Unavailable. Please try again.');
                  } else {
                    $mapped_status = $this->_getMappedStatus($res_data['status']);
                    $this->Flash->error('Error: ' . $mapped_status . '. There is an error processing your order. Please contact admin.');
                  }
                  // return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>$res_data['status'],'order_ref_id'=>$order->order_ref_id]));
                }
              } else {
                $updated_order = $this->Orders->find()->where(['id'=>$order->id])->first();
                if (in_array($updated_order->status,['done','waiting'])) {
                  return $this->redirect(['action'=>'thankYou']);
                } else {
                  $this->Flash->error('There is an error processing your order. Please contact admin.');
                }
              }
            } catch (\Cake\Http\Exception\HttpException $e) {
              $this->request->getSession()->delete('order');
              $this->ShoppingCart->clear();
              return $this->redirect(['action'=>'thankYou']);
              // return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id]]]));
            } catch (Exception $e) {
              return $this->response->withStatus(500)->withStringBody(json_encode(['status'=>5101,'message'=>[$e->getMessage()]]));
            }

            /* end new order using api */
          }
          else {
            /* Payment */
            if ($this->Orders->setPaymentMethod($order, $this->request->getData('pay_by')) && $this->Orders->setConvenienceFee($order)) {
              $payment = new Payments();
              $payment_action = $payment->handlePayments($this->request->getData('pay_by'), $order->order_ref_id, $order->checkout_info, $order->total);
              if ($payment_action) {
                if (!empty($payment_action['redirect'])) {
                  return $this->redirect($payment_action['redirect']);
                }
              }
              $this->Flash->error(__('Selected payment method cannot be processed'));
            }

            /* End payment */
            // if ($this->request->getData('pay_by') == 'credit_card') {
            //   return $this->redirect(['action'=>'executeStripe']);
            // } elseif ($this->request->getData('pay_by') == 'wechat_pay') {
            //   return $this->redirect(['action'=>'executeStripe','wechat_pay']);
            // }
            // if ($this->request->is('ajax')) {
            //   $this->autoRender = false;
            //   if ($this->Orders->save($order)) {
            //     return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>200,'data'=>['order_id'=>$order->id]]));
            //   }
            //   else {
            //     return $this->response->withStatus(400)->withStringBody(json_encode(['status'=>200,'data'=>['errors'=>$order->getErrors()]]));
            //   }
            // }
            // else {
            //   // debug($this->request->getData());
            //   $this->request->getSession()->delete('order');
            //   $this->ShoppingCart->clear();
            //   return $this->redirect(['action'=>'thankYou']);
            // }
          }
        }
        $this->request->getSession()->write('order_id',$order->id);
      }

      /* translate tickets */
      if ($this->request->getSession()->check('language')) {
        foreach ($order->order_items as $order_item) {
          Translator::translateTicket($this->request, $order_item->ticket);
        }
      }
      /* end translate tickets */

      $ticket_list = $this->ShoppingCart->getAll();
      $tickets = [];

      if (!empty($ticket_list)) {
        $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'=>['Merchants']])->toArray();
        if (!empty($tickets)) {
          $tickets_with_question_group = $this->_getTicketsWithQuestionGroup($tickets);
          $this->set(compact('tickets_with_question_group'));
        }
      }

      $markups = Payments::getPaymentMarkups();
      $payment_providers = Configure::read('Payment.providers');
      $other_payment_methods = Payments::getPaymentMethods();
      $is_credits_enabled = Payments::isCreditsEnabled();
      
      $this->set(compact('order', 'other_payment_methods', 'markups', 'payment_providers', 'is_credits_enabled'));
    }

    public function completeOrder($order_ref_id) {
      $order = $this->Orders->find()->where(['order_ref_id'=>$order_ref_id])->first();
      if ($order) {
        $http = new \Cake\Http\Client();
        $api_url = Configure::read('Api.url');
        $data = ['order_ref_id'=>$order_ref_id,'action'=>'complete'];
        $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
        try {
          $response = $http->post($api_url.'/order/complete',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
          if ($response->isOk()) {
            $res_data = $response->getJson();

            if ($res_data['status'] == '1000' || $res_data['status'] == '5006') {
              if (!empty($res_data['data'])) {
                $order_ref_id = $res_data['data']['order_ref_id'];
                $order = $this->Orders->find()->where(['order_ref_id'=>$order_ref_id])->first();
                if ($order) {
                  $agent = $this->Agents->find()->where(['id'=>$this->getAuthUser('agent_id')])->first();
                  if ($agent) {
                    $auth_user = $this->getAuthUser();
                    $auth_user['credits'] = $agent->credits;
                    $this->setAuthUser($auth_user);
                  }
                  $this->request->getSession()->delete('order');
                  $this->ShoppingCart->clear();
                  // return $this->redirect(['action'=>'thankYou']);
                  return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id]]]));
                } else {
                  $updated_order = $this->Orders->find()->where(['id'=>$order->id])->first();
                  return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$updated_order->status,'order_ref_id'=>$updated_order->order_ref_id]]]));

                }
              } else {
                $this->log(json_encode($res_data),'debug');
                return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id]]]));
              }
            } else {
              return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id]]]));
            }
          } else {
            return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id]]]));
          }

        } catch (\Cake\Http\Exception\HttpException $e) {
          return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id]]]));
        } catch (Exception $e) {
          $this->log($e,'debug');
          return $this->response->withStatus(500)->withStringBody(json_encode(['status'=>5101,'message'=>[$e->getMessage()]]));
        }
      }

    }

    public function getStatus() {
       /* new order using api */
       $order = $this->Orders->find()->where(['id'=>$this->request->getSession()->read('order_id')])->first();
       if (!$order) {
        return $this->response->withStatus(404)->withStringBody('Order not found');
       }
       $this->autoRender = false;
       $http = new \Cake\Http\Client();
       $api_url = Configure::read('Api.url');
       $data = ['order_ref_id'=>$order->order_ref_id];
       $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
      try {
        $response = $http->get($api_url.'/order/status',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
        if ($response->isOk()) {
          $res_data = $response->getJson();
          if ($res_data['status'] == '1000') {
            return $this->response->withStatus(200)->withStringBody(json_encode([
              'status'=>1000,
              'data'=>[
                'order'=>[
                  'status'=>$res_data['response']['data']['status'],
                  'order_ref_id'=>$res_data['response']['data']['order_ref_id']
                  ]
                ]
              ]));
          } else {
            return $this->response->withStatus(500)->withStringBody(json_encode($res_data));
          }
        } else {
          \Cake\Log\Log::error('Order status API response not ok: ' . $response->getStatusCode());
          return $this->response->withStatus(500)->withStringBody('Response not ok');
        }
      } catch (\Cake\Http\Exception\HttpException $e) {
        return $this->response->withStatus(500)->withStringBody($e->getMessage());
      } catch (Exception $e) {
        return $this->response->withStatus(500)->withStringBody(json_encode(['status'=>5101,'message'=>[$e->getMessage()]]));
      }
      //  $order = $this->Orders->find()->where(['id'=>$this->request->getSession()->read('order_id')])->first();
      //  if ($order) {
      //    return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>1000,'data'=>['order'=>['status'=>$order->status,'order_ref_id'=>$order->order_ref_id,'payment_method'=>$order->payment_method]]]));
      //  }
      //  return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>404,'message'=>'No order found.']));
    }

    public function executeStripe($type = null) {
      $this->viewBuilder()->setlayout('empty');
      $session_id = $_SESSION['session_id'];
      unset($_SESSION['session_id']);
      $this->set('session_id', $session_id);
          
    }

    public function sps() {
      $this->notificationReceiver('Stripe');
      return $this->response->withStatus(200);
    }

    public function frontendNotificationReceiver($provider, $order_ref_id = null) {
      if ($this->request->is('post')) {
        $order = null;
        if ($order_ref_id) {
          $order = $this->Orders->find()->where(['Orders.order_ref_id' => $order_ref_id])->contain(['OrderItems'=>['Tickets'=>['Attractions'],'Vouchers'],'AgentUsers'=>['Agents']])->first();
        } else {
          $order_id = $this->request->getSession()->read('order_id');
          if (!empty($order_id)) {
            $order = $this->Orders->find()->where(['Orders.id'=>$order_id])->contain(['OrderItems'=>['Tickets'=>['Attractions'],'Vouchers'],'AgentUsers'=>['Agents']])->first();
          } else {
            $this->Flash->error(__('Order not found.'));
            return $this->redirect(['controller' => 'Attractions', 'action' => 'index']);
          }
        }
        if (!$order) {
          $this->Flash->error(__('Order not found.'));
          return $this->redirect(['controller' => 'Attractions', 'action' => 'index']);
        }
        
        $payment = new Payments();
        $payment_success = $payment->isPaymentSuccess($provider, $order, $this->request->getData());
        if (!$payment_success) {
          $this->log('invalid payment','debug');
          $this->Flash->error(__('Invalid payment.'));
          return $this->redirect(['action' => 'confirmation']);
        }
        return $this->redirect(['action' => 'thankYou']);
      }
      return $this->response->withStatus(404);
    }

    /**
     * Receive backend payment notificaiton from providers
     *
     * @return Response
     */
    public function notificationReceiver($provider) {
      if ($this->request->is('post')) {
        $this->PaymentResponses = $this->fetchTable('PaymentResponses');
        if ($provider == 'Paypal' && $this->request->getData('event_type') !== 'CHECKOUT.ORDER.APPROVED') {
          return $this->response->withStatus(200);
        }
        $payment = new Payments();
        
        $payment_response = $this->PaymentResponses->create($provider,$this->request->getData());
        $order_ref_id = $payment->getOrderRefId($provider, $this->request->getData());
        $order = null;

        if ($order_ref_id) {
          $order = $this->Orders->find()->where(['order_ref_id' => $order_ref_id])->first();
        }
        
        if ($order && $order->status == 'pending') {
          $this->PaymentResponses->setOrderRefId($payment_response, $order_ref_id);
          $verified_payment = $payment->verifyPayment($provider, $order, $this->request->getData(), $this->request->getHeaders());
          if (!$verified_payment) {
            $this->log('invalid payment','debug');
            return $this->response->withStatus(200);
          }
          $order->status = 'in_progress';
          if (!$this->Orders->save($order)) {
            $this->log($order,'error');
            return;
          }
          $http = new \Cake\Http\Client();
          $api_url = Configure::read('Api.url');
          $data = ['order_ref_id'=>$order->order_ref_id,'action'=>'complete'];
          $agent_user = $this->AgentUsers->find()->where(['id'=>$order->agent_user_id])->first();
          $headers = $this->_buildHeaders($data,$agent_user->email,$agent_user->id);
          try {
            $response = $http->post($api_url.'/order/complete',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
            if ($response->isOk()) {
              $res_data = $response->getJson();
              // $this->log(json_encode($res_data),'debug');
            } else {
              $cat_mailer = new CatMailer(Configure::read('Default.from_email'));
              $cat_mailer->mail('exception_email', 'Error completing order ' . $order->order_ref_id, Configure::read('Default.admin_email'), [
                  'cause' => 'Complete Order after non-credit payment',
                  'exception' => 'Complete order did not return Ok'
              ], null, Configure::read('Default.exception_emails'));
            }
          } catch (Exception $e) {
            $cat_mailer = new CatMailer(Configure::read('Default.from_email'));
              $cat_mailer->mail('exception_email', 'Error completing order ' . $order->order_ref_id, Configure::read('Default.admin_email'), [
                  'cause' => 'Complete Order after non-credit payment',
                  'exception' => $e->getMessage()
              ], null, Configure::read('Default.exception_emails'));
          }
          
        }
        return $this->response->withStatus(200);
      }
      return $this->response->withStatus(404);
    }

    public function thankYou() {
      $order_id = $this->request->getSession()->read('order_id');
      $order = $this->Orders->find()->where(['Orders.id'=>$order_id])->first();

      if ($order && in_array($order->status,['in_progress', 'done'])) {
        $this->request->getSession()->delete('order_id');
        $this->request->getSession()->delete('order');
        $this->request->getSession()->delete('multiple_redemption');
        $this->ShoppingCart->clear();
      }

      if ($order) {
        $this->set(compact('order'));
      }
    }

    public function downloadInvoice($order_ref_id) {
      if (!Configure::read('Features.invoice')) {
        return $this->response->withStatus(404); 
      }
      
      $pdf = $this->Orders->createInvoice($order_ref_id, $this->getAuthUser());
      if ($pdf === false) {
        return $this->response->withStatus(404);
      }

      return $this->response->withStatus(200)->withStringBody($pdf)->withDownload('INVOICE_'.$order_ref_id.'.pdf');
    }

    private function _getTicketApiPrice($ticket, $strdatetime, $return_ticket = null, $str_return_datetime = null) {
      $http = new \Cake\Http\Client();
      $api_url = Configure::read('Api.url');
      $datetime = new \Cake\I18n\DateTime($strdatetime);
      $return_datetime = null;
      $time_from = $datetime->format('Y-m-d');
      $time_to = null;
      if ($ticket->pricing_type === PricingType::TWO_WAY_FDATETIME) {
        $time_from = $datetime->format('Y-m-d H:i');
        $return_datetime = new \Cake\I18n\DateTime($str_return_datetime);
        $time_to = $return_datetime->format('Y-m-d H:i');
      }
      if ($ticket->pricing_type === PricingType::TWO_WAY_FDATETIME) {
        $ticket_price = [];
        try {
          $data = ['depart_sku_id' => $ticket->sku_id, 'return_sku_id' => $return_ticket->sku_id, 'depart_datetime' => $time_from,'return_datetime' => $time_to, 'is_two_way' => true];
          $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
          $response = $http->get($api_url . '/ticket/availabilities', $data, ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false, 'timeout' => Configure::read('Api.timeout')]);
          if ($response->isOk()) {
            $res = $response->getJson();
            if (!empty($res['response']['data'])) {
              foreach ($res['response']['data'] as $date => $available_tickets) {
                foreach ($available_tickets as $key => $available_ticket) {
                  $ticket = $this->Tickets->find()->where(['sku_id' => $available_ticket['sku_id']])->contain(['TicketTripTypes'])->first();
                  // debug($ticket);
                  if ($ticket->ticket_trip_type->type == 'depart') {
                    $ticket_price['depart'] = $ticket->price;
                    if ($ticket->markup_value && $ticket->markup_value > 0 && isset($available_ticket['price'])) {
                      $ticket_price['depart'] = $available_ticket['price'];
                    }
                  } else {
                    if ($return_ticket->sku_id == $available_ticket['sku_id']) {
                      $ticket_price['return'] = $return_ticket->price;
                      if ($return_ticket->markup_value && $return_ticket->markup_value > 0 && isset($available_ticket['price'])) {
                        $ticket_price['return'] = $available_ticket['price'];
                      }
                    }
                    
                  }
                }
              }
            }
          }
        } catch (\Exception $e) {
          \Cake\Log\Log::error((string)json_encode($e->getMessage()));
        }

        return $ticket_price;
      } else {
        try {
          $data = ['sku_id' => $ticket->sku_id, 'time_from' => $time_from,'time_to' => $time_from];
          $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
          $response = $http->get($api_url.'/ticket/availabilities',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
          if ($response->isOk()) {
            $res = $response->getJson();
            if (!empty($res['response']['data'])) {
              $availabilities = $res['response']['data'];
              $api_price = 0;
              foreach ($availabilities as $date => $date_availabilities) {
                if ($date == $datetime->format('Y-m-d')) {
                  foreach ($date_availabilities as $availability) {
                    if (isset($availability['time']) && in_array($ticket->pricing_type, [PricingType::FDATETIME, PricingType::TWO_WAY_FDATETIME])) {
                      if ($availability['time'] == $datetime->format('H:i') && !empty($availability['price'])) {
                        $api_price = $availability['price'];
                      }
                    } else {
                      $api_price = $availability['price'];
                    }
                  }
                }
              }
              return $api_price;
            }
          }
        } catch (Exception $e) {
          Log::debug($e->getMessage());
        }
      }

      return null;
    }

    private function _checkAvailableVouchers($order = null, $ticket_list = null) {
      $is_voucher_enough = true;
      $now = DateTime::now();
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
                $barcode_count = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now ])->count();
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
                  $this->Flash->error(__('Not enough stock for {0} - {1} Only {2} left. Please call administrator. ', $ticket->attraction->title, $ticket->name, $pdf_count) . Configure::read('Default.admin_contact'));
                  $is_voucher_enough = false;
                }
              }
              elseif ($_ticket->voucher_type =='barcode') {
                if (!isset($total_barcode_needed[$_ticket->id])) {
                  $total_barcode_needed[$_ticket->id] = 0;
                }
                $total_barcode_needed[$_ticket->id] += $ticket_total_qty;
                $barcode_count = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now ])->count();
                if ($barcode_count < $total_barcode_needed[$_ticket->id]) {
                  $this->Flash->error(__('Not enough stock for {0} - {1} Only {2} left. Please call administrator. ', $ticket->attraction->title, $ticket->name, $barcode_count) . Configure::read('Default.admin_contact'));
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
                  $this->Flash->error(__('Not enough stock for {0} - {1} Only {2} left. Please call administrator. ', $ticket->attraction->title, $ticket->name, $_ticket->stock) . Configure::read('Default.admin_contact'));
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
      $now = DateTime::now();
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
                $package_voucher = $this->Vouchers->newEmptyEntity();
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
                $package_voucher->created = DateTime::now();
                if (!$this->GeneratedCodes->save($pk_generated_code)) {
                  return "Error saving generated code";
                }

                foreach ($ticket->child_tickets as $key => $child_ticket) {
                  if ($child_ticket->api_purchase) {
                    $api_purchases++;
                    continue;
                  }
                  $voucher = $this->Vouchers->newEmptyEntity();
                  $voucher->created = DateTime::now();
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
                    $pdf = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$child_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now])->orderBy(['Pdfs.created'=>'asc','Pdfs.id'=>'asc'])->epilog('FOR UPDATE')->first();
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
                    $barcode = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$child_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now ])->orderBy(['Barcodes.created'=>'asc','Barcodes.id'=>'asc'])->epilog('FOR UPDATE')->first();
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
                $voucher = $this->Vouchers->newEmptyEntity();
                $voucher->created = DateTime::now();
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
                  $pdf = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$ticket->sku_id,'status'=>'new','validity_end_date >='=>$now])->orderBy(['Pdfs.created'=>'asc','Pdfs.id'=>'asc'])->epilog('FOR UPDATE')->first();
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
                  $barcode = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$ticket->sku_id,'status'=>'new','validity_end_date >='=>$now ])->orderBy(['Barcodes.created'=>'asc','Barcodes.id'=>'asc'])->epilog('FOR UPDATE')->first();
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
                    $this->log(json_encode(['voucher'=>$ev,'order_ref_id'=>$order->order_ref_id]),'debug');
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
            $this->Mailer->mail('Order','new_order','New Booking Request (Order Reference '.$order->order_ref_id.') at ' . Configure::read('Default.site_name'),Configure::read('Default.company_email'),['checkout_info'=>$checkout_info,'order'=>$order,'ticket'=>$ticket,'vouchers'=>$vouchers],null,null,$attachments);
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
                    $dt = DateTime::createFromFormat('d M Y H:i',$datetime);
                    $order_item = $this->Orders->OrderItems->find()->where(['ticket_id'=>$ticket->id,'order_id'=>$order->id,'qty'=>$qty,'date_of_visit'=>$dt->format('Y-m-d'),'time_of_visit'=>$dt->format('H:i:s')])->first();
                    if (!$order_item) {
                      return false;
                    }
                  }
                  else {
                    $dt = DateTime::createFromFormat('d M Y',$datetime);
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
        $this->AgentUsers = $this->fetchTable('AgentUsers');
        $conditions = ['agent_user_id'=>$this->getAuthUser('id')];
        if ($this->getAuthUser('role') == 'master') {
          $agent_user_ids = $this->AgentUsers->find('list', valueField: 'id')->where(['agent_id'=>$this->getAuthUser('agent_id')])->all()->toList();
          if (empty($agent_user_ids)) {
            $agent_user_ids = [$this->getAuthUser('id')];
          }
          $conditions = ['agent_user_id IN' => $agent_user_ids];
        }

        if ($this->request->getQuery('order_ref_id')) {
            $conditions['order_ref_id like'] = $this->request->getQuery('order_ref_id');
        }

        if ($this->request->getQuery('tracking_no')) {
            $conditions['tracking_no like'] = '%' . $this->request->getQuery('tracking_no') . '%';
        }

        $conditions['Orders.status IN'] = ['done','in_progress','waiting','cancelled','refunded','rejected'];
        if ($this->request->getQuery('status') && in_array($this->request->getQuery('status'),['done','cancelled','refunded','rejected'])) {
            $conditions['Orders.status'] = $this->request->getQuery('status');
        } elseif ($this->request->getQuery('status') && $this->request->getQuery('status') == 'waiting') {
          $conditions['Orders.status IN'] = ['in_progress','waiting'];
        }
        $first_date_allowed = (new DateTime())->subMonths(Configure::read('Default.previous_orders_months_to_view'));
        $from = $first_date_allowed;
        $this->paginate = [
          'order'=>['created'=>'desc']
        ];
        $query = $this->Orders->find();
        if ($this->request->getQuery('date_type') == 'visit_date') {
          if ($this->request->getQuery('from')) {
            $start_date = null;
            try {
              $start_date = DateTime::createFromFormat('d M Y',$this->request->getQuery('from'));
            } catch (Exception $e) {
              // just ignore the exception for now
            }
            if ($start_date) {
              if ($start_date < $first_date_allowed) {
                $start_date = $first_date_allowed;
              }
              $query = $query->matching('OrderItems', function ($q) use($start_date) {
                return $q->where(['date_of_visit >=' => $start_date]);
              });
            }
          } else {
            $start_date = (new \Cake\I18n\DateTime('-6 months'))->hour(0)->minute(0)->second(0);
            $query = $query->matching('OrderItems', function ($q) use($start_date) {
              return $q->where(['date_of_visit >=' => $start_date]);
            });
          }
          if ($this->request->getQuery('to')) {
            $end_date = null;
            try {
              $end_date = DateTime::createFromFormat('d M Y',$this->request->getQuery('to'));
            } catch (Exception $e) {
              // just ignore the exception for now
            }
            if ($end_date) {
              $query = $query->matching('OrderItems', function ($q) use($start_date) {
                return $q->where(['date_of_visit <=' => $start_date]);
              });
            }
          }
          
        } else {
          if ($this->request->getQuery('from')) {
            $start_date = null;
            try {
              $start_date = DateTime::createFromFormat('d M Y',$this->request->getQuery('from'));
            } catch (Exception $e) {
              // just ignore the exception for now
            }
            if ($start_date) {
              if ($start_date < $first_date_allowed) {
                $start_date = $first_date_allowed;
              }
              $query = $query->where(['Orders.created >=' => $start_date->hour(0)->minute(0)->second(0)]);
            }
          } else {
            $start_date = (new \Cake\I18n\DateTime('-6 months'))->hour(0)->minute(0)->second(0);
            $query = $query->where(['Orders.created >=' => $start_date->hour(0)->minute(0)->second(0)]);
          }
          if ($this->request->getQuery('to')) {
            $end_date = null;
            try {
              $end_date = DateTime::createFromFormat('d M Y',$this->request->getQuery('to'));
            } catch (Exception $e) {
              // just ignore the exception for now
            }
            if ($end_date) {
              $query = $query->where(['Orders.created <=' => $end_date->hour(23)->minute(59)->second(59)]);
            }
          }
        }
        $orders = $this->paginate($query->where($conditions)->contain(['OrderItems'=>['Tickets']]));

        if ($this->request->getSession()->check('language')) {
          foreach ($orders as $order) {
            foreach ($order->order_items as $order_item) {
              $order_item->ticket = Translator::translateTicket($this->request, $order_item->ticket);
            }
          }
        }

        $this->set(compact('orders'));
    }

    /**
     * My Tickets method
     *
     * @return \Cake\Http\Response|null
     */
    public function myTickets()
    {
        $this->AgentUsers = $this->fetchTable('AgentUsers');
        $this->Vouchers = $this->fetchTable('Vouchers');
        $conditions = [];
        $order_conditions = ['Orders.agent_user_id'=>$this->getAuthUser('id')];
        $status_allowed = ['purchased','used','partially_redeemed_expired','partially_redeemed_cancelled','cancelled','expired'];
        if ($this->getAuthUser('role') == 'master') {
          $agent_user_ids = $this->AgentUsers->find('list', valueField: 'id')->where(['agent_id'=>$this->getAuthUser('agent_id')])->all()->toList();
          if (empty($agent_user_ids)) {
            $agent_user_ids = [$this->getAuthUser('id')];
          }
          $order_conditions = ['Orders.agent_user_id IN' => $agent_user_ids];
        }

        if ($this->request->getQuery('code')) {
            $conditions['code_id like'] = $this->request->getQuery('code').'%';
        }

        $first_date_allowed = (new DateTime())->subMonths(Configure::read('Default.previous_orders_months_to_view'));
        $from = $first_date_allowed;        
        if ($this->request->getQuery('from')) {
          $from = DateTime::createFromFormat('d M Y', $this->request->getQuery('from'));
          $from = $from->hour(0)->minute(0)->second(0);
          if ($from < $first_date_allowed) {
            $from = $first_date_allowed;
          }
          $to = DateTime::now();
          if ($this->request->getQuery('to')) {
            $to = DateTime::createFromFormat('d M Y', $this->request->getQuery('to'));
          }
          $to = $to->hour(23)->minute(59)->second(59);
          $conditions['Vouchers.created <='] = $to;
        } else {
          $from = (new \Cake\I18n\DateTime('-6 months'))->hour(0)->minute(0)->second(0);
        }
        $from = $from->hour(0)->minute(0)->second(0);
        $conditions['Vouchers.created >='] = $from;
        $this->paginate = [
          'order'=>['Vouchers.created'=>'desc'],
        ];
        $query = $this->Vouchers->find()->matching('OrderItems.Orders',function ($q) use ($order_conditions) {
          return $q->where($order_conditions);
        });
        $vouchers = $this->paginateToArray($query->where($conditions)->contain(['OrderItems'=>['Orders']]));
        foreach ($vouchers as $key => $voucher) {
          $voucher->code_status = $this->Vouchers->getStatus($voucher->code_id, $voucher->voucher_type);
          $voucher->is_cancellable = $this->Vouchers->isCancellable($voucher->code_id, $this->getAuthUser('agent_id'));

          // if ($this->request->getQuery('status') && in_array($this->request->getQuery('status'),$status_allowed)) {
          //   if ($voucher->code_status != $this->request->getQuery('status')) {
          //     unset($vouchers[$key]);
          //   }
          // }
        }

        $this->set(compact('vouchers'));
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
      $order = null;
      $http = new \Cake\Http\Client();
      $api_url = Configure::read('Api.url');
      $data = ['order_ref_id'=>$id];
      $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
      try {
        $response = $http->get($api_url.'/order/details',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
        if ($response->isOk()) {
          $res_data = $response->getJson();
          if ($res_data['status'] == '1000') {
            $order = $res_data['data'];
            $this->VoucherMerging = $this->fetchTable('VoucherMerging');
            $voucher_merging = $this->VoucherMerging->find()->where(['order_ref_id' => $order['order_ref_id'], 'agent_id' => $this->getAuthUser('agent_id')])->first();
            // $expired_voucher_merging = FrozenTime::now(Configure::read('App.defaultTimezone'))->subMinutes(Configure::read('Voucher.merged_expiration') ?? '60');
            // if ($voucher_merging && $voucher_merging->created < $expired_voucher_merging) {
            //   $voucher_merging = null;
            // }
            $this->set(compact('order', 'voucher_merging'));
          } else {
            $this->Flash->error(__('Order not found.'));

            return $this->redirect(['controller' => 'Orders', 'action' => 'index']);
          }
        } else {
          $this->Flash->error(__('Order not found.'));

          return $this->redirect(['controller' => 'Orders', 'action' => 'index']);
        }
        
      } catch (\Cake\Http\Exception\HttpException $e) {
        $this->Flash->error(__('Order not found.'));
      } catch (Exception $e) {
        $this->Flash->error(__('Order not found.'));
      }

    }

  //   public function cancelOrder($order_ref_id) {
  //     /* new order using api */
  //     $this->request->allowMethod(['post','delete']);
  //     $this->autoRender = false;
  //     $http = new \Cake\Http\Client();
  //     $api_url = Configure::read('Api.url');
  //     $data = ['order_ref_id'=>$order_ref_id,'action'=>'cancel'];
  //     $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
  //    try {
  //      $response = $http->post($api_url.'/order/update',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
  //      if ($response->isOk()) {
  //        $res_data = $response->getJson();
  //        if ($res_data['status'] == '1000') {
  //         $this->Flash->success('Successfully cancelled order '.$order_ref_id);
  //        } else {
  //         $msg = 'There was an error cancelling order '.$order_ref_id.'. Error code: '.$res_data['status'].'.';
  //         if (!empty($res_data['response']['data']['error'])) {
  //           $msg .= ' ' . $res_data['response']['data']['error'] . '.';
  //         }
  //         $this->Flash->error($msg);
  //        }
  //      } else {
  //        $this->Flash->error('There was an error cancelling order '.$order_ref_id.'.');
  //      }
  //    } catch (\Cake\Http\Exception\HttpException $e) {
  //       $this->Flash->error('There was an error cancelling order '.$order_ref_id.'. '.$e->getMessage());
  //    } catch (Exception $e) {
  //     $this->Flash->error('There was an error cancelling order '.$order_ref_id.'. '.$e->getMessage());
  //    }
  //    return $this->redirect($this->referer());
  //  }

    public function cancelVoucher($code) {
      /* new order using api */
      $this->request->allowMethod(['post','delete']);
      $this->autoRender = false;
      // Same rules as the Cancel button (own agent, cancel allowed, credits order, not redeemed),
      // enforced here so a crafted POST can't cancel other agents' vouchers.
      if (!$this->Vouchers->isCancellable($code, $this->getAuthUser('agent_id'))) {
        $this->Flash->error(__('Voucher {0} cannot be cancelled.', $code));
        return $this->redirect($this->referer());
      }
      $http = new \Cake\Http\Client();
      $api_url = Configure::read('Api.url');
      $data = ['code'=>$code,'action'=>'cancel'];
      $headers = $this->_buildHeaders($data,$this->getAuthUser('email'),$this->getAuthUser('id'));
      try {
        $response = $http->post($api_url.'/voucher/cancel',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
        if ($response->isOk()) {
          $res_data = $response->getJson();
          if ($res_data['status'] == '1000') {
          $this->Flash->success(__('Successfully cancelled voucher {0}', $code));
          } else {
          $msg = 'There was an error cancelling voucher '.$code.'. Error code: '.$res_data['status'].'.';
          if (!empty($res_data['message'])) {
            $msg .= ' ' . $res_data['message'] . '.';
          }
          if (!empty($res_data['response']['data']['error'])) {
            $msg .= ' ' . $res_data['response']['data']['error'] . '.';
          }
          $this->Flash->error($msg);
          }
        } else {
          $this->Flash->error(__('There was an error cancelling voucher {0}.', $code));
        }
      } catch (\Cake\Http\Exception\HttpException $e) {
        $this->Flash->error(__('There was an error cancelling voucher {0}. {1}', $code, $e->getMessage()));
      } catch (Exception $e) {
        $this->Flash->error(__('There was an error cancelling voucher {0}. {1}', $code, $e->getMessage()));
      }
      return $this->redirect($this->referer());
    }

    private function __convert_to_csv($input_array, $output_file_name, $delimiter) {
        /** open raw memory as file, no need for temp files */
        $temp_memory = fopen('php://memory', 'w');
        /** loop through array  */
        foreach ($input_array as $line) {
          // debug($line);
          /** default php csv handler * */
          fputcsv($temp_memory, $line, $delimiter, '"', '\\');
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
        $folder = new \App\Filesystem\Folder(WWW_ROOT. 'files/pdf',true,0775);
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

    private function _buildHeaders($data,$email,$user_id) {
      $secret_key = Configure::read('Api.secret');

      $header = [];
      $header['X-API-Version'] = 'v1.10';
      $header['Date'] = date('Y-m-d H:i:s');
      $header['X-CA-User'] = $email;
      $header['X-Api-Role'] = 'admin';
      $header['X-Ca-Nonce'] = \Cake\Utility\Text::uuid();
      $header['X-Ca-SignatureMethod'] = 'HmacSHA256';
      $header['X-CA-UserSecret'] = hash('SHA256', $email.$user_id);
      $textToSign = $header['X-CA-User']."\n".
            $header['Date']."\n".
            $header['X-Ca-SignatureMethod']."\n".
            $header['X-Ca-Nonce'];
      $signature = base64_encode(hash_hmac('SHA256', $textToSign, $secret_key, true));
      $header['Authorization'] = 'Bearer '.$signature;
      return $header;
    }

    private function _buildData($ticket_list, $tickets_with_question_group) {
      $subtotal = '0';
      $data = [
        'tracking_no' => trim(strip_tags($this->request->getData('tracking_no'))) ? trim(strip_tags($this->request->getData('tracking_no'))) : '',
        'first_name'=>trim(strip_tags($this->request->getData('first_name'))),
        'last_name'=>trim(strip_tags($this->request->getData('last_name'))),
        'customer_name' => trim(strip_tags($this->request->getData('first_name').' '.$this->request->getData('last_name'))),
        'customer_email' => trim(strip_tags($this->request->getData('email'))),
        'customer_mobile' => trim(strip_tags($this->request->getData('mobile'))),
        'currency'=>'SGD',
        'use_credits'=> true,
        'email_voucher'=> true,
        'from_mobile'=>true
      ];

      $no = 1;
      foreach ($ticket_list as $key_ticket_id => $date_ticket) {
        $ticket = $this->Tickets->find()->where(['Tickets.id'=>$key_ticket_id])->contain(['Attractions'=>['Merchants']])->first();
        if (!isset($ticket_total[$ticket->id])) {
          $ticket_total[$ticket->id] = 0;
        }

        foreach ($date_ticket as $date => $qty) {
          $ticket_total[$ticket->id] += $qty;

          $data['item_sku_id_'.$no] = $ticket->sku_id;
          $data['item_quantity_'.$no] = $qty;

          if ($ticket) {
            $_ticket_id = $ticket->id;
            if ($ticket->attraction->pricing_type == 'settlement') {
              $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$ticket->attraction,$this->getAuthUser());
              if ($ticket &&  ($qty > ($ticket->stock !== null ? $ticket->stock : Configure::read('Default.max_order_quantity')))) {
                $this->Flash->error(__('Sorry you have exceeded available {0} tickets.', $ticket->name));
                return false;
              }
              elseif (!$ticket) {
                $this->ShoppingCart->remove($_ticket_id);
              }
            }
            else {
              $ticket = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$ticket->attraction,$this->getAuthUser(),$date);
              if ($ticket->markup_value && $ticket->markup_value > 0) {
                $ticket->price = $this->_getTicketApiPrice($ticket, $date);
              }
              if ($ticket && ($qty > $this->TicketsHelper->getTicketAvailableQty($ticket,$date))) {
                $this->Flash->error(__('Sorry you have exceeded available {0} tickets.', $ticket->name));
                return false;
              }
              elseif (!$ticket) {
                $this->ShoppingCart->remove($_ticket_id);
              }
            }
            if ($ticket) {
              $subtotal = bcadd((string) $subtotal, bcmul((string) $ticket->price, $qty, 2), 2);
              if (!empty($ticket->min_quantity) && $qty < $ticket->min_quantity) {
                $this->Flash->error(__('Minimum quantity for {0} should be {1}.', $ticket->name, $ticket->min_quantity));
                return false;
              }
            }
          }

          if ($ticket) {
            if ($ticket->attraction->pricing_type != 'settlement') {
              // $order_data['order_data'][$idx]['date_of_visit'] = $date;

              if (in_array($ticket->attraction->pricing_type, [PricingType::FDATETIME, PricingType::TWO_WAY_FDATETIME])) {
                $datetime = DateTime::createFromFormat('d M Y H:i',$date);
                $data['item_booking_date_'.$no] = $datetime->format('Y-m-d H:i:s');
              }
              else {
                $datetime = DateTime::createFromFormat('d M Y',$date);
                $data['item_booking_date_'.$no] = $datetime->format('Y-m-d');
              }
            }

            $order_data = [];
            if ($this->request->getData('passport_number.'.$ticket->id.'.'.$date)) {
              for ($idx=0; $idx < count($this->request->getData('passport_number.'.$ticket->id.'.'.$date)); $idx++) {
                if ($ticket->require_travellers_info) {

                  if ($this->request->getData('passport_number.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['passport_number'] = trim(strip_tags($this->request->getData('passport_number.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                  if ($this->request->getData('passport_expiry.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['passport_expiry'] = trim(strip_tags($this->request->getData('passport_expiry.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                  if ($this->request->getData('passport_first_name.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['passport_first_name'] = trim(strip_tags($this->request->getData('passport_first_name.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                  if ($this->request->getData('passport_last_name.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['passport_last_name'] = trim(strip_tags($this->request->getData('passport_last_name.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                  if ($this->request->getData('date_of_birth.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['date_of_birth'] = trim(strip_tags($this->request->getData('date_of_birth.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                  if ($this->request->getData('nationality.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['nationality'] = trim(strip_tags($this->request->getData('nationality.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                  if ($this->request->getData('remarks.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['remarks'] = trim(strip_tags($this->request->getData('remarks.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                  if ($this->request->getData('multiple_redemption.'.$ticket->id.'.'.$date.'.'.$idx)) {
                    $order_data[$idx]['multiple_redemption'] = trim(strip_tags($this->request->getData('multiple_redemption.'.$ticket->id.'.'.$date.'.'.$idx)));
                  }
                }
              }
              if (!empty($order_data)) {
                $data['order_data_'.$no] = json_encode($order_data);
              }
            }
            if ($this->request->getData('question_group') && !empty($tickets_with_question_group[$ticket->id])) {
                $QuestionGroupForm = new QuestionGroupForm($tickets_with_question_group[$ticket->id], $ticket->id, $date);
                $qgroup_data = $QuestionGroupForm->getOrderData($this->request->getData('question_group'), $ticket->id, $date);
                foreach ($qgroup_data['question_group'] as $question_group_answer) {
                    if (!$QuestionGroupForm->validate($question_group_answer)) {
                        $str_errors = '';
                        foreach ($QuestionGroupForm->getErrors() as $qgroup_field_name => $qgroup_error) {
                            $str_errors .= Inflector::humanize($qgroup_field_name) . ' ' . str_replace('This field','', h(reset($qgroup_error)) .'<br>');
                        }
                        $this->Flash->error($str_errors,['escape' => false]);
                        return false;
                    }
                }
                $order_data += $qgroup_data;
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
            $no++;
          }
        }

      }
      $data['total_amount'] = $subtotal;
      return $data;
    }

    private function _buildTwoWayData($ticket_list, $tickets_with_question_group, $tickets, $ticket_prices) {
      $subtotal = 0;
      $data = [
        'tracking_no' => trim(strip_tags($this->request->getData('tracking_no'))) ? trim(strip_tags($this->request->getData('tracking_no'))) : '',
        'first_name'=>trim(strip_tags($this->request->getData('first_name'))),
        'last_name'=>trim(strip_tags($this->request->getData('last_name'))),
        'customer_name' => trim(strip_tags($this->request->getData('first_name').' '.$this->request->getData('last_name'))),
        'customer_email' => trim(strip_tags($this->request->getData('email'))),
        'customer_mobile' => trim(strip_tags($this->request->getData('mobile'))),
        'currency'=>'SGD',
        'use_credits'=> true,
        'email_voucher'=> true,
        'from_mobile'=>true
      ];

      $no = 1;
      $depart_ticket_info = [];
      $return_ticket_info = [];
      foreach ($ticket_list as $key_ticket_id => $date_ticket) {
        foreach ($tickets as $ticket_idx => $_ticket) {
          if ($_ticket->id == $key_ticket_id) {
            if ($_ticket->ticket_trip_type && $_ticket->ticket_trip_type->type === 'depart') {
              $depart_ticket_info[$_ticket->sku_id]['ticket'] = $_ticket;
              foreach ($date_ticket as $datetime => $qty) {
                $depart_ticket_info[$_ticket->sku_id]['datetime'] = $datetime;
                $depart_ticket_info[$_ticket->sku_id]['qty'] = $qty;
              }
              $return_ticket = $this->Tickets->getReturnTicket($_ticket->id);
              $return_ticket->price = $ticket_prices[$return_ticket->sku_id];
              if ($return_ticket) {
                $return_ticket_info[$_ticket->sku_id]['ticket'] = $return_ticket;
                foreach ($date_ticket as $datetime => $qty) {
                  $return_ticket_info[$_ticket->sku_id]['datetime'] = $datetime;
                  $return_ticket_info[$_ticket->sku_id]['qty'] = $qty;
                }
              }
              
            } else {
              foreach ($return_ticket_info as $rti_idx => $rti) {
                if ($rti['ticket']->sku_id == $_ticket->sku_id) {
                  $return_ticket_info[$rti_idx]['ticket'] = $_ticket;
                  foreach ($date_ticket as $datetime => $qty) {
                    $return_ticket_info[$rti_idx]['datetime'] = $datetime;
                  }
                }
              }
            }
          }
        }
      }
      $question_group_idx = 1;
      $total = '0';
      foreach ($depart_ticket_info as $dpt_sku_id => $dpt) {
        if (empty($return_ticket_info[$dpt_sku_id])) {
          $this->Flash->error(__('Depart ticket not found.'));
          return false;
        }
        if ($depart_ticket_info[$dpt_sku_id]['ticket'] && ($depart_ticket_info[$dpt_sku_id]['qty'] > $this->TicketsHelper->getTicketAvailableQty($depart_ticket_info[$dpt_sku_id]['ticket'],$depart_ticket_info[$dpt_sku_id]['datetime']))) {
          $this->Flash->error(__('Sorry you have exceeded available {0} tickets.', $depart_ticket_info[$dpt_sku_id]['ticket']->name));
          return false;
        }
        if (!empty($depart_ticket_info[$dpt_sku_id]['ticket']->min_quantity) && $depart_ticket_info[$dpt_sku_id]['qty'] < $depart_ticket_info[$dpt_sku_id]['ticket']->min_quantity) {
          $this->Flash->error(__('Minimum quantity for {0} should be {1}.', $depart_ticket_info[$dpt_sku_id]['ticket']->name, $depart_ticket_info[$dpt_sku_id]['ticket']->min_quantity));
          return false;
        }
        if (!empty($return_ticket_info[$dpt_sku_id]['ticket']->min_quantity) && $depart_ticket_info[$dpt_sku_id]['qty'] < $return_ticket_info[$dpt_sku_id]['ticket']->min_quantity) {
          $this->Flash->error(__('Minimum quantity for {0} should be {1}.', $return_ticket_info[$dpt_sku_id]['ticket']->name, $return_ticket_info[$dpt_sku_id]['ticket']->min_quantity));
          return false;
        }
        $data = array_merge($data, $this->_buildOrderItemData($depart_ticket_info[$dpt_sku_id], $tickets_with_question_group, $question_group_idx));
        $data = array_merge($data, $this->_buildOrderItemData($return_ticket_info[$dpt_sku_id], $tickets_with_question_group, $question_group_idx + 1));
        $question_group_idx += 2;
        $depart_total = bcmul((string) $ticket_prices[$dpt_sku_id], $depart_ticket_info[$dpt_sku_id]['qty'], 2);
        $return_total = bcmul((string) $ticket_prices[$return_ticket_info[$dpt_sku_id]['ticket']->sku_id], $return_ticket_info[$dpt_sku_id]['qty'], 2);
        $subtotal = bcadd($depart_total, $return_total, 2);
        $total = bcadd($subtotal, $total, 2);
      }
      $data['total_amount'] = $total;
      if (count($depart_ticket_info) != count($return_ticket_info)) {
        $this->Flash->error(__('Number of depart and return tickets do not match.'));
        return false;
      }
      // $ticket_total[$depart_ticket_info['ticket']->id] = $depart_ticket_info['qty'];
      // $ticket_total[$return_ticket_info['ticket']->id] = $return_ticket_info['qty'];
      

      return $data;
    }

    private function _buildOrderItemData($ticket_info, $tickets_with_question_group, $no) {
      $data = [];
      $date = $ticket_info['datetime'];
      $data['item_sku_id_'.$no] = $ticket_info['ticket']->sku_id;
      $data['item_quantity_'.$no] = $ticket_info['qty'];
      $datetime = DateTime::createFromFormat('d M Y H:i',$ticket_info['datetime']);
      $data['item_booking_date_'.$no] = $datetime->format('Y-m-d H:i:s');
      $order_data = [];
      if ($this->request->getData('passport_number.'.$ticket_info['ticket']->id.'.'.$date)) {
        for ($idx=0; $idx < count($this->request->getData('passport_number.'.$ticket_info['ticket']->id.'.'.$date)); $idx++) {
          if ($ticket_info['ticket']->require_travellers_info) {

            if ($this->request->getData('passport_number.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['passport_number'] = trim(strip_tags($this->request->getData('passport_number.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
            if ($this->request->getData('passport_expiry.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['passport_expiry'] = trim(strip_tags($this->request->getData('passport_expiry.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
            if ($this->request->getData('passport_first_name.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['passport_first_name'] = trim(strip_tags($this->request->getData('passport_first_name.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
            if ($this->request->getData('passport_last_name.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['passport_last_name'] = trim(strip_tags($this->request->getData('passport_last_name.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
            if ($this->request->getData('date_of_birth.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['date_of_birth'] = trim(strip_tags($this->request->getData('date_of_birth.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
            if ($this->request->getData('nationality.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['nationality'] = trim(strip_tags($this->request->getData('nationality.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
            if ($this->request->getData('remarks.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['remarks'] = trim(strip_tags($this->request->getData('remarks.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
            if ($this->request->getData('multiple_redemption.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)) {
              $order_data[$idx]['multiple_redemption'] = trim(strip_tags($this->request->getData('multiple_redemption.'.$ticket_info['ticket']->id.'.'.$date.'.'.$idx)));
            }
          }
        }
        if (!empty($order_data)) {
          $data['order_data_'.$no] = json_encode($order_data);
        }
      }
      if ($this->request->getData('question_group') && !empty($tickets_with_question_group[$ticket_info['ticket']->id])) {
          $QuestionGroupForm = new QuestionGroupForm($tickets_with_question_group[$ticket_info['ticket']->id], $ticket_info['ticket']->id, $date);
          $qgroup_data = $QuestionGroupForm->getOrderData($this->request->getData('question_group'), $ticket_info['ticket']->id, $date);
          foreach ($qgroup_data['question_group'] as $question_group_answer) {
              if (!$QuestionGroupForm->validate($question_group_answer)) {
                  $str_errors = '';
                  foreach ($QuestionGroupForm->getErrors() as $qgroup_field_name => $qgroup_error) {
                      $str_errors .= Inflector::humanize($qgroup_field_name) . ' ' . str_replace('This field','', h(reset($qgroup_error)) .'<br>');
                  }
                  $this->Flash->error($str_errors,['escape' => false]);
                  return false;
              }
          }
          $order_data += $qgroup_data;
      }
      if (!empty($this->request->getData('multiple_redemption.'.$ticket_info['ticket']->id.'.'.$date))) {
          $order_data['multiple_redemption'] = true;
      }
      if (!empty($order_data)) {
        $data['item_order_data_'.$no] = $order_data;
      }

      return $data;
    }

    private function _getMappedStatus($status) {
      $statusMessages = [
        '1001' => 'API Parameter(s) is missing',
        '1002' => 'Permission is denied',
        '1003' => 'Invalid parameter(s)',
        '2001' => 'Unauthorized access',
        '2002' => 'Apikey does not match any user',
        '2003' => 'Invalid session',
        '2004' => 'Session has expired',
        '2005' => 'Session/auth key pair has been used. Please request for new session',
        '2006' => 'Invalid token',
        '2007' => 'Token has expired',
        '2008' => 'Token has already been bound to a user',
        '5000' => 'Cart is empty',
        '5001' => 'Item not found',
        '5002' => 'Order quantity not specified',
        '5003' => 'Insufficient stocks available',
        '5004' => 'Total amount discrepancy',
        '5005' => 'Order not found',
        '5006' => 'Order has been processed and cannot be updated',
        '5007' => 'Reference ID already exists',
        '5008' => 'Tickets in your order are from different merchants and cannot be purchased together.',
        '6001' => 'Cancel voucher failed',
        '6002' => 'Voucher not found',
        '7000' => 'Credits balance is not enough',
      ];

      if (isset($statusMessages[$status])) {
        return $status . ' ' . $statusMessages[$status];
      }

      return $status;
    }
}
