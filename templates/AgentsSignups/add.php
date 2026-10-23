<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AgentsSignup $agentsSignup
 */
?>
<div class="text-center signup-box">
    <?= $this->Flash->render() ?>

    <?= $this->Form->create($signupForm, ['class'=>'mx-3']) ?>
    <?php
    $myTemplates = [
        // Used for button elements in button().
        'button' => '<button{{attrs}} class="btn btn-default">{{text}}</button>',
        // Used for checkboxes in checkbox() and multiCheckbox().
        'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}" {{attrs}} class="form-check-input">',
        // Input group wrapper for checkboxes created via control().
        'checkboxFormGroup' => '<div class="col-12"><div class="form-check">{{label}}</div></div>',
        // Wrapper container for checkboxes.
        'checkboxWrapper' => '<div class="form-group">{{label}}</div>',
        // Widget ordering for date/time/datetime pickers.
        'dateWidget' => '{{year}}{{month}}{{day}}{{hour}}{{minute}}{{second}}{{meridian}}',
        // Error message wrapper elements.
        'error' => '<div class="text-danger">{{content}}</div>',
        // Container for error items.
        'errorList' => '<ul>{{content}}</ul>',
        // Error item wrapper.
        'errorItem' => '<li>{{text}}</li>',
        // File input used by file().
        'file' => '<input type="file" name="{{name}}" {{attrs}} class="form-control"/>',
        // Fieldset element used by allControls().
        'fieldset' => '<fieldset{{attrs}}>{{content}}</fieldset>',
        // Open tag used by create().
        'formStart' => '<form{{attrs}}>',
        // Close tag used by end().
        'formEnd' => '</form>',
        // General grouping container for control(). Defines input/label ordering.
        'formGroup' => '{{label}}{{input}}',
        // Wrapper content used to hide other content.
        'hiddenBlock' => '<div style="display:none;">{{content}}</div>',
        // Generic input element.
        'input' => '<input type="{{type}}" name="{{name}}" {{attrs}} class="form-control" placeholder="{{name}}"/>',
        // Submit input element.
        'inputSubmit' => '<input type="{{type}}"{{attrs}}/>',
        // Container element used by control().
        'inputContainer' => '<div class="form-group">{{content}}</div>',
        // Container element used by control() when a field has an error.
        'inputContainerError' => '<div class="form-group {{type}}{{required}} has-error">{{content}}{{error}}</div>',
        // Label element when inputs are not nested inside the label.
        'label' => '<div class="sr-only"><label {{attrs}}>{{text}}</label></div>',
        // Legends created by allControls()
        'legend' => '<legend>{{text}}</legend>',
        // Multi-Checkbox input set title element.
        'multicheckboxTitle' => '<label>{{text}}</label>',
        // Option element used in select pickers.
        'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
        // Option group element used in select pickers.
        'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
        // Select element,
        'select' => '<select name="{{name}}" {{attrs}} class="form-control">{{content}}</select>',
        // Radio input element,
        // Wrapping container for radio input/label,
        'radioFormGroup' => '{{label}}{{input}}',
        'radioWrapper' => '{{label}}',
        'radio' => '<input type="radio" name="{{name}}" value="{{value}}" {{attrs}} class="form-check-input r">',
        // Textarea input element,
        'textarea' => '<textarea name="{{name}}" {{attrs}} class="form-control">{{value}}</textarea>',
        'submitContainer' => '<div class="submit">{{content}}</div>',
    ];
    $this->Form->setTemplates($myTemplates)->setConfig('errorClass','form-control is-invalid');
    ?>
    <fieldset>
        <legend class="mb-4"><?= __('Sign up') ?></legend>
        <h5 class="text-left"><?= __('Company Details') ?></h5>
        <div class="row">
            <div class="col-md-6"><?php echo $this->Form->control('company_name', ['class'=>'form-control','placeholder'=>__('Company Name'),'label'=>['class'=>'sr-only']]); ?></div>
            <div class="col-md-6"><?php echo $this->Form->control('country', ['options'=>\Cake\Core\Configure::read('Options.countries'), 'class'=>'form-control','placeholder'=>__('Country'),'label'=>['class'=>'sr-only']]); ?></div>
        </div>
        <h5 class="text-left"><?= __('Personal Details') ?></h5>
        <div class="row">
            <div class="col-md-6 col-12"><?php echo $this->Form->control('first_name', ['class'=>'form-control','placeholder'=>__('First Name'),'label'=>['class'=>'sr-only']]); ?></div>
            <div class="col-md-6 col-12"><?php echo $this->Form->control('last_name', ['class'=>'form-control','placeholder'=>__('Last Name'),'label'=>['class'=>'sr-only']]); ?></div>
        </div>
        <div class="row">
            <div class="col-md-6 col-12"><?php echo $this->Form->control('email', ['class'=>'form-control','placeholder'=>__('Email'),'label'=>['class'=>'sr-only']]); ?></div>
            <div class="col-md-6 col-12"><?php echo $this->Form->control('contact_no', ['class'=>'form-control','placeholder'=>__('Contact No'),'label'=>['class'=>'sr-only']]); ?></div>
        </div>
        <div class="row">

        </div>
    </fieldset>
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <?= $this->Form->button(__('Submit'), ['class'=>'btn btn-primary btn-block btn-lg']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>
