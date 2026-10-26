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

use ArrayAccess;
use ArrayObject;
use Authentication\Identifier\PasswordIdentifier;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;
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
        // Replaces AuthComponent. Unauthenticated requests are redirected to
        // /agent-users/login?redirect=... (see Application::getAuthenticationService()).
        $this->loadComponent('Authentication.Authentication');
        // AuthComponent always allowed its loginAction.
        if ($this->request->getParam('controller') === 'AgentUsers') {
          $this->Authentication->addUnauthenticatedActions(['login']);
        }

        if ($this->getAuthUser()) {
          $this->set('auth_user',$this->getAuthUser());
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

    /**
     * A `?redirect=` target that is safe to redirect to: a local path only.
     *
     * Rejects absolute URLs, protocol-relative `//host`, backslash tricks and
     * control characters, so login/top-up redirects can't send users off-site.
     *
     * @param mixed $url Candidate target (usually a query value).
     * @return string|null The path, or null if it is not a safe local path.
     */
    protected function safeRedirectTarget(mixed $url): ?string
    {
        if (!is_string($url) || $url === '' || !str_starts_with($url, '/')) {
            return null;
        }
        if (str_starts_with($url, '//') || str_contains($url, '\\') || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        return $url;
    }

    /**
     * paginate() returning a plain array, for actions that post-process the rows.
     *
     * Also sets the paginated result as a view var so PaginatorHelper finds the
     * paging info (3.x kept it in the request; an array has none).
     *
     * @param mixed $object Table or query to paginate.
     * @param array $settings Pagination settings.
     * @return array
     */
    protected function paginateToArray(mixed $object = null, array $settings = []): array
    {
        $paginated = $this->paginate($object, $settings);
        $this->set('paginated', $paginated);

        return $paginated->toArray();
    }

    /**
     * The logged-in agent user as stored in the session, like 3.x `$this->getAuthUser()`.
     *
     * Reads the session (not the request identity) so values written with
     * setAuthUser() earlier in the same request are seen, as with AuthComponent.
     *
     * @param string|null $key Dot path into the user data, e.g. `agent.mobile_otp`.
     * @return mixed
     */
    protected function getAuthUser(?string $key = null): mixed
    {
        $user = $this->request->getSession()->read('Auth.User');
        if (empty($user)) {
            return null;
        }
        if ($user instanceof EntityInterface) {
            $user = $user->toArray();
        } elseif ($user instanceof ArrayObject) {
            $user = $user->getArrayCopy();
        }

        return $key === null ? $user : Hash::get($user, $key);
    }

    /**
     * Logs the user in (or replaces the stored user), like 3.x `$this->setAuthUser()`.
     *
     * @param \ArrayAccess|array $user User data.
     * @return void
     */
    protected function setAuthUser(ArrayAccess|array $user): void
    {
        $session = $this->request->getSession();
        $session->renew();
        $session->write('Auth.User', $user);
    }

    /**
     * Checks email/password without logging in, like 3.x `$this->identifyAuthUser()`
     * with the Form authenticator on AgentUsers (email/password, default hasher).
     *
     * @return array|false The user data without the password, or false.
     */
    protected function identifyAuthUser(): array|false
    {
        $email = $this->request->getData('email');
        $password = $this->request->getData('password');
        if (!is_string($email) || $email === '' || !is_string($password) || $password === '') {
            return false;
        }
        $identifier = new PasswordIdentifier([
            'fields' => ['username' => 'email', 'password' => 'password'],
            'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'AgentUsers'],
        ]);
        $user = $identifier->identify(['username' => $email, 'password' => $password]);
        if (!$user instanceof EntityInterface) {
            return false;
        }
        $user->unset('password');

        return $user->toArray();
    }

    /**
     * Logs the user out, like 3.x `$this->logoutAuthUser()`.
     *
     * @return array The 3.x logoutRedirect.
     */
    protected function logoutAuthUser(): array
    {
        $this->Authentication->logout();

        return ['controller' => 'AgentUsers', 'action' => 'login'];
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
      if ($this->getAuthUser('agent_id')) {
        $this->Agents = $this->fetchTable('Agents');
        $auth_user = $this->getAuthUser();
        //get agent to get the credit amount
        $agent = $this->Agents->find()->where(['id'=>$this->getAuthUser('agent_id')])->first();
        //get agentuser to check the announcements_read
        $current_agentuser = $this->Agents->AgentUsers->find()->where(['id'=>$this->getAuthUser('id')])->first();
        $this->Announcements = $this->fetchTable('Announcements');
        $now = DateTime::now();
        $announcements = $this->Announcements->find()
                                            ->where(['publish_start_date <='=>$now])
                                            ->andWhere(['publish_end_date >='=>$now])
                                            ->orderBy(['publish_start_date'=>'desc']);
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
