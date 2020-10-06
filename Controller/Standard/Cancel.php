<?php

namespace Limitlex\ForumPay\Controller\Standard;

class Cancel extends \Limitlex\ForumPay\Controller\AbstractController{

    public function execute() {
        $error = true;
        $cancelMsg = __('Payment not authorized. Please try again later or choose a different payment method');
        $params = $this->getRequest()->getParams();
        if($params && is_array($params) && array_key_exists('action', $params) && $params['action'] == 'manual'){
            $response = $this->_forumPayModel->cancelPayment($params);
            $response = $this->_forumPayModel->checkPayment($params);
            if(isset($response->status) && strtolower($response->status) == 'cancelled' && isset($response->cancelled)){
                $cancelMsg = __('Payment request cancelled successfully.');
            }
        }
        if($error){
            $this->_checkoutSession->restoreQuote(); //Restore Cart
            $this->_messageManager->addErrorMessage($cancelMsg);
            return $this->getResponse()->setRedirect($this->_url->getUrl('checkout/cart'));
        }else{
            return $this->getResponse()->setRedirect($this->_url->getUrl('checkout/onepage/success'));
        }
    }
}
