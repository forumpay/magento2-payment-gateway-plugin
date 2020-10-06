require([
    "jquery",
    "jquery/ui"
], function($, jqueryui) {
    "use strict";
    var getTransactionRateTimeOut;
    var durationRefreshPaymentStatus = 30000;

    $('body').on('click', '[data-id="payment-qr"] img', function(){
      var _this = $(this);
      $('[data-id="payment-qr"]').addClass('disable_qr');
      _this.parents('[data-id="payment-qr"]').removeClass('disable_qr');
    });

    var getTransactionRate = function(showLoader){
      var _this = $('[data-id="buyer-cryptocurrency-list"]');
      var process_payment_url = window.checkoutConfig.payment.forumpay.redirect_url;
      var selectedCurrency = _this.val();
      var data = {
        'action': 'forumpay_get_payment',
        'payment_cryptocurrency':selectedCurrency
      };
      if(selectedCurrency){
        if(typeof showLoader != "undefined" && showLoader==true){
          var bodyLoader = $('body').loader();
          bodyLoader.loader('show');
        }
        $.ajax({
          url: process_payment_url,
          type: 'POST',
          data: data,
          dataType: 'json',
          success: function (response){
            $('#process_get_rate_window').remove();
            if(typeof response != "undefined" && typeof response.html != "undefined" && response.html && response.status){
              _this.parents('.field').after('<div id="process_get_rate_window" class="process_get_rate_window"></div>');
              $('#process_get_rate_window').html(response.html);
              if(typeof showLoader != "undefined" && showLoader==true){
                bodyLoader.loader('hide');
              }
              getTransactionRateTimeOut = setTimeout(function(){
                getTransactionRate(false);
              }, 15000);
            }else{
              console.log('Something Went Wrong!')
            }
          }
        });
      }else{
        $('#process_get_rate_window').remove();
      }
    }

    $('body').on('change', '[data-id="buyer-cryptocurrency-list"]', function(){
      clearTimeout(getTransactionRateTimeOut);
      getTransactionRate(true);
    });


    $('body').on('click', '[data-id="forumpay"]', function(){
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
              //$.mage.redirect(process_payment_url);
            }
          }
        });
    });

    var checkForumPayTrasaction = function(_responseData){
      var process_payment_url = window.checkoutConfig.payment.forumpay.redirect_url;
      if(typeof window.paymnetStatusAutoCheck != "undefined"){
        durationRefreshPaymentStatus = window.paymnetStatusAutoCheck*1000;
      }
      var paymentStatusContainer = $('[data-role="payment-status"]');
      paymentStatusContainer.trigger('show.loader');
      var _payment_response = _responseData.payment_response;

      var success_url = window.checkoutConfig.payment.forumpay.success_url;
      var failed_url = window.checkoutConfig.payment.forumpay.failed_url;
      var success_url = success_url+'payment_id/'+window.paymnetId;
      var failed_url = failed_url+'payment_id/'+window.paymnetId;

      if(typeof _payment_response != "undefined" 
          && typeof _payment_response.currency != "undefined" 
          && typeof _payment_response.payment_id != "undefined"
          && typeof _payment_response.address != "undefined"
        ){
          var data = {
            "currency" : _payment_response.currency,
            "invoice_no" : _payment_response.payment_id,
            "address" : _payment_response.address,
            'action': 'forumpay_check_payment',
          };

          $.ajax({
            url: process_payment_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (response){
              if(typeof response != "undefined" && typeof response.html != "undefined" && response.html && response.status){
                $('#payment_status').remove();
                $('#pageCheckPaymentResult').html('<div id="payment_status">'+response.html+'</div>');
                paymentStatusContainer.trigger('hide.loader');
                var responseData = JSON.parse(response.data);  
                if(typeof responseData != "undefined" && typeof responseData.payment_response != "undefined"){
                  var payment_response = responseData.payment_response;                 
                  if(typeof payment_response == "undefined" 
                      || (typeof payment_response.status != "undefined"  && ((payment_response.status == "Cancelled" && typeof payment_response.cancelled != "undefined" && payment_response.cancelled != null) || (payment_response.status == "Confirmed" && typeof payment_response.confirmed != "undefined" && payment_response.confirmed != null)))
                    ){
                    $('#pageStartPaymentResult, #pageForumPayAction').remove();
                    if(payment_response.status == "Confirmed"){
                      $('#pageCheckPaymentResult').addClass('confirmed');
                      $.mage.redirect(success_url);
                    }else if (payment_response.status == "Cancelled"){
                      $('#pageCheckPaymentResult').addClass('cancelled');
                      $.mage.redirect(failed_url);
                    }
                  }else{
                    setTimeout(function(){
                      checkForumPayTrasaction(_responseData); 
                    }, durationRefreshPaymentStatus);
                  }
                }
              }
            }
          });
        }
    };

    var _abortPayment = function(_this){
      var cancel_url = window.checkoutConfig.payment.forumpay.failed_url;
      var payment_id = _this.attr('data-payment-id');
      var cancel_url = cancel_url+'invoice_no/'+payment_id+'/action/manual';
      $.mage.redirect(cancel_url);
    };

    $('body').on('click', '[data-id="cancel-payment"]', function(){
      var _this = $(this);
      _abortPayment(_this);
    });


    $(document).on('ajaxComplete', function( event, request, settings ) {
      var process_payment_url = window.checkoutConfig.payment.forumpay.redirect_url;
      if (settings.url === process_payment_url) {    
        var settings_data = settings.data;   
        var settingsDataObj = JSON.parse('{"' + decodeURI(settings_data).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
        if( typeof settingsDataObj != "undefined" 
          && typeof settingsDataObj.action != "undefined" 
          && settingsDataObj.action == 'forumpay_start_payment'
          ){
            var response = request.responseJSON;
            if( typeof response != "undefined" && typeof response.data != "undefined"){
              var responseData = JSON.parse(response.data);  
              setTimeout(function(){
                checkForumPayTrasaction(responseData); 
              }, 5000);
            }
        }
      }
    });
});