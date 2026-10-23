<?php
namespace App\Controller;

use Cake\I18n\I18n;
use Cake\I18n\DateTime;
use App\Lib\ApiCaller;
use App\Lib\Translator;
use Cake\Core\Configure;
use Cake\Routing\Router;
use App\Controller\AppController;
use App\Lib\PricingType;
use Cake\Http\Client;
use Cake\Http\Response;
use Exception;

/**
 * Attractions Controller
 *
 * @property \App\Model\Table\AttractionsTable $Attractions
 *
 * @method \App\Model\Entity\Attraction[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AttractionsController extends AppController
{
    public function initialize(): void {
        parent::initialize();
        $this->loadComponent('ShoppingCart');
        $this->loadComponent('TicketsHelper');
        $this->SettlementAgents = $this->fetchTable('SettlementAgents');
        $this->Tickets = $this->fetchTable('Tickets');
        // $this->Auth->allow(['index','view','getTickets']);
    } 

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $this->paginate = ['limit'=>20];
        $now = DateTime::now();
        $conditions = [];
        $search = null;
        $attractions = null;
        $filtered_attractions = [];
        $fdate_attractions = null;
        $selected_categories = [];
        $user_id = $this->Auth->user('id');
        $selected_countries = [];
        $selected_cities = [];
        // Only use GET (query) parameters for search and filters.
        // Title matching is applied later (combined with tag matching) so that
        // title results can be ordered ahead of tag-only results.
        if ($this->request->getQuery('search')) {
          $search = $this->request->getQuery('search');
        }
        if ($this->request->getQuery('categories')) {
          $selected_categories = (array)$this->request->getQuery('categories');
        }
        if ($this->request->getQuery('countries')) {
          $selected_countries = (array)$this->request->getQuery('countries');
        }
        if ($this->request->getQuery('cities')) {
          $selected_cities = (array)$this->request->getQuery('cities');
        }
        $this->Cities = $this->fetchTable('Cities');
        // Filter by the explicitly checked cities. Fall back to expanding any
        // selected countries to their cities (backward compatibility with older
        // links that passed only `countries`).
        if (!empty($selected_cities)) {
            $conditions['Attractions.city_id IN'] = $selected_cities;
        } elseif (!empty($selected_countries)) {
            $city_ids = $this->Cities->find()
              ->select(['id'])
              ->where(['country IN' => $selected_countries])
              ->extract('id')
              ->toArray();
            if (!empty($city_ids)) {
              $conditions['Attractions.city_id IN'] = $city_ids;
            }
        }
        $agent = $this->Auth->user();
        $this->FdatesettlementsAgents = $this->fetchTable('FdatesettlementsAgents');
        $settlement_attraction_ids = $this->SettlementAgents->find()
          ->select(['attraction_id'])
          ->where(['agent_id' => $agent['agent_id']])
          ->extract('attraction_id')
          ->toArray();
        $fdate_attraction_ids = $this->FdatesettlementsAgents->find()
          ->select(['Tickets.attraction_id'])
          ->contain(['Tickets'])
          ->where(['FdatesettlementsAgents.agent_id' => $agent['agent_id']])
          ->extract('ticket.attraction_id')
          ->toArray();
        $fdate_attraction_ids = array_unique($fdate_attraction_ids);
        $agent_attraction_ids = array_unique(array_merge($settlement_attraction_ids, $fdate_attraction_ids));
        if (empty($agent_attraction_ids)) {
          $this->set(compact('attractions','search','selected_categories','selected_countries','selected_cities'));
          $this->set('categories', []);
          $this->set('citiesByCountry', []);
          $this->set('recommended_attractions', []);
          return;
        }
        // Attractions whose tags match the search term (partial), scoped to the
        // agent's attractions. Used to broaden the search below title matches.
        $tag_matched_ids = [];
        if ($search) {
          $tag_matched_ids = $this->Attractions->find()
            ->select(['Attractions.id'])
            ->innerJoinWith('Tags', function ($q) use ($search) {
              return $q->where(['Tags.name LIKE' => '%'.$search.'%']);
            })
            ->where(['Attractions.id IN' => $agent_attraction_ids])
            ->distinct(['Attractions.id'])
            ->extract('id')
            ->toArray();
        }
        $attraction_query = $this->Attractions->find()
        ->contain(['Cities', 'FavoriteAttractions'=>function($q) use($user_id) {
          return $q->where(['agent_user_id'=>$user_id]);
        }])
        ->select(['fav_id' => 'FavoriteAttractions.id','Attractions.status','Attractions.id','Attractions.title','Attractions.title_chinese','Attractions.pricing_type','Attractions.featured_image'])
        ->where(['Attractions.status'=>'published', 'Attractions.id IN' => $agent_attraction_ids])
        ->where($conditions)
        ->orderBy(['FavoriteAttractions.id is not null' => 'DESC','display_position'=>'ASC','title'=>'ASC']);
        if ($search) {
          $search_like = '%'.$search.'%';
          // Match by title OR by a tag hit; keep both within the agent scope
          // already applied above.
          if (!empty($tag_matched_ids)) {
            $attraction_query->where(['OR' => [
              'Attractions.title LIKE' => $search_like,
              'Attractions.id IN' => $tag_matched_ids,
            ]]);
          } else {
            $attraction_query->where(['Attractions.title LIKE' => $search_like]);
          }
          // List title matches first, then tag-only matches, keeping the
          // existing favorites/position/title order within each group.
          $title_match = $attraction_query->expr()->add(['Attractions.title LIKE' => $search_like]);
          $attraction_query
            ->select(['title_match' => $title_match])
            ->orderBy([
              'title_match' => 'DESC',
              'FavoriteAttractions.id is not null' => 'DESC',
              'display_position' => 'ASC',
              'title' => 'ASC',
            ], true);
        }
        if ($this->request->getQuery('filter') == 'favorites') {
          $favorite_attraction_ids = $this->Attractions->FavoriteAttractions->find()->where(['agent_user_id'=>$this->Auth->user('id')])->extract('attraction_id')->toArray();
          if (is_array($favorite_attraction_ids) && count($favorite_attraction_ids) > 0) {
            $attraction_query = $attraction_query->where(['Attractions.id IN' => $favorite_attraction_ids]);
          }
        }
        
        if(count($selected_categories)>0) {
          $attractions = $attraction_query
            ->contain(['Categories'])
            ->where(['Attractions.category_id IN'=>$selected_categories])
            ->distinct('Attractions.id')
            ->toArray();
        } else {
          $attractions = $attraction_query->distinct('Attractions.id')->toArray();
        }
        
        $now = DateTime::now();
        $viewable_attraction_ids = [];
        foreach ($attractions as $akey => $attraction) {
          if ($this->_attachTicketInfo($attraction)) {
            $viewable_attraction_ids[] = $attraction->id;
            $filtered_attractions[$attraction->id] = $attraction;
          } else {
            unset($attractions[$akey]);
          }
          if ($this->request->getSession()->check('language')) {
            Translator::translateAttraction($this->request, $attraction);
          } else {
            unset($attractions[$akey]);
          }
        }
        if (!empty($viewable_attraction_ids)) {
          $attractions = $this->paginate($attraction_query->where(['Attractions.id IN'=>$viewable_attraction_ids]))->toArray();
        } else {
          $attractions = [];
        }
        foreach ($attractions as $akey => $attraction) {
          if ($filtered_attractions[$attraction->id]) {
            $attractions[$akey] = $filtered_attractions[$attraction->id];
          }
        }

        $categories = $this->Attractions->Categories->find()
                    ->where(['parent_id is'=>null])
                    ->contain(['ChildCategories' => [
                        'sort' => ['name' => 'asc']
                      ]])
                    ->order(['Categories.name'=>'asc'])->toList();
        if ($this->request->getSession()->check('language')) {
          foreach ($categories as $category) {
            Translator::translateCategory($this->request, $category);
          }
        }
        // Pass cities grouped by country for the nested country/city filter.
        $this->Cities = $this->fetchTable('Cities');
        $cities = $this->Cities->find()
          ->select(['id', 'country', 'name'])
          ->order(['country' => 'ASC', 'name' => 'ASC'])
          ->all();
        $citiesByCountry = [];
        foreach ($cities as $city) {
          $citiesByCountry[$city->country][] = $city;
        }
        // Recommended showcase row: curated via the recommended_attractions table,
        // agent-scoped and published, narrowed only by the country/city filter
        // (reuse $conditions, which holds just the city filter), top 10 by
        // display_order among the sellable ones.
        $recommended_attractions = [];
        $recommended_query = $this->Attractions->find()
          ->contain(['Cities', 'FavoriteAttractions'=>function($q) use($user_id) {
            return $q->where(['agent_user_id'=>$user_id]);
          }])
          ->select(['fav_id' => 'FavoriteAttractions.id','Attractions.status','Attractions.id','Attractions.title','Attractions.title_chinese','Attractions.pricing_type','Attractions.featured_image'])
          ->innerJoinWith('RecommendedAttractions')
          ->where(['Attractions.status'=>'published', 'Attractions.id IN' => $agent_attraction_ids])
          ->where($conditions)
          ->orderBy(['RecommendedAttractions.display_order' => 'ASC'])
          ->distinct(['Attractions.id']);
        foreach ($recommended_query->toArray() as $rec) {
          if ($this->_attachTicketInfo($rec)) {
            if ($this->request->getSession()->check('language')) {
              Translator::translateAttraction($this->request, $rec);
            }
            $recommended_attractions[] = $rec;
            if (count($recommended_attractions) >= 10) {
              break;
            }
          }
        }
        $this->set(compact('attractions','categories','search','selected_categories','selected_countries','selected_cities','citiesByCountry','recommended_attractions'));
    }

    /**
     * Attach the sellable ticket + first-open-date info to an attraction, using
     * the current agent's scope. Mirrors the per-attraction enrichment used by
     * the index listing so the same cards can be reused elsewhere (e.g. the
     * recommended section).
     *
     * @param \App\Model\Entity\Attraction $attraction The attraction to enrich.
     * @return bool Whether the attraction is viewable/sellable for this agent.
     */
    private function _attachTicketInfo($attraction): bool
    {
        $agent = $this->Auth->user();
        $now = DateTime::now();
        $attraction->ticket = null;
        if ($attraction->pricing_type == 'settlement') {
            $attraction->ticket = $this->Attractions->Tickets->find()
            ->contain(['SettlementAgents'=>function($q) use($agent) {
              return $q->contain(['Settlements'])->where(['agent_id'=>$agent['agent_id']]);
            }])
            ->where(['SettlementAgents.attraction_id'=>$attraction->id])
            ->where(['publish_start_date <='=>$now,'publish_end_date >='=>$now])
            ->matching('SettlementAgents',function($q) use ($agent){
                return $q->where(['SettlementAgents.agent_id'=>$agent['agent_id']]);
            })->order(['sequence'=>'asc'])
            ->distinct('SettlementAgents.id')
            ->first();

            if (empty($attraction->ticket)) {
              return false;
            }
            if ($attraction->ticket->publish_start_date->addSeconds($attraction->ticket->cutoff_seconds)->isFuture()) {
              $attraction->first_open_date = $attraction->ticket->publish_start_date->addSeconds($attraction->ticket->cutoff_seconds)->diffInDays($now);
            }
            else {
              $ticket_release = DateTime::now()->addSeconds($attraction->ticket->cutoff_seconds);
              $attraction->first_open_date = $ticket_release->diffInDays($now);
            }
            if ($attraction->first_open_date == 0) {
              $attraction->first_open_date = __('Today');
            }
            elseif ($attraction->first_open_date > 1) {
              $attraction->first_open_date = __('{0} days', $attraction->first_open_date);
            }
            else {
              $attraction->first_open_date = __('Tomorrow');
            }
            return true;
        }
        else {
            $tickets = $this->Attractions->Tickets->find()
            ->where(['Tickets.attraction_id'=>$attraction->id])
            ->where(['publish_start_date <='=>$now,'publish_end_date >='=>$now])
            ->matching('FdatesettlementsAgents',function($q) use ($agent){
                return $q->where(['FdatesettlementsAgents.agent_id'=>$agent['agent_id']]);
            })->order(['sequence'=>'asc'])->toArray();
            if (empty($tickets)) {
              return false;
            }
            $attraction->ticket = $tickets[0];
            $ticket_ids = [];
            foreach ($tickets as $key => $ticket) {
              if ($ticket->publish_start_date <= $now) {
                $ticket_ids[] = $ticket->id;
              }
            }
            if (!empty($ticket_ids)) {
              $first_fixeddate = $this->Attractions->Tickets->Fixeddates->find()->where(['ticket_id IN'=>$ticket_ids,'date >= (CURDATE() + INTERVAL ABS(Tickets.cutoff_seconds) second)'])->contain('Tickets')->first();
              if ($first_fixeddate) {
                $attraction->first_open_date = (new DateTime($first_fixeddate->date->format('Y-m-d')))->diffInDays($now);
                if ($attraction->ticket) {
                  $attraction->ticket->fixeddates = [$first_fixeddate];
                }
              }
              if ($attraction->first_open_date == 0 && $first_fixeddate) {
                if ($first_fixeddate->date->format('Y-m-d') != $now->format('Y-m-d') ) {
                  $attraction->first_open_date = __('Tomorrow');
                }
                else {
                  $attraction->first_open_date = __('Today');
                }
              }
              elseif ($attraction->first_open_date > 1) {
                $attraction->first_open_date = __('{0} days', $attraction->first_open_date);
              }
              elseif ($attraction->first_open_date == 1) {
                $attraction->first_open_date = __('Tomorrow');
              }
            }
            return true;
        }
    }

    /**
     * View method
     *
     * @param string|null $id Attraction id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $now = DateTime::now();
        $attraction = $this->Attractions->get($id,
        contain: ['Merchants', 'Categories', 'AttractionMedias'],
        conditions: ['Attractions.status'=>'published']);
        $attraction->is_favorite = $this->Attractions->FavoriteAttractions->exists(['attraction_id'=>$id,'agent_user_id'=>$this->Auth->user('id')]);
        if ($this->request->getSession()->check('language')) {
          Translator::translateAttraction($this->request, $attraction);
        }

        if ($this->Auth->user('role') == 'viewer') {
          $this->set('not_allowed_to_add', true);
        }

        if ($this->request->is(['post'])) {
          if ($this->Auth->user('role') == 'viewer') {
            $this->Flash->error(__('You are not authorized to access that location.'));
            return $this->redirect(['action'=>'view',$id]);
          }
          $has_error = false;
          if ($this->request->getData('quantity')) {
            $date = $now;
            $this->Attractions->loadInto($attraction,['Tickets'=>function($q) use($date) {
            return $q->where(['type !='=>'ctc','publish_start_date <='=>$date,'publish_end_date >='=>$date])->order(['sequence'=>'ASC']);
            }]);
            if (is_array($this->request->getData('quantity'))) {
              $total_qty = 0;
              foreach ($this->request->getData('quantity') as $ticket_id => $qty) {
                $total_qty += $qty;
              }
              if ($total_qty > Configure::read('Default.max_order_quantity')) {
                $this->Flash->error(__('Cannot exceed max allowed quantity ({0})', Configure::read('Default.max_order_quantity')));
                return $this->redirect(['action'=>'view',$id]);
              }
              $total_qty = 0;
              $tickets_total_quantity = [];
              foreach ($this->request->getData('quantity') as $ticket_id => $qty) {
                if (intval($qty) >= 1){
                  $ticket = $this->Attractions->Tickets->find()->contain(['Attractions'])->where(['Tickets.id'=>$ticket_id,'Tickets.attraction_id'=>$id,'publish_start_date <='=>$date->format('Y-m-d H:i:s'),'publish_end_date >='=>$date->format('Y-m-d H:i:s')])->first();
                  if ($ticket && $this->_isVoucherEnough($attraction,$ticket_id,$qty,$this->request->getData())) {
                    /* check tickets to only allow purchase of single merchant when external */
                    $ticket_list = $this->ShoppingCart->getAll();
                    if (!empty($ticket_list)) {
                      $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'])->toArray();
                      if (!empty($tickets)) {
                        $supplier_used = null;
                        foreach ($tickets as $key => $_ticket) {
                          if ($_ticket->supplier_api_id) {
                            $supplier_used = $_ticket->supplier_api_id;
                          }
                          if ($_ticket->attraction->pricing_type == PricingType::TWO_WAY_FDATETIME) {
                            $this->ShoppingCart->clear();
                          }
                        }
                        if ($supplier_used && $supplier_used != $ticket->supplier_api_id) {
                          $this->Flash->error($ticket->name .__(' cannot be purchased together with the tickets in your cart.'));
                          $has_error = true;
                        } else if ($supplier_used == null && $ticket->supplier_api_id != null) {
                          $this->Flash->error($ticket->name .__(' cannot be purchased together with the tickets in your cart.'));
                          $has_error = true;
                        }
                        /* block duplicate supplier_api_id */
                        if (!$has_error && $this->ShoppingCart->hasDuplicateSupplier($ticket->supplier_api_id, $id)) {
                          $this->Flash->error(__('You cannot add more than 1 product from this supplier to your cart.'));
                          $has_error = true;
                        }
                        /* end block duplicate supplier */
                        foreach ($tickets as $key => $_ticket) {
                          if ($_ticket->supplier_api_id) {
                            $supplier_used = $_ticket->supplier_api_id;
                          }
                        }
                        if ($ticket->max_quantity) {
                          foreach ($tickets as $key => $_ticket) {
                            if ($_ticket->max_quantity) {
                              foreach ($ticket_list[$_ticket->id] as $_date => $date_qty) {
                                if (!empty($tickets_total_quantity[$_ticket->id])) {
                                  $tickets_total_quantity[$_ticket->id] += $date_qty;
                                } else {
                                  $tickets_total_quantity[$_ticket->id] = $date_qty;
                                }
                              }
                              if (isset($tickets_total_quantity[$ticket->id]) && ($tickets_total_quantity[$ticket->id] + $qty) > $ticket->max_quantity) {
                                $this->Flash->error(__('Sorry, you have exceeded max quantity {0} of ', $ticket->max_quantity) . $ticket->name);
                                $has_error = true;
                              }
                            }
                          }
                        }
                      }
                    }
                    /* end external */
                    if (!$has_error) {
                      if ($attraction->pricing_type != 'settlement') {
                        if ($attraction->pricing_type == 'fdate') {
                          $fixeddate = $this->TicketsHelper->getFixeddate($ticket,$this->Auth->user(),$this->request->getData('date'));
                          if ($fixeddate && ($fixeddate->stock === null || ($this->request->getSession()->read('ShoppingCart.'.$ticket->id.'.'.$this->request->getData('date')) + $qty <= $fixeddate->stock))) {
                            $this->ShoppingCart->add($ticket_id,$qty,$this->request->getData('date'));
                          } else {
                            $has_error = true;
                          }
                        } else {
                          $fixeddate = $this->TicketsHelper->getFixeddate($ticket,$this->Auth->user(),$this->request->getData('date') .' '.$this->request->getData('time') );
                          if ($fixeddate && ($fixeddate->stock === null || ($this->request->getSession()->read('ShoppingCart.'.$ticket->id.'.'.$this->request->getData('date') .' '.$this->request->getData('time') ) + $qty <= $fixeddate->stock))) {
                            $this->ShoppingCart->add($ticket_id,$qty,$this->request->getData('date') .' '.$this->request->getData('time') );
                          } else {
                            $has_error = true;
                          }
                        }

                      }
                      else {
                        $this->ShoppingCart->add($ticket_id,$qty,$date->format('d M Y'));
                      }
                      if (!$this->request->getData('buy_now')) {
                        $total_qty += $qty;
                      }
                    }
                  }
                  else {
                    $has_error = true;
                  }
                }
              }
              if (!$has_error) {
                if ($this->request->getData('buy_now')) {
                  return $this->redirect(['controller'=>'Orders','action'=>'cart']);
                }
                elseif ($total_qty > 0) {
                  $this->Flash->success(__('Successfully added {0} to cart. Go to <a href="{1}">cart</a> now.' ,$total_qty, Router::url(['controller'=>'Orders','action'=>'cart'])),['escape'=>false]);
                  return $this->response->withStatus(303)->withHeader('Location', $this->request->getRequestTarget());
                }
              }
            }
          }
        } else {
          /* check tickets to only allow purchase of single merchant when external */
          $supplier_used = null;
          $ticket_list = $this->ShoppingCart->getAll();
          if (!empty($ticket_list)) {
            $tickets = $this->Tickets->find()->where(['Tickets.id in'=>array_keys($ticket_list)])->contain(['Attractions'])->toArray();
            if (!empty($tickets)) {
              
              foreach ($tickets as $key => $_ticket) {
                if ($_ticket->supplier_api_id) {
                  $supplier_used = $_ticket->supplier_api_id;
                }
              }
            }
          }
          /* end external */
          $external_tickets = $this->Attractions->Tickets->find()->where(['attraction_id'=>$id,'supplier_api_id is not null'])->first();
          if ($external_tickets && empty($ticket_list)) {
            $this->Flash->info(__('This merchant\'s tickets cannot be bought with other merchants'),['escape'=>false]);
          } else {
            if ($external_tickets && $external_tickets->supplier_api_id != $supplier_used) {
              $this->set('not_allowed_to_add',true);
              $this->Flash->warning('<i class="fa fa-warning"></i>' . __('This merchant\'s tickets cannot be bought with other merchants'),['escape'=>false]);
            } elseif (!$external_tickets && $supplier_used) {
              $this->set('not_allowed_to_add',true);
              $this->Flash->warning('<i class="fa fa-warning"></i>' . __('Only tickets from the same merchant as those in your cart can be added to cart'),['escape'=>false]);
            }
          }
          /* block duplicate supplier_api_id */
          $attractionBlockedTicket = $this->Attractions->Tickets->find()->where(['attraction_id' => $id, 'supplier_api_id IS NOT NULL'])->first();
          if ($attractionBlockedTicket && $this->ShoppingCart->hasDuplicateSupplier($attractionBlockedTicket->supplier_api_id, $id)) {
            $this->set('not_allowed_to_add', true);
            $this->Flash->warning('<i class="fa fa-warning"></i>' . __('You cannot add more than 1 product from this supplier to your cart'), ['escape' => false]);
          }
          /* end block duplicate supplier */
          if ($this->ShoppingCart->getCount()) {
            $ticket_list = $this->ShoppingCart->getAll();
            foreach ($ticket_list as $ticket_id => $cart_ticket) {
              $ticket = $this->Attractions->Tickets->find()->where(['id' => $ticket_id])->first();
              if ($ticket->pricing_type == PricingType::TWO_WAY_FDATETIME) {
                $this->set('cart_has_two_way_fdatetime', true);
                break;
              }
            }
          }
        }
        $date = DateTime::now();
        $tickets = [];

        $tickets = $this->_getSettlementAttractionTickets($attraction);

        $http = new \Cake\Http\Client();
        $api_url = Configure::read('Api.url');
        $data = ['sku_id'=>$attraction->sku_id,'time_from'=>$now->format('Y-m-d'),'time_to'=>$now->format('Y-m-d')];
        $headers = $this->_buildHeaders($data,$this->Auth->user('email'),$this->Auth->user('id'));
        $ticket_ids = [];
        $tickets_max_quantity = [];
        $tickets_slots = [];
        foreach ($tickets as $key => $ticket) {
          $ticket_ids[$ticket->sku_id] = $ticket->id;
        }
        if (!empty($ticket_ids)) {
          try {
            $response = $http->get($api_url.'/availabilities',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
            if ($response->isOk()) {
              $res = $response->getJson();
              $availabilities = $res['response']['data'];
              if (!empty($res['response']['data'])) {
                foreach ($res['response']['data'] as $date => $available_tickets) {
                  foreach ($available_tickets as $key => $available_ticket) {
                    if (empty($ticket_ids[$available_ticket['sku_id']])) {
                      continue;
                    }
                    $ticket_id = $ticket_ids[$available_ticket['sku_id']];
                    $tickets_max_quantity[$ticket_id] = intval($available_ticket['available_quantity']);
                    $slot = $this->_extractSharedSlot($available_ticket);
                    if ($slot) {
                      $tickets_slots[$ticket_id] = $slot;
                    }
                  }

                }
              }
            }
          } catch (\Exception $e) {

          }
        }
        
        // $tickets_max_quantity = [];
        // foreach ($tickets as $key => $ticket) {
        //     $tickets_max_quantity[$ticket->id] = $this->TicketsHelper->getTicketAvailableQty($ticket);
        //     if ($tickets_max_quantity[$ticket->id] == 0) {
        //       unset($tickets[$key]);
        //     }
        // }
        if ($attraction->pricing_type != 'settlement') {
          $available_dates = $this->_getAvailableDates($attraction);
          $this->set('available_dates',$available_dates);
        }

        $attraction->tickets = $tickets;
        $this->set('attraction', $attraction);
        $this->set('tickets_max_quantity', $tickets_max_quantity);
        $this->set('tickets_slots', $tickets_slots);
        $this->set('cart_count',$this->ShoppingCart->getCount());
    }

    /**
     * View Two-way method
     *
     * @param string|null $id Attraction id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function viewTwoWay($id = null)
    {
        $now = DateTime::now();
        $attraction = $this->Attractions->get($id, contain: ['Merchants', 'Categories', 'AttractionMedias']);
        $attraction->is_favorite = $this->Attractions->FavoriteAttractions->exists(['attraction_id'=>$id,'agent_user_id'=>$this->Auth->user('id')]);
        if ($this->request->getSession()->check('language')) {
          Translator::translateAttraction($this->request, $attraction);
        }
        if ($attraction->pricing_type !== PricingType::TWO_WAY_FDATETIME) {
          return $this->redirect(['action'=>'view', $id]);
        }

        if ($this->Auth->user('role') == 'viewer') {
          $this->set('not_allowed_to_add', true);
        }

        if ($this->request->is(['post'])) {
          if ($this->Auth->user('role') == 'viewer') {
            $this->Flash->error(__('You are not authorized to access that location.'));
            return $this->redirect(['action'=>'viewTwoWay',$id]);
          }
          $has_error = false;
          if ($this->request->getData('quantity')) {
            $date = $now;
            $this->Attractions->loadInto($attraction,['Tickets'=>function($q) use($date) {
            return $q->where(['type !='=>'ctc','publish_start_date <='=>$date,'publish_end_date >='=>$date])->order(['sequence'=>'ASC']);
            }]);
            if (is_array($this->request->getData('quantity'))) {
              $total_qty = 0;
              foreach ($this->request->getData('quantity') as $ticket_id => $qty) {
                $total_qty += $qty;
              }
              if ($total_qty > Configure::read('Default.max_order_quantity')) {
                $this->Flash->error(__('Cannot exceed max allowed quantity ({0})', Configure::read('Default.max_order_quantity')));
                return $this->redirect(['action'=>'viewTwoWay',$id]);
              }
              $total_qty = 0;
              $tickets_total_quantity = [];
              $adult_depart_ticket = null;
              $child_depart_ticket = null;
              $tickets = [];
              $ticket_list = $this->ShoppingCart->clear();
              $depart_qty = [];
              foreach ($this->request->getData('quantity') as $ticket_id => $qty) {
                if (intval($qty) >= 1) {
                  $ticket = $this->Attractions->Tickets->find()->contain(['Attractions', 'TicketTripTypes'])->where(['Tickets.id'=>$ticket_id,'Tickets.attraction_id'=>$id,'publish_start_date <='=>$date->format('Y-m-d H:i:s'),'publish_end_date >='=>$date->format('Y-m-d H:i:s')])->first();
                  if ($ticket && $this->_isVoucherEnough($attraction, $ticket_id, $qty, $this->request->getData()) && $ticket->ticket_trip_type) {
                    if ($ticket->ticket_trip_type === 'depart') {
                      $return_ticket = $this->Tickets->getReturnTicket($ticket->id);
                      if ($return_ticket) {
                        $tickets[] = [
                          'depart' => [
                            'sku_id' => $ticket->sku_id,
                            'ticket' => $ticket,
                            'qty' => $qty,
                          ],
                          'return' => [
                            'sku_id' => $return_ticket->sku_id,
                            'ticket' => $return_ticket,
                            'qty' => 0,
                          ]
                        ];
                      }                      
                    } else {
                      $depart_ticket = null;
                      foreach ($tickets as $tidx => $_ticket) {
                        if ($_ticket['return']['sku_id'] == $ticket->sku_id) {
                          $tickets[$tidx]['return']['qty'] = $qty;
                        }
                      }
                    }
                    
                    $datetime = null;
                    if (empty($ticket->ticket_trip_type)) {
                      $this->Flash->error(__('Invalid two_way ticket {0}.', $ticket->sku_id));
                        return $this->redirect(['action'=>'viewTwoWay',$id]);
                    } else {
                      if ($ticket->ticket_trip_type->type == 'depart') {
                        $datetime = $this->request->getData('departure_date') . ' ' . $this->request->getData('departure_time');
                      } else {
                        $datetime = $this->request->getData('return_date') . ' ' . $this->request->getData('return_time');
                      }
                    }
                    $fixeddate = $this->TicketsHelper->getFixeddate($ticket,$this->Auth->user(), $datetime);
                    if ($fixeddate && ($fixeddate->stock === null || ($this->request->getSession()->read('ShoppingCart.'.$ticket->id.'.'.$datetime) + $qty <= $fixeddate->stock))) {
                      $this->ShoppingCart->add($ticket_id,$qty,$datetime);
                    } else {
                      $has_error = true;
                    }
                  }
                  else {
                    $has_error = true;
                  }
                }
              }
              foreach($tickets as $ticket) {
                if ($ticket['depart']['qty'] !== $ticket['return']['qty']) {
                  $this->Flash->error(__('Depart qty and return qty of {0} should be the same.', $ticket['depart']['ticket']->name));
                  return $this->redirect(['action'=>'viewTwoWay',$id]);
                }
              }
              if (!$has_error) {
                return $this->redirect(['controller'=>'Orders','action'=>'checkout']);
              }
            }
          }
        }
        $date = DateTime::now();
       
        if ($attraction->pricing_type != 'settlement') {
          $available_dates = $this->_getAvailableDates($attraction);
          $this->set('available_dates',$available_dates);
        }

        $this->Flash->info(__('Product can only be bought alone. Cart will be cleared to make this purchase.'),['escape'=>false]);

        $this->set('attraction', $attraction);
        $this->set('cart_count',$this->ShoppingCart->getCount());
    }

    private function _isVoucherEnough($attraction,$ticket_id,$qty,$data = null) {
      $now = DateTime::now();
      $is_voucher_enough = true;
      $total_qty = $qty;
      $cart_tickets = $this->ShoppingCart->getAll();
      if (!empty($cart_tickets) && !empty($cart_tickets[$ticket_id])) {
        foreach ($cart_tickets[$ticket_id] as $datetime => $qty) {
          $total_qty += $qty;
        }
      }
      $ticket = $this->Tickets->find()->where(['Tickets.id'=>$ticket_id])->first();

      if ($ticket) {
        if ($ticket->voucher_type =='pdf') {
          $pdf_count = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$ticket->sku_id,'status'=>'new','validity_end_date >='=>$now])->count();
          if ($pdf_count < $total_qty) {
            $is_voucher_enough = false;
            $this->Flash->error(__('Not enough vouchers. Only {0} left.', $pdf_count));
          }
        }
        elseif ($ticket->voucher_type =='barcode') {
          $barcode_count = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$ticket->sku_id,'status'=>'new','validity_end_date >='=>$now ])->count();
          if ($barcode_count < $total_qty) {
            $this->Flash->error(__('Not enough vouchers. Only {0} left.', $barcode_count));
            $is_voucher_enough = false;
          }
        }
        elseif ($ticket->voucher_type =='generated') {
          $gc_ticket = $this->Tickets->find()->where(['id'=>$ticket->id])->first();
          if ($ticket->stock !== null && $ticket->stock < $total_qty) {
            $this->Flash->error(__('Not enough vouchers. Only {0} left.', $ticket->stock));
            $is_voucher_enough = false;
          }
        }
      }
      else {
        $is_voucher_enough = false;
      }
      return $is_voucher_enough;
    }

    private function _getAvailableDates($attraction) {
      $available_dates = [];
      $agent = $this->Auth->user();
      $tickets = $this->Attractions->Tickets->find()->where(['Tickets.attraction_id'=>$attraction->id,'publish_start_date <='=>date('Y-m-d H:i:s'), 'publish_end_date >='=>date('Y-m-d H:i:s')])
        ->matching('FdatesettlementsAgents',function($q) use ($agent){
              return $q->where(['FdatesettlementsAgents.agent_id'=>$agent['agent_id']]);
          })
        ->distinct()
        ->toArray();
      if (!empty($tickets)) {
        $max_cutoff_seconds = 3600*24*100;
        $smallest_cutoff_seconds = $max_cutoff_seconds;
        foreach ($tickets as $key => $ticket) {
          if ($ticket->cutoff_seconds !== null && $ticket->cutoff_seconds < $smallest_cutoff_seconds) {
            $smallest_cutoff_seconds = $ticket->cutoff_seconds;
          }
        }
        if ($smallest_cutoff_seconds == $max_cutoff_seconds) {
          $smallest_cutoff_seconds = 0;
        }
        $first_release_date = DateTime::now();
        if ($smallest_cutoff_seconds > 0) {
          if ($attraction->pricing_type == 'fdate') {
            $first_release_date = DateTime::now()->addDays(1)->hour(0)->minute(0)->second(0);
          }
          $first_release_date = $first_release_date->addSeconds($smallest_cutoff_seconds);
        }
        $ticket_ids = array_map(function($ticket) {
          return $ticket->id;
        }, $tickets);
        
        $fixeddates = $this->Attractions->Tickets->Fixeddates->find()->where(['ticket_id In'=>$ticket_ids])
        ->where([
          'date >='=>$first_release_date, 
          'or' => ['Fixeddates.stock is null', 'Fixeddates.stock > 0'],
          'Tickets.redemption_end_date >= Fixeddates.date',
          ])
          ->distinct('date')
          ->contain(['Tickets']);
        $fixeddates = $fixeddates->toArray();
        foreach ($fixeddates as $key => $fixeddate) {
          $available_dates[] = $fixeddate->date->format('d-m-Y');
        }
      }
      return $available_dates;
    }

    private function _getSettlementAttractionTickets($attraction, $date = null, $time = null) {
      if (!$date) {
        $date = DateTime::now();
      }
      $agent = $this->Auth->user();
      if ($attraction->product_type == 'simple') {
        if ($attraction->pricing_type == 'settlement') {
          $tickets = $this->Attractions->Tickets->find()
            ->where(['Tickets.attraction_id'=>$attraction->id,
              'type !='=>'ctc',
              'publish_start_date <='=>$date->format('Y-m-d H:i:s'),
              'publish_end_date >='=>$date->format('Y-m-d H:i:s')])
            ->matching('SettlementAgents',function($q) use ($agent){
                return $q->where(['SettlementAgents.agent_id'=>$agent['agent_id']])->limit(1);
            })->order(['sequence'=>'asc'])->toArray();
          $tickets_max_quantity = [];

          foreach ($tickets as $key => $ticket) {
              $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$attraction,$this->Auth->user());
              if ($this->request->getSession()->check('language')) {
                Translator::translateTicket($this->request, $ticket);
              }
          }
          if (!empty($tickets)) {
            return $tickets;
          }
        }

      }
      return [];
    }

    public function getTickets($attraction_id) {
        $attraction = $this->Attractions->find()->where(['id'=>$attraction_id])->first();

        if (!$attraction) {
            return $this->response->withStatus(404);
        }
        $date = null;
        if ($this->request->getQuery('date')) {
          try {
            $date = DateTime::createFromFormat('d M Y',$this->request->getQuery('date'));
          } catch (Exception $e) {
          }
          if ($date === null) {
            return $this->response->withStatus(400);
          }
        }
        $time = null;
        if ($this->request->getQuery('time')) {
          try {
            $time = DateTime::createFromFormat('H:i',$this->request->getQuery('time'));
          } catch (Exception $e) {
          }
          if ($time === null) {
            return $this->response->withStatus(400);
          }
        }
        $agent = $this->Auth->user();
        $data = $this->_getAttractionTickets($attraction,$date,$time);

        return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>200,'data'=>$data]));
    }

    public function getTwoWayTickets($attraction_id)
    {
      $attraction = $this->Attractions->find()->where(['id' => $attraction_id])->first();

      if (!$attraction) {
        return $this->response->withStatus(404);
      }
      $departure_date = null;
      if ($this->request->getQuery('departure_date')) {
        try {
          $departure_date = DateTime::createFromFormat('d M Y', $this->request->getQuery('departure_date'));
        } catch (Exception $e) {
        }
        if ($departure_date === null) {
          return $this->response->withStatus(400);
        }
      }
      $return_date = null;
      if ($this->request->getQuery('return_date')) {
        try {
          $return_date = DateTime::createFromFormat('d M Y', $this->request->getQuery('return_date'));
        } catch (Exception $e) {
        }
        if ($return_date === null) {
          return $this->response->withStatus(400);
        }
      }
      $departure_time = null;
      if ($this->request->getQuery('departure_time')) {
        try {
          $departure_time = DateTime::createFromFormat('H:i', $this->request->getQuery('departure_time'));
        } catch (Exception $e) {
        }
        if ($departure_time === null) {
          return $this->response->withStatus(400);
        }
      }
      $return_time = null;
      if ($this->request->getQuery('departure_time')) {
        try {
          $return_time = DateTime::createFromFormat('H:i', $this->request->getQuery('return_time'));
        } catch (Exception $e) {
        }
        if ($return_time === null) {
          return $this->response->withStatus(400);
        }
      }
      $tickets = [];
      $tickets_max_quantity = [];
      $return_ticket_ids = [];
      $http = new Client();
      $api_url = Configure::read('Api.url');
      $time_from = $departure_date->format('Y-m-d') . $departure_time->format(' H:i');
      $return_time_from = $return_date->format('Y-m-d') . $return_time->format(' H:i');
      $data = ['sku_id' => $attraction->sku_id, 'depart_datetime' => $time_from, 'return_datetime' => $return_time_from];
      $headers = $this->_buildHeaders($data, $this->Auth->user('email'), $this->Auth->user('id'));
      try {
        $response = $http->get($api_url . '/availabilities', $data, ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false, 'timeout' => Configure::read('Api.timeout')]);
        if ($response->isOk()) {
          $res = $response->getJson();
          if (!empty($res['response']['data'])) {
            foreach ($res['response']['data'] as $date => $available_tickets) {
              foreach ($available_tickets as $key => $available_ticket) {
                $ticket = $this->Tickets->find()
                ->select(['id','attraction_id','supplier_api_id','sku_id','name','sequence','publish_start_date','publish_end_date','price','release_period','cutoff_seconds','redemption_start_date','redemption_end_date','redemption_type','duration','duration_type','pricing_type','instant_confirmation','stock','quantity','voucher_note','type','voucher_type','cost_price','api_sku','min_quantity','max_quantity','markup_value','markup_type','deleted','created','modified'])
                ->select($this->Tickets->TicketTripTypes)
                ->where(['sku_id' => $available_ticket['sku_id']])->contain(['TicketTripTypes'])->first();
                
                if ($ticket->ticket_trip_type && $ticket->ticket_trip_type->type == 'depart') {
                  // $ticket_sku_base = substr($ticket->sku_id, 0,strrpos($ticket->sku_id, '_'));
                  
                  $return_ticket = $this->Attractions->Tickets->getReturnTicket($ticket->id);
                  if ($return_ticket) {
                    if ($return_ticket->markup_value && $return_ticket->markup_value > 0 && isset($available_ticket['price'])) {
                      $return_ticket->price = floatval($available_ticket['price']);
                    }
                    $tickets[$return_ticket->ticket_trip_type->type.$return_ticket->name] = $return_ticket;
                    $return_ticket_ids[$ticket->id] = $return_ticket->id;
                    $tickets_max_quantity[$return_ticket->id] = intval($available_ticket['available_quantity']);
                  } else {
                    throw new Exception('No return ticket');
                  }
                  
                } else {
                  continue;
                }
                $tickets[$ticket->ticket_trip_type->type.$ticket->name] = $ticket;
                $tickets_max_quantity[$ticket->id] = intval($available_ticket['available_quantity']);
                if (!empty($tickets[$ticket->ticket_trip_type->type.$ticket->name])) {
                  if ($ticket->markup_value && $ticket->markup_value > 0 && isset($available_ticket['price'])) {
                    $tickets[$ticket->ticket_trip_type->type.$ticket->name]->price = floatval($available_ticket['price']);
                  }
                }
              }
            }
            ksort($tickets);
          }
        }
      } catch (\Exception $e) {
        debug($e->getMessage());
      }
      $response_data = ['tickets' => array_values($tickets), 'tickets_max_quantity' => $tickets_max_quantity, 'return_ticket_ids' => $return_ticket_ids];

      return $this->response->withStatus(200)->withStringBody(json_encode(['status' => 200, 'data' => $response_data]));
    }

    public function getFixeddateTime($attraction_id) {
        $attraction = $this->Attractions->find()->where(['id'=>$attraction_id])->first();

        if (!$attraction) {
            return $this->response->withStatus(404);
        }
        $date = null;
        $tickets = [];
        $times = [];
        $now = DateTime::now();
        if ($this->request->getQuery('date')) {
          try {
            $date = DateTime::createFromFormat('d M Y',$this->request->getQuery('date'));
          } catch (Exception $e) {
          }
          if ($date === null) {
            return $this->response->withStatus(400);
          }
          $agent = $this->Auth->user();
          $tickets_query = $this->Attractions->Tickets->find()->where(['Tickets.attraction_id'=>$attraction->id])->contain(['TicketTripTypes']);
          $tickets_query->contain(['Fixeddates'=>function ($q) use ($date) {
            return $q->where(['date'=>$date->format('Y-m-d')]);
          }])->where(['publish_start_date <='=>$now,'publish_end_date >='=>$now]);
          $tickets_query->matching('FdatesettlementsAgents',function($q) use ($agent){
              return $q->where(['FdatesettlementsAgents.agent_id'=>$agent['agent_id']]);
          });

          $tickets = $tickets_query->toArray();
          $now = \Cake\I18n\DateTime::now();
          if (!empty($tickets)) {
            foreach ($tickets as $key => $ticket) {
              if ($this->request->getQuery('type')) {
                $ticket_trip_type = $ticket->ticket_trip_type->type;
                if ($this->request->getQuery('type') == 'departure') {
                  if ($ticket_trip_type == 'return') {
                    continue;
                  }
                } elseif ($this->request->getQuery('type') == 'return') {
                  if ($ticket_trip_type == 'depart') {
                    continue;
                  }
                }
              }
              if (!empty($ticket->fixeddates)) {
                foreach ($ticket->fixeddates as $key => $fixeddate) {
                  if (($now = $now->addSeconds($ticket->cutoff_seconds)) <= new DateTime($fixeddate->date->format('Y-m-d'). ' ' . $fixeddate->time->format('H:i:s'))) {
                    $times[$fixeddate->time->format('H:i')] = ['time_value'=>$fixeddate->time->format('H:i'),'time_display'=>$fixeddate->time->format('h:ia')];
                  }
                }
              }
            }
          }
        }
        ksort($times);

        return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>200,'data'=>array_values($times)]));
    }

    public function getAvailability($attraction_id) {
      $attraction = $this->Attractions->find()->where(['id'=>$attraction_id])->first();

        if (!$attraction) {
            return $this->response->withStatus(404);
        }
        $date = null;
        $tickets = [];
        $date = null;
        if ($this->request->getQuery('date')) {
          $date = DateTime::createFromFormat('d M Y',$this->request->getQuery('date'));
          $agent = $this->Auth->user();
          $tickets_query = $this->Attractions->Tickets->find()->where(['Tickets.attraction_id'=>$attraction->id,'api_purchase'=>true]);
          $tickets_query->contain(['Attractions','Connections','Fixeddates'=>function ($q) use ($date) {
            return $q->where(['date'=>$date->format('Y-m-d')]);
          }]);
          $tickets_query->matching('FdatesettlementsAgents',function($q) use ($agent){
              return $q->where(['FdatesettlementsAgents.agent_id'=>$agent['agent_id']]);
          });
          $tickets = $tickets_query->toArray();
          $tickets_availability = [];
          if (!empty($tickets)) {
            foreach ($tickets as $key => $ticket) {
              if (!empty($ticket->connection)) {
                if (isset($tickets_availability[$ticket->api_sku])) {
                  continue;
                }
                $connection_function = '_'.$ticket->connection->function_name.'Availability';
                $tickets_availability = array_merge($this->$connection_function($attraction,$tickets,$ticket->connection,$date),$tickets_availability);
              }
            }
            
          }
        }
        // ksort($times);
        return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>200,'data'=>array_values($tickets)]));
    }

    private function _getAttractionTickets($attraction,$date,$time = null) {
      $tickets = [];
      $tickets_max_quantity = [];
      $tickets_slots = [];
      $agent = $this->Auth->user();
      $now = DateTime::now();
      if ($attraction) {
        if ($attraction->pricing_type == 'settlement') {
          $tickets_query = $this->Attractions->Tickets->find()->where(['Tickets.attraction_id'=>$attraction->id,'type !='=>'ctc','OR'=>[['publish_start_date <='=>$now,'publish_end_date >='=>$now],'redemption_type'=>'duration']]);
          $raw_tickets = $tickets_query->toArray();
          // foreach ($tickets as $key => $ticket) {
          //     $ticket = $this->TicketsHelper->getTicketResellerPrice($ticket,$attraction,$this->Auth->user());
          //     $stock = $this->TicketsHelper->getOpenDateAvailableQuantity($ticket);
          //     $tickets_max_quantity[$ticket->id] = $stock;
          // }
          $http = new \Cake\Http\Client();
          $api_url = Configure::read('Api.url');
          $data = ['sku_id'=>$attraction->sku_id,'time_from'=>$date,'time_to'=>$date];
          $headers = $this->_buildHeaders($data,$this->Auth->user('email'),$this->Auth->user('id'));
          $availabilities = [];
          $ticket_ids = [];
          foreach ($raw_tickets as $key => $ticket) {
            $ticket_ids[$ticket->sku_id] = $ticket->id;
            if ($this->request->getSession()->check('language')) {
              Translator::translateTicket($this->request, $ticket);
            }
            $tickets[$ticket->sku_id] = $ticket;
          }
          if (!empty($ticket_ids)) {
            try {
              $response = $http->get($api_url.'/availabilities',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
              if ($response->isOk()) {
                $res = $response->getJson();
                $availabilities = $res['response']['data'];
                if (!empty($res['response']['data'])) {
                  foreach ($res['response']['data'] as $date => $available_tickets) {
                    foreach ($available_tickets as $key => $available_ticket) {
                      if (empty($ticket_ids[$available_ticket['sku_id']])) {
                        continue;
                      }
                      $ticket_id = $ticket_ids[$available_ticket['sku_id']];
                      $tickets_max_quantity[$ticket_id] = intval($available_ticket['available_quantity']);
                      $slot = $this->_extractSharedSlot($available_ticket);
                      if ($slot) {
                        $tickets_slots[$ticket_id] = $slot;
                      }
                    }
                  }
                }
              }
            } catch (\Exception $e) {

            }
          }
          
        }
        else {
          $tickets_query = $this->Attractions->Tickets->find()->where(['Tickets.attraction_id'=>$attraction->id,'type !='=>'ctc']);
          if ($attraction->pricing_type == 'fdatetime') {
            if ($time) {
              $tickets_query
              ->where(['redemption_start_date <='=>$date->format('Y-m-d').' '.$time->format('H:i:00'),'redemption_end_date >='=>$date->format('Y-m-d').' '.$time->format('H:i:00'),'publish_start_date <='=>$now,'publish_end_date >='=>$now])
              ->contain(['Fixeddates'=>function ($q) use ($date,$time) {
                return $q->where(['date'=>$date->format('Y-m-d'),'time'=>$time->format('H:i:00')]);
              }]);
            }
          }
          else {
            $tickets_query
            ->where(['redemption_start_date <='=>$date->format('Y-m-d').' 23:59:59','redemption_end_date >='=>$date->format('Y-m-d 00:00:00'),'publish_start_date <='=>$now,'publish_end_date >='=>$now])
            ->contain(['Fixeddates'=>function ($q) use ($date) {
              return $q->where(['date'=>$date->format('Y-m-d')]);
            }]);
          }
          $datetime = null;
          if ($time) {
            $datetime = DateTime::createFromFormat('Y-m-d H:i:s',$date->format('Y-m-d'). ' '.$time->format('H:i:s'));
          }
          else {
            $datetime = DateTime::createFromFormat('Y-m-d H:i:s',$date->format('Y-m-d 00:00:00'));
          }

          $tickets_query
          ->order(['sequence'=>'ASC'])
          ->matching('FdatesettlementsAgents',function($q) use ($agent){
                return $q->where(['FdatesettlementsAgents.agent_id'=>$agent['agent_id']]);
            });
          $tickets_query->contain(['Connections']);
          $availabilities = [];
          $tickets_array = $tickets_query->toArray();
          foreach ($tickets_array as $key => $ticket) {
            if (!empty($ticket->fixeddates)) {
              if (
                ($attraction->pricing_type == 'fdate' && ((new DateTime($ticket->fixeddates[0]->date->format('Y-m-d')))->addSeconds($ticket->cutoff_seconds)->isToday() || (new DateTime($ticket->fixeddates[0]->date->format('Y-m-d')))->addSeconds($ticket->cutoff_seconds)->isFuture())) || 
                ($attraction->pricing_type == 'fdatetime' && (new DateTime($ticket->fixeddates[0]->date->format('Y-m-d') .' ' .(!empty($ticket->fixeddates[0]->time) ? $ticket->fixeddates[0]->time->format('H:i:s') : '00:00:00')))->addSeconds($ticket->cutoff_seconds)->isFuture())
                ) {
                $ticket->redemption_start_date = $ticket->redemption_start_date->format('Y M d');
                $ticket->redemption_end_date = $ticket->redemption_end_date->format('Y M d');
                $tickets[$ticket->sku_id] = $this->TicketsHelper->getFixeddateResellerPrice($ticket,$attraction,$this->Auth->user(),$datetime);
              }
            }
            if ($this->request->getSession()->check('language')) {
              Translator::translateTicket($this->request, $ticket);
            }
          }
          $http = new \Cake\Http\Client();
          $api_url = Configure::read('Api.url');
          $data = ['sku_id'=>$attraction->sku_id,'time_from'=>$datetime->format('Y-m-d'),'time_to'=>$datetime->format('Y-m-d')];
          $headers = $this->_buildHeaders($data,$this->Auth->user('email'),$this->Auth->user('id'));
          $ticket_ids = [];
          
          foreach ($tickets_array as $key => $ticket) {
            $ticket_ids[$ticket->sku_id] = $ticket->id;
          }
          
          if (!empty($ticket_ids)) {
            try {
              $response = $http->get($api_url.'/availabilities',$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout'=>Configure::read('Api.timeout')]);
              if ($response->isOk()) {
                $res = $response->getJson();
                $availabilities = $res['response']['data'];
                if (!empty($res['response']['data'])) {
                  foreach ($res['response']['data'] as $date => $available_tickets) {
                    foreach ($available_tickets as $key => $available_ticket) {
                      if (empty($ticket_ids[$available_ticket['sku_id']])) {
                        continue;
                      }
                      $ticket_id = $ticket_ids[$available_ticket['sku_id']];
                      if ($attraction->pricing_type == 'fdatetime') {
                        foreach ($tickets as $key => $ticket) {
                          if ($ticket->id == $ticket_id) {
                            if (isset($ticket->fixeddates[0])) {
                              if ($ticket->fixeddates[0]->time->format('H:i') == $available_ticket['time']) {
                                $tickets_max_quantity[$ticket_id] = intval($available_ticket['available_quantity']);
                                $slot = $this->_extractSharedSlot($available_ticket);
                                if ($slot) {
                                  $tickets_slots[$ticket_id] = $slot;
                                }
                                if (!empty($tickets[$available_ticket['sku_id']])) {
                                  if ($tickets[$available_ticket['sku_id']]->markup_value && $tickets[$available_ticket['sku_id']]->markup_value > 0 && isset($available_ticket['price'])) {
                                    $tickets[$available_ticket['sku_id']]->price = floatval($available_ticket['price']);
                                  }
                                }
                              }
                            }
                          }
                        }

                      } else {
                        $tickets_max_quantity[$ticket_id] = intval($available_ticket['available_quantity']);
                        $slot = $this->_extractSharedSlot($available_ticket);
                        if ($slot) {
                          $tickets_slots[$ticket_id] = $slot;
                        }
                        if (!empty($tickets[$available_ticket['sku_id']])) {
                          if ($tickets[$available_ticket['sku_id']]->markup_value && $tickets[$available_ticket['sku_id']]->markup_value > 0 && isset($available_ticket['price'])) {
                            $tickets[$available_ticket['sku_id']]->price = floatval($available_ticket['price']);
                          }
                        }
                      }
                    }
                    
                  }
                }
              }
            } catch (\Exception $e) {

            }
          }
        }

      }
      return ['tickets'=> array_values($tickets),'tickets_max_quantity'=>$tickets_max_quantity,'tickets_slots'=>$tickets_slots];
    }

    /**
     * Extract shared-slot info from an availability entry.
     *
     * Availability entries that belong to a shared inventory pool carry
     * type = "shared" and a slot_id. Tickets sharing the same slot_id draw
     * from one pool (available_quantity), so the view must cap their combined
     * selection. Returns null for non-shared entries.
     *
     * @param array $available_ticket A single availability entry from the API.
     * @return array|null ['slot_id'=>..,'type'=>..,'pool'=>int] or null.
     */
    private function _extractSharedSlot($available_ticket) {
      if (!empty($available_ticket['slot_id']) && (($available_ticket['type'] ?? '') === 'shared')) {
        return [
          'slot_id' => $available_ticket['slot_id'],
          'type'    => $available_ticket['type'],
          'pool'    => intval($available_ticket['available_quantity']),
        ];
      }
      return null;
    }

    private function _buildHeaders($data,$email,$user_id) {
      $secret_key = Configure::read('Api.secret');
      $header = [];
      $header['X-API-Version'] = 'v1.10';
      $header['Date'] = date('Y-m-d H:i:s');
      $header['X-CA-User'] = $email;
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

    /**
     * Toggle favorite
     *
     * @return \Cake\Http\Response
     */
    public function toggleFavorite(): Response {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $this->FavoriteAttractions = $this->fetchTable('FavoriteAttractions');
        $favorite = $this->FavoriteAttractions->newEntity($this->request->getData());
        if ($favorite->hasErrors()) {
            return $this->response->withStatus(400)->withStringBody(json_encode($favorite->getErrors()));
        }
        $attraction_id = $this->request->getData('attraction_id');
        $attraction = $this->Attractions->find()->where(['id'=>$attraction_id])->first();
        if (!$attraction) {
            return $this->response->withStatus(400)->withStringBody(json_encode(['error'=>'Attraction not found']));
        }
        $favorite_attraction = $this->FavoriteAttractions->toggle($attraction_id, $this->Auth->user('id'));
        if ($favorite_attraction) {
          return $this->response->withStatus(200)->withStringBody(json_encode(['data' => $favorite_attraction]));
        }
        return $this->response->withStatus(400);
    }
}
