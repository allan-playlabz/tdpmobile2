<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Filesystem\Folder;
use App\Filesystem\File;
use Cake\Core\Configure;
use Cake\Utility\Text;
use Cake\I18n\DateTime;
/**
 * Agents Controller
 *
 * @property \App\Model\Table\AgentsTable $Agents
 *
 * @method \App\Model\Entity\Agent[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AgentsController extends AppController
{
    private $_countries = ["Singapore","Afghanistan","Albania","Algeria","Andorra","Angola","Anguilla","Antigua &amp; Barbuda","Argentina","Armenia","Aruba","Australia","Austria","Azerbaijan","Bahamas"
    ,"Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia &amp; Herzegovina","Botswana","Brazil","British Virgin Islands"
    ,"Brunei","Bulgaria","Burkina Faso","Burundi","Cambodia","Cameroon","Canada","Cape Verde","Cayman Islands","Chad","Chile","China","Colombia","Congo","Cook Islands","Costa Rica"
    ,"Cote D Ivoire","Croatia","Cruise Ship","Cuba","Cyprus","Czech Republic","Denmark","Djibouti","Dominica","Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea"
    ,"Estonia","Ethiopia","Falkland Islands","Faroe Islands","Fiji","Finland","France","French Polynesia","French West Indies","Gabon","Gambia","Georgia","Germany","Ghana"
    ,"Gibraltar","Greece","Greenland","Grenada","Guam","Guatemala","Guernsey","Guinea","Guinea Bissau","Guyana","Haiti","Honduras","Hong Kong","Hungary","Iceland","India"
    ,"Indonesia","Iran","Iraq","Ireland","Isle of Man","Israel","Italy","Jamaica","Japan","Jersey","Jordan","Kazakhstan","Kenya","Kuwait","Kyrgyz Republic","Laos","Latvia"
    ,"Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg","Macau","Macedonia","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Mauritania"
    ,"Mauritius","Mexico","Moldova","Monaco","Mongolia","Montenegro","Montserrat","Morocco","Mozambique","Namibia","Nepal","Netherlands","Netherlands Antilles","New Caledonia"
    ,"New Zealand","Nicaragua","Niger","Nigeria","Norway","Oman","Pakistan","Palestine","Panama","Papua New Guinea","Paraguay","Peru","Philippines","Poland","Portugal"
    ,"Puerto Rico","Qatar","Reunion","Romania","Russia","Rwanda","Saint Pierre &amp; Miquelon","Samoa","San Marino","Satellite","Saudi Arabia","Senegal","Serbia","Seychelles"
    ,"Sierra Leone","Slovakia","Slovenia","South Africa","South Korea","Spain","Sri Lanka","St Kitts &amp; Nevis","St Lucia","St Vincent","St. Lucia","Sudan"
    ,"Suriname","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","Timor L'Este","Togo","Tonga","Trinidad &amp; Tobago","Tunisia"
    ,"Turkey","Turkmenistan","Turks &amp; Caicos","Uganda","Ukraine","United Arab Emirates","United Kingdom","United States","United States Minor Outlying Islands","Uruguay"
    ,"Uzbekistan","Venezuela","Vietnam","Virgin Islands (US)","Yemen","Zambia","Zimbabwe"];

    public function initialize(): void {
        parent::initialize();
        $this->loadComponent('S3Public');
        $this->Attractions = $this->fetchTable('Attractions');
    }
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
      $options=['first_name'=>'First Name', 'last_name'=>'Last Name'];
      $this->loadComponent('Search');
      $query=$this->Search->buildQuery($this->request->getQuery(), $options);
      $query['Agents.role'] = 'master';

      $this->paginate = [
        'order' => [
          'first_name' => 'asc'
        ],
      ];
      $agents = $this->paginate($this->Agents->find()
        ->where($query)
        ->contain(['MasterAccount'=>['UsersIpaddress'=>function($uiq) {
              return $uiq->orderBy(['UsersIpaddress.modified'=>'desc'])->limit(1);
            }]]));

      $this->set(compact('agents','options'));
    }

    /**
     * View method
     *
     * @param string|null $id Agent id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $agent = $this->Agents->get($id, contain: ['AgentTopups'=>function($q) {
            return $q->orderBy(['AgentTopups.created'=>'DESC'])->contain(['Admins']);
        }, 'AgentUsers','MasterAgents']);
        if ($agent) {
          $topup_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'debit']);
          $total_topup = $topup_query->select(['total_topup'=>$topup_query->func()->sum('amount')])->first();
          $total_topup = $total_topup->total_topup;
          $usage_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'credit']);
          $total_usage = $usage_query->select(['total_usage'=>$usage_query->func()->sum('amount')])->first();
          $total_usage = $total_usage->total_usage;
          $this->set(compact('total_topup','total_usage'));
        }
        $this->set('agent', $agent);
    }

    public function creditLogs($id = null)
    {
        $agent = $this->Agents->get($id, contain: ['MasterAgents']);
        if ($agent) {
          $agent->credit_logs = $this->paginate($this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id])->contain(['Orders'])->orderBy(['CreditLogs.created'=>'DESC']));
          $topup_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'debit']);
          $total_topup = $topup_query->select(['total_topup'=>$topup_query->func()->sum('amount')])->first();
          $total_topup = $total_topup->total_topup;
          $usage_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'credit']);
          $total_usage = $usage_query->select(['total_usage'=>$usage_query->func()->sum('amount')])->first();
          $total_usage = $total_usage->total_usage;
          $this->set(compact('total_topup','total_usage'));
        }
        $this->set('agent', $agent);
    }

    public function SubAgents($id = null)
    {
        $agent = $this->Agents->get($id, contain: ['SubAgents'=>function($q) {
            return $q->orderBy(['created'=>'DESC']);
        }, 'MasterAgents']);
        if ($agent) {
          $topup_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'debit']);
          $total_topup = $topup_query->select(['total_topup'=>$topup_query->func()->sum('amount')])->first();
          $total_topup = $total_topup->total_topup;
          $usage_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'credit']);
          $total_usage = $usage_query->select(['total_usage'=>$usage_query->func()->sum('amount')])->first();
          $total_usage = $total_usage->total_usage;
          $this->set(compact('total_topup','total_usage'));
        }
        $this->set('agent', $agent);
    }

    public function agentUsers($id = null)
    {
        $agent = $this->Agents->get($id, contain: ['AgentUsers'=>function($q) {
            return $q->where(['role'=>'normal'])->orderBy(['created'=>'DESC'])->contain(['UsersIpaddress'=>function($uiq) {
          return $uiq->orderBy(['UsersIpaddress.modified'=>'desc'])->limit(1);
        }]);
        }, 'MasterAgents']);
        if ($agent) {
          $topup_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'debit']);
          $total_topup = $topup_query->select(['total_topup'=>$topup_query->func()->sum('amount')])->first();
          $total_topup = $total_topup->total_topup;
          $usage_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'credit']);
          $total_usage = $usage_query->select(['total_usage'=>$usage_query->func()->sum('amount')])->first();
          $total_usage = $total_usage->total_usage;
          $this->set(compact('total_topup','total_usage'));
        }
        $this->set('agent', $agent);
    }

    public function notActive($id = null,$days = 15)
    {
        $agents = $this->Agents->find()->where(['OR'=>['last_activity IS NULL','last_activity <'=>\Cake\I18n\DateTime::now()->subDays(\Cake\Core\Configure::read('Default.inactive_days'))]])->toArray();

        $this->set('agents', $agents);
    }

    public function tickets($id = null)
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
        $agent = $this->Agents->get($id, contain: ['CreditLogs'=>function($q) {
            return $q->orderBy(['created'=>'DESC']);
        }, 'MasterAgents']);
        if ($agent) {
          $topup_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'debit']);
          $total_topup = $topup_query->select(['total_topup'=>$topup_query->func()->sum('amount')])->first();
          $total_topup = $total_topup->total_topup;
          $usage_query = $this->Agents->CreditLogs->find()->where(['agent_id'=>$agent->id,'debit_credit'=>'credit']);
          $total_usage = $usage_query->select(['total_usage'=>$usage_query->func()->sum('amount')])->first();
          $total_usage = $total_usage->total_usage;
          $this->set(compact('total_topup','total_usage'));
        }
        $attraction_query = $this->Attractions->find()
        ->select(['Attractions.status','Attractions.id','Attractions.title','Attractions.title_chinese','Attractions.pricing_type','Attractions.featured_image','settlement_agents.id','fixeddates_agents.id','settlement_agents.settlement_id'])
        ->where(['Attractions.status'=>'published'])
        ->where($conditions)
        ->orderBy(['display_position'=>'ASC','title'=>'ASC'])
        ->join(
          [[
            'table' => 'settlement_agents',
            'type' => 'LEFT',
            'conditions' => ['settlement_agents.attraction_id = Attractions.id','Attractions.pricing_type' => 'settlement',
            'settlement_agents.agent_id =(UNHEX(REPLACE("'.$agent->id.'", "-","")))'],
            'limit'=>1
          ],
          [
            'table' => 'fixeddates_agents',
            'type' => 'LEFT',
            'conditions' => ['fixeddates_agents.attraction_id = Attractions.id','Attractions.pricing_type !=' => 'settlement','fixeddates_agents.agent_id =(UNHEX(REPLACE("'.$agent->id.'", "-","")))'],
            'limit'=>1
          ]
        ])
        ->where([
          'OR'=>[['Attractions.pricing_type'=>'settlement','settlement_agents.id IS NOT NULL'],['Attractions.pricing_type !='=>'settlement','fixeddates_agents.id IS NOT NULL']
          ]
        ]);
        
        if(count($selected_categories)>0) {
          $attractions = $this->paginate($attraction_query
            ->contain(['Categories'])
            ->where(['Attractions.category_id IN'=>$selected_categories])
            ->distinct('Attractions.id')
          )->toArray();
        } else {
          $attractions = $this->paginate($attraction_query->distinct('Attractions.id'))->toArray();
        }
        
        $now = DateTime::now();
        foreach ($attractions as $key => $attraction) {
          $attraction->ticket = null;
          if ($attraction->pricing_type == 'settlement') {
            $attraction->tickets = $this->Attractions->Tickets->find()
            ->contain(['SettlementAgents'=>function($q) use($agent) {
              return $q->contain(['Settlements'])->where(['agent_id'=>$agent->id]);
            }])
            ->where(['SettlementAgents.attraction_id'=>$attraction->id])
            ->matching('SettlementAgents',function($q) use ($agent){
                return $q->where(['SettlementAgents.agent_id'=>$agent->id]);
            })->orderBy(['sequence'=>'asc'])
            ->distinct('SettlementAgents.id')
            ->toArray();
            
          }
          else {
            $tickets = $this->Attractions->Tickets->find()
            ->where(['Tickets.attraction_id'=>$attraction->id])
            ->matching('FixeddatesAgents',function($q) use ($agent){
                return $q->where(['FixeddatesAgents.agent_id'=>$agent->id]);
            })->orderBy(['sequence'=>'asc'])->toArray();
            if (!empty($tickets)) {
              $attraction->tickets = $tickets;
              $now = DateTime::now();
              $ticket_ids = [];
              foreach ($tickets as $key => $ticket) {
                if ($ticket->publish_start_date <= $now) {
                  $ticket_ids[] = $ticket->id;
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
                    ->orderBy(['Categories.name'=>'asc'])->all()->toList();

        $this->set(compact('attractions','categories','search','selected_categories','agent'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $agent = $this->Agents->newEmptyEntity();
        if ($this->request->is('post')) {
          $data = $this->request->getData();
          $data['password'] = $this->_getRandomPassword();
          unset($data['credits']);
          unset($data['rebates']);
          unset($data['api_key']);
          unset($data['secret_key']);
          $agent = $this->Agents->patchEntity($agent, $data);
          $agent->verification_code = Text::uuid();
          if ($this->Agents->save($agent)) {
              if ($this->request->getData('image')) {
                if ($this->request->getData('image')->getError() == 0 && !empty(getimagesize($this->request->getData('image')->getStream()->getMetadata('uri')))) {
                  // $dir = new Folder(WWW_ROOT.'img'.DS.'agents'.DS.$agent->id,true,0775);
                  // $filename = $agent->id.DS.$this->request->getData('image.name');
                  // move_uploaded_file($this->request->getData('image.tmp_name'), WWW_ROOT.'img'.DS.'agents'.DS.$filename);
                  // $agent->image = '/img/agents/'.str_replace(DS, '/', $filename);
                    $filename = $agent->id.'/'.$this->request->getData('image')->getClientFilename();
                    $image_filename = 'agents/'.$filename;
                    $result = $this->S3Public->putObject($image_filename,$this->request->getData('image')->getStream()->getMetadata('uri'));
                    if ($result) {
                      $agent->image = $result;
                      $agent->setDirty('image',true);
                      $this->Agents->save($agent);
                    }
                }
              }
              $agentUser = $this->Agents->AgentUsers->newEmptyEntity();
              $agentUser->name = $agent->first_name.' '.$agent->last_name;
              $agentUser->email = $agent->email;
              if ($data['password']) {
                $agentUser->password = $data['password'];
              }
              $agentUser->role = 'master';
              $agentUser->agent_id = $agent->id;
              $agentUser->status = 'active';
              $this->Flash->success(__('The agent has been saved.'));
              if($this->Agents->AgentUsers->save($agentUser)){
                $this->Flash->success(__('New agent user created.'));
                $this->loadComponent('Mailer');
                $this->Mailer->mail('New Account','new_agent_account','Account Created',$agentUser->email,['agent_user'=>$agentUser,'temp_password' => $data['password'],'user'=>$this->Auth->user()],null,null);
              } else {
                print_r($agentUser->getErrors());
              }


              return $this->redirect(['action' => 'index']);
          }
          $this->Flash->error(__('The agent could not be saved. Please, try again.'));
        }
        $masters = $this->Agents->find('list', keyField: 'id', valueField: 'fullname')->where(['role'=>'master'])->limit(200);
        $this->set('countries',$this->_countries);
        $this->set(compact('agent','masters'));
    }

    public function addSubagent($id)
    {
        $master_agent = $this->Agents->find()->where(['id'=>$id,'can_add_sub'=>true])->first();
        if (!$master_agent) {
            $this->Flash->error('This agent is not allowed to have sub agents.');
            return $this->redirect(['controller'=>'Agents']);
        }
        $agent = $this->Agents->newEmptyEntity();
        if ($this->request->is('post')) {
            $agent = $this->Agents->patchEntity($agent, $this->request->getData());
            $agent->role = 'sub';
            $agent->master_agent_id = $master_agent->id;
            $agent->verification_code = Text::uuid();
            if ($this->Agents->save($agent)) {
                $this->Flash->success(__('The sub agent has been saved.'));

                return $this->redirect(['action' => 'subAgents',$master_agent->id]);
            }
            $this->Flash->error(__('The sub agent could not be saved. Please, try again.'));
        }
        $this->set('countries',$this->_countries);
        $this->set(compact('agent'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Agent id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $agent = $this->Agents->get($id, contain: []);
        $agentUser = $this->Agents->AgentUsers->find()->where(['agent_id'=>$agent->id,'role'=>'master'])->first();
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (!empty($data['image'])) {
              unset($data['image']);
            }
            unset($data['credits']);
            unset($data['rebates']);
            unset($data['api_key']);
            unset($data['secret_key']);
            unset($data['password']);
            unset($data['confirm_password']);
            

            $agent = $this->Agents->patchEntity($agent, $data);
            if ($this->request->getData('image')) {
              if ($this->request->getData('image')->getError() == 0 && !empty(getimagesize($this->request->getData('image')->getStream()->getMetadata('uri')))) {
                // $dir = new Folder(WWW_ROOT.'img'.DS.'agents'.DS.$agent->id,true,0775);
                // $filename = $agent->id.DS.$this->request->getData('image.name');
                // move_uploaded_file($this->request->getData('image.tmp_name'), WWW_ROOT.'img'.DS.'agents'.DS.$filename);
                // $agent->image = '/img/agents/'.str_replace(DS, '/', $filename);
                $this->S3Public->deleteObject($agent->image);
                $filename = $agent->id.'/'.$this->request->getData('image')->getClientFilename();
                $image_filename = 'agents/'.$filename;
                $result = $this->S3Public->putObject($image_filename,$this->request->getData('image')->getStream()->getMetadata('uri'));
                $agent->image = $result;
                $agent->setDirty('image',true);
              }
            }
            if ($this->Agents->save($agent)) {
            	$agentUser->name = $agent->fullname;
            	$agentUser->email = $agent->email;
            	if (isset($data['password'])) {
            		$agentUser->password = $data['password'];
            	}
            	$this->Agents->AgentUsers->save($agentUser);
              $this->Flash->success(__('The agent has been saved.'));
              return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The agent could not be saved. Please, try again.'));
        }
        $masters = $this->Agents->find('list', keyField: 'id', valueField: 'fullname')->where(['role'=>'master'])->limit(200);
        $this->set('countries',$this->_countries);
        $this->set(compact('agent','masters','agentUser'));
    }

     public function api($id = null)
     {
         $agent = $this->Agents->get($id, contain: []);
         if ($this->request->is(['patch', 'post', 'put'])) {
             $data = $this->request->getData();
             if (!empty($data['image'])) {
               unset($data['image']);
             }
             $temp_api_key = md5(uniqid(rand(), true));
             $agentcheck = $this->Agents->find()
                                  ->where(['api_key'=>$temp_api_key])
                                  ->toArray();
             while(count($agentcheck)>0) {
               $temp_api_key = md5(uniqid(rand(), true));
               $agentcheck = $this->Agents->find()
                                    ->where(['api_key'=>$temp_api_key])
                                    ->toArray();
             }
             $agent->api_key = $temp_api_key;
             $agent->secret_key = md5(uniqid(rand(), true));
             $agent = $this->Agents->patchEntity($agent, $data);
             if ($this->Agents->save($agent)) {
               $this->Flash->success(__('API Key and Secret Key are generated.'));
             } else {
               $this->Flash->success(__('API Key and Secret Key could not be generated. Please, try again.'));
             }
         }
         $this->set(compact('agent'));
     }

    /**
     * Delete method
     *
     * @param string|null $id Agent id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $agent = $this->Agents->get($id);
        $agent->status = 'inactive';
        $agent->setDirty('status', true);
        if ($this->Agents->save($agent)) {
        	$this->Agents->AgentUsers->updateAll(['status'=>'inactive'],['agent_id'=>$agent->id]);
            $this->Flash->success(__('The agent has been suspended.'));
        } else {
            $this->Flash->error(__('The agent could not be suspended. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    private function _getRandomPassword() {
        $digits = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz_^!';//abcdefghijklmnopqrstuvwxyz
        $max = strlen($digits)-1;
        $len = 8;
        // $code = '';
        // $prefix = ['VC','PK'];
        $code = '';
        for ($i=0; $i < $len ; $i++) {
            $code .= $digits[mt_rand(0,$max)];
        }
        return $code;
    }
}
