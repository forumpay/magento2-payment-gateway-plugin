<?php

namespace Limitlex\ForumPay\Controller\Standard;

class Response extends \Limitlex\ForumPay\Controller\AbstractController{

    public function execute() {
        $error = false;
        $errorMsg = __('We are not able to place your order now. Please try again later.');
        // $params = $this->getRequest()->getParams();
        // echo "<pre>";
        // print_r($params);
        // echo "</pre>";
        // if(is_array($params) && array_key_exists('payment_id', $params)){
        //     $payment_id = 27;//$params['payment_id'];
        //     $payment_id = $params['payment_id'];
        //     $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //     $transaction = $objectManager->get('Magento\Sales\Model\Order\Payment\Transaction\Repository')->getByTransactionId($payment_id);
        //     echo "<pre>";
        //     print_r($transaction->getData());
        //     echo "</pre>";

        //     //$order_info = $order->loadByIncrementId($order_id);    
        // }


        // if(isset($params) &&  is_array($params)){
        //     if(array_key_exists('RESPONSE_CODE', $params) 
        //         && isset($params['RESPONSE_CODE']) 
        //         && $params['RESPONSE_CODE'] == 0
        //         && isset($params['TRANSACTION_ID'])
        //         && isset($params['TRANSACTION_ID'])
        //         ){
        //         $error = false;
        //         $order = $this->getOrder();
        //         $payment = $order->getPayment();
        //         $this->_forumPayModel->postProcessing($order, $payment, $params);
        //     }else{
        //         if(array_key_exists('RESPONSE_DESC', $params)){
        //             $errorMsg = $params['RESPONSE_DESC'].' ('.$params['RESPONSE_CODE'].')';
        //         }
        //     }
        // }

        if($error){
            $this->_checkoutSession->restoreQuote(); //Restore Cart
            $this->_messageManager->addErrorMessage($errorMsg);
            return $this->getResponse()->setRedirect($this->_url->getUrl('checkout/cart'));
        }else{
            return $this->getResponse()->setRedirect($this->_url->getUrl('checkout/onepage/success'));
        }
    }
}
