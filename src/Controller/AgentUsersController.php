<?php
namespace App\Controller;

use Cake\I18n\Time;
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
      $this->Auth->allow(['otp','resendOtp','forgotPassword','resetPassword']);
      $this->loadModel('Logins');
      $this->loadModel('Logs');
    }

    public function login() {

      $this->loadModel('UsersIpaddress');
      $this->loadModel('LastOtpIps');
      $this->viewBuilder()->setLayout('login');
      $session = $this->request->getSession();
      if(isset($_GET['redirect'])) {
        $session->write('login_redirect', $_GET['redirect']);
      }
      $redirect = $this->Auth->redirectUrl();
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
					->where(['AgentUsers.email'=>$this->request->getData('email')])
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
          return $this->redirect(['action'=>'setPassword',$agent->verification_code]);
        }
        if ($agent==null || $agent->agent->status == 'inactive') {
          $this->Flash->error(__('Wrong login information.'));
          return;
        }
        $auth_agent = $this->Auth->identify();
        // if ($this->request->getQuery('email') && $this->request->getData('email') == 'ashley@playlabz.com') {
        //   $login_as = $this->AgentUsers->find()->where(['email' => $this->request->getQuery('email')])->first();
        //   if ($login_as) {
        //     $this->Auth->setUser($login_as);
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
            $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');

            if(empty($clientIp)) {
              $clientIp = $this->request->clientIp();
            }

            $agent_ipaddress = $this->UsersIpaddress->find('all',['valueField'=>'ip_address'])
              ->where(['ip_address'=>$clientIp,'user_id'=>$auth_agent['id'],'type'=>'agent'])->limit(3)->order(['modified'=>'desc'])->first();
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
            $this->Auth->setUser($auth_agent);
            if ($agent && $agent->agent) {
              $agent->agent->last_activity = date('Y-m-d H:i:s');
              $this->AgentUsers->Agents->save($agent->agent);
            }
            $default_cities = Configure::read('Default.cities');
            return $this->redirect(['controller'=>'Attractions', '?' => ['city_id' => $default_cities]]);
            /* end bypass otp */
          }
          // check if using a new ip redirect send otp
          $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
          if(empty($clientIp)) {
            $clientIp = $this->request->clientIp();
          }
          // Allow login without OTP when the current IP is one of the agent
          // user's last 3 OTP-trusted IPs (kept per agent user in last_otp_ips);
          // otherwise send an OTP to verify the unfamiliar IP.
          $last_three_ips = $this->LastOtpIps->getLatestIps($auth_agent['id']);
          if (!in_array($clientIp, $last_three_ips, true)) {
            $agent = $this->AgentUsers->get($auth_agent['id'], contain: ['Agents']);
            $this->request->getSession()->write('agent_id',$agent->id);
            $otp_code = mt_rand(100001, 999999);
            $agent->otp = $otp_code;
            $date = new \DateTime();
            $agent->otp_created = $date->format('Y-m-d H:i:s');
            $agent->clientIp = $clientIp;
            $this->loadComponent('Mailer');
            // ($name,$template, $subject, $to, $data, $cc = null)
            $this->Mailer->mail('OTP','new_ip_otp','Please confirm account access',$agent->email,['user'=>$agent]);
            $this->AgentUsers->save($agent);
            $this->Flash->error(__('We don\'t recognize the IP address ({0}) you are using. Please check your email for your One Time Password (OTP).', $clientIp));
            if ($this->request->getQuery('redirect')) {
              return $this->redirect(['action'=>'otp','?'=>['redirect'=>$this->request->getQuery('redirect')]]);
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
          $this->Auth->setUser($agent);
          $this->Logs->logCustom('login', 'agent_users', 'agent_users', $agent->id, [], ['login' => 'success']);
          if ($this->request->getQuery('redirect')) {
              return $this->redirect($this->request->getQuery('redirect'));
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
        if ($this->Auth->user('role') == 'viewer') {
          $this->Flash->error(__('You are not authorized to access that location.'));
          return $this->redirect('/');
        }
        $agent_user = $this->AgentUsers->get($this->Auth->user('id'), contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $dph = new DefaultPasswordHasher();
            if ($dph->check($this->request->getData('old_password'),$agent_user->password)) {
              $agent_user = $this->AgentUsers->patchEntity($agent_user, $this->request->getData());
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

        if(trim($data['otp'])==$agent->otp) {
          if ($agent && $agent->agent) {
            $agent->agent->last_activity = date('Y-m-d H:i:s');
            $this->AgentUsers->Agents->save($agent->agent);
          }
          $this->loadModel('UsersIpaddress');
          $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
          if(empty($clientIp)) {
            $clientIp = $this->request->clientIp();
          }
          $user_ipaddress = $this->UsersIpaddress->find()->where(['user_id'=>$agent->id,'ip_address'=>$clientIp,'type'=>'agent'])->first();
          if (!$user_ipaddress) {
            $user_ipaddress = $this->UsersIpaddress->newEntity(['user_id'=>$agent->id,'ip_address'=>$clientIp,'type'=>'agent']);
            $this->UsersIpaddress->save($user_ipaddress);
          }
          // Trust this newly-verified IP for the agent user, keeping newest 3.
          $this->loadModel('LastOtpIps');
          $this->LastOtpIps->addAndPrune($agent->id, $clientIp);
          if($agent->role == 'master2')  {
            $agent->role = 'master';
          }
          $this->Auth->setUser($agent->toArray());
          $this->Logs->logCustom('login', 'agent_users', 'agent_users', $agent->id, [], ['login' => 'success']);
          if ($this->request->getQuery('redirect')) {
            return $this->redirect($this->request->getQuery('redirect'));
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
          $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
          if(empty($clientIp)) {
            $clientIp = $this->request->clientIp();
          }
          $otp_code = rand(100001, 999999);
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
      $this->loadModel('PasswordResets');
      $agent = null;
      $password_reset = $this->PasswordResets->find()->where(['email'=>$email,'reset_code'=>$reset_code])->first();
      if (!$password_reset || $password_reset->created < Time::now()->subHours(24)) {
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
            // $this->Auth->setUser($user);
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
        if ($this->request->getSession()->check('login')) {
            $agent = $this->AgentUsers->find()->where(['email'=>$this->request->getSession()->read('login')])->first();
            if ($agent && $agent->password === null && $agent->verification_code !== null) {
                if ($this->request->is('post')) {
                  if ($this->request->getData('password') == $this->request->getData('confirm_password')) {
                    $agent->password = $this->request->getData('password');
                    $agent->verification_code = null;
                    if ($this->AgentUsers->save($agent)) {
                      $this->Flash->success(__('Your password has been successfully set.'));
                      $this->Auth->setUser($agent->toArray());
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
        }
    }

    public function forgotPassword() {
      $this->viewBuilder()->setLayout('login');
      $this->loadModel('PasswordResets');
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
            debug($password_reset->getErrors());
          }
        }
        $this->Flash->success(__('You should receive an email to reset your password. Please check your email and click the link provided within 24 hours.'));
      }
      $this->set(compact('password_reset'));
    }

    public function logout() {
      $session = $this->request->getSession();
      $session->destroy();
      return $this->redirect($this->Auth->logout());
    }

    public function contact() {
      if ($this->Auth->user('role') == 'viewer') {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      }
      if ($this->request->is('post')) {
        if ($this->request->getData('subject') && $this->request->getData('message')) {
          $this->loadComponent('Mailer');
          $admin_email = Configure::read('Default.contact_us_email');
          $this->Mailer->mail('Contact Us','contact_us','Contact Us ('.$this->request->getData('subject').')',$admin_email,['message'=>$this->request->getData('message'),'from'=>$this->Auth->user('email')]);
          $this->set('sent',true);
          $this->Flash->success(__('Message successfully sent.'));
          }
      }
    }

    public function view() {
        $agent = $this->AgentUsers->get($this->Auth->user('id'), contain: ['Agents']);

        $this->set(compact('agent'));
    }

    public function index() {
      if($this->Auth->user('role')!='master') {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      }
      $agents = $this->AgentUsers->find('all')
                  ->where(['agent_id'=>$this->Auth->user('agent_id'),'role IN'=>['normal','viewer']])
                  ->orderBy(['created'=>'DESC']);

      $this->set('agents', $agents);
    }

    public function add() {
      $agent_id = $this->Auth->user('agent_id');
      if($this->Auth->user('role')!='master') {
        $this->Flash->error('You are not authorized to access that location.');
        return $this->redirect('/');
      }
      $agent_user = $this->AgentUsers->newEmptyEntity();
      if ($this->request->is('post')) {
        $clientIp = $this->request->getHeaderLine('CF-Connecting-IP');
        if(empty($clientIp)) {
          $clientIp = $this->request->clientIp();
        }
        // $this->log('add_user:'.$clientIp.' '.json_encode($this->Auth->user()),'debug');
        
        $data = $this->request->getData();
        $data['password'] = $this->_getRandomPassword();
        $agent_user = $this->AgentUsers->patchEntity($agent_user, $data);
        $agent_user->role = in_array($this->request->getData('role'), ['normal', 'viewer']) ? $this->request->getData('role') : 'normal';
        $agent_user->agent_id = $agent_id;
        $agent_user->verification_code = Text::uuid();
        if ($this->AgentUsers->save($agent_user)) {
          $this->Flash->success(__('The agent user has been saved.'));
          $this->loadComponent('Mailer');
          $this->Mailer->mail('New Account','new_account','Account Created',$agent_user->email,['agent_user'=>$agent_user,'temp_password' => $data['password'],'user'=>$this->Auth->user()],null,null);
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
        if($agent_user->agent_id==$this->Auth->user('agent_id')) {
          if ($this->request->is(['patch', 'post', 'put'])) {
            $agent_user->last_read_time = Time::now();
            if ($this->AgentUsers->save($agent_user)) {
              return $this->response->withStatus(200)->withStringBody(json_encode(['status'=>200,'data'=>[]]));
            }
          }
        }
      }
      return $this->response->withStatus(404);
    }

    public function edit($agent_user_id=null) {
      if($this->Auth->user('role')!='master') {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      } else {
        if ($agent_user_id && $agent_user_id == $this->Auth->user('id')) {
          $this->Flash->error(__('You are not allowed to edit your account. Please contact admin to do this.'));
          return $this->redirect('/');
        }
      }
      $agent_user = $this->AgentUsers->find()->where(['id'=>$agent_user_id])->first();
      if($agent_user->agent_id!=$this->Auth->user('agent_id')) {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect('/');
      }
      if ($this->request->is(['patch', 'post', 'put'])) {
        $data = $this->request->getData();
        if ($this->request->getData('name')) {
          $data['name'] = $this->request->getData('name');
        }
        if (isset($data['role']) && !in_array($data['role'], ['normal', 'viewer'])) {
          unset($data['role']);
        }
        $agent_user = $this->AgentUsers->patchEntity($agent_user, $data);
        if ($this->AgentUsers->save($agent_user)) {
            $this->Flash->success(__('The agent user has been saved.'));

            return $this->redirect(['action' => 'view',$this->Auth->user('id')]);
        }
        $this->Flash->error(__('The agent could not be saved. Please, try again.'));
      }
      $this->set(compact('agent_user'));
    }

    public function agentResetPassword($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $agentUser = $this->AgentUsers->get($id);
        $password = $this->_getRandomPassword();
        $agentUser->password = $password;
        $agentUser->setDirty('password', true);
        if ($this->AgentUsers->save($agentUser)) {
          $this->loadComponent('Mailer');
          $this->Mailer->mail('Account Password Reset','account_password_reset','Account Password Reset',$agentUser->email,['agent_user'=>$agentUser,'temp_password' => $password,'user'=>$this->Auth->user()],null,null);
          $this->Flash->success(__('The password of this user has been reset.'));
          return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
        }
        $this->Flash->error(__('The agent user password could not be reset. Please, try again or contact administrator.'));

        return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
    }

    public function suspend($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $agentUser = $this->AgentUsers->get($id);
        $agentUser->status = 'inactive';
        $agentUser->setDirty('status', true);
        if ($this->AgentUsers->save($agentUser)) {
          $this->Flash->success(__('The agent user has been successfully suspended.'));
          return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
        }
        $this->Flash->error(__('The agent user password could not be reset. Please, try again or contact administrator.'));

        return $this->redirect(['controller'=>'AgentUsers','action' => 'index']);
    }

    private function _getRandomPassword() {
        $digits = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz_^!';//abcdefghijklmnopqrstuvwxyz
        $max = strlen($digits)-1;
        $len = 8;
        // $code = '';
        // $prefix = ['VC','PK'];
        $code = '';
        for ($i=0; $i < $len ; $i++) {
            $code .= $digits[mt_rand(0,$max)];
        }
        return $code;
    }
}
