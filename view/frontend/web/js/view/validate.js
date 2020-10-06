define([
  "uiComponent",
  "Magento_Checkout/js/model/payment/additional-validators",
  "Limitlex_ForumPay/js/model/validate",
], function (Component, additionalValidators, orderBankValidation) {
  "use strict";
  additionalValidators.registerValidator(orderBankValidation);
  return Component.extend({});
});
