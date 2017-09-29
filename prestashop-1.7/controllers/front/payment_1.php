<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once dirname(__FILE__).'/../../classes/HepsipayApi.php';

class HepsipayPaymentModuleFrontController extends ModuleFrontController
{
    protected $transactionId = null;
    
	public function initContent()
	{
		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

		// Call parent init content method
		parent::initContent();
	}
    
    /**
     * Do whatever you have to before redirecting the customer on the website of your payment processor.
     */
    public function postProcess()
    {
        $errors = [];
        $paymentInfo = [
            'paymentCardHolder' => null,
            'paymentCardNumber' => null,
            'paymentCardCVC' => null,
            'paymentCardMonth' => null,
            'paymentCardYear' => null,
            'paymentInstallment' => 1,
            'paymentUse3D' => false,
        ];
        
        try {
            $cart = $this->context->cart;
            if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active || !$cart->nbProducts()) {
                Tools::redirect('index.php?controller=order&step=1');
            }
            
            if (((bool)Tools::isSubmit('paymentConfirmOrder')) == true) {
                foreach ($paymentInfo as $key => &$value) {
                    $value = Tools::getValue($key);
                }
                $errors = $this->_validatePaymentInfo($paymentInfo);
                if(!count($errors)) {
                    $result = $this->_processPayment($paymentInfo);
                    if($result === true) {
                        $this->_redirectToConfirmation();
                        exit;
                    } else {
                        $this->context->smarty->assign('paymentFailed', $result);
                    }
                }
            }

            $currency = Currency::getCurrency($this->context->cart->id_currency);
            $this->context->smarty->assign(array(
                'cart_id' => Context::getContext()->cart->id,
                'secure_key' => Context::getContext()->customer->secure_key,
                'nb_products' => $this->context->cart->nbProducts(),
                'currencyCode' => isset($currency['sign']) ? $currency['sign'] : $currency['iso_code'],
                'currencies' => $this->module->getCurrency((int)$this->context->cart->id_currency),
                'orderTotal' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
                'baseUri' => $this->module->getPathUri(),
                'paymentErrors' => $errors,
                'transactionId' => $this->transactionId,
                'force3D' => $this->module->force3D,
                'enable3D' => $this->module->enable3D,
                'customCss' => $this->module->customCss,
                'merchantPayFees' => $this->module->merchantPayFees,
                'tpl_dir' => _PS_THEME_DIR_,
                'tpl_uri' => _THEME_DIR_,
            ));

            // assign $_POST data
            foreach ($paymentInfo as $key => &$value) {
                $this->context->smarty->assign(array($key => $value));
            }
            
            return $this->setTemplate('module:hepsipay/views/templates/front/payment.tpl');
            
        } catch (Exception $ex) {
            $this->context->smarty->assign('path', '
                <a href="'.$this->context->link->getPageLink('order', null, null, 'step=3').'">'.$this->module->l('Payment').'</a>
                <span class="navigation-pipe">&gt;</span>'.$this->module->l('Error')
            );

            $this->context->smarty->assign(array(
                'code' => $ex->getCode(),
                'title' => $ex->getMessage(),
                'tranaction' => $this->transactionId,
            ));
            //echo $this->module->displayError($e->getMessage());
            return $this->setTemplate('module:hepsipay/views/templates/front/error.tpl');
        }
    }
    
    protected function _validatePaymentInfo($info)
    {
        $errors = [];
        if(!(isset($info['paymentCardHolder']) && mb_strlen($info['paymentCardHolder']))) {
            $errors['paymentCardHolder'] = $this->module->l("Please enter the card holder name.");
        }
        if(!(isset($info['paymentCardNumber']) && mb_strlen($info['paymentCardNumber']))) {
            $errors['paymentCardNumber'] = $this->module->l('Please enter your card number.');
        }
        if(!(isset($info['paymentCardCVC']) && mb_strlen($info['paymentCardCVC']))) {
            $errors['paymentCardCVC'] = $this->module->l('Please enter the CVC value of your card.');
        }
        $y = isset($info['paymentCardYear']) ? intval($info['paymentCardYear']) : 0;
        $m = isset($info['paymentCardMonth']) ? intval($info['paymentCardMonth']) : 0;
        if(!($y >= date('Y') && $y < date('Y')+15)) {
            $errors['paymentCardYear'] = $this->module->l('The expiration year is not valid.');
        }
        if(!($m >= 1 && $m <= 12)) {
            $errors['paymentCardMonth'] = $this->module->l('The expiration month is not valid.');
        }
        
        return $errors;
    }
    
    protected function _processPayment($data)
    {
        $cart = $this->context->cart;
        $language = LanguageCore::getLanguage((int)$cart->id_lang);
        $api = HepsipayApi::instance($this->module, $language['iso_code']);
        
        $request = $this->_preparePaymentInfo($api, $data);
        
        //var_dump($request);exit;
        $response = $api->pay($request);

        $code = isset($response['ErrorCode']) ? $response['ErrorCode'] : false;
        $error = isset($response['ErrorMSG']) ? $response['ErrorMSG'] : $this->module->l('Unexpected error occurred while processing payment transaction');
        $this->transactionId = isset($response['transaction_id']) ? $response['transaction_id'] : null;
        $pasiveData =  isset($response['passive_data']) ? $response['passive_data'] : null;
        $paymentInfo = json_decode($pasiveData, true);
        $logId =  isset($paymentInfo['logId']) ? $paymentInfo['logId'] : null;
        if($response['status']!==1) {
            if($logId) {
                Db::getInstance()->update('hespipay_transaction', [
                    'status' => 0,
                    'transaction_id' => $this->transactionId,
                    'response_code' => $code,
                    'response_message' => $error,
                    'updated' => date('Y-m-d H:i:s'),
                ], "id = {$logId }");
            }
            if($code) {
                $error = $error. " ({$code}): {$error}";
            }
            return $error;
        }
        
        if(isset($response['html'])) {
            echo $response['html'];
            exit;
        } else {
            try {
                HepsipayApi::processPayReaponse($this->module, $response);
                return true;
            } catch (Exception $ex) {
                
                
                return $ex->getMessage();
            }
        }
        
    }
    
    protected function _preparePaymentInfo($api, $data)
    {
        $payment = array(
            'cc_name'        => $data['paymentCardHolder'],
            'cc_number'        => $data['paymentCardNumber'],
            'cc_cvc'           => $data['paymentCardCVC'],
            'cc_month'         => intval($data['paymentCardMonth']),
            'cc_year'          => intval($data['paymentCardYear']),
            'installments'   => intval($data['paymentInstallment']),
            'use3d'         => boolval($data['paymentUse3D']),
        );
        
        $cart = $this->context->cart;
        $bin = substr($payment['cc_number'], 0, 6);
        
        $installment =  ($payment['installments']<=12 && $payment['installments'] > 0) ? $payment['installments'] : 1;
        $installment = $this->module->enableInstallment ? $installment : 1;
        $payment['installments'] = $installment;
        
        $commission=$api->getPaymentCommission($bin, $installment);
        
        if($commission===false) {
            $commission = $api->getPaymentCommission($bin, 1);
            $payment['installments'] = $installment = 1;
        }
        
        $use3D = $payment['use3d'];
        $use3D = $api->use3D($bin, $installment, $use3D);
        $payment['use3d'] = intval($use3D);
        
        // Check if module is enabled
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            throw new Exception($this->module->l('This payment method is not available.'), 500);
        }
        
        // Check if customer exists
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        
        
        $orderTotal = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $fee = (float)Tools::ps_round($orderTotal*($commission/100), 2);
        $currency = Currency::getCurrency($cart->id_currency);
        $totalWithFee = (float)Tools::ps_round($orderTotal*(1+$commission/100), 2);

        Db::getInstance()->insert("hespipay_transaction", array(
            'cart_id' => (int) $cart->id,
            'card_holder' => $payment['cc_name'],
            'crard_mask' => HepsipayApi::getCardMask($payment['cc_number']),
            'currency_id' => $cart->id_currency,
            'order_total' => (float)Tools::ps_round($orderTotal, 2),
            'commission' => $commission,
            'fee' => $fee,
            'grand_total' => $totalWithFee,
            'installment' => $installment,
            'order_currency' => $currency['iso_code'],
            'payment_currency' => null,
            'exchange_rate' => 0,
            'created' => date('Y-m-d H:i:s'),
        ));
        $last_insert_id = Db::getInstance()->Insert_ID();
        
        //$this->module->validateOrder($cart->id, Configuration::get('PS_OS_HEPSIPAY_PENDING'), $orderTotal, $this->module->name, NULL, [], (int)$currency['id'], false, $customer->secure_key);
        
        //$currency = $this->context->currency;
        $invoice = new Address((int)$cart->id_address_invoice);
        
        $payment['total'] = $totalWithFee;
        $payment['currency'] = $currency['iso_code'];
        $payment['customer_firstname'] =  $this->context->customer->firstname;
        $payment['customer_lastname'] =  $this->context->customer->lastname;
        $payment['customer_email'] =  $this->context->customer->email;
        $payment['customer_phone'] =  ($invoice->phone_mobile) ? $invoice->phone_mobile : $invoice->phone;
        
        $payment['payment_title'] = strtr('Hepsipay Payment: {firstName} {lastName}. Total {total} {currency}', [
            '{firstName}' => $payment['customer_firstname'],
            '{lastName}' => $payment['customer_lastname'],
            '{total}' => $payment['total'],
            '{currency}' => $payment['currency'],
        ]);
        
        if($payment['use3d'] === 1) {
            $message = $this->module->l('Awaiting 3D secure response');
            $this->_placeOrder(Configuration::get('PS_OS_HEPSIPAY_PENDING'), $orderTotal, $message, ['transaction_id'=>$payment['transaction_id']]);
            $returnUrl = $this->context->link->getModuleLink('hepsipay', 'process3D', [], true);
            $payment['return_url'] = $returnUrl;
        }
        $payment['passive_data'] = json_encode([
            'bin' => $bin,
            'commission' => $commission,
            'fee' => $fee,
            'logId' => $last_insert_id,
            'cartId' => $cart->id,
            'orderId' => $this->module->currentOrder,
            'orderTotal' => $orderTotal,
            'currencyId' => $cart->id_currency,
        ]);
        return $payment;
    }

    protected function displayError($message, $description = false)
    {
        /**
         * Create the breadcrumb for your ModuleFrontController.
         */
        $this->context->smarty->assign('path', '
			<a href="'.$this->context->link->getPageLink('order', null, null, 'step=3').'">'.$this->module->l('Payment').'</a>
			<span class="navigation-pipe">&gt;</span>'.$this->module->l('Error'));

        /**
         * Set error message and description for the template.
         */
        array_push($this->errors, $this->module->l($message), $description);

        return $this->setTemplate('error.tpl');
    }
    
    protected function _placeOrder($status, $total, $message, $extra=[])
    {
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $currency = $cart->id_currency;
        return $this->module->validateOrder($cart->id, $status, $total, $this->module->displayName, $message, $extra, $currency, false, $customer->secure_key);
    }
    
    protected function _redirectToConfirmation()
    {
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $key = $customer->secure_key;
        $orderId = $this->module->currentOrder;
        $returnUrl = $this->context->link->getModuleLink('hepsipay', 'confirmation', ['order_id'=>$orderId, 'secure_key'=>$key], true);
        Tools::redirect($returnUrl);
        exit;
        //Tools::redirect('index.php?controller=order-detail&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }
}
