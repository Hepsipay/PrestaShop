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

class HepsipayConfirmationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        /*if ((Tools::isSubmit('cart_id') == false) || (Tools::isSubmit('secure_key') == false)) {
            return false;
        }*/

        //$cart_id = Tools::getValue('cart_id');
        $order_id = Tools::getValue('order_id');
        $secure_key = Tools::getValue('secure_key');
        $currency = Context::getContext()->currency;
        $module_name = $this->module->displayName;

        //$cart = new Cart((int)$cart_id);
        $order = new Order((int)$order_id);
        $customer = new Customer((int)$order->id_customer);

        $id_order_state = (int)$order->getCurrentState();
        $order_status = new OrderState((int)$id_order_state, (int)$order->id_lang);      
        $status = ($id_order_state == Configuration::get('PS_OS_PAYMENT')) ? 'success' : 'danger';
        $message = Tools::getValue('message', $order->getFirstMessage());
        
        if ($order->id && ($secure_key == $customer->secure_key)) {
            $this->context->smarty->assign(array(
                'status' => $status,
                'message' => $message,
                'shop_name' => strval(Configuration::get('PS_SHOP_NAME')),
                'order' => $order,
                'currency' => $currency,
                'order_state' => $id_order_state,
                'transaction' => '12345678',
                'orderDetailLink' => 'index.php?controller=order-detail&id_order='.$order->id
            ));
            //$module_id = $this->module->id;
            //Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart_id.'&id_module='.$module_id.'&id_order='.$order_id.'&key='.$secure_key);
            return $this->setTemplate('confirmation.tpl');
        } else {
            /**
             * An error occured and is shown on a new page.
             */
            $this->errors[] = $this->module->l('An error occured. Please contact the merchant to have more informations');
            return $this->setTemplate('error.tpl');
        }
    }
}
