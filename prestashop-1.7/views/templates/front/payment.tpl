{capture name=path}
    {l s='Hepsipay' mod='hepsipay'}
{/capture}


<h1 class="page-heading">
{l s='Order summary' mod='hepsipay'}
</h1>

{assign var='current_step' value='payment'}
{*{include file="$tpl_dir./order-steps.tpl"}*}

{if isset($customCss)}<style type="text/css"> {$customCss} </style>{/if}

<div class="col-md-12">
    <div class="front panel hepsipay_payment_module" id="hepsipay_payment_module">
        {if isset($paymentFailed)}
            <div class="hepsipay_payment_error has-error">
                <h4>{$paymentFailed}</h4>
                <p>
                    {if isset($transactionId) }
                        {l s='Transaction ID:' mod='hepsipay'} : <strong>{$transactionId}</strong>
                    {/if}
                </p>
            </div>
        {/if}
        <form action="{$link->getModuleLink('hepsipay', 'payment', [], true)|escape:'html'}" method="post">
            <div class="checkout_form">
                {if isset($paymentErrors['paymentCardHolder'])}{$errorClass='form-error'}{else}{$errorClass=''}{/if}
                {if isset($paymentErrors['paymentCardHolder'])}{$errorMessage=$paymentErrors['paymentCardHolder']}{else}{$errorClass=''}{/if}
                <div class="form-group {$errorClass}">
                    <label class="control-label" for="paymentCardHolder">{l s='Card Holder' mod='hepsipay'}</label>
                    <input name="paymentCardHolder" type="text" id="payment_card_holder" class="form-control" value="{if isset($paymentCardHolder)}{$paymentCardHolder|escape:'html':'UTF-8'}{/if}" tabindex="1" />
                    {if isset($errorMessage)}
                        <div for="payment_card_holder" class="help-block">{$errorMessage}</div>
                    {/if}
                </div>

                {if isset($paymentErrors['paymentCardNumber'])}{$errorClass='form-error'}{else}{$errorClass=''}{/if}
                {if isset($paymentErrors['paymentCardNumber'])}{$errorMessage=$paymentErrors['paymentCardNumber']}{else}{$errorClass=''}{/if}
                <div class="form-group {$errorClass}">
                    <label class="control-label" for="card_number">{l s='Card Number' mod='hepsipay'}</label>
                    <div id="payment_card_number_wrap">
                        <input name="paymentCardNumber" type="text" id="payment_card_number" class="form-control"  maxlength="18" value="{if isset($paymentCardNumber)}{$paymentCardNumber|escape:'html':'UTF-8'}{/if}" tabindex="2"/>
                        <img id="payment_card_number_loading" src="{$baseUri}/views/img/loading.gif" alt="{l s='loading...' mod='hepsipay'}">
                    </div>
                    {if isset($errorMessage)}
                        <div for="payment_card_holder" class="help-block">{$errorMessage}</div>
                    {/if}
                </div>
                <div class="payment-images" id="payment_card_images">
                    <img id="payment_card_img_brand" src="" alt="">
                    <img id="payment_card_img_bank" src="" alt="">
                </div>

                {if !isset($paymentErrors['paymentCardMonth'], $paymentErrors['paymentCardMonth'])}{$errorClass=''}{else}{$errorClass='form-error'}{/if}
                <div class="form-group {$errorClass}">
                    <label class="control-label" for="card_year">{l s='Expiration Date' mod='hepsipay'}</label>
                    <div>
                        <div class="card-date-select">
                            <select class="form-control" name="paymentCardMonth" id="payment_card_month"  tabindex="4">
                                {for $var=1 to 12}
                                    <option {if $var==$paymentCardMonth}selected{/if} value="{$var}">{$var}</option>
                                {/for}
                            </select>
                        </div>
                        <div class="card-date-select">
                            <select class="form-control" name="paymentCardYear" id="payment_card_year" tabindex="5" >
                                {for $var=date('Y') to date('Y')+13}
                                    <option {if $var==$paymentCardYear}selected{/if} value="{$var}">{$var}</option>
                                {/for}
                            </select>
                        </div>
                    </div>
                    {if isset($paymentErrors['paymentCardMonth'])}
                        <div for="email" class="help-block">{$paymentErrors['paymentCardMonth']}</div>
                    {/if}
                    {if isset($paymentErrors['paymentCardYear'])}
                        <div for="email" class="help-block">{$paymentErrors['paymentCardYear']}</div>
                    {/if}
                </div>

                {if isset($paymentErrors['paymentCardCVC'])}{$errorClass='form-error'}{else}{$errorClass=''}{/if}
                {if isset($paymentErrors['paymentCardCVC'])}{$errorMessage=$paymentErrors['paymentCardCVC']}{else}{$errorClass=''}{/if}
                <div class="form-group {$errorClass}">
                    <label class="control-label" for="card_cvc">{l s='CVC' mod='hepsipay'}</label>
                    <input name="paymentCardCVC" type="text" id="payment_card_cvc" maxlength="4" class="form-control" value="{if isset($paymentCardCVC)}{$paymentCardCVC|escape:'html':'UTF-8'}{/if}" tabindex="3" />
                    {if isset($errorMessage)}
                        <div for="payment_card_holder" class="help-block">{$errorMessage}</div>
                    {/if}
                </div>

                <div class="form-group" id="installment_table_id">
                    <div class="installmet_head">
                        <div class="install_head_label add_space"><img style="display: none" class="bank_photo" data-src="<?php echo $bankImagesPath; ?>" src=""></div>
                        <div class="install_head_label">{l s='Installment' mod='hepsipay'}</div>
                        <div class="install_head_label">{l s='Amount / Month' mod='hepsipay'} ({$currencyCode|escape:'html':'UTF-8'})</div>
                        <div class="install_head_label">{l s='Total' mod='hepsipay'} ({$currencyCode|escape:'html':'UTF-8'})</div>
                    </div>
                    <div class="installment_body" id="installment_body">
                    </div>
                    <div class="installment_footer"></div>
                </div>

                {if $force3D || $enable3D}
                    <div class="form-group">
                        <label for="payment_use3d" class="{if $force3D}alert alert-success{/if}" style="{if $force3D}padding:5px{/if}">
                            <input name="paymentUse3D" type="{if $force3D}hidden{else}checkbox{/if}" id="payment_use3d" value="1" title="{l s='Pay with 3D Secure' mod='hepsipay'}" {if $force3D || (isset($paymentUse3D) && $paymentUse3D)}checked{/if} {if $force3D}disabled{/if} tabindex="6"/>
                            {*<input name="paymentUse3D" type="checkbox" id="payment_use3d" value="1" title="{l s='Pay with 3D Secure' mod='hepsipay'}" {if $force3D || (isset($paymentUse3D) && $paymentUse3D)}checked="checked"{/if} {if $force3D}disabled="disabled"{/if} tabindex="6"/>*}
                            {l s='Pay with 3D Secure' mod='hepsipay'}
                        </label>
                    </div>
                {/if}

                <input type="hidden" id="payment_installment" name="paymentInstallment" value="{if isset($paymentInstallment)} {$paymentInstallment} {else} 1 {/if}" />
            </div>
            <p class="cart_navigation clearfix" id="cart_navigation">
                <a class="button-exclusive btn btn-default"
                   href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                    <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='hepsipay'}
                </a>
                <button class="button btn btn-default button-medium" type="submit" name="paymentConfirmOrder">
                    <span>{l s='I confirm my order' mod='hepsipay'}<i class="icon-chevron-right right"></i></span>
                </button>
            </p>
        </form>
    </div>
</div>



<script>
    /*var $cc = document.getElementById('cc_number');
    $cc.addEventListener('keyup', ajaxTest, false);

    function ajaxTest(){
        var url = '{$link->getModuleLink('hepsipay', 'ajaxProcess', [], true)|escape:'html'}';
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("GET", url, true);
        xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xmlhttp.setRequestHeader("X_REQUESTED_WITH", "XMLHttpRequest");
        xmlhttp.send();

        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var response = JSON.parse(xmlhttp.responseText);
				alert(response.results);
                }
        };

    }*/

</script>
<script type="text/javascript">
    var orderTotal = {$orderTotal};
    var baseUrl = '{$baseUri}';
    var ajaxUrl = '{$link->getModuleLink('hepsipay', 'ajax', [], true)|escape:'html'}';
    var installment = {if isset($paymentInstallment)} {$paymentInstallment} {else} 1 {/if};
    var merchantPayFees = {if isset($merchantPayFees) && $merchantPayFees} true {else} false {/if};
    {literal}
    (function($) {

        $(document).ready(function(){
            hepsipayModule.run({
                ajaxUrl: ajaxUrl,
                baseUrl: baseUrl,
                installment: installment,
                merchantPayFees: merchantPayFees,
                orderTotal: orderTotal
            });
        });
    })(jQuery);
    {/literal}
</script>





