{*<div class="row">
	<div class="col-xs-12">
        <p class="payment_module">
			<a href="{$link->getModuleLink('hepsipay', 'payment')|escape:'html'}" class="hepsipay">
                {l s='Pay with Hepsipay Gateway.' mod='hepsipay'}
            </a>
        </p>
    </div>
</div>
*}

<div class="alert alert-info">
    <img src="../modules/hepsipay/logo.png" style="float:left; margin-right:15px;">
    <p><strong>{l s="Hepsipay Payment Gateway" mod='hepsipay'}</strong></p>
    <p>{l s="Pay securelly with any bank supported in TURKEY.'" mod='hepsipay'}</p>
</div>
