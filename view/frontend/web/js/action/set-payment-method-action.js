/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        return function (messageContainer) {
          var process_payment_url = window.checkoutConfig.payment.forumpay.redirect_url;
          var bodyLoader = $('body').loader();
          bodyLoader.loader('show');
          var selectedCurrency = $('select[data-id="buyer-cryptocurrency-list"]').val();
          var data = {
            'action': 'forumpay_start_payment',
            'payment_cryptocurrency':selectedCurrency
          };
          $.ajax({
            url: process_payment_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (response){
              if(typeof response != "undefined" && typeof response.html != "undefined" && response.html && response.status){
                $('body').addClass('open_payment_window');
                $('#process_payment_window').remove();
                $('#checkout').after('<div id="process_payment_window" class="process_payment_window"></div>');
                $('#process_payment_window').html(response.html);
                bodyLoader.loader('hide');
              }else{
                $.mage.redirect(process_payment_url);
              }
            }
          });
        };
    }
);