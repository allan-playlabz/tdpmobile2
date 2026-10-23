<?php
namespace App\View\Cell;

use Cake\I18n\Time;
use Cake\View\Cell;

class CreditsCell extends Cell
{
    public function display()
    {
        if ($_SESSION['Auth']) {
            $this->loadModel('Agents');
            $auth_user = $_SESSION['Auth']['User'];
            //get agent to get the credit amount
            $agent = $this->Agents->find()->where(['id'=>$_SESSION['Auth']['User']['agent_id']])->first();
            //get agentuser to check the announcements_read
            $current_agentuser = $this->Agents->AgentUsers->find()->where(['id'=>$_SESSION['Auth']['User']['id']])->first();
            $this->loadModel('Announcements');
            $now = Time::now();
            $announcements = $this->Announcements->find()
                                                ->where(['publish_start_date <='=>$now])
                                                ->andWhere(['publish_end_date >='=>$now])
                                                ->order(['publish_start_date'=>'desc']);
            $current_agentuser->announcements_read = 1;
            foreach ($announcements as $key => $value) {
              if($value->publish_start_date>$current_agentuser->last_read_time) {
                $current_agentuser->announcements_read = 0;
                break;
              }
            }
            if ($agent) {
              $auth_user['credits'] = $agent->credits;
              $auth_user['announcements_read'] = $current_agentuser->announcements_read;
            //   $this->Auth->setUser($auth_user);
              $this->set('auth_user',$auth_user);
            }
    
            $this->set(compact('announcements'));
          }
        
    }
}