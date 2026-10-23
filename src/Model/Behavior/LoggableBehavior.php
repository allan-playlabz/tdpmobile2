<?php
namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class LoggableBehavior extends Behavior
{
	private $Logs;
	public function initialize(array $config): void
	{
	    $this->Logs = TableRegistry::getTableLocator()->get('Logs');
	}

	public function afterSave(\Cake\Event\EventInterface $event, $entity, $options)
  {
      $log = $this->Logs->newEntity(['user_table'=>'agent_users']);
      if (!$entity->isNew()) {
      	$dirty_fields = $entity->getDirty();
      	if (!empty($dirty_fields)) {

      		if (!empty($_SESSION['Auth']) && !empty($_SESSION['Auth']['User'])) {
	        	$log->user_id = $_SESSION['Auth']['User']['id'];
	        }
      		$values_before = [];
      		$values_after = [];
      		foreach ($dirty_fields as $key => $dirty_field) {
      			if ($dirty_field != 'modified') {
      				$values_before[$dirty_field] = $entity->getOriginal($dirty_field);
      				$values_after[$dirty_field] = $entity->get($dirty_field);
      			}
      		}
      		if (!empty($values_before)) {
      			$log->values_before = json_encode($values_before);
      		}
      		if (!empty(($values_after))) {
      			$log->values_after = json_encode($values_after);
      			$log->type = 'edit';
      		}
      		$log->record_id = $entity->id;
	        $log->table_name = $entity->getSource();
	        $log->created = date('Y-m-d H:i:s');
          $headers = getallheaders();
          if (is_array($headers) && array_key_exists('CF-Connecting-IP', $headers)) {
            $clientIp = $headers['CF-Connecting-IP'];
          }
          // $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
          if(empty($clientIp)) {
            $clientIp = $_SERVER['REMOTE_ADDR'];
          }
          $log->ip_address = $clientIp;
	        if (!$this->Logs->save($log)) {
	        	Log::write('debug',$log);
	        }
      	}
      }
      else {
      	if (!empty($_SESSION['Auth']) && !empty($_SESSION['Auth']['User'])) {
	        	$log->user_id = $_SESSION['Auth']['User']['id'];
	        }
      	$log->type = 'add';
      	$log->values_after = json_encode($entity->toArray());
      	$log->record_id = $entity->id;
        $log->table_name = $entity->getSource();
        $log->created = date('Y-m-d H:i:s');
        $headers = getallheaders();
        if (is_array($headers) && array_key_exists('CF-Connecting-IP', $headers)) {
          $clientIp = $headers['CF-Connecting-IP'];
        }
        // $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
        if(empty($clientIp)) {
          $clientIp = $_SERVER['REMOTE_ADDR'];
        }
        $log->ip_address = $clientIp;
        if (!$this->Logs->save($log)) {
        	Log::write('debug',$log);
        }
      }
  }

    // public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    // {
    //     $log = $this->Logs->newEntity(['user_table'=>'admins']);
    //     if ($this->request->getSession()->check('Auth.User')) {
    //     	$log->user_id = $this->request->getSession()->read('Auth.User.id');
    //     }
    //     $log->values_after = json_encode($entity->toArray());
    //     if (!$entity->isNew()) {
    //     	$values_before = $entity->getOriginals();
    //     	$log->values_before = json_encode($values_before);

    //     }
    //     $log->record_id = $entity->id;
    //     $log->table_name = $entity->getRepository();
    //     $log->created = date('Y-m-d H:i:s');

    // }

  public function afterDelete(\Cake\Event\EventInterface $event, $entity, $options) { 
  	$log = $this->Logs->newEntity(['user_table'=>'user_agents','type'=>'delete']);
    if (!empty($_SESSION['Auth']) && !empty($_SESSION['Auth']['User'])) {
      $log->user_id = $_SESSION['Auth']['User']['id'];
    }
    $log->values_after = null;
    if (!$entity->isNew()) {
    	$values_before = $entity->toArray();
    	$log->values_before = json_encode($values_before);

    }
    $log->record_id = $entity->id;
    $log->table_name = $entity->getSource();
    $log->created = date('Y-m-d H:i:s');
    $headers = getallheaders();
    if (is_array($headers) && array_key_exists('CF-Connecting-IP', $headers)) {
      $clientIp = $headers['CF-Connecting-IP'];
    }
    // $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
    if(empty($clientIp)) {
      $clientIp = $_SERVER['REMOTE_ADDR'];
    }
    $log->ip_address = $clientIp;
    if (!$this->Logs->save($log)) {
    	Log::write('info',$log);
    }
  }

}