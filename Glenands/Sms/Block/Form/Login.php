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
use Magento\Framework\UrlInterface;

/**
 * Customer login form block
 *
 * @api
 * @since 100.0.2
 */
class Login extends \Magento\Customer\Block\Form\Login
{
    /**
     * @var \Glenands\Sms\Helper\Data
     */
    private $helperData;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param Url $customerUrl
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Url $customerUrl,
        UrlInterface $urlBuilder,
        HelperData $helperData,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $customerSession, $customerUrl, $data);
        $this->helperData = $helperData;
    }

    /**
     * Retrieve password forgotten url
     *
     * @return string
     */
    public function getForgotPasswordUrl()
    {
        return $this->urlBuilder->getUrl('glenands/account/forgotpassword');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->helperData->isActive();
    }

    /**
     * @return object
     */
    public function getMode()
    {
        return $this->addData($this->modeBoth());
    }

    /**
     * List of parameters to be used in form as phone mode.
     *
     * @return array
     */
    private function modePhone()
    {
        return [
            'note' => $this->escapeHtml(
                __('If you have an account, sign in with your phone number.')
            ),
            'label' => $this->escapeHtml(__('Phone Number')),
            'title' => $this->escapeHtmlAttr(__('Phone Number'))
        ];
    }

    /**
     * List of parameters to be used in form as phone and email mode.
     *
     * @return array
     */
    private function modeBoth()
    {
        return [
	    'email_label' => $this->escapeHtml(__('Email Address')),
	    'phone_label' => $this->escapeHtml(__('Phone Number')),
	    'phone_title' => $this->escapeHtmlAttr(__('Phone')),
	    'email_title' => $this->escapeHtmlAttr(__('Email'))
        ];
    }
}
