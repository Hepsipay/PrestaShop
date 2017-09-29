<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HepsipayApi
 *
 * @author houmam
 */
class HepsipayApi
{
    protected $apikey;
	protected $secret;
	protected $endpoint;
	protected $force3D;
	protected $enable3D;
	protected $force3DForDebit;
	protected $enableInstallment;
	protected $merchantPayFees;
	protected $language;
    
    public function __construct($config=[]) {
        foreach($config as $key=>$value) {
            if(!property_exists($this, $key)) {
                throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $key);
            }
            $this->$key = $value;
        }
    }
    
    public static function instance($module, $lang='tr')
    {        
        return new HepsipayApi([
            'apikey' => $module->apikey,
            'secret' => $module->secret,
            'endpoint' => $module->endpoint,
            'force3D' => $module->force3D,
            'enable3D' => $module->enable3D,
            'force3DForDebit' => $module->force3DForDebit,
            'enableInstallment' => $module->enableInstallment,
            'merchantPayFees' => $module->merchantPayFees,
            'language' => $lang,
        ]);
    }
    
    public function bin($bin)
    {
        $response = $this->sendRequest('get', [
            'bin' => $bin,
            'get_param' => 'installments',
        ]);
        if(!$this->enableInstallment) {
            foreach($response['data'] as &$data) {
                foreach ($data['installments'] as $opt) {
                    if($opt['count']==1) {
                        $data['installments'] = [$opt];
                        break;
                    }
                }
            }
        }
        if($this->merchantPayFees) {
            foreach($response['data'] as &$data) {
                foreach ($data['installments'] as &$opt) {
                    $opt['commission'] = 0;
                    $opt['percentage'] = "0%";
                }
            }
        }
        return $response;
    }
    
    public function issuer($bin)
    {
        $response = $this->sendRequest('get', [
            'bin' => $bin,
            'get_param' => 'issuer',
        ]);
        return $response;
    }
    
    public function pay($request)
    {
        $response = $this->sendRequest('sale', $request);
        return $response;
    }
    
    public function use3D($bin, $installment, $defaultValue=true)
    {
        if($this->force3D) {
            return true;
        }
        if($this->force3DForDebit && ($cardInfo = $this->issuer($bin))) {
            $type = isset($cardInfo['data']['type']) ? $cardInfo['data']['type'] : '';
            if(strtolower($type)==='debit') {
                return true;
            }
        }
        if($this->enable3D) {
            return $defaultValue;
        }
        return false;
    }
    
    public function getPaymentCommission($bin, $installment)
    {
        if($this->merchantPayFees) {
            return 0;
        }
        $result = $this->bin(substr($bin, 0, 6));
        if(isset($result['status']) && $result['status']===1) {
            $installments = $result['data'][0]['installments'];
            
            foreach($installments as $option) {
                if($option['count']===$installment) {
                    return floatval($option['commission']);
                }
            }
            return false;
            
        }
        return false;
    }
    
    protected function sendRequest($op, $data)
    {
        $data['type'] = $op;
        $data['merchant'] = $this->apikey;
        $data['language'] = $this->language;
        $data['client_ip'] = $this->getClinetIp();
        $data['hash'] = $this->hash($data);
        
        $response = self::post($this->endpoint, $data);
        $json = json_decode($response, true);
        return $json;
    }
    
    public function hash($data)
    {
        ksort($data);
        $string = [];
        foreach ($data as $key=>$value) {
            if(($n = mb_strlen($value)) > 0) {
                $string[] = $n.$value;
            }
        }
        $hash = hash_hmac("sha1", implode('', $string), $this->secret);        
        return $hash;
    }

    protected static function post($url, $data=array())
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_ENCODING       => "",
            CURLOPT_USERAGENT      => "curl",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CUSTOMREQUEST  => "POST",
        );

        $url = "https://pluginmanager.hepsipay.com/portal/web/api/v1";

        $curl = curl_init($url);
        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $content    = curl_exec($curl);
        $error      = curl_error($curl);
        curl_close($curl);

        if($content === false) {
            throw new Exception(strtr('Error occured in sending data to Hepsipay/Portal: {error}', array(
                '{error}' => $error,
            )));
        }

        return $content;
    }
    
    protected  function getClinetIp()
    {
        $ip = 'UNKNOWN';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ip;
    }
    
    public static function getCardMask($pan)
    {
        $bin = substr($pan, 0, 6);
        $card = substr($pan, -4);
        $asterisk = str_repeat('*', strlen($pan) - 10);
        return $bin.$asterisk.$card;
    }
    
    public static function processPayReaponse($module, $response=[], $use3D=false)
    {
        $status = isset($response['status']) ? intval($response['status']) : 0;
        $code = isset($response['ErrorCode']) ? $response['ErrorCode'] : 0;
        $message = isset($response['ErrorMSG']) ? $response['ErrorMSG'] : $module->l('Unexpected error occurred while processing payment transaction');
        $trix = isset($response['transaction_id']) ? $response['transaction_id'] : null;
        $total = isset($response['total']) ? floatval($response['total']) : 0;
        $currency = isset($response['currency']) ? $response['currency'] : 0;
        $installments = isset($response['installments']) ? $response['installments'] : 0;
        $originalTotal = isset($response['original_total']) ? $response['original_total'] : 0;
        $originalCurrency = isset($response['original_currency']) ? $response['original_currency'] : 0;
        $exchangeRate = isset($response['conversion_rate']) ? $response['conversion_rate'] : 1;
        $paymentInfo = isset($response['passive_data']) ? json_decode($response['passive_data'], true) : [];
        
        $hash = isset($response['hash']) ? $response['hash'] : null;
        
        unset($response['hash'], $response['_csrf']);
        
        $log_id = isset($paymentInfo['logId']) ? $paymentInfo['logId'] : null;
        $fee = isset($paymentInfo['fee']) ? floatval($paymentInfo['fee']) : 0;
        $cart_id = isset($paymentInfo['cartId']) ? $paymentInfo['cartId'] : 0;
        $order_id = isset($paymentInfo['orderId']) ? $paymentInfo['orderId'] : 0;
        $orderTotal = isset($paymentInfo['orderTotal']) ? $paymentInfo['orderTotal'] : 0;
        $currency_id = isset($paymentInfo['currencyId']) ? $paymentInfo['currencyId'] : 0;
        
        $exception = null;
        $customer = Context::getContext()->customer;
        
        try {
            $paidAmount = $total/$exchangeRate;
            if($paidAmount - ($orderTotal+$fee) > 0.01) {
                throw new Exception($module->l("Invalid paid amount. Please contact us to review your order."));
            }
            
            if($status && !$use3D) {
                // place the order for none 3D secure.
                $orderStatus = Configuration::get('PS_OS_PAYMENT');
                $module->validateOrder($cart_id, $orderStatus, $orderTotal, $module->displayName, $message, ['transaction_id'=>$trix], $currency_id, false, $customer->secure_key);
                //$order = Order::getOrderByCartId($cart_id);
                $order_id = $module->currentOrder;
            }
            
            //if(!$order) {
                $order = new Order($order_id);
            //}
            if(!Validate::isLoadedObject($order)) {
                throw new Exception($module->l("Invalid response. No matching order found"));
            }
            
            $paidStatus = (int)Configuration::get('PS_OS_PAYMENT');
            $pendingStatus = (int)Configuration::get('PS_OS_HEPSIPAY_PENDING');
            $currentState = $order->getCurrentOrderState();
            
            if($use3D && $currentState->id === $paidStatus) {
                throw new Exception($module->l("The order is already paid."));
            }
            
            self::addNewMessage($cart_id, $order_id, $customer->id, $message. " (code: $code, transaction: $trix)", true);
            
            if($status !==1) {
                $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                throw new Exception($message . " (code: $code)");
                //$order->addOrderPayment(0, null, $trix);
            } else {
                if($use3D) {
                    $order->addOrderPayment($orderTotal, null, $trix);
                    $history = new OrderHistory();
                    $history->id_order = $order->id;
                    $history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $order, TRUE /*use existing invoice*/);
                    $history->addWithemail(true, []);
                }
                self::addNewMessage($cart_id, $order_id, $customer->id, $module->l("Payment Information"). " (Installment: $installments, Fee: $fee)", false);
            }
            
            self::addNewMessage($cart_id, $order_id, $customer->id, $message. " (code: $code)", false);

            
        } catch (Exception $ex) {
            $status = 0;
            $message .= "  >>> ERROR: ".$ex->getMessage();
            $exception = $ex;
        } finally {
            if($log_id) {
                Db::getInstance()->update('hespipay_transaction', [
                    'status' => (int)$status,
                    'order_id' => $order_id,
                    'transaction_id' => $trix,
                    'response_code' => $code,
                    'response_message' => $message,
                    'installment' => $installments,
                    'payment_currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'updated' => date('Y-m-d H:i:s'),
                ], 'id = ' . $log_id);
            }
        }   
        
        if($exception) {
            throw $exception;
        }
        
        return true;
    }
    
    protected static function addNewMessage($cartId, $orderId, $customerId, $message, $private=true)
    {
        $msg = new Message();
        $msg->message = $message;
        $msg->id_cart = $cartId;
        $msg->id_customer = $customerId;
        $msg->id_order = $orderId;
        $msg->private = 1;
        $msg->add();
    }
}
