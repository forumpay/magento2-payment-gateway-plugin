<?php

namespace Limitlex\ForumPay\Controller\Standard;

class Redirect extends \Limitlex\ForumPay\Controller\AbstractController{

    public function execute() {
        $paymentData = [];
        return $this->processPaymentRequest($paymentData);

        $paymentData = $this->_forumPayModel->buildCheckoutRequest();
        if($paymentData && isset($paymentData)){
            $quote = $this->getQuote();
            $html = $this->processPaymentRequest($paymentData);
        }else{
            throw new \Magento\Framework\Exception\LocalizedException(__('Error to build payment request.'));
            $this->getResponse()->setRedirect($this->_url->getUrl('checkout'));
        }
        die('Redirecting...');
    }
    private function processPaymentRequest($paymentData = []){
        $html = '';
        $generateHtml = false;
        $response = [];
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $params = $this->getRequest()->getParams();

        if($params && is_array($params)){
            if(array_key_exists('action', $params) && $params['action'] == 'forumpay_start_payment'){
                $template = 'forumpay-ajax-start-payment-window.phtml';
                $generateHtml = true;
                $response = $this->_forumPayModel->startPayment($params); //return array
            }

            if(array_key_exists('action', $params) && $params['action'] == 'forumpay_get_payment'){
                $template = 'forumpay-ajax-get-rate-payment-window.phtml';
                $generateHtml = true;
                $response = $this->_forumPayModel->getRate($params);
            }

            if(array_key_exists('action', $params) && $params['action'] == 'forumpay_check_payment'){
                $template = 'forumpay-ajax-check-payment-window.phtml';
                $generateHtml = true;
                $response = $this->_forumPayModel->checkPayment($params); //return array
            }
        }

        $data = [
            'payment_response' => $response,
            'action' => $params['action'],
            'locale' => $this->_helper->getStoreLocale()
        ];
        $data = json_encode($data);

        if($generateHtml){
            $html = $resultPage->getLayout()
                ->createBlock('Limitlex\ForumPay\Block\Payment\ForumPay')
                ->setTemplate('Limitlex_ForumPay::payment/'.$template)
                ->setData('data', $data)
                ->toHtml();
        }
        $result->setData(['html' => $html, 'status' => true, 'data'=> $data]);
        return $result;
    }
}
