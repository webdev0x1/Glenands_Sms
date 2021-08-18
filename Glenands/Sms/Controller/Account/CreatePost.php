<?php
declare(strict_types=1);

namespace Glenands\Sms\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Controller\AbstractAccount;
use Glenands\Sms\Model\Sms\Sms;
use Glenands\Sms\Model\SmsFactory;
use Glenands\Sms\Model\ResourceModel\Sms\CollectionFactory as SmsCollectionFactory;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;

/**
 * Post create customer action
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePost extends \Magento\Customer\Controller\Account\CreatePost
{

    CONST OTP_DIGIT = 6;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var \Magento\Customer\Helper\Address
     */
    protected $addressHelper;

    /**
     * @var \Magento\Customer\Model\Metadata\FormFactory
     */
    protected $formFactory;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Magento\Customer\Api\Data\RegionInterfaceFactory
     */
    protected $regionDataFactory;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var \Magento\Customer\Model\Registration
     */
    protected $registration;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerDataFactory;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var smsFactory
     */
    protected $smsFactory;

    /**
     * @var Sms
     */
    protected $sms;

    /**
     * @var \Magento\Customer\Model\CustomerExtractor
     */
    protected $customerExtractor;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlModel;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AccountRedirect
     */
    private $accountRedirect;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var smsCollectionFactory
     */
    protected $smsCollectionFactory;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManagement
     * @param Address $addressHelper
     * @param UrlFactory $urlFactory
     * @param FormFactory $formFactory
     * @param SubscriberFactory $subscriberFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerUrl $customerUrl
     * @param Registration $registration
     * @param Escaper $escaper
     * @param CustomerExtractor $customerExtractor
     * @param DataObjectHelper $dataObjectHelper
     * @param AccountRedirect $accountRedirect
     * @param CustomerRepository $customerRepository
     * @param Validator $formKeyValidator
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlFactory $urlFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        CustomerExtractor $customerExtractor,
        DataObjectHelper $dataObjectHelper,
        AccountRedirect $accountRedirect,
        Encryptor $encryptor,
        SmsFactory $smsFactory,
        Sms $sms,
        ResultFactory $resultFactory,
        SmsCollectionFactory $smsCollectionFactory,
        CustomerRepository $customerRepository,
        Validator $formKeyValidator = null
    ) {
        $this->session = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->accountManagement = $accountManagement;
        $this->addressHelper = $addressHelper;
        $this->formFactory = $formFactory;
        $this->resultFactory = $resultFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->regionDataFactory = $regionDataFactory;
        $this->addressDataFactory = $addressDataFactory;
        $this->customerDataFactory = $customerDataFactory;
        $this->customerUrl = $customerUrl;
        $this->registration = $registration;
        $this->escaper = $escaper;
        $this->customerExtractor = $customerExtractor;
        $this->urlModel = $urlFactory->create();
        $this->dataObjectHelper = $dataObjectHelper;
        $this->accountRedirect = $accountRedirect;
        $this->smsFactory = $smsFactory;
        $this->sms = $sms;
        $this->smsCollectionFactory = $smsCollectionFactory;
        $this->encryptor = $encryptor;
        $this->formKeyValidator = $formKeyValidator ?: ObjectManager::getInstance()->get(Validator::class);
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $customerSession, $scopeConfig, $storeManager, $accountManagement, $addressHelper, $urlFactory, $formFactory, $subscriberFactory, $regionDataFactory, $addressDataFactory, $customerDataFactory, $customerUrl, $registration, $escaper, $customerExtractor, $dataObjectHelper, $accountRedirect, $formKeyValidator);
    }

    /**
     * Get scope config
     *
     * @return ScopeConfigInterface
     * @deprecated 100.0.10
     */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\App\Config\ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * Add address to customer during create account
     *
     * @return AddressInterface|null
     */
    protected function extractAddress()
    {
        if (!$this->getRequest()->getPost('create_address')) {
            return null;
        }

        $addressForm = $this->formFactory->create('customer_address', 'customer_register_address');
        $allowedAttributes = $addressForm->getAllowedAttributes();

        $addressData = [];

        $regionDataObject = $this->regionDataFactory->create();
        $userDefinedAttr = $this->getRequest()->getParam('address') ?: [];
        foreach ($allowedAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if ($attribute->isUserDefined()) {
                $value = array_key_exists($attributeCode, $userDefinedAttr) ? $userDefinedAttr[$attributeCode] : null;
            } else {
                $value = $this->getRequest()->getParam($attributeCode);
            }

            if ($value === null) {
                continue;
            }
            switch ($attributeCode) {
                case 'region_id':
                    $regionDataObject->setRegionId($value);
                    break;
                case 'region':
                    $regionDataObject->setRegion($value);
                    break;
                default:
                    $addressData[$attributeCode] = $value;
            }
        }
        $addressData = $addressForm->compactData($addressData);
        unset($addressData['region_id'], $addressData['region']);

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            $addressData,
            \Magento\Customer\Api\Data\AddressInterface::class
        );
        $addressDataObject->setRegion($regionDataObject);

        $addressDataObject->setIsDefaultBilling(
            $this->getRequest()->getParam('default_billing', false)
        )->setIsDefaultShipping(
            $this->getRequest()->getParam('default_shipping', false)
        );
        return $addressDataObject;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $url = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
        $resultRedirect->setUrl($this->_redirect->error($url));

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Invalid Form Key. Please refresh the page.')]
        );
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return null;
    }

    /**
     * Create customer account action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        if (!$this->getRequest()->isPost()
            || !$this->formKeyValidator->validate($this->getRequest())
        ) {
            $url = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
            return $this->resultRedirectFactory->create()
                ->setUrl($this->_redirect->error($url));
        }
        
        if($this->getRequest()->getParam('email')) {
            try {
                $email = $this->customerRepository->get($this->getRequest()->getParam('email'));
                if($email) {
                        $this->messageManager->addErrorMessage(__('A customer with the same email address already exists in an associated website.!'));
                        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                        return $resultRedirect;
                }
            } catch (\Exception $e) {
            }
        }

        if($this->getScopeConfig()->getValue('glenands_sms/module/enabled') && $this->getScopeConfig()->getValue('glenands_sms/otp/enabled')) {
            if($this->getRequest()->getParam('password') && $this->getRequest()->getParam('password_confirmation')) {
                $password = $this->getRequest()->getParam('password');
                $confirmation = $this->getRequest()->getParam('password_confirmation');
                $redirectUrl = $this->session->getBeforeAuthUrl();
                $this->checkPasswordConfirmation($password, $confirmation);
                $this->accountManagement->checkPasswordStrengthGlenands($this->getRequest()->getParam('password'));
            } else {
                $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                $this->getRequest()->setParam('password', $this->encryptor->decrypt($sms->getData('pass')));
                $this->getRequest()->setParam('is_subscribed', $sms->getData('is_subscribed'));
                $this->getRequest()->setParam('firstname', $sms->getData('firstname'));
                $this->getRequest()->setParam('lastname', $sms->getData('lastname'));
                $this->getRequest()->setParam('password_confirmation', $this->encryptor->decrypt($sms->getData('pass')));
                $this->accountManagement->checkPasswordStrengthGlenands($this->encryptor->decrypt($sms->getData('pass')));
            }

            if($this->getRequest()->getParam('email') && empty($this->getRequest()->getParam('otp'))) {
                $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                $sms->delete();
                $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                $sms->setData('status', 1);
                $sms->setData('is_subscribed', $this->getRequest()->getParam('is_subscribed'));
                $sms->setData('firstname', $this->getRequest()->getParam('firstname'));
                $sms->setData('lastname', $this->getRequest()->getParam('lastname'));
                $sms->setData('user_name', $this->getRequest()->getParam('email'));
                $sms->setData('pass', $this->encryptor->encrypt($this->getRequest()->getParam('password')));
                $sms->setData('type', 'otp');
                $sms->save();

                $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                
                $uniqueHash = $this->encryptor->getHash("username:".$this->getRequest()->getParam('email')."password:".$this->encryptor->getHash($this->getRequest()->getParam('password'))."time:".$sms->getData('created_at'), true);
                $sms->setData('hashlogin', $uniqueHash);
                $otp = $this->generateNumericOTP(self::OTP_DIGIT);
                $email = $this->getRequest()->getParam('email');
                $sms->setData('otp_for', $this->getRequest()->getParam('phone_number'));
                $sms->save();

                do {
                    $smsGrid = $this->smsCollectionFactory->create()->addFieldToFilter('user_name', ['eq' => $email])->addFieldToFilter('otp_text', ['eq' => $otp]);
                    $otp = $this->generateNumericOTP(self::OTP_DIGIT);
                } while (count($smsGrid) < 0);

                $response  = '';
                //print_r($customerData->getCustomAttribute('phone_number')->getValue());die();
                if(!empty($this->getRequest()->getParam('phone_number'))) {
                    $response = $this->sms->sendOtp($this->getRequest()->getParam('phone_number'), $otp);
                }

                if($response == true) {
                    $sms->setData('otp_text', $otp);
                    $sms->setData('status', 1);
                } else {
                    $sms->setData('status', 0);
                }
                $sms->save();
                
                $params = array('hash' => $uniqueHash, 'phone_number' => $this->getRequest()->getParam('phone_number'), 'email' => $email, 'firstname' => $this->getRequest()->getParam('firstname'), 'lastname' => $this->getRequest()->getParam('lastname'), 'is_subscribed' => $this->getRequest()->getParam('is_subscribed'));
                
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('glenands/account/accountcreateotp', $params);
                return $resultRedirect;
            }
        }
        if(!empty($this->getRequest()->getParam('email')) && !empty($this->getRequest()->getParam('otp'))) {
            $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
            if(!empty($sms) && !empty($sms->getData('otp_text'))) {
                if($this->getRequest()->getParam('otp') == $sms->getData('otp_text')) {
                    
                $this->session->regenerateId();
                try {
                    $address = $this->extractAddress();
                    $addresses = $address === null ? [] : [$address];
                    $customer = $this->customerExtractor->extract('customer_account_create', $this->_request);
                    $customer->setAddresses($addresses);
                    $password = $this->getRequest()->getParam('password');
                    $confirmation = $this->getRequest()->getParam('password_confirmation');
                    $phone = $this->getRequest()->getParam('phone_number');
                    $redirectUrl = $this->session->getBeforeAuthUrl();
                    $this->checkPasswordConfirmation($password, $confirmation);

                    $extensionAttributes = $customer->getExtensionAttributes();
                    $extensionAttributes->setIsSubscribed($this->getRequest()->getParam('is_subscribed', false));
                    $customer->setExtensionAttributes($extensionAttributes);

                    $customer = $this->accountManagement
                        ->createAccountCustom($customer, $password, $redirectUrl, $phone);

                    $this->_eventManager->dispatch(
                        'customer_register_success',
                        ['account_controller' => $this, 'customer' => $customer]
                    );
                    $sms->delete();
                    $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
                    if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                        $this->messageManager->addComplexSuccessMessage(
                            'confirmAccountSuccessMessage',
                            [
                                'url' => $this->customerUrl->getEmailConfirmationUrl($customer->getEmail()),
                            ]
                        );
                        $url = $this->urlModel->getUrl('*/*/index', ['_secure' => true]);
                        $resultRedirect->setUrl($this->_redirect->success($url));
                    } else {
                        $this->session->setCustomerDataAsLoggedIn($customer);
                        $this->messageManager->addMessage($this->getMessageManagerSuccessMessage());
                        $requestedRedirect = $this->accountRedirect->getRedirectCookie();
                        if (!$this->scopeConfig->getValue('customer/startup/redirect_dashboard') && $requestedRedirect) {
                            $resultRedirect->setUrl($this->_redirect->success($requestedRedirect));
                            $this->accountRedirect->clearRedirectCookie();
                            return $resultRedirect;
                        }
                        $resultRedirect = $this->accountRedirect->getRedirect();
                    }
                    if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                        $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                        $metadata->setPath('/');
                        $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                    }

                    return $resultRedirect;
                } catch (StateException $e) {
                    $this->messageManager->addComplexErrorMessage(
                        'customerAlreadyExistsErrorMessage',
                        [
                            'url' => $this->urlModel->getUrl('customer/account/forgotpassword'),
                        ]
                    );
                } catch (InputException $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    foreach ($e->getErrors() as $error) {
                        $this->messageManager->addErrorMessage($error->getMessage());
                    }
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                } catch (\Exception $e) {
                    print_r($e->getMessage());die();
                    $this->messageManager->addExceptionMessage($e, __('We can\'t save the customer.'));
                }
        } else {
            $this->messageManager->addErrorMessage(__('Invalid OTP please try again!'));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }
    }
}

        $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        $defaultUrl = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
        return $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
    }

    /**
     * Make sure that password and password confirmation matched
     *
     * @param string $password
     * @param string $confirmation
     * @return void
     * @throws InputException
     */
    protected function checkPasswordConfirmation($password, $confirmation)
    {
        if ($password != $confirmation) {
            throw new InputException(__('Please make sure your passwords match.'));
        }
    }

    /**
     * Retrieve success message
     *
     * @deprecated 102.0.4
     * @see getMessageManagerSuccessMessage()
     * @return string
     */
    protected function getSuccessMessage()
    {
        if ($this->addressHelper->isVatValidationEnabled()) {
            if ($this->addressHelper->getTaxCalculationAddressType() == Address::TYPE_SHIPPING) {
                // @codingStandardsIgnoreStart
                $message = __(
                    'If you are a registered VAT customer, please <a href="%1">click here</a> to enter your shipping address for proper VAT calculation.',
                    $this->urlModel->getUrl('customer/address/edit')
                );
                // @codingStandardsIgnoreEnd
            } else {
                // @codingStandardsIgnoreStart
                $message = __(
                    'If you are a registered VAT customer, please <a href="%1">click here</a> to enter your billing address for proper VAT calculation.',
                    $this->urlModel->getUrl('customer/address/edit')
                );
                // @codingStandardsIgnoreEnd
            }
        } else {
            $message = __('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName());
        }
        return $message;
    }

    /**
     * Retrieve success message manager message
     *
     * @return MessageInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getMessageManagerSuccessMessage(): MessageInterface
    {
        if ($this->addressHelper->isVatValidationEnabled()) {
            if ($this->addressHelper->getTaxCalculationAddressType() == Address::TYPE_SHIPPING) {
                $identifier = 'customerVatShippingAddressSuccessMessage';
            } else {
                $identifier = 'customerVatBillingAddressSuccessMessage';
            }

            $message = $this->messageManager
                ->createMessage(MessageInterface::TYPE_SUCCESS, $identifier)
                ->setData(
                    [
                        'url' => $this->urlModel->getUrl('customer/address/edit'),
                    ]
                );
        } else {
            $message = $this->messageManager
                ->createMessage(MessageInterface::TYPE_SUCCESS)
                ->setText(
                    __('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
                );
        }

        return $message;
    }

    // Function to generate OTP 
    function generateNumericOTP($n)
    {
        $generator = "1357902468";
        $result = "";
    
        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, (rand() % (strlen($generator))), 1);
        }
    
        return $result;
    }    
}
