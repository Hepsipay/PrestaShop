<?php

require_once dirname(__FILE__).'/../../classes/HepsipayApi.php';

class HepsipayProcess3DModuleFrontController extends ModuleFrontController
{
    protected  $_portal = null;
    
    public function initContent()
    {
        $trix = null;
        $last_inserted_id = null;
        $code = Tools::getValue('ErrorCode');
        $trix = Tools::getValue('transaction_id');
        $status = intval(Tools::getValue('status', 0));
        $message = Tools::getValue('ErrorMSG', $this->module->l('Unexpected error occurred while processing payment transaction'));
        
        $passiveData = Tools::getValue('passive_data', null);
        $paymentInfo = json_decode($passiveData, true);
        $log_id = isset($paymentInfo['logId']) ? $paymentInfo['logId'] : null;
        $order_id = isset($paymentInfo['orderId']) ? $paymentInfo['orderId'] : 0;
        $order = new Order($order_id);
        
        $response = $_POST;

        $confirmData = HepsipayApi::processPayReaponse($this->module, $response, true);
        $redirect = $this->context->link->getModuleLink('hepsipay', 'confirm', [], true);
        $form = HepsipayApi::renderAutoSubmitForm($redirect, $confirmData);
        echo $form;
        exit;
    }
}
