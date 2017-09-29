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

class HepsipayConfirmModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        /*if ((Tools::isSubmit('cart_id') == false) || (Tools::isSubmit('secure_key') == false)) {
            return false;
        }*/

        //$cart_id = Tools::getValue('cart_id');
        $success = Tools::getValue('success', false);
        $cartId = Tools::getValue('cartId', false);
        $orderId = Tools::getValue('orderId');
        $orderTotal = Tools::getValue('orderTotal');
        $orderCurrencyId = Tools::getValue('orderCurrencyId');
        $exchangeRate = Tools::getValue('exchangeRate');
        $paidTotal = Tools::getValue('paidTotal');
        $paidCurrency = Tools::getValue('paidCurrency');
        $installment = Tools::getValue('installment');
        $transaction = Tools::getValue('transaction');
        $fee = Tools::getValue('fee');
        $code = Tools::getValue('code');
        $message = Tools::getValue('message', $this->module->l('Unexpected error occurred while processing payment transaction'));
        
        $order = new Order((int)$orderId);
        $customer = new Customer((int)$order->id_customer);
        $currency = new Currency($orderCurrencyId);
        $module_name = $this->module->displayName;
        $secure_key = $customer->secure_key;
        $module_id = $this->module->id;

        //$cart = new Cart((int)$cart_id);

        $id_order_state = (int)$order->getCurrentState();
        $order_status = new OrderState((int)$id_order_state, (int)$order->id_lang);      
        //$status = ($id_order_state == Configuration::get('PS_OS_PAYMENT')) ? 'success' : 'danger';
        $status = boolval($success) ? 'success' : 'danger';
        
        $this->context->smarty->assign(array(
            'status' => $status,
            'message' => $message,
            'shop_name' => strval(Configuration::get('PS_SHOP_NAME')),
            'order' => $order,
            'currency' => $currency,
            'order_state' => $id_order_state,
            'transaction' => $transaction,
            'orderTotal' => $orderTotal,
            'orderCurrency' => $currency->iso_code,
            'exchangeRate' => $exchangeRate,
            'paidTotal' => $paidTotal,
            'paidCurrency' => $paidCurrency,
            'installment' => $installment,
            'fee' => $fee,
            'orderDetailLink' => 'index.php?controller=order-detail&id_order='.$order->id
        ));
        //Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cartId.'&id_module='.$module_id.'&id_order='.$orderId.'&key='.$secure_key);
        //return $this->setTemplate('confirmation.tpl');
        return $this->setTemplate('module:hepsipay/views/templates/front/confirm.tpl');
    }
}
