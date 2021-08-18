<?php
/**
 * Copyright (C) 2021 Empye Technologies LLP
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Glenands\Sms\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Glenands\Sms\Helper\Data as HelperData;
use Glenands\Sms\Model\Config\Source\SigninMode;
use Glenands\Sms\Model\SmsFactory;

/**
 * Otp form block
 *
 * @api
 * @since 100.0.2
 */
class FrpOtp extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Glenands\Sms\Helper\Data
     */
    private $helperData;

    /**
     * @var smsFactory
     */
    protected $smsFactory;

    protected $customerRepository;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param Url $customerUrl
     * @param HelperData $helperData
     * @param \Magento\Framework\App\Request\Http $request
     * @param SmsFactory $smsFactory,
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Url $customerUrl,
        HelperData $helperData,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        SmsFactory $smsFactory,
        array $data = []
    ) {
        $this->customerRepository = $customer;
        $this->request = $request;
        $this->smsFactory = $smsFactory;
        parent::__construct($context, $data);
        $this->helperData = $helperData;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->helperData->isActive();
    }

    /**
     * @return bool
     */
    public function isOtpGenerated()
    {
        $sms = $this->smsFactory->create()->load($this->getHash(), 'hashlogin');
        
        if(!empty($sms) && !empty($sms->getData('otp_text'))) {
                return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isCustomerExist()
    {
        $sms = $this->smsFactory->create()->load($this->getHash(), 'hashlogin');
        try {
            $customer = $this->customerRepository->get($sms->getData('user_name'));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isPhoneNumberRequired() {
        $sms = $this->smsFactory->create()->load($this->getHash(), 'hashlogin');
        
        if(!empty($sms) && !empty($sms->getData('user_name'))) {
            $customer = $this->customerRepository->get($sms->getData('user_name'));
            if(!empty($customer->getCustomAttribute('phone_number'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getPhoneNumber() {
        if($this->request->getParam('phone_number')) {
            return $this->request->getParam('phone_number');
        } else {
            $sms = $this->smsFactory->create()->load($this->getHash(), 'hashlogin');
            try {
                if(!empty($sms) && !empty($sms->getData('user_name'))) {
                    $customer = $this->customerRepository->get($sms->getData('user_name'));
                    if(!empty($customer->getCustomAttribute('phone_number'))) {
                        return $customer->getCustomAttribute('phone_number')->getValue();
                    }
                }
            } catch (\Exception $e) {
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getEmail() {
        if($this->request->getParam('email')) {
            return $this->request->getParam('email');
        }
        return false;
    }

    /**
     * @return string
     */
    public function getHash() {
        return $this->request->getParam('hash');
    }

    /**
     * @return string
     */
    public function getFirstname() {
        return $this->request->getParam('firstname');
    }

    /**
     * @return string
     */
    public function getLastname() {
        return $this->request->getParam('lastname');
    }

    /**
     * @return string
     */
    public function getIsSubscribed() {
        return $this->request->getParam('is_subscribed');
    }
}
