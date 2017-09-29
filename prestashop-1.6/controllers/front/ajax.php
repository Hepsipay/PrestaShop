<?php

require_once dirname(__FILE__).'/../../classes/HepsipayApi.php';

class HepsipayAjaxModuleFrontController extends ModuleFrontController
{
    protected  $_portal = null;
    
    public function initContent()
    {
        $cart = $this->context->cart;
        $language = LanguageCore::getLanguage((int)$cart->id_lang);
        $this->_portal = HepsipayApi::instance($this->module, $language['iso_code']);
        
        try {
            $this->passIfAjax();
            $command = Tools::getValue('command', '');
            $method = "processAjax".ucfirst($command);
            if(!method_exists($this, $method)) {
                throw new Exception('Invalid request.');
            }
            
            $result = $this->$method();  
            $this->sendJson($result);
            
        } catch (Exception $ex) {
            $this->sendError(400, 'Bad Request', $ex->getMessage());
        }   
        
        exit;
    }
    
    protected function processAjaxBin()
    {
        $bin = Tools::getValue('bin', null);
        if($bin) {
            $result = $this->_portal->bin($bin);
            if(isset($result['status']) && $result['status']===1) {
                $card = $result['card'];
                $installments = array('installments' => isset($result['data'][0]['installments']) ? $result['data'][0]['installments'] : null);
                $merge = array_merge($card, $installments);
                return $merge;
            }
            throw new Exception($result['ErrorMSG']);
        }
        return null;
    }
    
    protected function processAjaxIssuer()
    {
        $bin = Tools::getValue('bin', null);
        if($bin) {
            $result = $this->_portal->bin($bin);
            if(isset($result['status']) && $result['status']===1) {
                return $result['data'][0];
            }
            throw new Exception($result['ErrorMSG']);
        }
        return null;
    }

    protected function sendJson($response)
    {
        //header("HTTP/1.1 200 OK");
        //header("Content-type: application/json");
        
        echo Tools::jsonEncode($response);
        exit;
    }
    
    protected function sendError($code, $status, $message)
    {
        header("HTTP/1.1 $code $status");
        header("Content-type: text");
        
        echo $message;
        exit;
    }
    
    protected function passIfAjax()
    {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        throw new Exception("This is not an ajax request");
    }
}
