<?php
namespace App\View\Helper;

use App\Form\QuestionGroupForm;
use App\View\AppView;
use Cake\Form\Form;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\Helper\FormHelper;
use Exception;

class QuestionGroupHelper extends Helper
{
    private $Form;
    private $name_suffix;

    static $group_title = '';
    static $attraction_ticket_name = '';

    /**
     * render method
     * creates a control based on QuesionGroupField
     * @param $field
     * @param string $name_suffix
     * @param string $attraction_name
     * @param string $ticket_name
     * @param string $date_to_show
     * @param int $index
     * @param array|null $templates
     * @return string|void
     * @throws Exception
     */
    public function render($question_group, $form_helper, $name_suffix, $attraction_name, $ticket_name, $date_to_show, $index, $templates = null, $last_row = false)
    {
        $this->Form = $form_helper;
        $this->name_suffix = $name_suffix;

        $question_group_form = new QuestionGroupForm($question_group);

        $attraction_ticket_name = $attraction_name . '<br>' . $ticket_name . (!empty($date_to_show) ?  '<br>' . $date_to_show : '' );
        $group_title = $question_group['title'];
        
        if ($group_title !== self::$group_title || $attraction_ticket_name !== self::$attraction_ticket_name) {
            echo '<div class="form_title">';
            echo '<h5>' . $attraction_ticket_name . '</h5>';
            echo '<p>' . $group_title . '</p>';
            echo '</div>';
        }
        
        // echo '<div class="row">';
        foreach ($question_group['fields'] as $field) {
            echo $this->createField($field, $templates, $index);
        }
        
        // echo '</div>';
        if (!$last_row) {
            echo '<hr class="hr-50">';
        }
        self::$group_title = $group_title;
        self::$attraction_ticket_name = $attraction_ticket_name;

    }

    /**
     * creates a field
     * @param $field
     * @return void
     */
    private function createField($field, $user_templates, $index) {
        $control_element = null;
        $name = 'question_group' . '.' . $field['name'];
        $type = 'text';
        $options = null;
        $templates = [];
        $control_options = [
            'label' => '[' . $index . '] ' . $field['question'],
            'required' => $field['is_required']
        ];

        if (empty($field['question'])) {
            throw new Exception('question is required in QuestionGroupField data');
        }

        if (!empty($this->name_suffix)) {
            $name .= '.' . $this->name_suffix;
        }

        if (!empty($field['data'])) {
            $data = !is_array($field['data']) ? explode('|', $field['data']) : $field['data'];
            $options = array_map(function ($op) {
                return trim($op);
            }, $data);
        }

        if (!empty($field['type'])) {
            $type = trim($field['type']);
            if (in_array($type, ['country', 'country_code', 'nationality'])) {
                $type = 'select';
            }
            switch ($field['type']) {
                //ENUM('text', 'textarea', 'checkbox', 'radio', 'select', 'range', 'date', 'time')
                case 'text':
                case 'textarea':
                case 'number':
                case 'country':
                case 'country_code':
                case 'nationality':
                case 'select':
                    $templates = [
                        'input' => '<input type="{{type}}" name="{{name}}" {{attrs}} class="form-control"/>',
                        'select' => '<select name="{{name}}" {{attrs}} class="form-control">{{content}}</select>',
                        'textarea' => '<textarea name="{{name}}" {{attrs}} class="form-control">{{value}}</textarea>',
                    ];
                    
                    if ($field['min'] !== null) {
                        if (in_array($field['type'], ['text','textarea'])) {
                            $control_options['minlength'] = $field['min'];
                        } elseif ($field['type'] === 'number') {
                            $control_options['min'] = $field['min'];
                        }
                    }
                    if ($field['max'] !== null) {
                        if (in_array($field['type'], ['text','textarea'])) {
                            $control_options['maxlength'] = $field['max'];
                        } elseif ($field['type'] === 'number') {
                            $control_options['max'] = $field['max'];
                        }
                    }
                    if (!empty($options)) {
                        $control_options +=[
                            'options' => array_combine($options, $options),
                        ];
                    }
                    break;
                case 'date':
                    $control_options +=[
                        'type' => 'text',
                        'class' => 'date-picker form-control'
                    ];
                    break;
                case 'time':
                    $templates = [
                        'inputContainer' => '<div class="form-row mb-3">{{content}}</div>',
                        'label' => '<label class="col-12" {{attrs}}>{{text}}</label>',
                        'select' => '<div class="col-auto"><select name="{{name}}" {{attrs}} class="form-control mr-1">{{content}}</select></div> ',
                    ];
                    break;
                case 'checkbox':
                    $templates = [
                        'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}" {{attrs}} class="form-check-input">',
                        'checkboxFormGroup' => '<div class="form-group">{{label}}</div>',
                        'checkboxWrapper' => '<div class="form-check">{{label}}</div>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>'
                    ];
                    if (!empty($options)) {
                        $control_options += [
                            'options' => array_combine($options, $options),
                            'multiple' => 'checkbox',
                            'nestedInput' => false,
                            'type'=>'select'
                        ];
                        $control_options['required'] = false;
                    }
                    break;
                case 'radio':
                    $templates = [
                        'radioFormGroup' => '<div class="form-group">{{label}}{{input}}</div>',
                        'radioWrapper' => '<div class="form-check">{{label}}</div>',
                        'radio' => '<input type="radio" name="{{name}}" value="{{value}}" {{attrs}} class="form-check-input">',
                        'inputContainer' => '<div class="form-group">{{content}}</div>'
                    ];
                    if (!empty($options)) {
                        $control_options += [
                            'options' => array_combine($options, $options),
                            'nestedInput' => false,
                            'type'=>'radio'
                        ];
                    }
                    break;
                case 'range':
                    
                    $control_options += [
                        'type' => 'range',
                        'class' => 'custom-range'
                    ];
                    if (!empty($field['data'][0])) {
                        $data = json_decode(html_entity_decode($field['data'][0]), true);
                        if (!empty($data)) {
                            $control_options['min'] = $data['from_select'];
                            $control_options['max'] = $data['to_select'];
                            $control_options['label'] .= '('.$data['from_label'] .' to '. $data['to_label'] . ')';
                        }
                        
                    }
                    $control_options['required'] = false;
                    break;
                default:
                    break;
            }
        }

        if (!empty($user_templates)) {
            $templates = array_merge($user_templates, $templates);
        }

        $control_options += [
            'templates' => $templates,
            'type' => $type,
        ];

        $control_element = $this->Form->control($name, $control_options);

        return $control_element;
    }
}
