<?php
namespace App\Controller;

use App\Lib\TwoFactor;
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
      $this->Authentication->addUnauthenticatedActions(['otp','resendOtp','twoFactorSetup','twoFactorBackupCodes','forgotPassword','resetPassword','setPassword']);
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

          // 2FA when the agent has OTP on and the user's own switch is on.
          if (!TwoFactor::required($agent)) {
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
          $clientIp = ClientIp::fromRequest($this->request);
          $redirect = $this->safeRedirectTarget($this->request->getQuery('redirect'));
          $otp_url = $redirect ? ['action'=>'otp','?'=>['redirect'=>$redirect]] : ['action'=>'otp'];
          // Authenticator users enter an app code at every login (no trusted-IP skip).
          // Without an app yet, an emailed code comes first, then the set-up page:
          // linking a phone needs both the password and the mailbox.
          if (TwoFactor::usesAuthenticator($agent)) {
            $session->write('agent_id', $agent->id);
            $session->delete('otp_resends');
            $session->delete('TwoFactor');
            if (!TwoFactor::isSetUp($agent)) {
              $this->_sendEmailOtp($agent, $clientIp);
              $this->Flash->success(__('Please check your email for your One Time Password (OTP). Next, you will set up an authenticator app.'));
            }
            return $this->redirect($otp_url);
          }
          // Email OTP: allow login without OTP when the current IP is one of the agent
          // user's last 3 OTP-trusted IPs (kept per agent user in last_otp_ips);
          // otherwise send an OTP to verify the unfamiliar IP.
          $last_three_ips = $this->LastOtpIps->getLatestIps($auth_agent['id']);
          if (!in_array($clientIp, $last_three_ips, true)) {
            $agent = $this->AgentUsers->get($auth_agent['id'], contain: ['Agents']);
            $session->write('agent_id',$agent->id);
            $session->delete('TwoFactor');
            $this->_sendEmailOtp($agent, $clientIp);
            $this->Flash->error(__('We don\'t recognize the IP address ({0}) you are using. Please check your email for your One Time Password (OTP).', $clientIp));
            $session->delete('otp_resends');
            return $this->redirect($otp_url);
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
      $agent = $agent_id ? $this->AgentUsers->find()
                  ->where(['AgentUsers.id'=>$agent_id])
                  ->contain(['Agents'])
                  ->first() : null;
      // The method comes from the user's record, never from the session.
      $use_app = $agent && TwoFactor::usesAuthenticator($agent) && TwoFactor::isSetUp($agent);
      if ($this->request->is('post')) {
        $data = $this->request->getData();
        if ($this->_otpAttemptsExceeded()) {
          $session->destroy();
          $this->Flash->error(__('You have exceeded maximum attempts. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }

        if(!$agent) {
          $session->destroy();
          $this->Flash->error(__('Session has expired. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }

        $redirect = $this->safeRedirectTarget($this->request->getQuery('redirect'));
        $submitted_otp = trim((string)($data['otp'] ?? ''));

        if ($use_app) {
          $left = null;
          if (TwoFactor::verify($agent, $submitted_otp) || ($left = TwoFactor::useBackupCode($agent, $submitted_otp)) !== null) {
            $response = $this->_completeLogin($agent, $redirect);
            if ($left !== null) {
              $this->Flash->warning(__n(
                'You signed in with a backup code. {0} backup code is left.',
                'You signed in with a backup code. {0} backup codes are left.',
                $left,
                $left
              ) . ($left <= 2 ? ' ' . __('Create new ones under Two-factor sign-in.') : ''));
            }
            return $response;
          }
          $this->_countOtpAttempt();
          $this->Flash->error(__('Incorrect code. Please try again.'));
          return $this->redirect($redirect ? ['action'=>'otp','?'=>['redirect'=>$redirect]] : ['action'=>'otp']);
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

        if ($agent->otp !== null && $submitted_otp !== '' && hash_equals((string)$agent->otp, $submitted_otp)) {
          // One-time: the code can't be replayed after a successful login.
          $this->AgentUsers->updateAll(['otp' => null, 'otp_created' => null], ['id' => $agent->id]);
          $session->delete('otp_resends');
          if (TwoFactor::usesAuthenticator($agent) && !TwoFactor::isSetUp($agent)) {
            // Mailbox proven: now link the authenticator app.
            $session->write('TwoFactor.setup_pending', time());
            $session->write('TwoFactor.redirect', $redirect);
            return $this->redirect(['action'=>'twoFactorSetup']);
          }
          return $this->_completeLogin($agent, $redirect);
        } else {
          $this->_countOtpAttempt();
          $this->Flash->error(__('Incorrect OTP. Please try again.'));
        }
      }

      $this->set('method', $use_app ? TwoFactor::METHOD_AUTHENTICATOR : TwoFactor::METHOD_EMAIL);
      $this->viewBuilder()->setLayout('login');
    }

    public function resendOtp() {
      $this->autoRender = false;
      $session = $this->request->getSession();
      $agent_id = $session->read('agent_id');
      if (!empty($agent_id)) {
        $agent_user = $this->AgentUsers->find()->where(['AgentUsers.id' => $agent_id])->contain(['Agents'])->first();
        // Authenticator users have no emailed code.
        if (!empty($agent_user) && !(TwoFactor::usesAuthenticator($agent_user) && TwoFactor::isSetUp($agent_user))) {
          // Limit re-sends per password login (each re-send emails a fresh code).
          $resends = (int)$session->read('otp_resends');
          if ($resends >= 3) {
            $this->Flash->error(__('Too many OTP requests. Please log in again.'));
            return $this->redirect(['action'=>'otp']);
          }
          $session->write('otp_resends', $resends + 1);
          $this->_sendEmailOtp($agent_user, ClientIp::fromRequest($this->request));
        }
      }

      return $this->redirect(['action'=>'otp']);
    }

    /**
     * Authenticator set-up: QR code and key for a new secret, saved once a code
     * from the app matches. Reached during login (after the emailed code, when the
     * user's method is the authenticator and none is linked yet) or from the
     * Two-factor sign-in page ("set up a new phone").
     *
     * @return \Cake\Http\Response|null
     */
    public function twoFactorSetup() {
      $session = $this->request->getSession();
      $logged_in = (bool)$this->getAuthUser('id');
      $agent = $this->_twoFactorFlowUser($logged_in, (bool)$session->read($logged_in ? 'TwoFactor.change' : 'TwoFactor.setup_pending'));
      if ($agent === null) {
        return $logged_in ? $this->redirect(['action'=>'twoFactor']) : $this->_restartLogin();
      }
      if (!$logged_in) {
        if (TwoFactor::isSetUp($agent)) {
          return $this->_restartLogin();
        }
        $this->viewBuilder()->setLayout('login');
      }
      $secret = (string)$session->read('TwoFactor.setup_secret');
      if ($secret === '') {
        $secret = TwoFactor::newSecret();
        $session->write('TwoFactor.setup_secret', $secret);
      }

      if ($this->request->is('post')) {
        if (!$logged_in && $this->_otpAttemptsExceeded()) {
          $session->destroy();
          $this->Flash->error(__('You have exceeded maximum attempts. Please log in again.'));
          return $this->redirect(['action'=>'login']);
        }
        $step = TwoFactor::matchStep($secret, (string)$this->request->getData('code'));
        if ($step > 0) {
          $codes = TwoFactor::enable($agent, $secret, $step);
          $session->delete('TwoFactor.setup_secret');
          $session->write('TwoFactor.backup_codes', $codes);
          $this->Logs->logCustom('two_factor_setup', 'agent_users', 'agent_users', $agent->id, [], ['two_factor' => 'authenticator set up']);
          return $this->redirect(['action'=>'twoFactorBackupCodes']);
        }
        if (!$logged_in) {
          $this->_countOtpAttempt();
        }
        $this->Flash->error(__('That code didn\'t match. Check the time on your phone and enter the newest code.'));
      }

      $this->set([
        'qr' => TwoFactor::qrSvg($agent->email, $secret),
        'secret' => TwoFactor::formatSecret($secret),
        'email' => $agent->email,
        'loggedIn' => $logged_in,
      ]);
    }

    /**
     * Shows new backup codes once; confirming finishes the login (or returns to
     * the Two-factor sign-in page).
     *
     * @return \Cake\Http\Response|null
     */
    public function twoFactorBackupCodes() {
      $session = $this->request->getSession();
      $logged_in = (bool)$this->getAuthUser('id');
      $codes = (array)$session->read('TwoFactor.backup_codes');
      $agent = $this->_twoFactorFlowUser($logged_in, $codes !== [] && ($logged_in || $session->read('TwoFactor.setup_pending')));
      if ($agent === null) {
        return $logged_in ? $this->redirect(['action'=>'twoFactor']) : $this->_restartLogin();
      }
      if (!$logged_in) {
        $this->viewBuilder()->setLayout('login');
      }

      if ($this->request->is('post')) {
        $redirect = $session->read('TwoFactor.redirect');
        $session->delete('TwoFactor');
        if (!$logged_in) {
          $this->Flash->success(__('Two-factor sign-in is set up. Next time, enter the code from your authenticator app.'));
          return $this->_completeLogin($agent, $this->safeRedirectTarget($redirect));
        }
        $this->Flash->success(__('Your backup codes are saved.'));
        return $this->redirect(['action'=>'twoFactor']);
      }

      $this->set(['codes' => $codes, 'loggedIn' => $logged_in]);
    }

    /**
     * The signed-in user's Two-factor sign-in page: method and status, backup codes
     * left, "new backup codes" and "set up a new phone" (both need a current app
     * code once an authenticator is linked). The switch and the method themselves
     * are managed in the back office.
     *
     * @return \Cake\Http\Response|null
     */
    public function twoFactor() {
      $agent = $this->AgentUsers->get($this->getAuthUser('id'), contain: ['Agents']);
      $uses_app = TwoFactor::usesAuthenticator($agent);
      $is_set_up = TwoFactor::isSetUp($agent);

      if ($this->request->is('post') && $uses_app) {
        $session = $this->request->getSession();
        $do = (string)$this->request->getData('do');
        if ($is_set_up && !TwoFactor::verify($agent, (string)$this->request->getData('code'))) {
          $this->Flash->error(__('Incorrect code. Enter the current code from your authenticator app.'));
          return $this->redirect(['action'=>'twoFactor']);
        }
        if ($do === 'regenerate' && $is_set_up) {
          $session->write('TwoFactor.backup_codes', TwoFactor::regenerateBackupCodes($agent));
          $this->Logs->logCustom('two_factor_backup_codes', 'agent_users', 'agent_users', $agent->id, [], ['two_factor' => 'backup codes regenerated']);
          return $this->redirect(['action'=>'twoFactorBackupCodes']);
        }
        if ($do === 'setup') {
          $session->write('TwoFactor.change', true);
          $session->delete('TwoFactor.setup_secret');
          return $this->redirect(['action'=>'twoFactorSetup']);
        }
        return $this->redirect(['action'=>'twoFactor']);
      }

      $this->set([
        'required' => TwoFactor::required($agent),
        'usesApp' => $uses_app,
        'isSetUp' => $is_set_up,
        'setUpOn' => $agent->totp_enabled,
        'codesLeft' => TwoFactor::backupCodesLeft($agent),
      ]);
    }

    /**
     * A master removes a sub-user's authenticator and backup codes (lost phone and
     * no backup codes left). The user sets it up again at the next login, after an
     * emailed code. Masters themselves are reset with `bin/cake two_factor reset`.
     *
     * @param string|null $id Agent user id
     * @return \Cake\Http\Response|null
     */
    public function resetTwoFactor($id = null) {
      $this->request->allowMethod(['post']);
      $agentUser = $this->_getManageableAgentUser($id);
      if ($agentUser === null) {
        $this->Flash->error(__('You are not authorized to access that location.'));
        return $this->redirect(['action' => 'index']);
      }
      TwoFactor::reset($agentUser->id);
      $this->Logs->logCustom('two_factor_reset', 'agent_users', 'agent_users', $agentUser->id, [], ['two_factor' => 'reset', 'by' => $this->getAuthUser('id')]);
      $this->Flash->success(__('The authenticator of {0} was reset. They will set it up again at their next login.', $agentUser->email));

      return $this->redirect(['action' => 'index']);
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
     * Emails a fresh 6-digit OTP (valid 5 minutes) to the agent user.
     *
     * @param \App\Model\Entity\AgentUser $agent_user Agent user
     * @param string $clientIp IP shown in the email
     * @return void
     */
    private function _sendEmailOtp($agent_user, string $clientIp): void {
      $agent_user->otp = random_int(100001, 999999);
      $date = new \DateTime();
      $agent_user->otp_created = $date->format('Y-m-d H:i:s');
      $agent_user->clientIp = $clientIp;
      $this->loadComponent('Mailer');
      // ($name,$template, $subject, $to, $data, $cc = null)
      $this->Mailer->mail('OTP','new_ip_otp','Please confirm account access',$agent_user->email,['user'=>$agent_user]);
      $this->AgentUsers->save($agent_user);
    }

    /**
     * Logs the agent user in after the second factor: clears the OTP and the pending
     * login state, trusts the IP, stores the user and redirects.
     *
     * @param \App\Model\Entity\AgentUser $agent Agent user loaded with Agents
     * @param string|null $redirect Safe local redirect target
     * @return \Cake\Http\Response|null
     */
    private function _completeLogin($agent, ?string $redirect) {
      $session = $this->request->getSession();
      $this->AgentUsers->updateAll(['otp' => null, 'otp_created' => null], ['id' => $agent->id]);
      $agent->otp = null;
      $agent->otp_created = null;
      foreach (['agent_id', 'otp_resends', 'otp_attempts', 'TwoFactor'] as $key) {
        $session->delete($key);
      }
      if ($agent->agent) {
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
      if ($redirect) {
        return $this->redirect($redirect);
      }
      $default_cities = Configure::read('Default.cities');
      return $this->redirect(['controller'=>'Attractions', '?' => ['city_id' => $default_cities]]);
    }

    private function _otpAttemptsExceeded(): bool {
      return intval($this->request->getSession()->read('otp_attempts') ?? 0) >= intval(Configure::read('Login.max_count_per_max_minutes') ?? 5);
    }

    private function _countOtpAttempt(): void {
      $session = $this->request->getSession();
      $session->write('otp_attempts', ($session->read('otp_attempts') ?? 0) + 1);
    }

    /**
     * The user of a two-factor set-up step: the logged-in user, or (during login)
     * the pending user from the session within 15 minutes of the emailed code.
     *
     * @param bool $logged_in Whether a user is logged in
     * @param bool $allowed Whether the step's session flag is present
     * @return \App\Model\Entity\AgentUser|null
     */
    private function _twoFactorFlowUser(bool $logged_in, bool $allowed) {
      $session = $this->request->getSession();
      if (!$allowed) {
        return null;
      }
      if ($logged_in) {
        $id = $this->getAuthUser('id');
      } else {
        $id = $session->read('agent_id');
        if (time() - (int)$session->read('TwoFactor.setup_pending') > 15 * 60) {
          return null;
        }
      }
      if (!is_string($id) || $id === '') {
        return null;
      }
      $agent = $this->AgentUsers->find()->where(['AgentUsers.id' => $id, 'AgentUsers.status' => 'active'])->contain(['Agents'])->first();
      if ($agent === null || (!$logged_in && !TwoFactor::usesAuthenticator($agent))) {
        return null;
      }

      return $agent;
    }

    private function _restartLogin() {
      $this->request->getSession()->destroy();
      $this->Flash->error(__('Session has expired. Please log in again.'));
      return $this->redirect(['action'=>'login']);
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
