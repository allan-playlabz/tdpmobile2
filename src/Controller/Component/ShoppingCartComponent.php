<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\I18n\Number;
use Cake\ORM\TableRegistry;

class ShoppingCartComponent extends Component
{    
	private $session;

	public function initialize(array $config): void {
		$controller = $this->_registry->getController();
		$this->session = $controller->getRequest()->getSession();
	}

    /*public function add($ticket_id, $qty) {
        if ($this->session->check('ShoppingCart.'.$ticket_id)) {
            $new_value = $this->session->read('ShoppingCart.'.$ticket_id) + $qty;
            $this->session->write('ShoppingCart.'.$ticket_id, $new_value);
            if ($new_value <= 0) {
                $this->remove($ticket_id);
            }
        }
        else {
            $this->session->write('ShoppingCart.'.$ticket_id, $qty);
        }
        

    }

    public function decrease($ticket_id, $qty) {
        if ($this->session->check('ShoppingCart.'.$ticket_id)) {
            if ($this->session->read('ShoppingCart.'.$ticket_id) > $qty) {
                $this->session->write('ShoppingCart.'.$ticket_id, $this->session->read('ShoppingCart.'.$ticket_id) - $qty);
            }
            if (intval($this->session->read('ShoppingCart.'.$ticket_id)) <= 0) {
                $this->remove($ticket_id);
            }           
        }
    }

    public function remove($ticket_id) {
        if ($this->session->check('ShoppingCart.'.$ticket_id) !== false) {
            $this->session->delete('ShoppingCart.'.$ticket_id);
        }
    }*/
	 
    public function add($ticket_id, $qty,$date) {

        $cart_products = $this->session->read('ShoppingCart');
        // if (!empty($cart_products)) {
        //     foreach ($cart_products as $id => $product) {
        //         $ticket = $this->Tickets->find()->where(['id'=>$id,'connection_id is not null'])->first();
                
        //     }
        // }
        if ($this->session->check('ShoppingCart.'.$ticket_id.'.'.$date)) {
        	$new_value = $this->session->read('ShoppingCart.'.$ticket_id.'.'.$date) + $qty;
        	$this->session->write('ShoppingCart.'.$ticket_id.'.'.$date, $new_value);
        	if ($new_value <= 0) {
        		$this->remove($ticket_id,$date);
        	}
        }
        else {
        	$this->session->write('ShoppingCart.'.$ticket_id.'.'.$date, $qty);
        }
        

    }

    public function decrease($ticket_id, $qty,$date) {
    	if ($this->session->check('ShoppingCart.'.$ticket_id.'.'.$date)) {
    		if ($this->session->read('ShoppingCart.'.$ticket_id.'.'.$date) > $qty) {
    			$this->session->write('ShoppingCart.'.$ticket_id.'.'.$date, $this->session->read('ShoppingCart.'.$ticket_id) - $qty);
    		}
    		if (intval($this->session->read('ShoppingCart.'.$ticket_id.'.'.$date)) <= 0) {
    			$this->remove($ticket_id,$date);
    		}        	
        }
    }

    public function remove($ticket_id , $date = null) {
        if (!$date) {
            if ($this->session->check('ShoppingCart.'.$ticket_id) !== false) {
                $this->session->delete('ShoppingCart.'.$ticket_id);
            }
        }
    	else {
            if ($this->session->check('ShoppingCart.'.$ticket_id.'.'.$date) !== false) {
                $this->session->delete('ShoppingCart.'.$ticket_id.'.'.$date);
                if (empty($this->session->read('ShoppingCart.'.$ticket_id))) {
                   $this->session->delete('ShoppingCart.'.$ticket_id);
                }
            }
        }
    }

    public function clear() {
    	$this->session->delete('ShoppingCart');
        $this->session->delete('ShoppingCartReturnDates');
    }

    public function getAll() {
    	return $this->session->check('ShoppingCart') ? $this->session->read('ShoppingCart') : [];
    }

    public function updateQuantity($data) {
        if (!empty($data)) {
            foreach ($data as $ticket_id => $ticket_data) {
                if (!empty($ticket_data)) {
                    foreach ($ticket_data as $date => $qty) {
                        if ($qty > 0) {
                            $this->session->write('ShoppingCart.'.$ticket_id.'.'.$date, intval($qty));
                        }
                        else {
                            $this->session->delete('ShoppingCart.'.$ticket_id.'.'.$date);
                            
                        }
                    }
                }
            }
        }
        if ($this->session->check('ShoppingCart')) {
            foreach ($this->session->read('ShoppingCart') as $ticket_id => $dates) {
                if (empty($this->session->read('ShoppingCart.'.$ticket_id))) {
                    $this->session->delete('ShoppingCart.'.$ticket_id);
                }
            }
        }
        
        
    }

    public function getCount() {
    	$count = 0;
    	$tickets = $this->session->read('ShoppingCart');
    	if (!is_array($tickets)) {
    		return 0;
    	}
    	// foreach ($tickets as $key => $qty) {
    	// 	$count += $qty;
    	// }
        foreach ($tickets as $key => $ticket_date) {
            foreach ($ticket_date as $date => $qty) {
                $count += $qty;
            }
        }
    	return $count;
    }

    public function gettickets() {
        $ticket_lists = $this->session->read('ShoppingCart');
        if (!is_array($ticket_lists) || empty(array_keys($ticket_lists))) {
            return [];
        }
        $this->Tickets = TableRegistry::getTableLocator()->get('Tickets');
        $tickets = $this->Tickets->find()->where(['id IN'=>array_keys($ticket_lists)])->toArray();
        foreach ($tickets as $idx => $ticket) {
            $ticket->qty = $ticket_lists[$ticket->id];
            $ticket->price =  Number::precision($ticket->price,2);
            $ticket->total_price = Number::currency(bcmul((string) $ticket->price, (string) $ticket->qty, 2));
        }
        return $tickets;
    }

    public function hasDuplicateSupplier($supplier_api_id, $attraction_id = null) {
        if (empty($supplier_api_id)) {
            return false;
        }
        $blockedSuppliers = Configure::read('BlockDuplicateSupplier.supplier_api_ids');
        if (empty($blockedSuppliers) || !in_array($supplier_api_id, $blockedSuppliers)) {
            return false;
        }
        $ticket_list = $this->getAll();
        if (empty($ticket_list)) {
            return false;
        }
        $Tickets = TableRegistry::getTableLocator()->get('Tickets');
        $cartTickets = $Tickets->find()->where(['id IN' => array_keys($ticket_list)])->toArray();
        foreach ($cartTickets as $_ticket) {
            if ($_ticket->supplier_api_id == $supplier_api_id && $_ticket->attraction_id != $attraction_id) {
                return true;
            }
        }
        return false;
    }

    public function addReturnDatetime($ticket_id, $depart_date, $return_date) {
        if ($this->session->check('ShoppingCart.'.$ticket_id.'.'.$depart_date)) {
        	if (!$this->session->check('ShoppingCartReturnDates.'.$ticket_id.'.'.$depart_date)) {
                $this->session->write('ShoppingCartReturnDates.'.$ticket_id.'.'.$depart_date, $return_date);
            }
        }
    }
}
