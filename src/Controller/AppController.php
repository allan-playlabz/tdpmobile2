<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\I18n\I18n;
use Cake\I18n\DateTime;
use App\View\AjaxView;
use Cake\Event\EventInterface;
use Cake\I18n\Number;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Controller\Controller;
use Cake\Controller\Exception\SecurityException;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
#[\AllowDynamicProperties]
class AppController extends Controller
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        //$this->loadComponent('Security', ['blackHoleCallback' => 'forceSSL']);
        $this->loadComponent('Auth', [
          'authorize'=> 'Controller',//added this line
          'authenticate' => [
            'Form' => [
              'fields' => [
                'username' => 'email',
                'password' => 'password'
              ],
              'userModel' => 'AgentUsers'
            ]
          ],
          'loginRedirect' => '/dashboard',
          'loginAction' => [
            'controller' => 'AgentUsers',
            'action' => 'login'
          ],
          'logoutRedirect' => [
              'controller' => 'AgentUsers',
              'action' => 'login'
          ],
          'unauthorizedRedirect' => '/agent-users/login'
        ]);

        if ($this->Auth->user()) {
          $this->set('auth_user',$this->Auth->user());
        }
        /*
         * Enable the following component for recommended CakePHP security settings.
         * see https://book.cakephp.org/3.0/en/controllers/components/security.html
         */
        //$this->loadComponent('Security');
    }

    /**
     * Render XHR requests with AjaxView (ajax layout), as RequestHandlerComponent did in 3.x.
     *
     * @param \Cake\Event\EventInterface $event The beforeRender event.
     * @return void
     */
    public function beforeRender(EventInterface $event)
    {
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setClassName(AjaxView::class);
        }
    }

    public function isAuthorized() {
      return true;
    }

    public function beforeFilter(\Cake\Event\EventInterface $event) {
      //$this->Security->requireSecure();
      if ($this->request->getQuery('l')) {
        $l = $this->request->getQuery('l');
        $languages_available = Configure::read('Translation.languages');
        if (!empty($languages_available)) {
          if (array_key_exists($l, $languages_available)) {
            I18n::setLocale($l);
            $this->request->getSession()->write('language', $l);
          } else {
            I18n::setLocale('en_US');
            $this->request->getSession()->delete('language');
          }
        }
      }
      Number::setDefaultCurrency("SGD");
      if ($this->request->getSession()->check('language')) {
        I18n::setLocale($this->request->getSession()->read('language'));
        
      }
      if ($this->Auth->user('agent_id')) {
        $this->Agents = $this->fetchTable('Agents');
        $auth_user = $this->Auth->user();
        //get agent to get the credit amount
        $agent = $this->Agents->find()->where(['id'=>$this->Auth->user('agent_id')])->first();
        //get agentuser to check the announcements_read
        $current_agentuser = $this->Agents->AgentUsers->find()->where(['id'=>$this->Auth->user('id')])->first();
        $this->Announcements = $this->fetchTable('Announcements');
        $now = DateTime::now();
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
          $auth_user['allowed_api_cancel'] = $agent->allowed_api_cancel;
          $auth_user['announcements_read'] = $current_agentuser->announcements_read;
          $auth_user['agent']['mobile_otp'] = $agent->mobile_otp;
          $this->set('auth_user',$auth_user);
        }

        $this->set(compact('announcements'));
      }

    }

    public function forceSSL($error = '', ?SecurityException $exception = null) {
        if ($exception instanceof SecurityException && $exception->getType() === 'secure') {
            return $this->redirect('https://' . env('SERVER_NAME') . Router::url($this->request->getRequestTarget()));
        }

        throw $exception;
    }
}
