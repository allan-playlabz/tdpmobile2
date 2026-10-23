<?php
namespace App\Form;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Http\Client;
use Cake\I18n\FrozenDate;
use Cake\Log\Log;
use Cake\Utility\Text;
use Cake\Validation\Validator;


/**
 * Form for question group
 */
class QuestionGroupForm extends Form
{
    private $question_group;

    public function __construct($question_group)
    {
        parent::__construct();
        $this->question_group = $question_group;
    }

    protected function _buildSchema(Schema $schema): \Cake\Form\Schema
    {
        return $schema;
    }

    public function validationDefault(Validator $validator)
    {

        if (!empty($this->question_group['fields'])) {
            foreach ($this->question_group['fields'] as $field) {
//                'text', 'textarea', 'checkbox', 'radio', 'select', 'range', 'date', 'time')
                if ($field['is_required']) {
                    $validator->requirePresence($field['name'],true);
                }

                switch ($field['type']) {
                    case 'text':
                    case 'textarea':
                        if ($field['min'] !== null) {
                            $validator->minLength($field['name'], $field['min']);
                        }
                        if ($field['max'] !== null) {
                            $validator->maxLength($field['name'], $field['max']);
                        }
                        break;
                    case 'number':
                        if ($field['min'] !== null) {
                            $validator->add($field['name'], 'min', [
                                'rule' => function ($value, $context) use ($field) {
                                        if ($value >= $field['min']) {
                                            return true;
                                        }
                                        return false;
                                    },
                                'message' => 'Min for '  . $field['name'] . ' is '. $field['min']
                            ]);
                        }
                        if ($field['max'] !== null) {
                            $validator->add($field['name'], 'max', [
                                'rule' => function ($value, $context) use ($field) {
                                        if ($value >= $field['max']) {
                                            return true;
                                        }
                                        return false;
                                    },
                                'message' => 'Max for '  . $field['name'] . ' is '. $field['max']
                            ]);
                        }
                        break;
                    case 'checkbox':
                    case 'radio':
                    case 'select':
                    case 'country':
                    case 'nationality':
                        if (!empty($field['data'])) {
                            $validator->add($field['name'], 'valid_value', [
                                'rule' => function ($value, $context) use ($field) {
                                    if (is_array($value)) {
                                        foreach ($value as $v) {
                                            if (!in_array($v, $field['data'])) {
                                                return false;
                                            }
                                        }
                                    } else {
                                        if (!in_array($value, $field['data'])) {
                                            return false;
                                        }
                                    }

                                    return true;
                                },
                                'message' => $field['name'] . ' has invalid value.'
                            ]);
                        }
                        break;
                    case 'range':
                        break;
                    case 'date':
                        break;
                    case 'time':
                        break;
                    default:
                        break;
                }
            }
        }

        return $validator;
    }

    public function getOrderData($data, $ticket_id, $date) {
        $order_data = [];
        foreach ($data as $field_name => $ticket_data) {
            if (!empty($ticket_data[$ticket_id][$date])) {
                for ($idx=1; $idx <= count($ticket_data[$ticket_id][$date]); $idx++) {
                    $val = $ticket_data[$ticket_id][$date][$idx-1];
                    if (is_array($val)) {
                        if (!empty($val['year'])) {
                            $val = implode('-',$val);
                        }
                        if (!empty($val['hour'])) {
                            $val = implode(':',$val);
                        }
                    }
                    if ($this->_getFieldType($field_name) === 'date') {
                        $val = (new FrozenDate($val))->format('Y-m-d');
                    }
                    $order_data['question_group'][$idx-1][$field_name] = $val;

                }
            }
        }
        return $order_data;
    }

    private function _getFieldType($field_name) {
        $type = null;
        foreach ($this->question_group['fields'] as $field) {
            if ($field['name'] == $field_name) {
                $type = $field['type'];
            }
        }
        return $type;
    }
}
