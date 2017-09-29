<div class="row">
	<div class="col-xs-12 col-md-6">
        <p class="payment_module paypal">
            <a href="{$link->getModuleLink('hepsipay', 'payment')|escape:'html'}" title="{l s='Pay securely with Hepsipay' mod='hepsipay'}">
                <img src="{$base_dir_ssl|escape:'htmlall':'UTF-8'}modules/hepsipay/logo.png" alt="" width="86" height="49"/>
                {l s='Pay securely with Hepsipay.' mod='hepsipay'}<br/>
                {*<span>({l s='Pay securely with any card with variety options of installment plans.' mod='hepsipay'})</span>*}
			</a>
		</p>
    </div>
</div>