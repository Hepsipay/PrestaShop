{*
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
*}
{extends file='page.tpl'}
{block name='content'}
    <section id="content">
        <div class="hepsipay_payment_confirm alert alert-{$status}">
            <h3>{$message}</h3>
        </div>
        <div class="card-block" style="background:#fff">
            <h3 class="card-title h3">{l s='Transaction Info' mod='hepsipay'}</h3>
            <table class="table table-striped table-bordered table-labeled">
                <tr>
                    <th>{l s='Transaction ID' mod='hepsipay'}</th>
                    <td>{$transaction}</td>
                </tr>
                <tr>
                    <th>{l s='Order Total' mod='hepsipay'}</th>
                    <td>{$orderTotal} {$orderCurrency}</td>
                </tr>
                <tr>
                    <th>{l s='Paid Amount' mod='hepsipay'}</th>
                    <td>{$paidTotal} {$paidCurrency}</td>
                </tr>
                <tr>
                    <th>{l s='Exchange Rate' mod='hepsipay'}</th>
                    <td>{$exchangeRate}</td>
                </tr>
                <tr>
                    <th>{l s='Installment' mod='hepsipay'}</th>
                    <td>{$installment}</td>
                </tr>
                <tr>
                    <th>{l s='Payment Fee' mod='hepsipay'}</th>
                    <td>{$fee}</td>
                </tr>
            </table>
                
            <div>
                <p class="" id="">
                    <a class="btn btn-default" href="{$orderDetailLink}">
                        <span>{l s='Order Details' mod='hepsipay'}</span>
                    </a>
                </p>
            </div>
        </div>
                    
        <p><br/></p>

    </section>
{/block}