<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Form\SignupForm;
use Cake\Core\Configure;

/**
 * AgentsSignups Controller
 *
 * @property \App\Model\Table\AgentsSignupsTable $AgentsSignups
 *
 * @method \App\Model\Entity\AgentsSignup[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AgentsSignupsController extends AppController
{

    public function initialize(): void
    {
        parent::initialize();
        $this->Auth->allow(['add','thankyou']);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $agentsSignups = $this->paginate($this->AgentsSignups->find()->contain(['MasterAgents']));

        $this->set(compact('agentsSignups'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $signupForm = new SignupForm();
        $this->viewBuilder()->setLayout('signup');
        if ($this->request->is('post')) {
            if ($signupForm->validate($this->request->getData())) {
                if ($signupForm->execute($this->request->getData())) {
                    return $this->redirect(['action'=>'thankyou']);
                }
            }
            $this->Flash->error(__('The information could not be saved. Please, try again.'));
        }
        $this->set(compact('signupForm'));
    }

    /**
     * @return \Cake\Http\Response|null Renders thank you view.
    */
    public function thankyou() {
        $this->viewBuilder()->setLayout('login');
    }


}
