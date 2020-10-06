<?php

namespace Limitlex\ForumPay\Controller\Standard;

class Webhook extends \Limitlex\ForumPay\Controller\AbstractController{

    public function execute() {
        $this->_logger->debug('ForumPay WebHook Triggered');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->_logger->debug(json_encode($params));
        if($params && is_array($params) && array_key_exists('payment_id', $params)){
            $params['invoice_no'] = $params['payment_id'];
            $response = $this->_forumPayModel->checkPayment($params);
            echo 'success';
            die();
        }else{
            header("HTTP/1.0 404 Not Found");
            die();
        }
    }
}
