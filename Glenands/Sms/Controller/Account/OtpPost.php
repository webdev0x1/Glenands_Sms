<?php
namespace Glenands\Sms\Controller\Account;

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
class OtpPost extends AbstractAccount implements CsrfAwareActionInterface, HttpPostActionInterface
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
        AccountManagementInterface $customerAccountManagement,
        CustomerUrl $customerHelperData,
        Validator $formKeyValidator,
        Encryptor $encryptor,
        PageFactory $resultPageFactory,
        SmsFactory $smsFactory,
        Sms $sms,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        SmsCollectionFactory $smsCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        AccountRedirect $accountRedirect
    ) {
        $this->_customerFactory = $customerFactory;
        $this->customerRepository = $customer;
        $this->sms = $sms;
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
        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
       
        if ($this->getRequest()->isPost()) {
            $hash = $this->getRequest()->getPost('hash');
            $otp = $this->getRequest()->getPost('otp');
            $phone = $this->getRequest()->getPost('phone_number');
            $send = $this->getRequest()->getPost('send');
            $resend = $this->getRequest()->getPost('resend');
            
            $sms = '';
            if(!empty($phone) && empty($otp) && isset($send) || isset($resend)) {
                $sms        = $this->smsFactory->create()->load($hash, 'hashlogin');
                $sms->setData('status', 1);
                $sms->setData('user_name', $sms->getData('user_name'));
                $sms->setData('type', 'otp');
                $otp = $this->generateNumericOTP(self::OTP_DIGIT);
                $email = $sms->getData('user_name');

                do {
                    $smsGrid = $this->smsCollectionFactory->create()->addFieldToFilter('user_name', ['eq' => $email])->addFieldToFilter('otp_text', ['eq' => $otp]);
                    $otp = $this->generateNumericOTP(self::OTP_DIGIT);
                } while (count($smsGrid) < 0);

                $customerData = $this->customerRepository->get($email);
                $sms->setData('otp_text', $otp);
                $sms->setData('otp_for', $phone);
                $sms->save();

                $response = $this->sms->sendOtp($phone, $otp);

                if($response == true) {
                    $sms->setData('status', 1);
                    $message = 'OTP generated Successfully!';
                    $this->_messageManager->addSuccess($message);
                } else {
                    $sms->setData('status', 0);
                    $message = 'Error while generating OTP! Please try again!';
                    $this->_messageManager->addErrorMessage($message);
                }

                $sms->save();

                $params = array('hash' => $hash, 'phone_number' => $phone);
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('glenands/account/otp', $params);
                return $resultRedirect;
            }

            $hashAll = false;
            $login = [];

            $sms        = $this->smsFactory->create()->load($hash, 'hashlogin');
            if(!empty($sms) && $sms->getData('user_name') && !empty($otp)) {
                $customer = $this->customerRepository->get($sms->getData('user_name'));
                if($customer->getId()){
                    $login['username'] = $customer->getEmail();
                    $login['password'] = $this->getCurrentPasswordHash($customer->getId());
                    $hashAll = $this->encryptor->validateHash("username:".$login['username']."password:".$this->getCurrentPasswordHash($customer->getId())."time:".$sms->getData('created_at'), $hash);
                }
            } else {
                $this->messageManager->addErrorMessage(__('Invalid OTP! Please try again!'));
                return $this->accountRedirect->getRedirect();
            }

           // echo $hash;
            //$hashAll;
            //die();
            if($hashAll) {
                        //$customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                        if(!empty($otp) && !empty($sms->getData('otp_text')) && $sms->getData('otp_text') == $otp) {
                            $sms->delete();
                            try {
                                //$customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                                $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                                $websiteId = $storeManager->getStore()->getWebsiteId();
                                $customer = $this->customerRepository->get($sms->getData('user_name'), $websiteId);

                                $customerData = $this->_customerFactory->create()->load($customer->getId())->getDataModel();

                                $customer->setCustomAttribute('phone_number', $sms->getData('otp_for'));
                                $this->customerRepository->save($customer);
                                $customer = $this->customerRepository->get($sms->getData('user_name'), $websiteId);
                                $this->session->setCustomerDataAsLoggedIn($customer);
                                if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                                    $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                                    $metadata->setPath('/');
                                    $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                                }
                                $redirectUrl = $this->accountRedirect->getRedirectCookie();
                                if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
                                    $this->accountRedirect->clearRedirectCookie();
                                    $resultRedirect = $this->resultRedirectFactory->create();
                                    // URL is checked to be internal in $this->_redirect->success()
                                    $resultRedirect->setUrl('/customer/account/login');
                                    return $resultRedirect;
                                }
                            } catch (EmailNotConfirmedException $e) {
                                $this->messageManager->addComplexErrorMessage(
                                    'confirmAccountErrorMessage',
                                    ['url' => $this->customerUrl->getEmailConfirmationUrl($login['username'])]
                                );
                                $this->session->setUsername($login['username']);
                            } catch (AuthenticationException $e) {
                                $message = __(
                                    'The account sign-in was incorrect or your account is disabled temporarily. '
                                    . 'Please wait and try again later.'
                                );
                            } catch (LocalizedException $e) {
                                $message = $e->getMessage();
                            } catch (\Exception $e) {
                                // PA DSS violation: throwing or logging an exception here can disclose customer password
                                $this->messageManager->addErrorMessage(
                                    __('An unspecified error occurred. Please contact us for assistance.')
                                );
                            } finally {
                                if (isset($message)) {
                                    $this->messageManager->addErrorMessage($message);
                                    $this->session->setUsername($login['username']);
                                }
                            }
                        } else {
                            $this->messageManager->addErrorMessage(__('Invalid OTP! Please try again!'));
                        }
            } else {
                $this->messageManager->addErrorMessage(__('A login and a password are required.'));
            }
        }

        return $this->accountRedirect->getRedirect();
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
