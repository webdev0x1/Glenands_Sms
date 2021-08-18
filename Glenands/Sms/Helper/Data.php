<?php
/**
 * Copyright (C) 2021 Empye Technologies LLP
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Glenands\Sms\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Model\Customer;

/**
 * Configuration class.
 */
class Data extends AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Framework\ObjectManagerInterface  $objectManager
     * @param \Magento\Customer\Model\Session            $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->_objectManager = $objectManager;
        $this->_scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_encryptor = $encryptor;
    }


    /**
    * Send otp sms
    * @return array
    */
    public function _sendSMS($phone_number, $country_code)
    {

    }

    /**
    * verification otp sms
    * @return array
    */
    public function _verifyOtp($phone_number, $verification_code, $country_code)
    {

    }

    /**
     * get website base url
     * @return string
     */
    public function getSiteUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Is module active
     *
     * @param string|null $scopeCode
     * @return bool
     */
    public function isActive($scopeCode = null)
    {
        return (bool) $this->scopeConfig->isSetFlag(
            'glenands_sms/module/enabled',
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    
    /**
     * Register in mode.
     *
     * @param string|null $scopeCode
     * @return string
     */
    public function getRegisterMode($scopeCode = null)
    {
        return $this->scopeConfig->getValue(
            'glenands_sms/options/mode',
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }



    /**
     * Sign in mode.
     *
     * @param string|null $scopeCode
     * @return string
     */
    public function getSigninMode($scopeCode = null)
    {
        return $this->scopeConfig->getValue(
            'glenands_sms/options/mode',
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * Get if customer accounts are shared per website.
     *
     * @see \Magento\Customer\Model\Config\Share
     * @param string|null $scopeCode
     * @return string
     */
    public function getCustomerShareScope($scopeCode = null)
    {
        return $this->scopeConfig->getValue(
            \Magento\Customer\Model\Config\Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }
}
