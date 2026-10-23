<?php
namespace App\Controller\Component;

use App\Lib\PricingType;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\I18n\Number;
use Cake\ORM\TableRegistry;
use Cake\I18n\DateTime;

class TicketsHelperComponent extends Component
{

  private $Tickets;
  private $Allotments;
  
	public function initialize(array $config): void {
		$this->Tickets = TableRegistry::getTableLocator()->get('Tickets');
    $this->Allotments = TableRegistry::getTableLocator()->get('Allotments');
	}

  public function getTicketAvailableQty($ticket, $date = null) {
    $max_quantity = Configure::read('Default.max_order_quantity'); //unlimited
    if ($ticket->type == 'complex') {
      $now = DateTime::now();
      $this->Tickets->loadInto($ticket,['ChildTickets'=>['Attractions']]);
      if ($ticket->stock !== null) {
        $max_quantity = $ticket->stock > $max_quantity ? $max_quantity : $ticket->stock;
      }
      if (Configure::read('Features.allotment')) {
        if ($ticket->pricing_type == 'fdatetime') {
          $datetime_obj = DateTime::createFromFormat('d M Y H:i',$date);
          $allotment = $this->getTicketAllotment($ticket,$datetime_obj->format('Y-m-d'),$datetime_obj->format('H:i:00'));
        } else {
          $datetime_obj = new \Cake\I18n\DateTime($date);
          $allotment = $this->getTicketAllotment($ticket,$datetime_obj->format('Y-m-d'));
        }
      }
      if (!empty($ticket->child_tickets)) {
        foreach ($ticket->child_tickets as $key => $child_ticket) {
          if ($child_ticket->attraction && $child_ticket->attraction->pricing_type != 'settlement' && $date != null) {
            if ($child_ticket->attraction->pricing_type == 'fdatetime') {
              $datetime_obj = DateTime::createFromFormat('d M Y H:i',$date);
              $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id'=>$child_ticket->id,'date'=>$datetime_obj->format('Y-m-d'),'time'=>$datetime_obj->format('H:i:00')])->first();
              if (!empty($fixeddate)) {
                if ($fixeddate->stock !== null) {
                  $available_quantity = $fixeddate->stock;
                  if ($max_quantity > $available_quantity) {
                     $max_quantity = $available_quantity;
                   } 
                }
              } else {
                return 0;
              }
            }
            else {
              $date_parts = explode(' ', trim($date));
              $date_only = $date_parts[0] . ' ' . $date_parts[1] . ' ' . $date_parts[2];
              $datetime_obj = DateTime::createFromFormat('d M Y', $date_only);
              $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id'=>$child_ticket->id,'date'=>$datetime_obj->format('Y-m-d')])->first();
              if (!empty($fixeddate)) {
                if ($fixeddate->stock !== null) {
                  $available_quantity = $fixeddate->stock;
                  if ($max_quantity > $available_quantity) {
                    $max_quantity = $available_quantity;
                  }
                }
              } else {
                return 0;
              }
            }
            if (Configure::read('Features.allotment')) {
              if ($child_ticket->attraction->pricing_type == 'fdatetime') {
                $allotment = $this->getTicketAllotment($child_ticket,$datetime_obj->format('Y-m-d'),$datetime_obj->format('H:i:00'));
              } else {
                $allotment = $this->getTicketAllotment($child_ticket,$date);
              }
              if ($allotment < $max_quantity) {
                $max_quantity = $allotment;
              }
            }
          }
          if ($max_quantity > 0 && $child_ticket->voucher_type =='pdf') {
            if ($max_quantity > 0) {
              $pdf_count = $this->Tickets->Pdfs->find()->where(['ticket_sku_id'=>$child_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now]);
              $pdf_count = $pdf_count->count();
              if ($max_quantity > $pdf_count) {
                $max_quantity = $pdf_count;
              }
            } else {
              $max_quantity = 0;
            }
          }
          elseif ($child_ticket->voucher_type =='barcode') {
            if ($max_quantity > 0) {
              $barcode_count = $this->Tickets->Barcodes->find()->where(['ticket_sku_id'=>$child_ticket->sku_id,'status'=>'new','validity_end_date >='=>$now ])->count();
              if ($max_quantity > $barcode_count) {
                $max_quantity = $barcode_count;
              }
            } else {
              $max_quantity = 0;
            }
          }
          else {
            $gc__ticket = $this->Tickets->find()->where(['id'=>$child_ticket->id])->first();
            if ($child_ticket->stock !== null && $child_ticket->stock < $max_quantity) {
              $max_quantity = $child_ticket->stock;
            }
            if ($child_ticket->stock === 0) {
              $max_quantity = 0;
            }
          }
        }
      }
      if ($ticket->stock !== null && $ticket->stock < $max_quantity) {
        $max_quantity = $ticket->stock;
      }
      return $max_quantity ;
    } else {
      if ($ticket->attraction && $ticket->attraction->pricing_type != 'settlement' && $date != null) {
        $fixeddate = null;
        if (in_array($ticket->attraction->pricing_type, [PricingType::FDATETIME, PricingType::TWO_WAY_FDATETIME])) {
          $datetime_obj = DateTime::createFromFormat('d M Y H:i',$date);
          $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id'=>$ticket->id,'date'=>$datetime_obj->format('Y-m-d'),'time'=>$datetime_obj->format('H:i:00')])->first();
        }
        else {
          $datetime_obj = DateTime::createFromFormat('d M Y',$date);
          $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id'=>$ticket->id,'date'=>$datetime_obj->format('Y-m-d')])->first();
        }
        if (!empty($fixeddate)) {
          if ($fixeddate->stock !== null) {
            $available_quantity = $fixeddate->stock;
            if ($max_quantity > $available_quantity) {
              $max_quantity = $available_quantity;
            }
          }
          if (Configure::read('Features.allotment')) {
          $allotment = $max_quantity;
            if ($ticket->attraction->pricing_type == 'fdatetime') {
              $datetime_obj = new \Cake\I18n\DateTime($date);
              $allotment = $this->getTicketAllotment($ticket,$datetime_obj->format('Y-m-d'),$datetime_obj->format('H:i:00'));
            } else {
              $datetime_obj = new \Cake\I18n\DateTime($date);
              $allotment = $this->getTicketAllotment($ticket,$datetime_obj->format('Y-m-d'));
            }
            if ($allotment < $max_quantity) {
              $max_quantity = $allotment;
            }
          }
          return $max_quantity;
        }
        return 0;
      }

      return $ticket->stock !== null ? $ticket->stock : $max_quantity;
    }

    // if ($ticket->fixed_date_ticket) {
    //   if ($ticket->quantity == null) {

    //     if ($ticket->fixeddates) {
    //       $submitted_date_time = explode('|',$date);
    //       foreach ($ticket->fixeddates as $key => $fixeddate) {
    //         if (!empty($submitted_date_time[0]) && trim($submitted_date_time[0]) ==  $fixeddate->date->format('d M Y') && trim($submitted_date_time[1] == 'Any Time')) {
    //           return $fixeddate->quantity- intval($fixeddate->quantity_sold);
    //         }
    //         elseif (!empty($submitted_date_time[0]) && trim($submitted_date_time[0]) ==  $fixeddate->date->format('d M Y') && $fixeddate->time && $fixeddate->time->format('H:i') == trim($submitted_date_time[1])) {
    //           return $fixeddate->quantity - intval($fixeddate->quantity_sold);
    //         }
    //         elseif ($date == null) {
    //           return $fixeddate->quantity - intval($fixeddate->quantity_sold);
    //         }
    //         else {
    //           return 0;
    //         }
    //       }
    //     }
    //     else {
    //       return $max_quantity;
    //     }
    //   }
    //   else {
    //     return $ticket->quantity - $ticket->quantity_sold;
    //   }
    // }
    // else {
    //   return $ticket->quantity ? $ticket->quantity - $ticket->quantity_sold: $max_quantity;
    // }
  }

  public function getOpenDateAvailableQuantity($ticket) {
    $max_quantity = Configure::read('Default.max_order_quantity');
    if ($ticket->stock !== null && $ticket->stock < $max_quantity) {
      $max_quantity = $ticket->stock;
    }
    if ($ticket->allotment_id) {
      $allotment = $this->Allotments->find()->where(['id'=>$ticket->allotment_id])->first();
      if ($allotment && $allotment->available !== null) {
        $available = $allotment->available;
        
        if ($available < $max_quantity) {
          return $available;
        }
      }
    }
    return $max_quantity;
  }

  public function getTicketAllotment($ticket,$date,$time = null) {
    $max_quantity = Configure::read('Default.max_order_quantity'); //unlimited
    if ($ticket->allotment_id) {
      $start_time = new DateTime($date.' '.$time);
      if ($ticket->pricing_type == 'fdate') {
        $allotment = $this->Allotments->find()->where(['id'=>$ticket->allotment_id])->contain(['Slots'=>function($q) use($date) {
          return $q->where(['date'=>$date]);
        }])->first();
        if ($allotment && $allotment->slots[0]) {
          $available = $allotment->slots[0]->available;
          if ($allotment->available !== null && $allotment->available < $available) {
            $available = $allotment->available;
          }
          if ($available < $max_quantity) {
            return $available;
          }
        }
      } else {
        $endtime = (new DateTime($start_time))->addMinutes($ticket->activity_duration);
        $allotment = $this->Allotments->find()->where(['id'=>$ticket->allotment_id])->contain(['Slots'=>function($q) use($date,$time,$endtime) {
          return $q->where(['date'=>$date,'time >='=>$time,'time <'=>$endtime]);
        }])->first();
        if ($allotment && !empty($allotment->slots)) {
          $available = $max_quantity;
          foreach ($allotment->slots as $key => $slot) {
            if ($slot->available !== null && $slot->available < $available) {
              $available = $slot->available;
            }
          }
          if ($allotment->available !== null && $allotment->available < $available) {
            $available = $allotment->available;
          }
          if ($available < $max_quantity) {
            return $available;
          }
          
        } else {
          return 0;
        }
      }
      
    }
    return $max_quantity;
  }

  public function getTicketResellerPrice($ticket,$attraction,$agent) {
    $SettlementAgents = TableRegistry::getTableLocator()->get('SettlementAgents');
    $settlement_rate = $SettlementAgents->find()
                          ->where(['agent_id'=>$agent['agent_id']])
                          ->where(['SettlementAgents.ticket_id'=>$ticket->id])
                          ->contain(['Settlements'])
                          ->first();
    // debug($settlement_rate);
    if ($settlement_rate && !empty($settlement_rate->settlement)) {
      $ticket->retail_price = $ticket->price;
      $ticket->price = $settlement_rate->settlement->rate;
      return $ticket;
    }
    else {
      return null;
    }
  }

  public function getFixeddateResellerPrice($ticket,$attraction,$agent,$datetime = null) {
    $ticket->retail_price = $ticket->price;

    // Look up agent's fdatesettlement group for this ticket
    $FdatesettlementsAgents = TableRegistry::getTableLocator()->get('FdatesettlementsAgents');
    $agentGroup = $FdatesettlementsAgents->find()
        ->contain(['Fdatesettlements'])
        ->where(['FdatesettlementsAgents.ticket_id' => $ticket->id, 'FdatesettlementsAgents.agent_id' => $agent['agent_id']])
        ->first();

    if ($agentGroup && !empty($agentGroup->fdatesettlement)) {
      $rate = $agentGroup->fdatesettlement->rate;

      // Check for per-date override if we have a fixeddate
      $fixeddate = null;
      if (!empty($ticket->fixeddates)) {
        $fixeddate = $ticket->fixeddates[0];
      } elseif ($datetime != null) {
        if (in_array($attraction->pricing_type, [PricingType::FDATETIME, PricingType::TWO_WAY_FDATETIME])) {
          $datetime_obj = DateTime::createFromFormat('d M Y H:i', $datetime);
          $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id' => $ticket->id, 'date' => $datetime_obj->format('Y-m-d'), 'time' => $datetime_obj->format('H:i:00')])->first();
        } else {
          $datetime_obj = DateTime::createFromFormat('d M Y', $datetime);
          $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id' => $ticket->id, 'date' => $datetime_obj->format('Y-m-d')])->first();
        }
      }

      if ($fixeddate) {
        $FdateOverrideRates = TableRegistry::getTableLocator()->get('FdateOverrideRates');
        $override = $FdateOverrideRates->find()
            ->where(['fdatesettlement_id' => $agentGroup->fdatesettlement->id, 'fdate_id' => $fixeddate->id])
            ->first();
        if ($override) {
          $rate = $override->rate;
        }
      }

      $ticket->price = $rate;
    }

    return $ticket;
  }

  public function getFixeddate($ticket,$agent,$datetime = null) {
    
    if ($datetime != null) {
      if (in_array($ticket->attraction->pricing_type, [PricingType::FDATETIME, PricingType::TWO_WAY_FDATETIME])) {
        $datetime_obj = DateTime::createFromFormat('d M Y H:i',$datetime);
        $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id'=>$ticket->id,'date'=>$datetime_obj->format('Y-m-d'),'time'=>$datetime_obj->format('H:i:00')])->first();
        if (!empty($fixeddate)) {
          return $fixeddate;
        }
      }
      else {
        $datetime_obj = DateTime::createFromFormat('d M Y',$datetime);
        $fixeddate = $this->Tickets->Fixeddates->find()->where(['ticket_id'=>$ticket->id,'date'=>$datetime_obj->format('Y-m-d')])->first();
        if (!empty($fixeddate)) {
          return $fixeddate;
        }
      }
    }
    return null;
  }

}
