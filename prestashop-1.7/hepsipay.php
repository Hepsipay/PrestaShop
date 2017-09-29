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

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Hepsipay extends PaymentModule
{
    protected $config_form = false;
    
    public $apikey;
	public $secret;
	public $endpoint;
	public $force3D;
	public $enable3D;
	public $force3DForDebit;
	public $enableInstallment;
	public $merchantPayFees;
	public $customCss;

    public function __construct()
    {
        $this->name = 'hepsipay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Hepsipay';
        $this->need_instance = 0;
        $this->is_eu_compatible = 1;
        
        $this->loadConfigValues();
        $this->module_key = '6a536418645deb9550e8ac06bda40900';
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Hepsipay Payment Gateway');
        $this->description = $this->l('Hepsipay Payment Gateway');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module and delete all settings associated with it?');

        //$this->limited_countries = array('FR');

        //$this->limited_currencies = array('EUR');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        Configuration::updateValue('HEPSIPAY_LIVE_MODE', false);

        $result = parent::install() &&
            //$this->registerHook('header') &&
            $this->registerHook('displayOrderDetail') &&
            //$this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            //$this->registerHook('paymentReturn') &&
            //$this->registerHook('actionPaymentCCAdd') &&
            //$this->registerHook('actionPaymentConfirmation') &&
            //$this->registerHook('displayPayment') &&
            //$this->registerHook('displayPaymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->installOrderState() &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            //$this->registerHook('displayPaymentTop')
            true
        ;
        return $result && $this->installDatabase();
    }

    public function uninstall()
    {
        Configuration::deleteByName('HEPSIPAY_LIVE_MODE');
        
        /*$configMap = $this->getConfigMap();
        foreach (array_keys($configMap) as $key) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }*/
        
        Configuration::deleteByName('PS_OS_HEPSIPAY_PENDING');
        return parent::uninstall();
    }
    
    protected function installDatabase()
    {
        $result = Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'hespipay_transaction` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `status` int(11) unsigned NULL,
                `cart_id` int(11) unsigned NOT NULL,
                `order_id` int(11) unsigned NULL,
                `card_holder` varchar(100) NULL,
                `crard_mask` varchar(30) NULL,
                `transaction_id` varchar(255) NULL,
                `currency_id` double NOT NULL,
                `order_currency` varchar(3) NOT NULL,
                `payment_currency` varchar(3) NOT NULL,
                `exchange_rate` double NOT NULL,
                `order_total` double NOT NULL,
                `commission` double NOT NULL,
                `fee` double NOT NULL,
                `grand_total` double NOT NULL,
                `installment` int(11) unsigned NOT NULL,
                `response_code` varchar(10) NULL,
                `response_message` varchar(255) NULL,
                `created` datetime DEFAULT NULL,
                `updated` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE= ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8
        ');
        
        return $result;
    }

	protected function installOrderState()
	{
		if (Configuration::get('PS_OS_HEPSIPAY_PENDING') < 1)
		{
			$order_state = new OrderState();
			$order_state->send_email = false;
			$order_state->module_name = $this->name;
			$order_state->invoice = false;
			$order_state->color = '#f99521';
			$order_state->logable = false;
			$order_state->shipped = false;
			$order_state->unremovable = false;
			$order_state->delivery = false;
			$order_state->hidden = false;
			$order_state->paid = false;
			$order_state->deleted = false;
			$order_state->name = array((int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('Hepsipay: Pending Payment')));
			$order_state->template = array();
            
            // I do not know what is this: <<< begin
			foreach (LanguageCore::getLanguages() as $l)
				$order_state->template[$l['id_lang']] = 'hepsipay';

			/*// We copy the mails templates in mail directory
			foreach (LanguageCore::getLanguages() as $l)
			{
				$module_path = dirname(__FILE__).'/views/templates/mails/'.$l['iso_code'].'/';
				$application_path = dirname(__FILE__).'/../../mails/'.$l['iso_code'].'/';
				if (!copy($module_path.'hepsipay.txt', $application_path.'hepsipay.txt') ||
					!copy($module_path.'hepsipay.html', $application_path.'hepsipay.html'))
					return false;
			}*/
            // <<< end

			if ($order_state->add())
			{
				// We save the order State ID in Configuration database
				Configuration::updateValue('PS_OS_HEPSIPAY_PENDING', $order_state->id);
                
                /*// I do not know what is this: <<< begin
				// We copy the module logo in order state logo directory
				copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/os/'.$order_state->id.'.gif');
				copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/tmp/order_state_mini_'.$order_state->id.'.gif');
                // <<< end
                */
			}
			else {
				return false;
            }
		}
		return true;
	}
        
    protected function getConfigMap()
    {
        return array(
            'HEPSIPAY_APIKEY' => 'apikey',
            'HEPSIPAY_SECRET' => 'secret',
            'HEPSIPAY_ENDPOINT' => 'endpoint',
            'HEPSIPAY_FORCE_3D_CHECK' => 'force3D',
            'HEPSIPAY_ENABLE_3D_CHECK' => 'enable3D',
            'HEPSIPAY_FORCE_3D_DEBIT_CHECK' => 'force3DForDebit',
            'HEPSIPAY_ENABLE_INSTALLMENT_CHECK' => 'enableInstallment',
            'HEPSIPAY_MERCHANT_PAY_FEES_CHECK' => 'merchantPayFees',
            'HEPSIPAY_CUSTOM_CSS' => 'customCss',
        );
    }

    public function loadConfigValues()
    {
        $configMap = $this->getConfigMap();
        $config = Configuration::getMultiple(array_keys($configMap));
        foreach ($config as $key=>$value) {
                $this->{$configMap[$key]} = $value;
        }
        return $this->merchantPayFees;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitHepsipayModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = '';//$this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }
    
    public function getModulePath()
    {
        return $this->_path;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitHepsipayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected static function getAddresses($domain) {
        $records = @dns_get_record($domain);
        $records = ($records)?$records:[];
        $res = array();
        foreach ($records as $r) {
            if ($r['host'] != $domain) continue; // glue entry
            if (!isset($r['type'])) continue; // DNSSec

            if ($r['type'] == 'A') $res['ip'] = $r['ip'];
            if ($r['type'] == 'AAAA') $res['ipv6'] = $r['ipv6'];
        }
        return $res;
    }

    protected static function getAddresses_www($domain) {
        $res = self::getAddresses($domain);
        if (count($res) == 0) {
            $res = self::getAddresses('www.' . $domain);
            if (count($res) == 0) {
                $res = $domain;
            }
        }
        return $res;
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $serverIP = self::getAddresses_www($_SERVER['SERVER_NAME']);
        $serverIP = isset($serverIP['ip'])?$serverIP['ip']:$serverIP;

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Hepsipay Module Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'html',
                        'label' => $this->l('Sunucunuzun IP\'si').': <strong>'.$serverIP.'</strong>',
                        'html_content' => '',
                    ),
					array(
						'type' => 'text',
						'label' => $this->l('Endpoint'),
						'name' => 'HEPSIPAY_ENDPOINT',
						'required' => true,
                        'disabled'  => true,
					),
                    array(
						'type' => 'text',
						'label' => $this->l('API Key'),
						'name' => 'HEPSIPAY_APIKEY',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Secret'),
						'name' => 'HEPSIPAY_SECRET',
						'required' => true
					),
					array(
						'type' => 'checkbox',
						//'label' => $this->l('Enable Installment'),
						'name' => 'HEPSIPAY_ENABLE_INSTALLMENT',
                        'values' => array(
                            'query' => array(
                                array(
                                    'id' => 'CHECK',
                                    'name' => $this->l('Enable installment options in the  checkout page.'),
                                    'val' => '1'
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
					),
					array(
						'type' => 'checkbox',
						//'label' => $this->l('Enable 3D'),
						'name' => 'HEPSIPAY_ENABLE_3D',
                        'values' => array(
                            'query' => array(
                                array(
                                    'id' => 'CHECK',
                                    'name' => $this->l('Enable "3D Secure" as an option in the checkout page.'),
                                    'val' => '1'
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
					),
					array(
						'type' => 'checkbox',
						//'label' => $this->l('Force 3D'),
						'name' => 'HEPSIPAY_FORCE_3D',
                        'values' => array(
                            'query' => array(
                                array(
                                    'id' => 'CHECK',
                                    'name' => $this->l('Forec 3D for all transctions'),
                                    'val' => '1'
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
					),
					array(
						'type' => 'checkbox',
						//'label' => $this->l('Force 3D for Debit'),
						'name' => 'HEPSIPAY_FORCE_3D_DEBIT',
                        'desc' => $this->l('If 3D secure option is mandatory in Hepsipay side, this option must be enable. Otherwise your transactions will fail.'),
                        'values' => array(
                            'query' => array(
                                array(
                                    'id' => 'CHECK',
                                    'name' => $this->l('Force 3D for debit cards.'),
                                    'val' => '1'
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
					),
					array(
						'type' => 'checkbox',
						'name' => 'HEPSIPAY_MERCHANT_PAY_FEES',
						'desc' => $this->l('If not checked, the transaction fee will be added to the order\'s total so the customer will be charged for the fees.'),
                        'values' => array(
                            'query' => array(
                                array(
                                    'id' => 'CHECK',
                                    'name' => $this->l('Merchant Pay Fees.'),
                                    'val' => '1'
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
					),
					array(
						'type' => 'textarea',
						'label' => $this->l('Custom CSS'),
						'name' => 'HEPSIPAY_CUSTOM_CSS',
						'desc' => $this->l('Put your CSS here to make customization to the checkout form.'),
					),
                    array(
                        'type' => 'html',
                        'label' => '',
                        'html_content' => '
                            <script>
                                document.getElementById("HEPSIPAY_FORCE_3D_DEBIT_CHECK").checked  = "checked";
                                document.getElementById("HEPSIPAY_FORCE_3D_DEBIT_CHECK").disabled = "disabled";
                            </script>',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $fields = array();
        $configMap = $this->getConfigMap();
        foreach (array_keys($configMap) as $key) {
            $fields[$key] = Tools::getValue($key, Configuration::get($key));
        }
        $fields['HEPSIPAY_ENDPOINT']       = 'https://pluginmanager.hepsipay.com/portal/web/api/v1';
        $fields['HEPSIPAY_FORCE_3D_DEBIT_CHECK'] = true;
		return $fields;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        $controllerArray = array('order');
        if (in_array($this->context->controller->php_self, $controllerArray)) {
            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-style',
                'modules/' . $this->name . '/views/css/front.css',
                array(
                    'media' => 'all',
                    'priority' => 200,
                )
            );

            $this->context->controller->registerJavascript(
                'module-wcs-simple-lib',
                'modules/'.$this->name.'/views/js/front.js',
                array(
                    'priority' => 202,
                    'attribute' => 'async',
                )
            );
        }
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        /*if (in_array($currency->iso_code, $this->limited_currencies) == false) {
            return false;
        }*/

        $this->smarty->assign('module_dir', $this->_path);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    /*public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR'))
            $this->smarty->assign('status', 'ok');

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }*/

	/*public function hookDisplayPayment($params)
	{
        //echo "hookDisplayPayment ::::::::: ";
        //var_dump($params);exit;
		$controller = $this->getHookController('displayPayment');
		return $controller->run($params);
	}*/
    
    public function hookDisplayOrderDetail($params)
    {
        $order = $params['order'];
        $paymentModule = $order->module;
        if($paymentModule !== $this->name) {
            return;
        }
        $table = _DB_PREFIX_.'hespipay_transaction';
        $sql = "SELECT * FROM `$table` WHERE order_id = {$order->id}";
        $row = Db::getInstance()->getRow("SELECT * FROM `$table` WHERE order_id = {$order->id} ORDER BY id DESC");
        if($row) {
            $status = intval($row['status'])==1 ? 'success' : 'danger';
            $result = intval($row['status'])==1 ? $this->l('Succeeded') : $this->l('Failed');
            $html = [
                //"<h1 class=\"page-heading\">".$this->l("Payment Detials")."</h1>",
                //"<div class=\"\">",
                "<table class=\"table table-bordered footab\">",
                "<tr><td><strong class=\"dark\">".$this->l("Payment Method").":</strong></td><td> {$order->payment}</td></tr>",
                "<tr><td><strong class=\"dark\">".$this->l("Transaction").":</strong></td><td> {$row['transaction_id']}</td></tr>",
                "<tr><td><strong class=\"dark\">".$this->l("Installment").":</strong></td><td> {$row['installment']}</td></tr>",
                "<tr><td><strong class=\"dark\">".$this->l("Fees").":</strong></td><td> {$row['fee']} {$row['order_currency']}</td></tr>",
                "<tr><td><strong class=\"dark\">".$this->l("Order Total").":</strong></td><td> {$row['order_total']} {$row['order_currency']}</td></tr>",
                "<tr><td><strong class=\"dark\">".$this->l("Grand Total").":</strong></td><td> {$row['grand_total']} {$row['order_currency']}</td></tr>",
                //"<tr><td><strong class=\"dark\">".$this->l("Exchange Rate").":</strong></td><td> {$row['exchange_rate']}</td></tr>",
                "<tr><td><strong class=\"dark\">".$this->l("Status").":</strong></td><td> <span class=\"btn btn-sm btn-$status\">{$result}</span></td></tr>",
                "</table>",
                //"</div>",
            ];
            return implode("\n", $html);
        }
//        $cart = $params['cart'];
//        $carrier = $params['carrier'];
//        var_dump($params['cart']);
//        die ("<h1>Hi from Hepsipay::hookActionOrderDetail(</h1>");
    }
    
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        
        $currency = Currency::getCurrency($this->context->cart->id_currency);
        $action = $this->context->link->getModuleLink('hepsipay', 'payment', [], true);
        $ajaxUrl = $this->context->link->getModuleLink('hepsipay', 'ajax', [], true);
        $orderTotal = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        
        $this->context->smarty->assign(
            array(
                'action' => $action,
                'currencyCode' => isset($currency['sign']) ? $currency['sign'] : $currency['iso_code'],
                'orderTotal' => $orderTotal,
                'baseUri' => $this->getPathUri(),
                'force3D' => $this->force3D,
                'enable3D' => $this->enable3D,
                'customCss' => $this->customCss,
                'jsConfig' => [
                    'ajaxUrl' =>  $ajaxUrl,
                    'baseUrl' =>  $this->getPathUri(),
                    'orderTotal' => $orderTotal,
                    'installment' => 1,
                    'merchantPayFees' =>  $this->merchantPayFees,
                ]
            )
        );
        $paymentForm = $this->context->smarty->fetch('module:hepsipay/views/templates/hook/form.tpl');
        
        $payment = new PaymentOption();
        $payment->setLogo(_MODULE_DIR_.'hepsipay/logo-small.png')
            ->setCallToActionText($this->l('Hepsipay Payment Gateway'))
            //->setAdditionalInformation('Pay securelly with any bank supported in TURKEY.')
            //->setAction($this->context->link->getModuleLink($this->name, 'payment'))
            ->setAction($action)
            ->setBinary(true)
            ->setForm($paymentForm)
        ;
        return [$payment];
    }
}

