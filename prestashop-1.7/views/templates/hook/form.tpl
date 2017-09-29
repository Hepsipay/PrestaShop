<form action="{$action|escape:'htmlall':'UTF-8'}" id="hepsipay_payment_form" class="payment_form_hepsipay" method="post"
    data-config="{$jsConfig|@json_encode}"
>
    <div class="checkout_form">
        <div class="form-group">
            <label class="control-label" for="paymentCardHolder">{l s='Card Holder' mod='hepsipay'}</label>
            <input name="paymentCardHolder" type="text" id="payment_card_holder" class="form-control" value="" tabindex="1" />
            <div class="help-block"></div>
        </div>

        <div class="form-group">
            <label class="control-label" for="card_number">{l s='Card Number' mod='hepsipay'}</label>
            <div id="payment_card_number_wrap">
                <input name="paymentCardNumber" type="text" id="payment_card_number" class="form-control"  maxlength="18" value="" tabindex="2"/>
                <img id="payment_card_number_loading" src="{$baseUri}/views/img/loading.gif" alt="{l s='loading...' mod='hepsipay'}">
            </div>
            <div class="help-block"></div>
            <div class="payment-images" id="payment_card_images">
                <img id="payment_card_img_brand" src="" alt="">
                <img id="payment_card_img_bank" src="" alt="">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label" for="card_year">{l s='Expiration Date' mod='hepsipay'}</label>
            <div>
                <div class="card-date-select">
                    <select class="form-control" name="paymentCardMonth" id="payment_card_month"  tabindex="4">
                        {for $var=1 to 12}
                            <option value="{$var}">{$var}</option>
                        {/for}
                    </select>
                </div>
                <div class="card-date-select">
                    <select class="form-control" name="paymentCardYear" id="payment_card_year" tabindex="5" >
                        {for $var=date('Y') to date('Y')+13}
                            <option value="{$var}">{$var}</option>
                        {/for}
                    </select>
                </div>
            </div>
            <input name="paymentExpiryDate" type="hidden" />
            <div class="help-block"></div>
        </div>

        <div class="form-group">
            <label class="control-label" for="card_cvc">{l s='CVC' mod='hepsipay'}</label>
            <input name="paymentCardCVC" type="text" id="payment_card_cvc" maxlength="4" class="form-control"  value="" tabindex="3" />
            <div class="help-block"></div>
        </div>

        <div class="form-group" id="installment_table_id">
            <div class="installmet_head">
                <div class="install_head_label add_space"></div>
                <div class="install_head_label">{l s='Installment' mod='hepsipay'}</div>
                <div class="install_head_label">{l s='Amount / Month' mod='hepsipay'}</div>
                <div class="install_head_label">{l s='Total' mod='hepsipay'} ({$currencyCode|escape:'html':'UTF-8'})</div>
            </div>
            <div class="installment_body" id="installment_body">
            </div>
            <div class="installment_footer"></div>
        </div>

        {if $force3D || $enable3D}
            <div class="form-group">
                <label for="payment_use3d" class="{if $force3D}alert alert-success{/if}" style="{if $force3D}padding:5px{/if}">
                    <input name="paymentUse3D" type="{if $force3D}hidden{else}checkbox{/if}" id="payment_use3d" value="1" title="{l s='Pay with 3D Secure' mod='hepsipay'}" {if $force3D}checked{/if} {if $force3D}disabled{/if} tabindex="6"/>
                    {*<input name="paymentUse3D" type="checkbox" id="payment_use3d" value="1" title="{l s='Pay with 3D Secure' mod='hepsipay'}" {if $force3D || (isset($paymentUse3D) && $paymentUse3D)}checked="checked"{/if} {if $force3D}disabled="disabled"{/if} tabindex="6"/>*}
                    {l s='Pay with 3D Secure' mod='hepsipay'}
                </label>
            </div>
        {/if}

        <input type="hidden" id="payment_installment" name="paymentInstallment" value="1" /> 
    </div>
            
    <div class="form-group general-error" style="display: none">
        <div class="alert alert-danger" role="alert">
        </div>
    </div>
    <div class="form-group">
        <input class="btn btn-primary center-block" type="submit" id="hepsipay_payment_form_submit_btn" name="paymentConfirmOrder" disabled="disabled" value="{l s='Order with obligation to pay' mod='hepsipay'}">
        <img class="processing-payment" style="display:none" src="{$baseUri}/views/img/loading.gif" alt="{l s='Processing payment. Please wait...' mod='hepsipay'}" >
    </div>
</form>
