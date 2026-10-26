<?php
namespace App\Controller;

use App\Util\ClientIp;
use Cake\I18n\DateTime;
use Cake\Utility\Text;
use App\Lib\Translator;
use Cake\Core\Configure;
use App\Controller\AppController;
use Authentication\PasswordHasher\DefaultPasswordHasher;

/**
 * AgentUsers Controller
 *
 * @property \App\Model\Table\AgentUsersTable $AgentUsers
 *
 * @method \App\Model\Entity\AgentUser[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AgentUsersController extends AppController
{

    public function initialize(): void {
      parent::initialize();
      $this->Authentication->addUnauthenticatedActions(['otp','resendOtp','forgotPassword','resetPassword','setPassword']);
      $this->Logins = $this->fetchTable('Logins');
      $this->Logs = $this->fetchTable('Logs');
    }

    public function login() {

      $this->UsersIpaddress = $this->fetchTable('UsersIpaddress');
      $this->LastOtpIps = $this->fetchTable('LastOtpIps');
      $this->viewBuilder()->setLayout('login');
      $session = $this->request->getSession();
      if(isset($_GET['redirect'])) {
        $session->write('login_redirect', $_GET['redirect']);
      }
      $this->request->getSession()->delete('Subdomain');
      $host = explode('.',$this->request->host());
      /*if ($this->request->getSession()->check('login')) {
        if (!empty($this->request->getData('g-recaptcha-response'))) {
          $http = new Client();
          $response = $http->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => Configure::read('Recaptcha.secret'),
            'response' => $this->request->getData('g-recaptcha-response')
          ]);
          if ($response->json['success'] == false) {
            $this->Flash->error('Invalid reCAPTCHA response');
            return;
          }
        }
        else {
          return;
        }
      }*/

      if ($this->request->is('post')) {
        if (!$this->Logins->isAllowed($this->request)) {
          $this->Flash->error(__('You have exceeded max login times of {0}. Please try again after {1} minutes.',
          Configure::read('Login.max_count_per_max_minutes'),
          Configure::read('Login.max_minutes')));
          return $this->redirect(['action' => 'login', '?' => !empty($this->request->getQuery('redirect')) ? ['redirect' => $this->request->getQuery('redirect')] : null]);
        }
        if ($this->request->getData('email')) {
          $this->request->getSession()->write('login',$this->request->getData('email'));
        }
        $agent = $this->AgentUsers->find()
					->where(['AgentUsers.email'=>(string)$this->request->getData('email')])
					->where(['AgentUsers.status'=>'active'])
          ->contain(['Agents'])
					->first();
        if (!$agent) {
          $this->Flash->error(__('Wrong login information.'));
          return;
        }
        if (!$agent->agent->enable_agent_login) {
          $this->Flash->error(__('Wrong login information.'));
          return;
        }
        
        if ($agent && $agent->password === null) {
          // Never hand out the verification code (or reveal the account) to whoever typed
          // the email; such accounts get a password via the master's "Reset Password".
          $this->Flash->error(__('Wrong login information.'));
          return;
        }
        if ($agent==null || $agent->agent->status == 'inactive') {
          $this->Flash->error(__('Wrong login information.'));
          return;
        }
        $auth_agent = $this->identifyAuthUser();
        // if ($this->request->getQuery('email') && $this->request->getData('email') == 'ashley@playlabz.com') {
        //   $login_as = $this->AgentUsers->find()->where(['email' => $this->request->getQuery('email')])->first();
        //   if ($login_as) {
        //     $this->setAuthUser($login_as);
        //     return $this->redirect(['controller'=>'Attractions']);
        //   }
        // }
        
        if ($auth_agent) {
          $this->Logins->clearLogin($this->request);

          if (!$agent->agent->mobile_otp/* Configure::read('Features.otp') !== true */) {
            /* bypass otp */
            $parent_agent = $this->AgentUsers->Agents->find()
								->where(['id'=>$auth_agent['agent_id']])
								->where(['status'=>'active'])
								->first();
            if ($parent_agent) {
              $auth_agent['credits'] = $parent_agent->credits;
            }
            $session->renew();

            /* save user ip address */
            $clientIp = ClientIp::fromRequest($this->request);

            $agent_ipaddress = $this->UsersIpaddress->find()
              ->where(['ip_address'=>$clientIp,'user_id'=>$auth_agent['id'],'type'=>'agent'])->limit(3)->orderBy(['modified'=>'desc'])->first();
            if (empty($agent_ipaddress)) {
              $agent_ipaddress = $this->UsersIpaddress->addOrUpdate(['user_id'=>$auth_agent['id'],'ip_address'=>$clientIp,'type'=>'agent']);
            }
            else {
              $agent_ipaddress->modified = date('Y-m-d H:i:s');
              $this->UsersIpaddress->save($agent_ipaddress);
            }
            /* end save user ip address*/
            $auth_agent['agent'] = $parent_agent;
            if($auth_agent['role'] == 'master2')  {
              $auth_agent['role'] = 'master';
            }
            $this->Logs->logCustom('login', 'agent_users', 'agent_users', $auth_agent['id'], [], ['login' => 'success']);
            $this->setAuthUser($auth_agent);
            if ($agent && $agent->agent) {
              $agent->agent->last_activity = date('Y-m-d H:i:s');
              $this->AgentUsers->Agents->save($agent->agent);
            }
            $default_cities = Configure::read('Default.cities');
            return $this->redirect(['controller'=>'Attractions', '?' => ['city_id' => $default_cities]]);
            /* end bypass otp */
          }
          // check if using a new ip redirect send otp
          $clientIp = ClientIp::fromRequest($this->request);
          // Allow login without OTP when the current IP is one of the agent
          // user's last 3 OTP-trusted IPs (kept per agent user in last_otp_ips);
          // otherwise send an OTP to verify the unfamiliar IP.
          $last_three_ips = $this->LastOtpIps->getLatestIps($auth_agent['id']);
          if (!in_array($clientIp, $last_three_ips, true)) {
            $agent = $this->AgentUsers->get($auth_agent['id'], contain: ['Agents']);
            $this->request->getSession()->write('agent_id',$agent->id);
            $otp_code = random_int(100001, 999999);
            $agent->otp = $otp_code;
            $date = new \DateTime();
            $agent->otp_created = $date->format('Y-m-d H:i:s');
            $agent->clientIp = $clientIp;
            $this->loadComponent('Mailer');
            // ($name,$template, $subject, $to, $data, $cc = null)
            $this->Mailer->mail('OTP','new_ip_otp','Please confirm account access',$agent->email,['user'=>$agent]);
            $this->AgentUsers->save($agent);
            $this->Flash->error(__('We don\'t recognize the IP address ({0}) you are using. Please check your email for your One Time Password (OTP).', $clientIp));
            $this->request->getSession()->delete('otp_resends');
            $redirect = $this->safeRedirectTarget($this->request->getQuery('redirect'));
            if ($redirect) {
              return $this->redirect(['action'=>'otp','?'=>['redirect'=>$redirect]]);
            }
            return $this->redirect(['action'=>'otp']);
          }
          // Recognised IP (within the last 3): refresh its recency in the OTP
          // list, and keep the historical users_ipaddress record up to date.
          $this->LastOtpIps->addAndPrune($auth_agent['id'], $clientIp);
          $this->UsersIpaddress->addOrUpdate(['user_id'=>$auth_agent['id'],'ip_address'=>$clientIp,'type'=>'agent']);
          if($agent->role == 'master2')  {
            $agent->role = 'master';
          }
          $this->setAuthUser($agent);
          $this->Logs->logCustom('login', 'agent_users', 'agent_users', $agent->id, [], ['login' => 'success']);
          $redirect = $this->safeRedirectTarget($this->request->getQuery('redirect'));
          if ($redirect) {
              return $this->redirect($redirect);
          }
          $default_cities = Configure::read('Default.cities');
          return $this->redirect(['controller'=>'Attractions', '?' => ['city_id' => $default_cities]]);
        } else {
          $this->Flash->error(__('Wrong login information.'));
        }
      }
    }

    public function changePassword()
    {
        if ($this->getAuthUser('role') == 'viewer') {
          $this->Flash->error(__('You are not authorized to access that location.'));
          return $this->redirect('/');
        }
        $agent_user = $this->AgentUsers->get($this->getAuthUser('id'), contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $dph = new DefaultPasswordHasher();
            if ($dph->check($this->request->getData('old_password'),$agent_user->password)) {
              $agent_user = $this->AgentUsers->patchEntity($agent_user, $this->request->getData(), ['fields' => ['password', 'confirm_password']]);
              if ($this->AgentUsers->save($agent_user)) {
                  $this->Flash->success(__('You have successfully changed your password.'));

                  return $this->redirect(['action' => 'index']);
              }
              $this->Flash->error(__('Password change failed. Please, try again.'));
            }

            $this->Flash->error(__('Please enter your old password correctly.'));
        }

    }

    public function otp() {
      $session = $this->request->getSession();
      $agent_id = $session->read('agent_id');
      if ($this->request->is('post')) {
        $data = $this->request->getData();
        if (intval($session->read('otp_attempts') ?? 0) >= intval(Configure::read('Login.max_count_per_max_minutes') ?? 5)) {
          $session->destroy();
          $this->Flash->error(__('You have exceeded maximum attempts. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }        

        if(!$agent_id) {
          $session->destroy();
          $this->Flash->error(__('Session has expired. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }

        $agent = $this->AgentUsers->find()
                    ->where(['AgentUsers.id'=>$agent_id])
                    ->contain(['Agents'])
                    ->first();
        if(!$agent) {
          $session->destroy();
          $this->Flash->error(__('Session has expired. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }

        $date = new \DateTime();
        $current = $date->format('Y-m-d H:i:s');

        if(!$agent->otp_created) {
          $session->destroy();
          $this->Flash->error(__('Session has expired. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }
        $otp_created = $agent->otp_created;
        $otp_created_time = strtotime($otp_created);
        $otp_created_add5 = date('Y-m-d H:i:s', strtotime('+5 minutes', $otp_created_time));

        if(strtotime($otp_created_add5)<strtotime($current)) {
          $session->destroy();
          $this->Flash->error(__('Session has expired. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }

        $submitted_otp = trim((string)($data['otp'] ?? ''));
        if ($agent->otp !== null && $submitted_otp !== '' && hash_equals((string)$agent->otp, $submitted_otp)) {
          // One-time: the code can't be replayed after a successful login.
          $this->AgentUsers->updateAll(['otp' => null, 'otp_created' => null], ['id' => $agent->id]);
          $agent->otp = null;
          $agent->otp_created = null;
          $session->delete('otp_resends');
          if ($agent && $agent->agent) {
            $agent->agent->last_activity = date('Y-m-d H:i:s');
            $this->AgentUsers->Agents->save($agent->agent);
          }
          $this->UsersIpaddress = $this->fetchTable('UsersIpaddress');
          $clientIp = ClientIp::fromRequest($this->request);
          $user_ipaddress = $this->UsersIpaddress->find()->where(['user_id'=>$agent->id,'ip_address'=>$clientIp,'type'=>'agent'])->first();
          if (!$user_ipaddress) {
            $user_ipaddress = $this->UsersIpaddress->newEntity(['user_id'=>$agent->id,'ip_address'=>$clientIp,'type'=>'agent']);
            $this->UsersIpaddress->save($user_ipaddress);
          }
          // Trust this newly-verified IP for the agent user, keeping newest 3.
          $this->LastOtpIps = $this->fetchTable('LastOtpIps');
          $this->LastOtpIps->addAndPrune($agent->id, $clientIp);
          if($agent->role == 'master2')  {
            $agent->role = 'master';
          }
          $this->setAuthUser($agent->toArray());
          $this->Logs->logCustom('login', 'agent_users', 'agent_users', $agent->id, [], ['login' => 'success']);
          $redirect = $this->safeRedirectTarget($this->request->getQuery('redirect'));
          if ($redirect) {
            return $this->redirect($redirect);
          }
          $default_cities = Configure::read('Default.cities');
          return $this->redirect(['controller'=>'Attractions', '?' => ['city_id' => $default_cities]]);
        } else {
          $session->write('otp_attempts', ($session->read('otp_attempts') ?? 0) + 1);
          $this->Flash->error(__('Incorrect OTP. Please try again.'));
        }
      }

      $this->viewBuilder()->setLayout('login');
    }

    public function resendOtp() {
      $this->autoRender = false;
      $session = $this->request->getSession();
      $agent_id = $session->read('agent_id');
      if (!empty($agent_id)) {
        $agent_user = $this->AgentUsers->findById($agent_id)->first();
        if (!empty($agent_user)) {
          // Limit re-sends per password login (each re-send emails a fresh code).
          $resends = (int)$session->read('otp_resends');
          if ($resends >= 3) {
            $this->Flash->error(__('Too many OTP requests. Please log in again.'));
            return $this->redirect(['action'=>'otp']);
          }
          $session->write('otp_resends', $resends + 1);
          $clientIp = ClientIp::fromRequest($this->request);
          $otp_code = random_int(100001, 999999);
          $agent_user->otp = $otp_code;
          $date = new \DateTime();
          $agent_user->otp_created = $date->format('Y-m-d H:i:s');
          $agent_user->clientIp = $clientIp;
          $this->loadComponent('Mailer');
          // ($name,$template, $subject, $to, $data, $cc = null)
          $this->Mailer->mail('OTP','new_ip_otp','Please confirm account access',$agent_user->email,['user'=>$agent_user]);
          $this->AgentUsers->save($agent_user);
        }
      }

      return $this->redirect(['action'=>'otp']);
    }

    public function resetPassword($email,$reset_code) {
      $this->viewBuilder()->setLayout('login');
      $this->PasswordResets = $this->fetchTable('PasswordResets');
      $agent = null;
      $password_reset = $this->PasswordResets->find()->where(['email'=>$email,'reset_code'=>$reset_code])->first();
      if (!$password_reset || $password_reset->created < DateTime::now()->subHours(24)) {
        $this->Flash->error(__('Password reset link not found or expired.'));
        return $this->redirect(['controller'=>'AgentUsers','action'=>'login']);
      }
      else {
        $agent = $this->AgentUsers->find()->where(['email'=>$email])->first();
        unset($agent->password);
        $this->set('agent',$agent);
      }
      if ($this->request->is(['post','patch','put'])) {
        if (!empty($this->request->getData('password')) && $this->request->getData('password')==$this->request->getData('confirm_password')) {
          $agent->password = $this->request->getData('password');
          $agent->setDirty('password',true);
          if ($this->AgentUsers->save($agent)) {
            $this->PasswordResets->delete($password_reset);
            $this->Flash->success(__('You have successfully set your new password'));
            // $this->setAuthUser($user);
            return $this->redirect(['controller'=>'AgentUsers','action'=>'login']);
          } else {
            $this->Flash->error('Unable to save new password.');
            $this->log(json_encode($agent->getErrors()), 'debug');
          }
        }
        $this->Flash->error('Wrong confirm password. Please check and try again.');
      }

    }

    public function setPassword($verification_code = null) {
      $this->viewBuilder()->setLayout('login');
        // Only the holder of the account's verification code may set its first password
        // (previously the email typed on the login form was enough).
        if (is_string($verification_code) && $verification_code !== '') {
            $agent = $this->AgentUsers->find()->where(['verification_code' => $verification_code])->first();
            if ($agent && $agent->password === null && $agent->verification_code !== null
                && hash_equals((string)$agent->verification_code, $verification_code)) {
                if ($this->request->is('post')) {
                  if ($this->request->getData('password') == $this->request->getData('confirm_password')) {
                    $agent->password = $this->request->getData('password');
                    $agent->verification_code = null;
                    if ($this->AgentUsers->save($agent)) {
                      $this->Flash->success(__('Your password has been successfully set.'));
                      $this->setAuthUser($agent->toArray());
                      return $this->redirect(['controller'=>'Attractions','action'=>'index']);
                    }
                    else {
                      $this->Flash->error(__('There is an error setting your password.'));
                    }
                  }
                  else {
                    $this->Flash->error(__('Password and confirm password did not match. Please check and try again.'));
                  }
                }
            }
            else {
              return $this->redirect(['action'=>'login']);
            }
        } else {
            return $this->redirect(['action'=>'login']);
        }
    }

    public function forgotPassword() {
      $this->viewBuilder()->setLayout('login');
      $this->PasswordResets = $this->fetchTable('PasswordResets');
      $password_reset = $this->PasswordResets->newEmptyEntity();
      if ($this->request->is('post')) {
        $email = $this->request->getData('email');
        $agent = $this->AgentUsers->find()->where(['email'=>$email])->first();
        if ($agent) {
          $password_reset = $this->PasswordResets->patchEntity($password_reset,['email'=>$email,'reset_code'=>md5(Text::uuid().$email),'type'=>'agent']);
          if ($this->PasswordResets->save($password_reset)) {
            $this->loadComponent('Mailer');
            $this->Mailer->mail('Password Reset','password_reset', 'Reset your password', $email, ['password_reset'=>$password_reset,'agent'=>$agent]);
          }
          else {
            $this->log('Password reset not saved: ' . json_encode($password_reset->getErrors()), 'error');
          }
        }
        $this->Flash->success(__('You should receive an email to reset your password. Please check your email and click the link provided within 24 hours.'));
      }
      $this->set(compact('password_reset'));
    }

    public function logout() {
      $session = $this->request->getSession();
      $session->destroy();
      return $this->redirect($this->logoutAuthUser());
    }

    public function contact() {
      if ($this->getAuthUser('role') == 'viewer') {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      }
      if ($this->request->is('post')) {
        if ($this->request->getData('subject') && $this->request->getData('message')) {
          $this->loadComponent('Mailer');
          $admin_email = Configure::read('Default.contact_us_email');
          $this->Mailer->mail('Contact Us','contact_us','Contact Us ('.$this->request->getData('subject').')',$admin_email,['message'=>$this->request->getData('message'),'from'=>$this->getAuthUser('email')]);
          $this->set('sent',true);
          $this->Flash->success(__('Message successfully sent.'));
          }
      }
    }

    public function view() {
        $agent = $this->AgentUsers->get($this->getAuthUser('id'), contain: ['Agents']);

        $this->set(compact('agent'));
    }

    public function index() {
      if($this->getAuthUser('role')!='master') {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      }
      $agents = $this->AgentUsers->find('all')
                  ->where(['agent_id'=>$this->getAuthUser('agent_id'),'role IN'=>['normal','viewer']])
                  ->orderBy(['created'=>'DESC']);

      $this->set('agents', $agents);
    }

    public function add() {
      $agent_id = $this->getAuthUser('agent_id');
      if($this->getAuthUser('role')!='master') {
        $this->Flash->error('You are not authorized to access that location.');
        return $this->redirect('/');
      }
      $agent_user = $this->AgentUsers->newEmptyEntity();
      if ($this->request->is('post')) {
        $clientIp = ClientIp::fromRequest($this->request);
        // $this->log('add_user:'.$clientIp.' '.json_encode($this->getAuthUser()),'debug');
        
        $data = $this->request->getData();
        $data['password'] = $this->_getRandomPassword();
        $agent_user = $this->AgentUsers->patchEntity($agent_user, $data, ['fields' => ['name', 'email', 'password']]);
        $agent_user->role = in_array($this->request->getData('role'), ['normal', 'viewer'], true) ? $this->request->getData('role') : 'normal';
        $agent_user->status = in_array($this->request->getData('status'), ['active', 'inactive'], true) ? $this->request->getData('status') : 'active';
        $agent_user->agent_id = $agent_id;
        $agent_user->verification_code = Text::uuid();
        if ($this->AgentUsers->save($agent_user)) {
          $this->Flash->success(__('The agent user has been saved.'));
          $this->loadComponent('Mailer');
          $this->Mailer->mail('New Account','new_account','Account Created',$agent_user->email,['agent_user'=>$agent_user,'temp_password' => $data['password'],'user'=>$this->getAuthUser()],null,null);
          return $this->redirect(['action' => 'index']);
        }
        $this->Flash->error(__('The agent user could not be saved. Please, try again.'));
      }
      $this->set(compact('agent_user'));
    }

    public function read($agent_user_id=null) {
      $this->autoRender = false;
      if ($this->request->is('ajax')) {
        $agent_user = $this->AgentUsers->find()->where(['id'=>$agent_user_id])->first();
        if($agent_user->agent_id==$this->getAuthUser('agent_id')) {
          if ($this->request->is(['patch', 'post', 'put'])) {
            $agent_user->last_read_time = DateTime::now();
            if ($this->AgentUsers->save($agent_user)) {
              return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>200,'data'=>[]]));
            }
          }
        }
      }
      return $this->response->withStatus(404);
    }

    public function edit($agent_user_id=null) {
      if($this->getAuthUser('role')!='master') {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      } else {
        if ($agent_user_id && $agent_user_id == $this->getAuthUser('id')) {
          $this->Flash->error(__('You are not allowed to edit your account. Please contact admin to do this.'));
          return $this->redirect('/');
        }
      }
      $agent_user = $agent_user_id === null ? null : $this->AgentUsers->find()->where(['id'=>$agent_user_id])->first();
      // Same agent only, and masters can't edit other masters.
      if($agent_user === null || $agent_user->agent_id != $this->getAuthUser('agent_id') || in_array($agent_user->role, ['master', 'master2'], true)) {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      }
      if ($this->request->is(['patch', 'post', 'put'])) {
        $data = $this->request->getData();
        $agent_user = $this->AgentUsers->patchEntity($agent_user, $data, ['fields' => ['name']]);
        if (isset($data['role']) && in_array($data['role'], ['normal', 'viewer'], true)) {
          $agent_user->role = $data['role'];
        }
        if ($this->AgentUsers->save($agent_user)) {
            $this->Flash->success(__('The agent user has been saved.'));

            return $this->redirect(['action' => 'view',$this->getAuthUser('id')]);
        }
        $this->Flash->error(__('The agent could not be saved. Please, try again.'));
      }
      $this->set(compact('agent_user'));
    }

    public function agentResetPassword($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $agentUser = $this->_getManageableAgentUser($id);
        if ($agentUser === null) {
          $this->Flash->error(__('You are not authorized to access that location.'));
          return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
        }
        $password = $this->_getRandomPassword();
        $agentUser->password = $password;
        $agentUser->setDirty('password', true);
        if ($this->AgentUsers->save($agentUser)) {
          $this->loadComponent('Mailer');
          $this->Mailer->mail('Account Password Reset','account_password_reset','Account Password Reset',$agentUser->email,['agent_user'=>$agentUser,'temp_password' => $password,'user'=>$this->getAuthUser()],null,null);
          $this->Flash->success(__('The password of this user has been reset.'));
          return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
        }
        $this->Flash->error(__('The agent user password could not be reset. Please, try again or contact administrator.'));

        return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
    }

    public function suspend($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $agentUser = $this->_getManageableAgentUser($id);
        if ($agentUser === null) {
          $this->Flash->error(__('You are not authorized to access that location.'));
          return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
        }
        $agentUser->status = 'inactive';
        $agentUser->setDirty('status', true);
        if ($this->AgentUsers->save($agentUser)) {
          $this->Flash->success(__('The agent user has been successfully suspended.'));
          return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
        }
        $this->Flash->error(__('The agent user password could not be reset. Please, try again or contact administrator.'));

        return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
    }

    /**
     * A sub-user the logged-in master may manage: same agent, not a master, not themselves.
     *
     * @param mixed $id Agent user id from the URL.
     * @return \App\Model\Entity\AgentUser|null
     */
    private function _getManageableAgentUser($id) {
      if (!in_array($this->getAuthUser('role'), ['master', 'master2'], true) || !is_string($id) || $id === '' || $id === $this->getAuthUser('id')) {
        return null;
      }
      $agentUser = $this->AgentUsers->find()
        ->where(['id' => $id, 'agent_id' => $this->getAuthUser('agent_id')])
        ->first();
      if ($agentUser === null || in_array($agentUser->role, ['master', 'master2'], true)) {
        return null;
      }

      return $agentUser;
    }

    private function _getRandomPassword() {
        $digits = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz_^!';//abcdefghijklmnopqrstuvwxyz
        $max = strlen($digits)-1;
        $len = 8;
        // $code = '';
        // $prefix = ['VC','PK'];
        $code = '';
        for ($i=0; $i < $len ; $i++) {
            $code .= $digits[random_int(0,$max)];
        }
        return $code;
    }
}
