<?php
namespace App\Controller;

use App\Util\Csv;
use Cake\I18n\Time;
use Cake\I18n\Number;
use Cake\Core\Configure;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use App\Controller\AppController;
/**
 * Reports Controller
 *
 *
 * @method \App\Model\Entity\Report[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ReportsController extends AppController
{
    public function initialize(): void {
      parent::initialize();
      $this->paginate = ['maxLimit'=>500];
    }
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */

    public function index() {
      /*
       this report is similar to admin except cost_price,redeem dates and status are removed
       Quantity Issued is changed to Units Issued so it makes sense to do multiply by price
       Total Sales Price is units issued * unit price
      */
      $this->loadModel('Orders');
      $this->loadModel('Barcodes');
      $this->loadModel('GeneratedCodes');
      $this->loadModel('Pdfs');
      $this->loadModel('ApiPdfs');
      $this->loadModel('Agents');
      $agent = $this->Agents->find()
            ->where(['id'=>$this->Auth->user('agent_id')])
            ->first();
      $agent_user_ids = [];
      if (!empty($agent)) {
        if ($this->Auth->user('role') == 'master') {
          $agent_user_ids = $this->Agents->AgentUsers->find('list',['valueField'=>'id'])->where(['agent_id'=>$this->Auth->user('agent_id')])->toList();
          if (empty($agent_user_ids)) {
            $agent_user_ids = [$this->Auth->user('id')];
          }
          $conditions = ['agent_user_id IN' => $agent_user_ids];
        } else {
          $agent_user_ids = [$this->Auth->user('id')];
          $conditions = ['agent_user_id' => $this->Auth->user('id')];
        }
      
        $conditions['Orders.created >='] = date('Y-m-1 00:00:00');

        if ($this->request->is('post')) {
          if ($this->_getUnfinishedReports() > 0) {
            $this->Flash->error('You have a report that is processing. Please wait before creating another report.');
            return $this->redirect(['action' => 'index']);
          }
          $first_date_allowed = (new Time())->subMonths(Configure::read('Default.previous_orders_months_to_view'));
          $start_date = $first_date_allowed;
          $start_date->hour(0)->minute(0)->second(0);
          $end_date = Time::now();
          if ($this->request->getData('issue_start_date')) {
            $start_date = Time::createFromFormat('d M Y',$this->request->getData('issue_start_date'));;
            if ($start_date) {
              $start_date->hour(0)->minute(0)->second(0);
            }
          }
          $conditions['Orders.created >='] = $start_date;
          if ($start_date < $first_date_allowed) {
            $start_date = $first_date_allowed->hour(0)->minute(0)->second(0);
          }
          if ($this->request->getData('issue_end_date')) {
            $end_date = Time::createFromFormat('d M Y',$this->request->getData('issue_end_date'));;
            if ($end_date) {
              $end_date->hour(23)->minute(59)->second(59);
            }
            
            $conditions['Orders.created <='] = $end_date;
          }
          
          $conditions['Orders.status NOT IN'] = ['pending', 'void'];
          $orders_query = $this->Orders->find()->where($conditions)
            ->contain(['AgentUsers'=>['Agents'],'OrderItems'=>['Tickets'=>['Attractions'=>function($aq) {
              return $aq->select(['Attractions.title','Attractions.type','Merchants.name'])->contain(['Merchants']);
            }],'Vouchers']])
            ->order(['Orders.created'=>'ASC']);
        
          $orders_count = $orders_query->count();
          if (!empty($orders_count > 0)) {
            $cmd = 'php '.ROOT.DS.'bin'.DS.'cake.php generate_report';
            $cmd .= ' --agent_user_id="'. $this->Auth->user('id') . '"';
            $cmd .= ' --issue_start_date="' . $this->request->getData('issue_start_date') . '"';
            $cmd .= ' --issue_end_date="' . $this->request->getData('issue_end_date') . '"';
            $cmd .= ' --start_date="' . $start_date->format('Y-m-d H:i:s') . '"';
            $cmd .= ' --end_date="' . $end_date->format('Y-m-d H:i:s') . '"';
            $cmd .= ' --agent_user_ids="' . implode(',', $agent_user_ids) . '"';
            $cmd .= ' > /dev/null 2>&1 &';
            // debug($cmd);
            try {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $p_string = "start {$cmd}";
                    pclose(popen($p_string, "r"));
                } else {
                    exec($cmd);
                }
            } catch (\Exception $exc) {
                debug('Error: '.$exc);
            }
            $this->Flash->success('We are generating the report. Please check the Generated Reports link to download your report file.');

          } else {
            $this->Flash->error(__('No transactions found.'));
          }
          
        }
      }
      $this->set(compact('agent'));
    }

    public function usage() {
      $this->loadModel('CreditLogs');
      $conditions = ['agent_id'=>$this->Auth->user('agent_id'),'debit_credit'=>'credit'];

      $query = $this->CreditLogs;
      $this->paginate = [
        'conditions' => $conditions,
        'order'=>['created'=>'desc'],
        'limit'=>500
      ];
      $credit_logs = $this->paginate($query);
      
      if ($this->request->is('post')) {
        $array_records = [];
        $array_records[] = ['Usage History'];
        $array_records[] = ['Report Date:',date('Y-m-d H:i:s')];
        $array_records[] = [];
        $array_records[] = ['Amount','Date Updated','Ref ID','Remarks'];
        
        foreach ($credit_logs as $key => $credit_log) {
          $array_records[] = [Number::currency($credit_log->amount,'SGD'),$credit_log->modified->format('Y M d h:i a'),$credit_log->id,'N/A'];
        }
        // debug($array_records);
        $output_file_name = 'Usage_History_'.date('dMYHi').'.csv';
        $csv = Csv::writeReport($array_records, $output_file_name, ',');

        return $this->response->withFile($csv, ['download' => true, 'name' => $output_file_name]);
      }

      $this->set(compact('credit_logs'));
    }

    public function reports() {
      $folder = new Folder(TMP . 'reports' . DS . $this->Auth->user('id'));
      $files = $folder->find('.*\.csv');
     
      $reports = [];
      $files_age = 24 * 60 * 60;
      foreach ($files as $file) {
        $report_file = new File($file);
        $created = filectime($folder->path.DS.$file);
        $last_changed = $report_file->lastChange();
        if (time() - $created > $files_age) {
          $report_file->delete();
        } else {
          
          $reports[$created] = [
            'name' => str_replace($this->Auth->user('id'), '', $report_file->name()),
            'filename' => $report_file->name(),
            'created' => date('d M Y H:i:s', $created),
          ];
        }
      }
      krsort($reports);
      $tmp_files = $folder->find('.*\.tmp');
      $processing_files_count = count($tmp_files);
      $processing_files = [];
      foreach ($tmp_files as $tmp_file) {
        $report_file = new File($tmp_file);
        $created = filectime($folder->path.DS.$tmp_file);
        $last_changed = $report_file->lastChange();
        if (time() - $created > $files_age) {
          $report_file->delete();
        } else {          
          $processing_files[$created] = [
            'name' => str_replace([$this->Auth->user('id'), '.tmp'], '', $tmp_file),
            'filename' => $report_file->name(),
            'created' => date('d M Y H:i:s', $created),
          ];
        }
      }
      $this->set(compact('reports', 'processing_files_count', 'processing_files'));
    }

    public function download($name) {
      // debug(TMP . 'reports' . DS . $name . '.csv');exit;
      return $this->response->withFile(TMP . 'reports' . DS . $this->Auth->user('id') . DS . $name . '.csv', [
        'name' => str_replace($this->Auth->user('id'), '', $name).'.csv',
        'download' => true
      ]);
    }

    private function _getUnfinishedReports() {
      $folder = new Folder(TMP . 'reports' . DS . $this->Auth->user('id'));
      $files = $folder->find('.*\.tmp');
      
      return count($files);
    }
}
