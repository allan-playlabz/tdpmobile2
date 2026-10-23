<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class MailerComponent extends Component
{    
 
    public function mail($name,$template, $subject, $to, $data, $cc = null,$bcc = null, $attachments = null) {
        $Email = new Email();
        
        $Email->addHeaders(['Content-Type: text/html; charset=UTF-8']);
        $Email->viewBuilder()->setTemplate($template);
        $Email->setViewVars(array('data' => $data));
        $Email->setEmailFormat('html');
        $Email->setFrom(Configure::read('Default.from_email'));

        $Email->setTo($to);
        if (!empty($cc)) {
            $Email->setCc($cc);
        }
        if (!empty($bcc)) {
            $Email->addBcc($bcc);
        }
        if (Configure::read('Default.admin_email')) {
            $Email->addBcc(Configure::read('Default.admin_email'));
        }
        $Email->setSubject($subject);


        /* set attachments if available */
        
        if (!empty($attachments)) {
          $Email->setAttachments($attachments);
        }
        /* end set attachments */


        try {
          $Email->send();
        } catch (\Cake\Network\Exception\SocketException $e) {
          $Email->setTransport('default');
          $Email->setSubject('[TDP Email Error] '.$Email->getSubject());
          if (Configure::read('Default.admin_email')) {
            $Email->setTo(Configure::read('Default.admin_email'));
          }
          $Email->send();
        } catch (Exception $e) {
          $Email->setTransport('default');
          $Email->setSubject('[TDP Email Error] '.$Email->getSubject());
          if (Configure::read('Default.admin_email')) {
            $Email->setTo(Configure::read('Default.admin_email'));
          }
          $Email->send();
        }
        
    }
}
