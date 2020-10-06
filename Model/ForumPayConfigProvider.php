<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Limitlex\ForumPay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Limitlex\ForumPay\Helper\Data as ForumPayHelper;
use Limitlex\ForumPay\Model\ForumPay as ForumPayModel;

class ForumPayConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCode = ForumPay::PAYMENT_METHOD_FORUM_PAY_CODE;

    /**
     * @var Checkmo
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Limitlex\ForumPay\Helper\Data
     */
    protected $_forumPayHelper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        ForumPayHelper $forumPayHelper,
        ForumPayModel $forumPayModel
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_forumPayHelper = $forumPayHelper;
        $this->_forumPayModel = $forumPayModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(){
        $image = $this->_forumPayHelper->getPaymentMethodImage();
        
        if($image){
            $isimagevisible = 'visible';
        }else{
            $isimagevisible = 'hidden';
        }

        return $this->method->isAvailable() ? [
            'payment' => [
                $this->methodCode => [
                    'success_url' => $this->getSuccessUrl(),
                    'failed_url' => $this->getFailedUrl(),
                    'redirect_url' => $this->getRedirectUrl(),
                    'instructions' => $this->getInstructions(),
                    'paymentmethodimage' => $image,
                    'isimagevisible' => $isimagevisible,
                    'cryptoCurrencyList' => $this->getCryptoCurrencyList()
                ],
            ],
        ] : [];
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */
    protected function getRedirectUrl(){
        return $this->_forumPayHelper->getPaymentRedirectUrl();
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */
    protected function getSuccessUrl(){
        return $this->_forumPayHelper->getPaymentResponseUrl();
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */
    protected function getFailedUrl(){
        return $this->_forumPayHelper->getPaymentCancelUrl();
    }


    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions(){
        return $this->_forumPayHelper->getInstructions();;
    }


    protected function getCryptoCurrencyList(){
        $html = '';
        $cryptoCurrencyList = $this->_forumPayModel->getCryptoCurrencyList();
        $cryptoCurrencyListTmp = [];
         if($cryptoCurrencyList){
            $html .= '<select data-id="buyer-cryptocurrency-list" id="cryptocurrency" name="cryptocurrency">';
            $html .= '<option value="">'.__('-- Select Crypto Currency --').'</option>';
            foreach ($cryptoCurrencyList as $key => $cryptoCurrency) {
                $attr = 'enabled="enabled"';
                if(isset($cryptoCurrency->status) && $cryptoCurrency->status == 'OK'){
                    $html .= '<option data-label="'.$cryptoCurrency->description.'" '.$attr.' value="'.$cryptoCurrency->currency.'">'.$cryptoCurrency->description.' ('.$cryptoCurrency->currency.')</option>';
                }else{
                    $attr = 'disabled="disabled"';
                    $html .= '<option '.$attr.' value="'.$cryptoCurrency->currency.'">'.$cryptoCurrency->description.' ('.$cryptoCurrency->status.')</option>';
                }
            }
            $html .= '</select>';
        }
        return json_encode($html, true);
    }
}
