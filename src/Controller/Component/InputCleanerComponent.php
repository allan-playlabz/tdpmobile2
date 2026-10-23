<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;


class InputCleanerComponent extends Component
{

  protected $ignore_strip_tags_fields = null;
  protected $ignore_strip_tags_actions = null;

  protected $_defaultConfig = [
        'ignore_fields' => null,
        'ignore_actions' => null
    ];

  private function _trimItem(&$item,$key){
    if (is_string($item)){
        $item = trim($item);
    }
  }

  private function _stripItem(&$item,$key){
    if (is_string($item)){
        $item = strip_tags($item);
    }
  }

  public function startup(EventInterface $event): ?Response {
    $request = $this->getController()->getRequest();
    $data = $request->getData();
    if (empty($data)) {
        return null;
    }
    if ($this->_config['ignore_actions'] && in_array($request->getParam('action'), $this->_config['ignore_actions'])) {
        return null; // no need to check others
    }
    array_walk_recursive($data, [$this,'_trimItem']);
    $ignore = $this->_config['ignore_fields'];
    if ($ignore) {
        $to_check = $data;
        foreach ($ignore as $key => $field_name) {
            unset($to_check[$field_name]);
        }
        array_walk_recursive($to_check, [$this,'_stripItem']);
        foreach ($to_check as $key => $value) {
            $data[$key] = $value;
        }
    } else {
        array_walk_recursive($data, [$this,'_stripItem']);
    }
    foreach ($data as $key => $value) {
      $request = $request->withData($key,$value);
    }
    $this->getController()->setRequest($request);
    return null;
  }
}