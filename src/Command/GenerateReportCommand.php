<?php

namespace App\Command;

use App\Util\Csv;
use Cake\Log\Log;
use Cake\Core\Configure;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * GenerateReport command.
 */
#[\AllowDynamicProperties]
class GenerateReportCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser
            ->addOption('agent_user_id', [
                'help' => 'agent_user_id'
            ])
            ->addOption('issue_start_date', [
                'help' => 'issue_start_date'
            ])
            ->addOption('issue_end_date', [
                'help' => 'issue_end_date'
            ])
            ->addOption('start_date', [
                'help' => 'start_date'
            ])
            ->addOption('end_date', [
                'help' => 'end_date'
            ])
            ->addOption('agent_user_ids', [
                'help' => 'agent_user_ids'
            ]);
        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->Agents = $this->fetchTable('Agents');
        $this->AgentUsers = $this->fetchTable('AgentUsers');
        $this->ApiPdfs = $this->fetchTable('ApiPdfs');
        $this->Barcodes = $this->fetchTable('Barcodes');
        $this->GeneratedCodes = $this->fetchTable('GeneratedCodes');
        $this->Orders = $this->fetchTable('Orders');
        $this->Pdfs = $this->fetchTable('Pdfs');

        $agent_user_id = $args->getOption('agent_user_id');
        $issue_start_date = $args->getOption('issue_start_date');
        $issue_end_date = $args->getOption('issue_end_date');
        $start_date = $args->getOption('start_date');
        $end_date = $args->getOption('end_date');
        $agent_user_ids = $args->getOption('agent_user_ids');

        $conditions = [
            'Orders.created >=' => $start_date,
            'Orders.created <=' => $end_date,
            'agent_user_id IN' => explode(',', $agent_user_ids),
            'Orders.status NOT IN' => ['pending', 'waiting', 'void'],
        ];
        Log::debug($conditions);
        $agent_user = $this->AgentUsers->find()->where([
            'AgentUsers.id' => $agent_user_id
        ])->contain(['Agents'])
            ->first();
        if (!$agent_user) {
            return null;
        }
        $agent = $agent_user->agent;

        $orders_query = $this->Orders->find()->where($conditions)
            ->contain(['AgentUsers' => ['Agents'], 'OrderItems' => ['Tickets' => ['Attractions'], 'Vouchers']])
            ->order(['Orders.created' => 'ASC']);

        $array_records = [];
        $array_records[] = ['Report Date', date('Y-m-d H:i:s')];
        $array_records[] = ['Report Title', 'Report Sheet from ' . Configure::read('Site.company_name')];
        $array_records[] = ['Reseller', $agent->company_name];
        $array_records[] = ['Start Date', $issue_start_date];
        $array_records[] = ['End Date', $issue_end_date];
        $array_records[] = [];

        $column_headers = ['No', 'Date Issued', 'Time Issued', 'Reseller Name', 'Reseller Email', 'Order Tracking Number', 'Order Reference', 'Platform', 'Confirmation No.', 'Attraction Name', 'Product Type', 'Package Name', 'Ticket Type', 'Customer Name', 'Payment Method', 'Unit Price', 'Units', 'Total Amount', 'Status'];
        if ($agent->hide_voucher_status) {
            array_pop($column_headers);
        }
        $array_records[] = $column_headers;
        $no = 1;
        $orders_count = $orders_query->count();
        $limit = 500;
        $pages = ceil($orders_count / $limit);
        if (!empty($orders_count > 0)) {
            $output_file_name = $agent->company_name . ' Sales Report ' . $issue_start_date . ' to ' . $issue_end_date . '.csv.tmp';
            $csv = Csv::writeReport($array_records, $output_file_name, ',', $agent_user_id);

            for ($page = 1; $page <= $pages; $page++) {
                $array_records = [];
                $orders = $orders_query->limit($limit)->page($page)->toArray();
                foreach ($orders as $key => $order) {
                    $platform =  $order->is_api ? 'API' : 'B2B';
                    $reseller_name = !empty($order->agent_user) ? $order->agent_user->name : '';
                    $reseller_email = !empty($order->agent_user) ? $order->agent_user->email : '';
                    foreach ($order->order_items as $key => $order_item) {
                        $order_item_records = [];
                        if ($order_item->ticket->type == 'complex' && empty($order_item->ticket->flexi_options)) {
                            $pkg_ticket_data = [];
                            $pkg_ticket_data['no'] = '';
                            $pkg_ticket_data['purchased_date'] = $order_item->created->format('d-m-Y');
                            $pkg_ticket_data['purchased_time'] = $order_item->created->format('H:i:s');
                            $pkg_ticket_data['redemption_date'] = '';
                            $pkg_ticket_data['redemption_time'] = '';
                            $pkg_ticket_data['quantity_redemmed'] = 0;
                            $pkg_ticket_data['status'] = '';
                            $pkg_ticket_data['confirmation_code'] = ''; //to check
                            $pkg_ticket_data['quantity'] = $order_item->qty;
                            $pkg_ticket_data['redemption_date'] = '';
                            $pkg_ticket_data['redemption_time'] = '';
                            $pkg_ticket_data['quantity_redemmed'] = 0;
                            $pkg_ticket_data['order_reference'] = $order->order_ref_id; //'';//to check
                            $pkg_ticket_data['reseller_reference'] = $order->tracking_no;
                            $pkg_ticket_data['sku_id'] = $order_item->ticket->sku_id;
                            $pkg_ticket_data['type'] = $order_item->ticket->name;
                            $pkg_ticket_data['attraction_name'] = '';
                            $pkg_ticket_data['package_name'] = '';
                            $pkg_ticket_data['attraction_name'] = ''; //to check
                            $pkg_ticket_data['package_name'] = $order_item->ticket->attraction->title;
                            $pkg_ticket_data['product_type'] = 'Package';
                            $pkg_ticket_data['payment_method'] = $order->payment_method;
                            $pkg_ticket_data['unit_price'] = $order_item->price;
                            $pkg_ticket_data['commission_rate'] = 0; //to check
                            // $pkg_ticket_data['quantity'] = $order_item->qty;
                            $pkg_ticket_data['total_amount'] = $order_item->total;
                            $pkg_ticket_data['total_commision'] = 0; //to check


                            $to_add_package_data = [
                                $pkg_ticket_data['no'],
                                $pkg_ticket_data['purchased_date'],
                                $pkg_ticket_data['purchased_time'],
                                // $pkg_ticket_data['redemption_date'],
                                // $pkg_ticket_data['redemption_time'],
                                $reseller_name,
                                $reseller_email,
                                $pkg_ticket_data['reseller_reference'],
                                $pkg_ticket_data['order_reference'],
                                $platform,
                                $pkg_ticket_data['confirmation_code'],
                                $pkg_ticket_data['attraction_name'],
                                $pkg_ticket_data['product_type'],
                                $pkg_ticket_data['package_name'],
                                $pkg_ticket_data['type'],
                                $order->customer_name,
                                $order->payment_method,
                                $pkg_ticket_data['unit_price'],
                                $pkg_ticket_data['quantity'],
                                $pkg_ticket_data['total_amount'],
                                $pkg_ticket_data['status']
                            ];
                            if ($agent->hide_voucher_status) {
                                array_pop($to_add_package_data);
                            }
                            $order_item_records['pkg'] = $to_add_package_data;
                        }
                        foreach ($order_item->vouchers as $key => $voucher) {
                            $remove = false;
                            $data = [];
                            $belongs_to_merchant = true;
                            $data['no'] = $no++;
                            $data['purchased_date'] = $order_item->created->format('d-m-Y');
                            $data['purchased_time'] = $order_item->created->format('H:i:s');
                            $data['confirmation_code'] = $voucher->code_id; //'';//to check
                            $data['redemption_date'] = '';
                            $data['redemption_time'] = '';
                            $data['attraction_name'] = '';
                            $data['quantity'] = 1;
                            $data['status'] = '';
                            if ($voucher && $voucher->voucher_type == 'barcode') {
                                $barcode = $this->Barcodes->find()->where(['code' => $voucher->code_id])->first();
                                $data['quantity'] = 1;
                                $data['status'] = $barcode->status;
                                if ($barcode && $barcode->status == 'used') {
                                    $data['redemption_date'] = $barcode->modified->format('d-m-Y');
                                    $data['redemption_time'] = $barcode->modified->format('H:i:s');
                                    $data['status'] = 'redeemed';
                                }
                                if ($order_item->ticket->type == 'complex' && empty($order_item->ticket->flexi_options)) {
                                    $this->Barcodes->loadInto($barcode, ['Tickets' => function ($q) {
                                        return $q->contain(['Attractions'])->applyOptions(['excludeDeleted' => false]);
                                    }]);
                                    if (!empty($barcode->ticket) && !empty($barcode->ticket->attraction)) {
                                        $data['attraction_name'] = $barcode->ticket->attraction->title;
                                    }
                                }
                            } elseif ($voucher && $voucher->voucher_type == 'pdf') {
                                $pdf = $this->Pdfs->find()->where(['id' => $voucher->code_id])->first();
                                $data['quantity'] = 1;
                                $data['status'] = $pdf->status;
                                if ($pdf && $pdf->status == 'used') {
                                    $data['redemption_date'] = $pdf->modified->format('d-m-Y');
                                    $data['redemption_time'] = $pdf->modified->format('H:i:s');
                                }
                                if ($pdf) {
                                    $data['confirmation_code'] = basename($pdf->pdf_filename);
                                }
                                if ($order_item->ticket->type == 'complex' && empty($order_item->ticket->flexi_options)) {
                                    $this->Pdfs->loadInto($pdf, ['Tickets' => function ($q) {
                                        return $q->contain(['Attractions'])->applyOptions(['excludeDeleted' => false]);
                                    }]);
                                    if (!empty($pdf->ticket) && !empty($pdf->ticket->attraction)) {
                                        $data['attraction_name'] = $pdf->ticket->attraction->title;
                                    }
                                }
                            } elseif ($voucher && $voucher->voucher_type == 'api') {
                                $api_pdf = $this->ApiPdfs->find()->where(['code' => $voucher->code_id])->first();
                                if (!$api_pdf) {
                                    $api_pdf = $this->ApiPdfs->find()->where(['id' => $voucher->code_id])->first();
                                }
                                if ($api_pdf) {
                                    $data['quantity'] = 1;
                                    if (count($order_item->vouchers) == 1 && $order_item->qty > 1) {
                                        $data['quantity'] = $order_item->qty;
                                    }
                                    $data['status'] = $api_pdf->status;
                                    if ($api_pdf && $api_pdf->status == 'used') {
                                        $data['redemption_date'] = $api_pdf->modified->format('d-m-Y');
                                        $data['redemption_time'] = $api_pdf->modified->format('H:i:s');
                                    }
                                    if ($order_item->ticket->type == 'complex' && empty($order_item->ticket->flexi_options)) {
                                        $this->ApiPdfs->loadInto($api_pdf, ['Tickets' => function ($q) {
                                            return $q->contain(['Attractions'])->applyOptions(['excludeDeleted' => false]);
                                        }]);
                                        if (!empty($api_pdf->ticket) && !empty($api_pdf->ticket->attraction)) {
                                            $data['attraction_name'] = $api_pdf->ticket->attraction->title;
                                        }
                                    }
                                }
                            } elseif ($voucher && $voucher->voucher_type == 'generated') {
                                $generated_code = $this->GeneratedCodes->find()->where(['code' => $voucher->code_id])->first();
                                $data['quantity'] = $generated_code->redeemable;
                                $data['status'] = $generated_code->status;
                                if ($generated_code && $generated_code->status == 'used') {
                                    $data['redemption_date'] = !empty($generated_code->redeemed_datetime) ? $generated_code->redeemed_datetime->format('d-m-Y') : '';
                                    $data['redemption_time'] = !empty($generated_code->redeemed_datetime) ? $generated_code->redeemed_datetime->format('H:i:s') : '';
                                    $data['status'] = 'redeemed';
                                }
                                $data['quantity'] = 1;
                                if (empty($order_item->ticket->flexi_options)) {
                                    $data['quantity'] = $generated_code->redeemable; //when redeemed as single code, quantity will be multiplied by price to give price of this voucher (single/multiple code)
                                }

                                if ($order_item->ticket->type == 'complex' && empty($order_item->ticket->flexi_options)) {
                                    $this->GeneratedCodes->loadInto($generated_code, ['Tickets' => function ($q) {
                                        return $q->contain(['Attractions'])->applyOptions(['excludeDeleted' => false]);
                                    }]);
                                    if (!empty($generated_code->ticket) && !empty($generated_code->ticket->attraction)) {
                                        $data['attraction_name'] = $generated_code->ticket->attraction->title;
                                    }
                                }
                            }

                            $data['reseller'] = !empty($order->agent_user->agent) ? $order->agent_user->agent->fullname : '';
                            $data['order_reference'] = $order->order_ref_id; //'';//to check
                            $data['reseller_reference'] = $order->tracking_no;
                            $data['sku_id'] = $order_item->ticket->sku_id;
                            $data['type'] = $order_item->ticket->name;

                            $data['package_name'] = '';
                            if ($order_item->ticket->type == 'normal') {
                                $data['attraction_name'] = $order_item->ticket->attraction->title;
                                $data['package_name'] = ''; //to check
                                $data['product_type'] = 'Attraction';
                                $data['unit_price'] = $order_item->price;
                                $data['total_amount'] = floatval($order_item->price) * intval($data['quantity']);
                            } else if ($order_item->ticket->type == 'complex') {
                                $data['package_name'] = $order_item->ticket->attraction->title;
                                $data['product_type'] = 'Package';
                                if (!empty($order_item->ticket->flexi_options)) {
                                    $data['unit_price'] = $order_item->price;
                                    $data['total_amount'] = floatval($order_item->price) * intval($data['quantity']);
                                } else {
                                    $data['unit_price'] = 0;
                                    $data['total_amount'] = 0;
                                }
                            }
                            $data['payment_method'] = $order->payment_method;
                            $data['commission_rate'] = 0; //to check
                            $data['total_commision'] = 0; //to check

                            $to_add_data =  [
                                $data['no'],
                                $data['purchased_date'],
                                $data['purchased_time'],
                                // $data['redemption_date'],
                                // $data['redemption_time'],
                                $reseller_name,
                                $reseller_email,
                                $data['reseller_reference'],
                                $data['order_reference'],
                                $platform,
                                $data['confirmation_code'],
                                $data['attraction_name'],
                                $data['product_type'],
                                $data['package_name'],
                                $data['type'],
                                $order->customer_name,
                                $order->payment_method,
                                $data['unit_price'],
                                $data['quantity'],
                                $data['total_amount'],
                                $data['status']
                            ];
                            if ($agent->hide_voucher_status) {
                                array_pop($to_add_data);
                            }
                            $order_item_records[] = $to_add_data;

                            if ($order_item->ticket->type == 'complex' && !empty($order_item->ticket->flexi_options)) {
                                $flexi_generated_codes = $this->GeneratedCodes->find()->where(['package_code' => $voucher->code_id])->contain(['Tickets' => function ($q) {
                                    return $q->contain(['Attractions'])->applyOptions(['excludeDeleted' => false]);
                                }])->toArray();
                                foreach ($flexi_generated_codes as $key => $flexi_generated_code) {
                                    $sub_ticket_data = $data;
                                    $sub_ticket_data['quantity'] = $flexi_generated_code->redeemable;
                                    $sub_ticket_data['sku_id'] = $flexi_generated_code->ticket_sku_id;
                                    $sub_ticket_data['attraction_name'] = $flexi_generated_code->ticket->attraction->title;
                                    $sub_ticket_data['unit_price'] = 0;
                                    $sub_ticket_data['total_amount'] = 0;
                                    $sub_ticket_data['customer_name'] = $order->customer_name;
                                    $sub_ticket_data['status'] = $flexi_generated_code->status;
                                    if ($flexi_generated_code && $flexi_generated_code->status == 'used') {
                                        $sub_ticket_data['status'] = 'redeemed';
                                    }
                                    $to_add_subticket_data = [
                                        '', //$sub_ticket_data['no'],
                                        $sub_ticket_data['purchased_date'],
                                        $sub_ticket_data['purchased_time'],
                                        // $sub_ticket_data['redemption_date'],
                                        // $sub_ticket_data['redemption_time'],
                                        $reseller_name,
                                        $reseller_email,
                                        $sub_ticket_data['reseller_reference'],
                                        $sub_ticket_data['order_reference'],
                                        $platform,
                                        $sub_ticket_data['confirmation_code'],
                                        $sub_ticket_data['attraction_name'],
                                        $sub_ticket_data['product_type'],
                                        $sub_ticket_data['package_name'],
                                        $sub_ticket_data['type'],
                                        $sub_ticket_data['customer_name'],
                                        $order->payment_method,
                                        $sub_ticket_data['unit_price'],
                                        $sub_ticket_data['quantity'],
                                        $sub_ticket_data['total_amount'],
                                        $sub_ticket_data['status']
                                    ];
                                    if ($agent->hide_voucher_status) {
                                        array_pop($to_add_subticket_data);
                                    }
                                    $order_item_records[] = $to_add_subticket_data;
                                }
                            }
                        }
                        if (empty($order_item->vouchers)) {
                            $data = [];
                            $data['no'] = $no++;
                            $data['purchased_date'] = $order_item->created->format('d-m-Y');
                            $data['purchased_time'] = $order_item->created->format('H:i:s');
                            $data['confirmation_code'] = '';
                            $data['quantity'] = $order_item->qty;
                            $data['status'] = $order->status;
                            $data['attraction_name'] = $order_item->ticket->attraction->title;
                            $data['package_name'] = $order_item->ticket->type == 'complex' ? $order_item->ticket->attraction->title : '';
                            $data['product_type'] = $order_item->ticket->type == 'complex' ? 'Package' : 'Attraction';
                            $data['unit_price'] = $order_item->price;
                            $data['total_amount'] = floatval($order_item->price) * intval($order_item->qty);

                            $to_add_data = [
                                $data['no'],
                                $data['purchased_date'],
                                $data['purchased_time'],
                                $reseller_name,
                                $reseller_email,
                                $order->tracking_no,
                                $order->order_ref_id,
                                $platform,
                                $data['confirmation_code'],
                                $data['attraction_name'],
                                $data['product_type'],
                                $data['package_name'],
                                $order_item->ticket->name,
                                $order->customer_name,
                                $order->payment_method,
                                $data['unit_price'],
                                $data['quantity'],
                                $data['total_amount'],
                                $data['status']
                            ];
                            if ($agent->hide_voucher_status) {
                                array_pop($to_add_data);
                            }
                            $order_item_records[] = $to_add_data;
                        }
                        if (sizeof($order_item_records) <= 1) {
                            if (empty($order_item_records) || !empty($order_item_records['pkg'])) {
                                unset($order_item_records['pkg']);
                            }
                        }
                        foreach ($order_item_records as $key => $order_item_record) {
                            $array_records[] = $order_item_record;
                        }
                    }
                }
                $csv = Csv::appendToReport($array_records, $output_file_name, ',', $agent_user_id);
            }


            $finished_output_file_name = Csv::finishReport($output_file_name, $agent_user_id);
        }
    }
}
