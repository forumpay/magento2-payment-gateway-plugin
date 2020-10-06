/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
  "ko",
  "jquery",
  "Magento_Checkout/js/view/payment/default",
  "Limitlex_ForumPay/js/action/set-payment-method-action",
], function (ko, $, Component, setPaymentMethodAction) {
  "use strict";

  return Component.extend({
    defaults: {
      redirectAfterPlaceOrder: false,
      template: "Limitlex_ForumPay/payment/forumpay",
    },
    afterPlaceOrder: function () {
      setPaymentMethodAction(this.messageContainer);
      return false;
    },
    getData: function () {
      return {
        method: this.item.method,
        additional_data: {
          cryptocurrency: $("#cryptocurrency").val(),
          payment_gateway: "forumpay",
        },
      };
    },
    getInstructions: function () {
      return window.checkoutConfig.payment.forumpay.instructions;
    },
    getPaymentMethodMarkSrc: function () {
      return window.checkoutConfig.payment.forumpay.paymentmethodimage;
    },
    isImageVisible: function () {
      return window.checkoutConfig.payment.forumpay.isimagevisible;
    },
    getCryptoCurrencyList: function () {
      var cryptoCurrencyList =
        window.checkoutConfig.payment.forumpay.cryptoCurrencyList;
      cryptoCurrencyList = JSON.parse(cryptoCurrencyList);
      return cryptoCurrencyList;
    },
  });
});
