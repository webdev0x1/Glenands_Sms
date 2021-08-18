<?php
namespace Glenands\Sms\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Phrase;
use Magento\Framework\View\Result\PageFactory;
use Glenands\Sms\Model\SmsFactory;
use Glenands\Sms\Model\ResourceModel\Sms\CollectionFactory as SmsCollectionFactory;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Controller\ResultFactory;
use Glenands\Sms\Model\Sms\Sms;

/**
 * Post login customer action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginPost extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    CONST OTP_DIGIT = 6;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var smsFactory
     */
    protected $smsFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var smsFactory
     */
    protected $smsCollectionFactory;
    
    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var Sms
     */
    protected $sms;

        /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */ 
    protected $objectManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerUrl $customerHelperData
     * @param Validator $formKeyValidator
     * @param Encryptor $encryptor
     * @param Sms $sms
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param SmsCollectionFactory $smsCollectionFactory
     * @param Magento\Customer\Api\CustomerRepositoryInterface $customer
     * @param \Magento\Framework\App\ObjectManager $objectManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param AccountRedirect $accountRedirect
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        \Magento\Framework\Json\Helper\Data $helper,
        AccountManagementInterface $customerAccountManagement,
        CustomerUrl $customerHelperData,
        Validator $formKeyValidator,
        Encryptor $encryptor,
        PageFactory $resultPageFactory,
        SmsFactory $smsFactory,
        Sms $sms,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        SmsCollectionFactory $smsCollectionFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        AccountRedirect $accountRedirect
    ) {
        $this->_customerFactory = $customerFactory;
        $this->customerRepository = $customer;
        $this->sms = $sms;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_messageManager = $messageManager;
        $this->smsFactory = $smsFactory;
        $this->encryptor = $encryptor;
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerUrl = $customerHelperData;
        $this->formKeyValidator = $formKeyValidator;
        $this->accountRedirect = $accountRedirect;
        $this->smsCollectionFactory = $smsCollectionFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
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
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
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
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/');

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

    private function getCurrentPasswordHash($customerId){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = "Select password_hash from ".$resource->getTableName('customer_entity')." WHERE entity_id = ".$customerId;
        $hash = $connection->fetchOne($sql);
        return $hash;
    }

    /**
     * Login post action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $credentials = $this->helper->jsonDecode($this->getRequest()->getContent());
        if (!$this->getRequest()->isAjax()) {
            return false;
        }
        $otp = '';
        $email = '';
        $password = '';
        $phone = '';

        if($this->getScopeConfig()->getValue('glenands_sms/module/enabled') && $this->getScopeConfig()->getValue('glenands_sms/otp/enabled')) { 
            if(isset($credentials['username']) && $credentials['username']) {
                $email = $credentials['username'];
            }

            if(isset($credentials['password']) && $credentials['password']) {
                $password = $credentials['password'];
            }

            if(isset($credentials['otp']) && $credentials['otp']) {
                $otp = $credentials['otp'];
            }

            if(isset($credentials['phone_number']) && $credentials['phone_number']) {
                $phone = $credentials['phone_number'];
            }

            /*if(preg_match('/^([0|\+[0-9]{1,5})?([7-9][0-9]{9})$/', $phone,$matches)){
                $phone = $credentials['phone_number'];
            } else {
                $response = [
                    'errors' => true,
                    'message' => __('Invalid phone number!!'),
                    'phoneRequired' => true
                ];
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData($response);
            }*/
            
            $credentials = null;
            $httpBadRequestCode = 400;

            /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
            $resultRaw = $this->resultRawFactory->create();
            try {
                $credentials = $this->helper->jsonDecode($this->getRequest()->getContent());
            } catch (\Exception $e) {
                return $resultRaw->setHttpResponseCode($httpBadRequestCode);
            }
            if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
                return $resultRaw->setHttpResponseCode($httpBadRequestCode);
            }
            
            if ($this->getRequest()->isPost()) {
                
                $customer = '';
                if(empty($otp)) {
                    $customer = $this->customerAccountManagement->authenticate($email, $password);
                }
                
                if(empty($otp) && !empty($customer) && $customer->getCustomAttribute('phone_number') && $customer->getCustomAttribute('phone_number')->getValue()) {
                    $phone = $customer->getCustomAttribute('phone_number')->getValue();
                }
                if(empty($otp) && empty($phone)) {
                    $response = [
                        'errors' => true,
                        'message' => __('No phone number associated with this account please update the phone number.'),
                        'phoneRequired' => true
                    ];
                    $resultJson = $this->resultJsonFactory->create();
                    return $resultJson->setData($response);
                }

                $sms = '';
                if(!empty($phone) && empty($otp)) {
                    $sms        = $this->smsFactory->create()->load($email, 'user_name');
                    $sms->setData('status', 1);
                    $sms->setData('user_name', $email);
                    $sms->setData('pass', $this->encryptor->encrypt($password));
                    $sms->setData('type', 'otp');
                    $otp = $this->generateNumericOTP(self::OTP_DIGIT);

                    do {
                        $smsGrid = $this->smsCollectionFactory->create()->addFieldToFilter('user_name', ['eq' => $email])->addFieldToFilter('otp_text', ['eq' => $otp]);
                        $otp = $this->generateNumericOTP(self::OTP_DIGIT);
                    } while (count($smsGrid) < 0);

                    //$customerData = $this->customerRepository->get($email);
                    $sms->setData('otp_text', $otp);
                    $sms->setData('otp_for', $phone);
                    $sms->save();

                    $response = $this->sms->sendOtp($phone, $otp);

                    if($response == true) {
                        $sms->setData('status', 1);
                        $message = 'OTP generated Successfully!';
                        $response = [
                            'errors' => false,
                            'message' => __($message),
                            'isOtpSuccess' => true
                        ];
            
                        $resultJson = $this->resultJsonFactory->create();
                        return $resultJson->setData($response);
                    } else {
                        $sms->setData('status', 0);
                        $message = 'Error while generating OTP! Please try again!';
                        $response = [
                            'errors' => true,
                            'message' => __($message)
                        ];
            
                        $resultJson = $this->resultJsonFactory->create();
                        return $resultJson->setData($response);
                    }

                    $sms->save();

                    $resultJson = $this->resultJsonFactory->create();
                    return $resultJson->setData($response);
                }

                if(!empty($otp)) {
                    $sms        = $this->smsFactory->create()->load($otp, 'otp_text');
                    
                    $credentials['username'] = $sms->getData('user_name');
                    $credentials['password'] = $this->encryptor->decrypt($sms->getData('pass'));
                        if(!empty($sms) && $sms->getData('user_name') && !empty($otp) && $sms->getData('otp_text') == $otp) {
                            $customer = $this->customerRepository->get($sms->getData('user_name'));
                            if($customer->getId()){
                                $credentials['username'] = $customer->getEmail();
                                $credentials['password'] = $this->encryptor->decrypt($sms->getData('pass'));
                            }
                        } else {
                            $response = [
                                'errors' => true,
                                'message' => __('Invalid OTP please try again!')
                            ];
                
                            $resultJson = $this->resultJsonFactory->create();
                            return $resultJson->setData($response);
                        }

                    try {
                        $customer = $this->customerAccountManagement->authenticate(
                            $credentials['username'],
                            $credentials['password']
                        );
                        $this->session->setCustomerDataAsLoggedIn($customer);
                        $redirectRoute = $this->getAccountRedirect()->getRedirectCookie();
                        if ($this->cookieManager->getCookie('mage-cache-sessid')) {
                            $metadata = $this->cookieMetadataFactory->createCookieMetadata();
                            $metadata->setPath('/');
                            $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
                        }
                        if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectRoute) {
                            $response['redirectUrl'] = $this->_redirect->success($redirectRoute);
                            $this->getAccountRedirect()->clearRedirectCookie();
                        }
                    } catch (LocalizedException $e) {
                        $response = [
                            'errors' => true,
                            'message' => $e->getMessage(),
                        ];
                    } catch (\Exception $e) {
                        $response = [
                            'errors' => true,
                            'message' => __($e->getMessage()),
                        ];
                    }
                    /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                    $resultJson = $this->resultJsonFactory->create();
                    return $resultJson->setData($response);
                }
            }
        } else {
            $response = [
                'errors' => true,
                'message' => __('Please enable the OTP settings.')
            ];

            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData($response);
        }
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
