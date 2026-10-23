<?php

namespace App\Lib;

use Exception;
use Cake\Log\Log;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use PSpell\Config;

/**
 * Payments class
 * Library class for processing non-credit payments
 */
class Payments
{

    public function __construct()
    {
        
    }

    public static function config() {
        $PaymentMethods = TableRegistry::getTableLocator()->get('PaymentMethods');
        $PaymentGateways = TableRegistry::getTableLocator()->get('PaymentGateways');
        $PaymentMarkups = TableRegistry::getTableLocator()->get('PaymentMarkups');
        $config_payment_methods = [];
        $config_payment_providers = [];
        

        $payment_methods = $PaymentMethods->find()
            ->where(['status' => 'enabled', 'payment_gateway_id is not null'])
            ->contain(['PaymentGateways'])
            ->toArray();
        
        foreach ($payment_methods as $payment_method) {
            $config_payment_methods[$payment_method->name] = $payment_method->description ?? Inflector::humanize($payment_method->name);
            $config_payment_providers[$payment_method->name] = $payment_method->payment_gateway->name;
            $payment_gateway_data = json_decode($payment_method->payment_gateway->data, true);
            if (is_array($payment_gateway_data)) {
                foreach ($payment_gateway_data as $pgd_key => $pgd_value) {
                    Configure::write('Payment.' . $payment_method->payment_gateway->name . '.' . $pgd_key, $pgd_value);
                }
            }
            
            $payment_markups = $PaymentMarkups->find()
                ->where(['payment_method_id' => $payment_method->id, 'payment_gateway_id' => $payment_method->payment_gateway->id])
                ->toArray();
            // $config_payment_markups = [];
            foreach ($payment_markups as $key => $payment_markup) {
                $config_payment_markup = [
                    'value' => $payment_markup->rate,
                    'type' => $payment_markup->type,
                    'add' => $payment_markup->additional
                ];
                Configure::write('Payment.' . $payment_method->payment_gateway->name . '.payment_markups.' . $payment_method->name, $config_payment_markup);
            }
        }
        Configure::write('Payment.methods', $config_payment_methods);
        Configure::write('Payment.providers', $config_payment_providers);
    }

    /**
     * Handles payment methods other than credits
     *
     * @param string $payment_method
     * @param string $order_ref_id
     * @param array $checkout_info
     * @param double $total
     * 
     * @return array|null
     */
    public function handlePayments($payment_method, $order_ref_id, $checkout_info, $total)
    {
        $provider = $this->getPaymentProvider($payment_method);
        if ($provider == '2C2P') {
            $p2c2p = new Payment2C2P();
            $redirect_link = $p2c2p->getRedirectLink($payment_method, $order_ref_id, $checkout_info, $total);
            return ['redirect' => $redirect_link];
        } elseif ($provider == 'Stripe') {
            $pstripe = new PaymentStripe();
            $redirect_link = $pstripe->getRedirectLink($payment_method, $order_ref_id, $checkout_info, $total);
            return ['redirect' => $redirect_link];
        } elseif ($provider == 'Paypal') {
            $ppaypal = new PaymentPaypal();
            $redirect_link = $ppaypal->getRedirectLink($payment_method, $order_ref_id, $checkout_info, $total);
            return ['redirect' => $redirect_link];
        }
        return null;
    }

    /**
     * Gets order_ref_id based on return data
     *
     * @param [type] $provider
     * @param [type] $this
     * @return void
     */
    public function getOrderRefId($provider, $data) {
        if ($provider == '2C2P') {
            $p2c2p = new Payment2C2P();
            $order_ref_id = $p2c2p->getOrderRefId($data);
            return $order_ref_id;
        } elseif ($provider == 'Stripe') {
            $pstripe = new PaymentStripe();
            $order_ref_id = $pstripe->getOrderRefId($data);
            return $order_ref_id;
        } elseif ($provider == 'Paypal') {
            $paypal = new PaymentPaypal();
            $order_ref_id = $paypal->getOrderRefId($data);
            return $order_ref_id;
        }


        return null;
    }

     /**
     * Checks if payment is successful or not
     *
     * @param string $provider
     * @param \App\Model\Entity\Order $order
     * @param array $data
     * @return boolean
     */
    public function isPaymentSuccess($provider, $order, $data) {
        // $provider = $this->getPaymentProvider($payment_method);
        if ($provider == '2C2P') {
            $p2c2p = new Payment2C2P();
            $success = $p2c2p->isPaymentSuccess($order, $data);
            return $success;
        } elseif ($provider == 'Stripe') {
            $pstripe = new PaymentStripe();
            $success = $pstripe->isPaymentSuccess($order, $data);
            return $success;
        } elseif ($provider == 'Paypal') {
            $paypal = new PaymentPaypal();
            $success = $paypal->isPaymentSuccess($order, $data);
            return $success;
        }
        return false;
    }

    /**
     * Handles payment notification received
     *
     * @param string $provider
     * @param \App\Model\Entity\Order $order
     * @param array $data
     * @return boolean
     */
    public function verifyPayment($provider, $order, $data, $headers) {
        // $provider = $this->getPaymentProvider($payment_method);
        if ($provider == '2C2P') {
            $p2c2p = new Payment2C2P();
            $correct = $p2c2p->verifyPayment($order, $data);
            return $correct;
        } elseif ($provider == 'Stripe') {
            $pstripe = new PaymentStripe();
            $correct = $pstripe->verifyPayment($order, $data);
            return $correct;
        } elseif ($provider == 'Paypal') {
            $paypal = new PaymentPaypal();
            $correct = $paypal->verifyPayment($order, $data, $headers);
            return $correct;
        }
        return false;
    }

    /**
     * Gets markup for other payment methods
     *
     * @param string $payment_method
     * 
     * @return float
     */
    public static function getMarkup($payment_method)
    {
        $providers = Configure::read('Payment.providers');
        $provider = !empty($providers[$payment_method]) ? $providers[$payment_method] : null;
        if ($provider == '2C2P') {
            $p2c2p = new Payment2C2P();
            return $p2c2p->getMarkup($payment_method);
        } elseif ($provider == 'Stripe') {
            $pstripe = new PaymentStripe();
            return $pstripe->getMarkup($payment_method);
        } elseif ($provider == 'Paypal') {
            $paypal = new PaymentPaypal();
            return $paypal->getMarkup($payment_method);
        }
        return 0;
    }

    /**
     * Gets payment method list from bootstrap
     *
     * @return array
     */
    public static function getPaymentMethodList()
    {
        $payment_methods = Configure::read('Payment.methods') ?: [];

        return !empty($payment_methods) ? array_keys($payment_methods) : [];
    }

    /**
     * Gets payment methods from bootstrap
     *
     * @return array
     */
    public static function getPaymentMethods()
    {
        $payment_methods = Configure::read('Payment.methods') ?: [];

        return $payment_methods;
    }

    /**
     * Gets payment markups from bootstrap
     *
     * @return array
     */
    public static function getPaymentMarkups()
    {
        $payment_methods = self::getPaymentMethodList();
        $providers = Configure::read('Payment.providers');
        $markups = [];
        foreach ($payment_methods as $payment_method) {
            $provider = !empty($providers[$payment_method]) ? $providers[$payment_method] : null;
            if ($provider == '2C2P') {
                $p2c2p = new Payment2C2P();
                $markup = $p2c2p->getMarkup($payment_method);
                $markups[$payment_method] = [
                    'value' => $markup['value'],
                    'type' => $markup['type'],
                    'add' => $markup['add']
                ];
            } elseif ($provider == 'Stripe') {
                $pstripe = new PaymentStripe();
                $markup = $pstripe->getMarkup($payment_method);
                $markups[$payment_method] = [
                    'value' => $markup['value'],
                    'type' => $markup['type'],
                    'add' => $markup['add']
                ];
            } elseif ($provider == 'Paypal') {
                $paypal = new PaymentPaypal();
                $markup = $paypal->getMarkup($payment_method);
                $markups[$payment_method] = [
                    'value' => $markup['value'],
                    'type' => $markup['type'],
                    'add' => $markup['add']
                ];
            } else {
                $markups[$payment_method] = [
                    'value' => 0,
                    'type' => 'value',
                    'add' => 0
                ];
            }
        }
        return $markups;
    }

    /**
     * Gets payment providers based on payment method selected
     *
     * @param string $payment_method
     * @return string|null
     */
    public function getPaymentProvider($payment_method)
    {
        $payment_providers = Configure::read('Payment.providers');
        if (!empty($payment_providers[$payment_method])) {
            return $payment_providers[$payment_method];
        }
        return null;
    }

    /**
     * Checks if credits payment is enabled
     *
     * @return boolean
     */
    public static function isCreditsEnabled() {
        $PaymentMethods = TableRegistry::getTableLocator()->get('PaymentMethods');
        $payment_method = $PaymentMethods->find()
            ->where(['name' => 'credits','status' => 'disabled'])
            ->first();
        if ($payment_method) {
            return false;
        }
        return true;
    }

}
