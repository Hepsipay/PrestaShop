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
        
        try {
            $response = $_POST;
            
            if(HepsipayApi::processPayReaponse($this->module, $response, true)) {
                
            }
            $this->_redirectToConfirmation($order, $message);
            exit;
        } catch (Exception $ex) {
            if($log_id) {
                Db::getInstance()->update('hespipay_transaction', ['status' => 0], 'id = ' . $log_id);
            }
            $this->context->smarty->assign('path', '
                <a href="'.$this->context->link->getPageLink('order', null, null, 'step=3').'">'.$this->module->l('Payment').'</a>
                <span class="navigation-pipe">&gt;</span>'.$this->module->l('Error')
            );

            $this->context->smarty->assign(array(
                'code' => $ex->getCode(),
                'title' => $ex->getMessage(),
                'tranaction' => $trix,
            ));
            
            return $this->setTemplate('error.tpl');
        }
    }
    
    protected function _redirectToConfirmation($order, $message)
    {
        $customer = new Customer($order->id_customer);
        $key = $customer->secure_key;
        $params = [
            'order_id'=>$order->id,
            'secure_key'=>$key,
            'message'=>$message,
        ];
        $returnUrl = $this->context->link->getModuleLink('hepsipay', 'confirmation', $params, true);
        Tools::redirect($returnUrl);
        
        //Tools::redirect('index.php?controller=order-confirmation&id_cart='.$order->id_cart.'&id_module='.$this->module->id.'&id_order='.$order->id_order.'&key='.$customer->secure_key);
    }
}
