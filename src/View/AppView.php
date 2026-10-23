<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use Cake\View\View;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/3.0/en/views.html#the-app-view
 */
class AppView extends View
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading helpers.
     *
     * e.g. `$this->loadHelper('Html');`
     *
     * @return void
     */
    public function initialize(): void
    {
        $myTemplates = [
            // Container element used by control().
            'inputContainer' => '<div class="form-group">{{content}}</div>',
            'error' => '<div class="error-message text-danger mb-2">{{content}}</div>',
            // Wrapper container for checkboxes.
            'checkboxWrapper' => '<div class="form-check">{{label}}</div>',
            'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}" {{attrs}} class="form-check-input">',
            // Input group wrapper for checkboxes created via control().
            'checkboxFormGroup' => '<div class="col-12"><div class="form-check">{{label}}</div></div>',
      ];
      $this->Form->setTemplates($myTemplates);
      $this->Paginator->setTemplates(['nextActive' => '<li class="btn next"><a rel="next" href="{{url}}">{{text}}</a></li>',
            'nextDisabled' => '<li class="btn next disabled"><a href="" onclick="return false;">{{text}}</a></li>',
            'prevActive' => '<li class="btn prev"><a rel="prev" href="{{url}}">{{text}}</a></li>',
            'prevDisabled' => '<li class="btn prev disabled"><a href="" onclick="return false;">{{text}}</a></li>',
            'counterRange' => '{{start}} - {{end}} of {{count}}',
            'counterPages' => '{{page}} of {{pages}}',
            'first' => '<li class="btn first"><a href="{{url}}">{{text}}</a></li>',
            'last' => '<li class="btn last"><a href="{{url}}">{{text}}</a></li>',
            'number' => '<li class="btn"><a href="{{url}}">{{text}}</a></li>',
            'current' => '<li class="btn btn-primary active"><a href="" class="text-white">{{text}}</a></li>',
            'ellipsis' => '<li class="btn ellipsis">&hellip;</li>',
            'sort' => '<a href="{{url}}">{{text}}</a>',
            'sortAsc' => '<a class="btn asc" href="{{url}}">{{text}}</a>',
            'sortDesc' => '<a class="btn desc" href="{{url}}">{{text}}</a>',
            'sortAscLocked' => '<a class="btn asc locked" href="{{url}}">{{text}}</a>',
            'sortDescLocked' => '<a class="btn desc locked" href="{{url}}">{{text}}</a>']);
        $this->addHelper('QuestionGroup');
    }
}
