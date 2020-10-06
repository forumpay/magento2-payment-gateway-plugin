<?php
namespace Limitlex\ForumPay\Block\Payment;
 
use Magento\Framework\View\Element\Template;
 
class ForumPay extends Template{
  public function __construct(
  		Template\Context $context,
  		\Limitlex\ForumPay\Helper\Data $helper,
  		array $data = []
  	){
  		$this->_helper = $helper;
        parent::__construct($context, $data);
    }
  	protected function _prepareLayout(){
        return parent::_prepareLayout();
    }

    public function getPaymentMethodLogo(){
    	return $this->_helper->getPaymentMethodImage();
    }
}