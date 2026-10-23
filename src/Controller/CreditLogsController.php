<?php
namespace App\Controller;

use Exception;
use App\Util\Csv;
use Cake\I18n\Time;
use Cake\I18n\Number;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use App\Controller\AppController;

/**
 * CreditLogs Controller
 *
 * @property \App\Model\Table\CreditLogsTable $CreditLogs
 *
 * @method \App\Model\Entity\CreditLog[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CreditLogsController extends AppController
{
    public function index()
    {
      // filter by date
      // max 30 days only
      $agent_id = $this->Auth->user('agent_id');
      $start_date = null;
      $end_date = null;
      if (!$this->Auth->user('agent.enable_credit_logs_view')) {
        return $this->redirect('/');
      }
      $this->paginate = [
          'contain' => ['Agents']
      ];
      $days_to_show = Configure::read('CreditLogs.days_to_show') ?? 100;
      $first_date_allowed = new FrozenTime('-' . $days_to_show . ' days');
      $query = $this->Agents->CreditLogs->find()
      ->contain(['Orders'])
      ->where(['CreditLogs.agent_id'=>$agent_id, 'CreditLogs.created >=' => $first_date_allowed])
      ->order(['CreditLogs.log_no'=>'desc']);
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
          $query = $query->where(['CreditLogs.created >=' => $start_date->hour(0)->minute(0)->second(0)]);
        }
      } else {
        $start_date = $first_date_allowed->hour(0)->minute(0)->second(0);
        $query = $query->where(['CreditLogs.created >=' => $start_date->hour(0)->minute(0)->second(0)]);
      }
      if ($this->request->getQuery('to')) {
        
        try {
          $end_date = Time::createFromFormat('d M Y',$this->request->getQuery('to'));
        } catch (Exception $e) {
          // just ignore the exception for now
        }
        if ($end_date) {
          $query = $query->where(['CreditLogs.created <=' => $end_date->hour(23)->minute(59)->second(59)]);
        }
      }

      if ($this->request->is('post')) {
        if (!($start_date && $end_date)) {
          $this->Flash->error('Select a date and click filter to download!');
          return $this->redirect(['controller' => 'CreditLogs', 'action' => 'index']);
        }
        if ($start_date && $end_date && $end_date->diffInDays($start_date) > 31) {
          $this->Flash->error('Date range cannot be more than 30 days! Please select another date then filter.');
          return $this->redirect(['controller' => 'CreditLogs', 'action' => 'index', '?' => $this->request->getQuery()]);
        }
        $agent = $this->Agents->find()
          ->where(['id'=>$this->Auth->user('agent_id')])
          ->first();
        $array_records = [];
        $array_records[] = ['Report Date',date('Y-m-d H:i:s')];
        $array_records[] = ['Report Title','Balance Report'];
        $array_records[] = ['Reseller',$agent->company_name];
        $array_records[] = ['Start Date', $first_date_allowed->format('Y-m-d')];
        $array_records[] = ['End Date', date('Y-m-d')];
        $array_records[] = [];
        
        $column_headers = ['Date','Type/Order Ref ID','Amount (SGD)', 'Balance (SGD)'];
        
        $array_records[] = $column_headers;
        $no = 1;
        $credit_logs = $query->toArray();
        if (!empty($credit_logs)) {
          foreach ($credit_logs as $key => $credit_log) {
            $data = [];
            $amount = $credit_log->debit_credit == 'debit' ? Number::precision($credit_log->amount, 2) : '(' . Number::precision($credit_log->amount, 2) . ')';
            $credits = $credit_log->credits > 0 ? Number::precision($credit_log->credits, 2) : '(' . Number::precision($credit_log->credits, 2) . ')';
            $type = $credit_log->order_id === null && $credit_log->debit_credit == 'debit' ? 'Top Up' : $credit_log->order->order_ref_id;
            if ($credit_log->order_id !== null && bccomp(strval($credit_log->amount), strval($credit_log->order->total), 2) !== 0 && $credit_log->debit_credit == 'debit') {
              $type .= ' (Rebate)';
            }
            if ($credit_log->order_id !== null && bccomp(strval($credit_log->amount), strval($credit_log->order->total), 2) === 0 && $credit_log->debit_credit == 'debit') {
              $type .= ' (Cancel)';
            }
            if ($credit_log->order_id !== null && bccomp(strval($credit_log->amount), strval($credit_log->order->total), 2) !== 0 && $credit_log->debit_credit == 'credit') {
              $type .= ' (Cancel Rebate)';
            }
            $data[] = $credit_log->created->format('d M Y h:ia');
            $data[] = $type;
            $data[] = $amount;
            $data[] = $credits;
            $array_records[] = $data;
          }
          
          $output_file_name = $agent->company_name . ' Balance Report ' . date("YmdHi") . '.csv';
          $csv = Csv::writeReport($array_records, $output_file_name, ',');

          return $this->response->withFile($csv, ['download' => true, 'name' => $output_file_name]);
        }
        $this->Flash->error('No records found!');
        return $this->redirect(['controller' => 'CreditLogs', 'action' => 'index']);
      }
      $creditLogs = $this->paginate($query);

      $this->set(compact('creditLogs'));
    }
}
