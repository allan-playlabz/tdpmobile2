<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\I18n\Time;
/**
 * Categories Controller
 *
 * @property \App\Model\Table\CategoriesTable $Categories
 *
 * @method \App\Model\Entity\Category[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CategoriesController extends AppController
{
    public function initialize(): void {
        parent::initialize();
        $this->loadModel('Attractions');
    }
    /**
     * View method
     *
     * @param string|null $id Category id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id)
    {

      $this->paginate = ['limit'=>60];
      $conditions = [];
      $search = null;
      $attractions = null;
      $fdate_attractions = null;
      $selected_categories = [];
      if ($this->request->getData('search')) {
          $conditions['title like'] = '%'.$this->request->getData('search').'%';
          $search = $this->request->getQuery('search');
      }
      if ($this->request->getData('categories')) {
          $selected_categories = $this->request->getData('categories');
      }
      $agent = $this->Auth->user();
      $attraction_query = $this->Attractions->find()
      ->select(['Attractions.status','Attractions.id','Attractions.title','Attractions.pricing_type','Attractions.featured_image','settlement_agents.id','fixeddates_agents.id','settlement_agents.settlement_id'])
      ->where(['Attractions.status'=>'published'])
      ->where($conditions)
      ->order(['display_position'=>'ASC','title'=>'ASC'])
      ->join(
        [[
          'table' => 'settlement_agents',
          'type' => 'LEFT',
          'conditions' => ['settlement_agents.attraction_id = Attractions.id','Attractions.pricing_type' => 'settlement',
          'settlement_agents.agent_id =(UNHEX(REPLACE("'.$agent['agent_id'].'", "-","")))'],
          'limit'=>1
        ],
        [
          'table' => 'fixeddates_agents',
          'type' => 'LEFT',
          'conditions' => ['fixeddates_agents.attraction_id = Attractions.id','Attractions.pricing_type !=' => 'settlement','fixeddates_agents.agent_id =(UNHEX(REPLACE("'.$agent['agent_id'].'", "-","")))'],
          'limit'=>1
        ]
      ])
      ->where([
        'OR'=>[['Attractions.pricing_type'=>'settlement','settlement_agents.id IS NOT NULL'],['Attractions.pricing_type !='=>'settlement','fixeddates_agents.id IS NOT NULL']
        ]
      ]);
      $attractions = $this->paginate($attraction_query
        ->contain(['Categories'])
        ->where(['Attractions.category_id'=>$id])
        ->distinct('Attractions.id')
      )->toArray();
      
      
      $now = Time::now();
      foreach ($attractions as $key => $attraction) {
        $attraction->ticket = null;
        if ($attraction->pricing_type == 'settlement') {
          $attraction->ticket = $this->Attractions->Tickets->find()
          ->contain(['SettlementAgents.Settlements'])
          ->where(['SettlementAgents.attraction_id'=>$attraction->id])
          ->matching('SettlementAgents',function($q) use ($agent){
              return $q->where(['SettlementAgents.agent_id'=>$agent['agent_id']]);
          })->order(['sequence'=>'asc'])->first();
          if (!empty($attraction->ticket)) {
              
              if ($attraction->ticket->publish_start_date->addSeconds($attraction->ticket->cutoff_seconds)->isFuture()) {
                $attraction->first_open_date = $attraction->ticket->publish_start_date->addSeconds($attraction->ticket->cutoff_seconds)->diffInDays($now);
              }
              else {
                $ticket_release = Time::now()->addSeconds($attraction->ticket->cutoff_seconds);
                $attraction->first_open_date = $ticket_release->diffInDays($now);
              }
              if ($attraction->first_open_date == 0) {
                $attraction->first_open_date = 'Today';
              }
              elseif ($attraction->first_open_date > 1) {
                $attraction->first_open_date = 'in '.$attraction->first_open_date .' days';
              }
              else {
                $attraction->first_open_date ='tomorrow';
              }
          }
        }
        else {
          $tickets = $this->Attractions->Tickets->find()
          // ->select(['Tickets.id'])
          // ->contain(['Fixeddates'=>function ($q) {
          //   return $q->where(['date >='=>Time::now()])->limit(1)->order(['date'=>'ASC']);
          // }])
          ->where(['Tickets.attraction_id'=>$attraction->id])
          ->matching('FixeddatesAgents',function($q) use ($agent){
              return $q->where(['FixeddatesAgents.agent_id'=>$agent['agent_id']]);
          })->order(['sequence'=>'asc'])->toArray();
          if (!empty($tickets)) {
            $attraction->ticket = $tickets[0];
            $now = Time::now();
            $ticket_ids = [];
            foreach ($tickets as $key => $ticket) {
              if ($ticket->publish_start_date <= $now) {
                $ticket_ids[] = $ticket->id;
              }
            }

            if (!empty($ticket_ids)) {
              $first_fixeddate = $this->Attractions->Tickets->Fixeddates->find()->where(['ticket_id IN'=>$ticket_ids,'date >= (CURDATE() + INTERVAL Tickets.cutoff_seconds second)'])->contain('Tickets')->first();
              if ($first_fixeddate) {
                $attraction->first_open_date = $first_fixeddate->date->diffInDays($now);
                if ($attraction->ticket) {
                  $attraction->ticket->fixeddates = [$first_fixeddate];
                }
              }

              if ($attraction->first_open_date == 0 && $first_fixeddate) {
                if ($first_fixeddate->date->format('Y-m-d') != $now->format('Y-m-d') ) {
                  $attraction->first_open_date = 'tomorrow';
                }
                else {
                  $attraction->first_open_date = 'today';
                }
              }
              elseif ($attraction->first_open_date > 1) {
                $attraction->first_open_date = 'in '.$attraction->first_open_date .' days';
              }
              elseif ($attraction->first_open_date == 1) {
                $attraction->first_open_date = 'tomorrow';
              }
            }
            
          }
        }
      }
      $categories = $this->Attractions->Categories->find()
                  ->where(['parent_id is'=>null])
                  ->contain(['ChildCategories' => [
                      'sort' => ['name' => 'asc']
                    ]])
                  ->order(['Categories.name'=>'asc'])->toList();

      $this->set(compact('attractions','categories','search','selected_categories'));
    }

    
}
