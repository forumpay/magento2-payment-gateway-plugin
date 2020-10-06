<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Limitlex\ForumPay\Model;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Pay In Store payment method model
 */
class ForumPay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_FORUM_PAY_CODE = 'forumpay';
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_FORUM_PAY_CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true;


    /**
    *
    * \Magento\Framework\HTTP\Client\Curl
    *
    */
    protected $_curl = true;
    

    /**
     * Payment Module Static Parameters
     *
     * @var bool
     */
    protected $_transactionType = 3;
    protected $_responseType = 'HTTP';
    protected $_hashType = 'SHA512';
    protected $_transaction;
    protected $_orderModel;


    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Limitlex\ForumPay\Helper\Data $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Model\Info $additionalInfo,
        EncryptorInterface $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Sales\Model\Order\Payment\Transaction $transaction,
        \Magento\Sales\Model\Order $orderModel
              
    ) {
        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->_additionalInfo = $additionalInfo;
        $this->encryptor = $encryptor;
        $this->_curl = $curl;
        $this->_transaction = $transaction;
        $this->_orderModel = $orderModel;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

    }


    private function generateApiRequestByTransaction($paymentId=null){
      $request_data = [];
      if($paymentId!=null){
        $transaction = $this->_transaction->load($paymentId, 'txn_id');
        if($transaction && $transaction->getId()){
          $additionalInfoKey = $this->_transaction::RAW_DETAILS;
          $additionaldata = $transaction->getAdditionalInformation($additionalInfoKey);
          if($additionaldata && is_array($additionaldata)){
            $request_data = [
              'pos_id' => $additionaldata['pos_id'],
              'currency' => $additionaldata['currency'],
              'address' => $additionaldata['address'],
              'payment_id' => $additionaldata['payment_id']
            ];
          }
        }
      }
      return $request_data;
    }

    private function getOrderByTransactionId($transactionId = null){
      if($transactionId==null){
        return false;
      }
      $transaction = $this->_transaction->load($transactionId, 'txn_id');
      if($transaction->getId()){
        $orderId = $transaction->getOrderId();
        $order = $this->_orderModel->load($orderId);
        return $order;
      }
      return false;
    }

    public function getConfigPaymentAction(){
        return ($this->helper->getNewOrderStatus()? $this->helper->getNewOrderStatus() : parent::getConfigPaymentAction());
    }

    public function initPaymentProcess(){
        $order = $this->checkoutSession->getLastRealOrder();
        //Set Payment Status Before Payment Success
        $order->setStatus($this->helper->getNewOrderStatus()?$this->helper->getNewOrderStatus():'pending');
        $order->save();
        return $order;
    }

    public function getCryptoCurrencyList(){
      $action = 'GetCurrencyList';
      $response = $this->makeCurlRequest($action);
      $response = json_decode($response);
      return $response;
    }

    public function getRate($params=null){
      $pos_id = $this->helper->getPosId();
      $locale = $this->helper->getStoreLocale();
      $cryptocurrency = 'BCH';
      $quote = $this->checkoutSession->getQuote();
      $orderCurrency = $quote->getQuoteCurrencyCode();
      $total = $quote->getGrandTotal();
      if($params && is_array($params) && array_key_exists('payment_cryptocurrency', $params) ){
        $cryptocurrency = $params['payment_cryptocurrency'];
      }
      $action = 'GetRate';
      $req_params = [
        'pos_id' => $pos_id,
        'invoice_currency' => $orderCurrency,
        'invoice_amount' => $total,
        'currency' => $cryptocurrency,
        'locale'=>$locale
      ];

      $response = $this->makeCurlRequest($action, $req_params);
      $response = json_decode($response);
      //$response->print_string = '';
      if($response && !isset($response->err)){
        $response->pos_id = $pos_id;
        $response->locale = $locale;
        //$this->savePaymentDataToOrder($order, $response);
      }
      return $response;
    }
    
    public function startPayment($params=null){
      
      $cryptocurrency = 'BCH';
      $order = $this->initPaymentProcess();
      if(!$order){
        return false;
      }
      $transactionAmount = number_format($order->getBaseGrandTotal(), 2);
      $orderNo = $order->getIncrementId();
      $orderCurrency = $order->getOrderCurrencyCode();
      if($params && is_array($params) && array_key_exists('payment_cryptocurrency', $params) ){
        $cryptocurrency = $params['payment_cryptocurrency'];
      }
      $pos_id = $this->helper->getPosId();
      $locale = $this->helper->getStoreLocale();

      $action = 'StartPayment';
      $params = [
        'pos_id' => $pos_id,
        'invoice_currency' => $orderCurrency,
        'invoice_amount' => $transactionAmount,
        'currency' => $cryptocurrency,
        //'accept_zero_confirmations' => 'false',
        'reference_no' => $orderNo,
        'locale'=>$locale
      ];
        
      $response = $this->makeCurlRequest($action, $params);
      $response = json_decode($response);
      //$response->print_string = '';
      if($response && !isset($response->err)){
        $response->pos_id = $pos_id;
        $response->locale = $locale;
        $this->savePaymentDataToOrder($order, $response);
      }
      return $response;
    }

    public function checkPayment($params=null){
      if($params != null){
        $transactionId = $params['invoice_no'];
        $request_data = $this->generateApiRequestByTransaction($transactionId);
        $action = 'CheckPayment';
        $response = $this->makeCurlRequest($action, $request_data);
        $response = json_decode($response);
        if($response && !isset($response->err)){
          $response->payment_id = $params['invoice_no'];
          if(isset($response->status)){
            if(strtolower($response->status) == 'cancelled' || strtolower($response->status) == 'confirmed'){
              $order = $this->getOrderByTransactionId($transactionId);
              $this->savePaymentDataToOrder($order, $response);
            }
          }
        }
        return $response;
      }
      return false;
    }

    public function cancelPayment($params){
      if(!$params){
        return false;
      }

      $locale = '';
      $action = 'CancelPayment';
      $paymentId = $params['invoice_no']; //Magento Transaction Id
      $request_data = $this->generateApiRequestByTransaction($paymentId);
      $response = $this->makeCurlRequest($action, $request_data);
      $response = json_decode($response);
      //$response->print_string = '';
      if(!isset($response->err)){
        if(isset($response->status)){
          if(strtolower($response->status) == 'cancelled'){
            $response->payment_id = $paymentId;
            $order = $this->getOrderByTransactionId($paymentId);
            $this->savePaymentDataToOrder($order, $response);
          }
        }
      }
      return $response;
    }

    public function makeCurlRequest($action = null, $params = null){
      if($action==null){
        return false;
      }
      $url = $this->helper->getApiUrl().$action.'/';

      $merchantAcNo = trim($this->helper->getMerchantAcNo());
      $merchantPass = trim($this->helper->getMerchantPass());

      $this->_curl->setCredentials($merchantAcNo, $merchantPass);
      $this->_curl->post($url, $params);
      $response = $this->_curl->getBody();
      return $response;
    }


    public function savePaymentDataToOrder(\Magento\Sales\Model\Order $order, $response){
      $ifUpdateOrderStatus = false;
      $paymentResponse = json_decode(json_encode($response), true);
      $formatedPrice = $order->getBaseCurrency()->formatTxt(
        $order->getGrandTotal()
      );

      $txnType = $this->_transaction::TYPE_AUTH;
      $isClosed = false;
      $message = __('Initialize Payment amount is %1.', $formatedPrice);

      if(array_key_exists('status', $paymentResponse)){
        if(strtolower($paymentResponse['status']) == 'cancelled' && array_key_exists('cancelled', $paymentResponse)){
          $ifUpdateOrderStatus = true;
          $orderStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
          $txnType = $this->_transaction::TYPE_VOID;
          $message = __('Payment Failed amount is %1.', $formatedPrice);
          $isClosed = true;
        }elseif (strtolower($paymentResponse['status']) == 'confirmed' && array_key_exists('confirmed', $paymentResponse)) {
          $ifUpdateOrderStatus = true;
          $orderStatus = ($this->helper->getOrderStatusAfterPayment()?$this->helper->getOrderStatusAfterPayment():'processing');
          $txnType = $this->_transaction::TYPE_CAPTURE;
          $message = __('The Captured Payment amount is %1.', $formatedPrice);
          $isClosed = true;
        }  
      }

      $paymentId = $paymentResponse['payment_id'];
      $_transaction = $this->_transaction->load($paymentId, 'txn_id');
      if($_transaction && $_transaction->getId()){
        $additionalInfoKey = $this->_transaction::RAW_DETAILS;
        $additionaldata = $_transaction->getAdditionalInformation($additionalInfoKey);
        if($additionaldata && is_array($additionaldata)){
          $paymentResponse = array_merge($additionaldata,$paymentResponse);
        }
      }


      $payment = $order->getPayment();
      $payment->setLastTransId($paymentResponse['payment_id']);
      $payment->setTransactionId($paymentResponse['payment_id']);
      $payment->setIsClosed($isClosed);
      $payment->setAdditionalInformation(
        [$this->_transaction::RAW_DETAILS => (array) $paymentResponse]
      );
      
      
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $trans = $objectManager->create('Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface');
      $transaction = $trans->setPayment($payment)->setOrder($order)->setTransactionId($paymentResponse['payment_id'])->setAdditionalInformation(
        [$this->_transaction::RAW_DETAILS => (array) $paymentResponse]
      )->setFailSafe(false)->build($txnType);
      $payment->addTransactionCommentsToOrder(
        $transaction,
        $message
      );
      $payment->setParentTransactionId($paymentResponse['payment_id']);
      $payment->save();
      if($ifUpdateOrderStatus){
        $order->setStatus($orderStatus);
      }
      $order->save();
    }



    public function postProcessingPayment(\Magento\Sales\Model\Order $order, $response) {
        $array = $response;
        $paymentResponse = json_decode(json_encode($response), true);
        return true;
    }


    public function array_flatten($array, $parent_key=null) { 
      if (!is_array($array)) { 
        return false; 
      } 
      $result = array();
      foreach ($array as $key => $value) {
        if (is_array($value)) {
          $_key = $parent_key.$key.'__';
          $result = array_merge($result, $this->array_flatten($value, $_key));
        } else {
          $result[$parent_key.$key] = $value;
        }
        
      } 
      return $result; 
    }
}
