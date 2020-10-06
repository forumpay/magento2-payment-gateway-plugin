<?php
/**
 * @Author      : Limitlex
 * @package     ForumPay
 * @copyright   Copyright (c) 2019 Forupay
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 **/
namespace Limitlex\ForumPay\Helper;
 
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper{
    //All path system config from system.xml
    const XML_PATH_ENABLED = 'payment/forumpay/active';
    const XML_PATH_PAYMENT_MODE = 'payment/forumpay/sandbox_mode';
    const XML_PATH_MERCHANT_API_USER = 'payment/forumpay/merchant_api_user';
    const XML_PATH_MERCHANT_PASS = 'payment/forumpay/merchant_api_secret';
    const XML_PATH_ORDER_STATUS_AFTER_PAYMENT = 'payment/forumpay/order_status_after_payment';
    const XML_PATH_ORDER_STATUS = 'payment/forumpay/order_status';
    const XML_PATH_INSTRUCTIONS = 'payment/forumpay/instructions';
    const XML_PATH_POS_ID = 'payment/forumpay/pos_id';
    const XML_PATH_PAYMENT_ICON = 'payment/forumpay/payment_icon';
    
    const SANDBOX_REQUEST_URL = 'https://forumpay.com/api/v2/'; // Trailing Slash(/) is required
    const LIVE_REQUEST_URL = 'https://forumpay.com/api/v2/'; // Trailing Slash(/) is required

    
	protected $_objectmanager;
	protected $assetRepo;
	protected $categoryRepository;
	protected $_storeManager;
	protected $_categoryFactory;
    protected $_localeCurrency;
    protected $_encryptor;
	
    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
		ObjectManagerInterface $objectmanager,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor
    ) 
	{
        parent::__construct($context);
		$this->_storeManager = $storeManager;
		$this->_objectmanager=$objectmanager;
        $this->_encryptor = $encryptor;

    }
 
    /**
	 * Check for module is enabled in frontend
	 *
	 * @return bool
	 */
    public function getIsEnable(){
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymentMode(){
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }
    public function getMerchantAcNo(){
        $api_key = $this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_API_USER,
            ScopeInterface::SCOPE_STORE
        );
        return trim($this->_encryptor->decrypt($api_key));
    }
    public function getMerchantPass(){
        $authorizationCode = $this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_PASS,
            ScopeInterface::SCOPE_STORE
        );
        return trim($this->_encryptor->decrypt($authorizationCode));
    }
    public function getOrderStatusAfterPayment(){
        return $this->scopeConfig->getValue(
            self::XML_PATH_ORDER_STATUS_AFTER_PAYMENT,
            ScopeInterface::SCOPE_STORE
        );
    }


    public function getNewOrderStatus(){
        return $this->scopeConfig->getValue(
            self::XML_PATH_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPosId(){
        $posId = $this->scopeConfig->getValue(
            self::XML_PATH_POS_ID,
            ScopeInterface::SCOPE_STORE
        );
        if($posId){
            $posId = str_replace(' ', '-', $posId);
            $posId = preg_replace('/[^A-Za-z0-9\-]/', '', $posId);
        }
        if(!$posId){
            $posId = 'magento-2';
        }
        return $posId;
    }

    public function getInstructions(){
        return $this->scopeConfig->getValue(
            self::XML_PATH_INSTRUCTIONS,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getApiUrl(){
        if($this->getPaymentMode()){
            return self::SANDBOX_REQUEST_URL;
        }
        return self::LIVE_REQUEST_URL;
    }

    public function getApiHost(){
        $parse = parse_url($this->getApiUrl());
        return $parse['host'];
    }

    public function getPaymentResponseUrl(){
        return $this->_getUrl('forumpay/standard/response');
    }

    public function getPaymentRedirectUrl(){
        return $this->_getUrl('forumpay/standard/redirect');
    }

    public function getPaymentCancelUrl(){
        return $this->_getUrl('forumpay/standard/cancel');
    }

    public function getPaymentMethodIcon(){
       return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_ICON,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymentMethodImage(){
       $image = $this->getPaymentMethodIcon();
        if($image){
            $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            return $mediaUrl.'forumpay/'.$image;
        }
        return false;
    }


    public function getStoreLocale(){
        return $this->_storeManager->getStore()->getLocaleCode();
    }

    public function getBasicAuthorization(){
        return $this->generateBasicAuthorization();
    }

    private function generateBasicAuthorization(){
        $user = $this->getMerchantAcNo();
        $pass = $this->getMerchantPass();
        $token = $user.':'.$pass;
        $hash = base64_encode($token);
        return "Basic ".$hash;
    }
}

