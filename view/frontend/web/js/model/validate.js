define(
	[
		'jquery',
		'Magento_Ui/js/modal/modal',
		'mage/url',
		'mage/validation'
	],
	function($, modal, url) {
		'use strict';
		return {
			validate: function() {
				var orderFlag = false;
				var cryptocurrencyEle = $('select[data-id="buyer-cryptocurrency-list"]');
				if ((cryptocurrencyEle).parents('.payment-method').hasClass('_active')) {
					var cryptocurrency = cryptocurrencyEle.val();
					cryptocurrencyEle.parents('._required').removeClass('_error');
					$('[data-id="error-cryptocurrency-empty"]').remove();
					if (cryptocurrency) {
						return true;
					} else {
						var error = '<div class="field-error" data-id="error-cryptocurrency-empty"><span>This is a required field.</span></div>';
						cryptocurrencyEle.parents('._required').addClass('_error').append(error);
					}
					return orderFlag;
				} else {
					return true;
				}
			}
		};
	}
);