<?php
namespace App\Controller;

use Exception;
use App\Util\Csv;
use Cake\I18n\Time;
use Cake\I18n\Number;
use Cake\Core\Configure;
use App\Filesystem\File;
use Cake\I18n\FrozenTime;
use App\Filesystem\Folder;
use App\Controller\AppController;

/**
 * AgentTopups Controller
 *
 * @property \App\Model\Table\AgentTopupsTable $AgentTopups
 *
 * @method \App\Model\Entity\AgentTopup[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AgentTopupsController extends AppController
{

    public function initialize(): void {
        parent::initialize();
        $this->loadComponent('S3');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
      if($this->Auth->user('role')!='master') {
        $this->Flash->error('You are not authorized to access that location.');
        return $this->redirect('/');
      }
      $months_to_show = Configure::read('AgentTopups.months_to_show') ?? 6;
      $first_date_allowed = new \Cake\I18n\DateTime('-' . $months_to_show . ' months');
      $start_date = null;
      $end_date = \Cake\I18n\DateTime::now(Configure::read('App.defaultTimezone'));;
      $query = $this->AgentTopups->find('all',
      contain: ['Agents'],
      conditions: ['agent_id'=>$this->Auth->user('agent_id')],
      order: ['AgentTopups.created'=>'desc']);
      if ($this->request->getQuery('from')) {
        
        try {
          $start_date = Time::createFromFormat('d M Y',$this->request->getQuery('from'));
        } catch (Exception $e) {
          // just ignore the exception for now
        }
        if ($start_date) {
          if ($start_date < $first_date_allowed) {
            $start_date = $first_date_allowed;
          }
          $query = $query->where(['AgentTopups.created >=' => $start_date->hour(0)->minute(0)->second(0)]);
        }
      } else {
        $start_date = $first_date_allowed->hour(0)->minute(0)->second(0);
        $query = $query->where(['AgentTopups.created >=' => $start_date->hour(0)->minute(0)->second(0)]);
      }
      if ($this->request->getQuery('to')) {
        
        try {
          $end_date = Time::createFromFormat('d M Y',$this->request->getQuery('to'));
        } catch (Exception $e) {
          // just ignore the exception for now
        }
        if ($end_date) {
          $query = $query->where(['AgentTopups.created <=' => $end_date->hour(23)->minute(59)->second(59)]);
        }
      }
      // if ($start_date && $end_date && $end_date->diffInDays($start_date) > 31) {
      //   $this->Flash->error('Date range cannot be more than 30 days! Please select another date then filter.');
      //   return $this->redirect(['controller' => 'AgentTopups', 'action' => 'index']);
      // }
      if ($this->request->is('post')) {
        // if (!($start_date && $end_date)) {
        //   $this->Flash->error('Select a date and click filter to download!');
        //   return $this->redirect(['controller' => 'AgentTopups', 'action' => 'index']);
        // }
        $agent = $this->Agents->find()
          ->where(['id'=>$this->Auth->user('agent_id')])
          ->first();
        $array_records = [];
        $array_records[] = ['Report Date',date('Y-m-d H:i:s')];
        $array_records[] = ['Report Title','Top Up Report'];
        $array_records[] = ['Reseller',$agent->company_name];
        $array_records[] = ['Start Date', $first_date_allowed->format('Y-m-d')];
        $array_records[] = ['End Date', date('Y-m-d')];
        $array_records[] = [];
        
        $column_headers = ['Top Up Date', 'Amount (SGD)', 'Status', 'Remarks'];
        
        $array_records[] = $column_headers;
        $no = 1;
        $agentTopups = $query->toArray();
        if (!empty($agentTopups)) {
          foreach ($agentTopups as $key => $agentTopup) {
            $data = [
              $agentTopup->topup_date->format('d M Y'),
              Number::precision($agentTopup->amount, 2),
              ucwords($agentTopup->status),
              h($agentTopup->remarks),
            ];
            $array_records[] = $data;
          }
          
          $output_file_name = $agent->company_name . ' Top Up Report ' . date("YmdHi") . '.csv';
          $csv = Csv::writeReport($array_records, $output_file_name, ',');

          return $this->response->withFile($csv, ['download' => true, 'name' => $output_file_name]);
        }
        $this->Flash->error('No records found!');
        return $this->redirect(['controller' => 'AgentTopups', 'action' => 'index']);
      }
      $agentTopups = $this->paginate($query);

      $agent = $this->Agents->find()
        ->where(['id' => $this->Auth->user('agent_id')])
        ->first();
      $bank_reference_code = $agent ? $agent->bank_reference_code : null;

      $this->set(compact('agentTopups', 'bank_reference_code'));
    }

    /**
     * View method
     *
     * @param string|null $id Agent Topup id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
      if($this->Auth->user('role')!='master') {
        $this->Flash->error('You are not authorized to access that location.');
        return $this->redirect('/');
      }
      $agentTopup = $this->AgentTopups->get($id,
      contain: ['Agents'],
      conditions: ['agent_id'=>$this->Auth->user('agent_id')]);
      if ($agentTopup && $agentTopup->status == 'approved') {
        $this->AgentTopups->loadInto($agentTopup,['Admins']);
      }

      $this->set('agentTopup', $agentTopup);
    }

    public function add()
    {
      if($this->Auth->user('role')!='master') {
        $this->Flash->error('You are not authorized to access that location.');
        return $this->redirect('/');
      }
      $agentTopup = $this->AgentTopups->newEmptyEntity();
      if ($this->request->is('post')) {
          $agentTopup = $this->AgentTopups->patchEntity($agentTopup, $this->request->getData());
          $agentTopup->amount = floatval($agentTopup->amount);
          $agentTopup->agent_id = $this->Auth->user('agent_id');
          $agentTopup->topup_date = date('Y-m-d'); 
          $agentTopup->image = '';
          $agentTopup->status = 'pending';
          if ($this->request->getData('image')) {
            if ($this->request->getData('image.error') == 0) {
              if (getimagesize($this->request->getData('image.tmp_name'))) {
                if ($this->AgentTopups->save($agentTopup)) {
                    $this->Flash->success(__('The agent top up has been saved.'));
                        $filename = $this->Auth->user('agent_id').'/'.$this->request->getData('image.name');
                        $image_filename = 'agenttopups/'.$filename;
                        $this->S3->putObject($image_filename,$this->request->getData('image.tmp_name'));
                        $agentTopup->image = 'agent-topups/topup-images/'. $image_filename;
                        $agentTopup->setDirty('image',true);
                        if (!$this->AgentTopups->save($agentTopup)) {
                        }
                      
                    if ($this->request->getQuery('redirect')) {
                      return $this->redirect($this->request->getQuery('redirect'));
                    }
                    return $this->redirect(['action' => 'index']);
                }
              } else {
                $this->Flash->error(__('Invalid image.'));
              }
            }
            else {
              $this->Flash->error(__('Invalid file. Please, try again.'));
            }
          }
          else {
            $this->Flash->error(__('Please provide an image and try again.'));
          }
          $this->Flash->error(__('The agent top up could not be saved. Please, try again.'));
      }
      $this->set(compact('agentTopup'));
    }

    public function topupImages($key = null)
    {
        $this->autoRender = false;
        
        if ($key) {

            // $response = $this->response->withFile(WWW_ROOT.'order_files'.DS.$voucher->order_item->order->id.DS.$voucher->code_id.'.pdf',['download' => true,]);
            $file = $this->S3->getObject($key);
            $response = $this->response;
            if ($file) {
                $filenames = explode('.', $key);
                $ext = end($filenames);
                $response = $this->response->withStringBody($file->__toString())->withType($ext);
                // $response = $response->withDownload(end($filenames));
            }
            else {
                $response = $response->withStatus(404);
            }
            return $response;
        }
        else {
            return $this->response->withStatus(404);
        }

        $this->set('voucher', $voucher);
    }
}
