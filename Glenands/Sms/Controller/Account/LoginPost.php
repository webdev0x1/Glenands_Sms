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
class LoginPost extends \Magento\Customer\Controller\Account\LoginPost
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
     * @var Sms
     */
    protected $sms;

    /**
     * @var smsFactory
     */
    protected $smsCollectionFactory;

    /**
     * \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    
    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerUrl $customerHelperData
     * @param Validator $formKeyValidator
     * @param Encryptor $encryptor
     * @param PageFactory $resultPageFactory
     * @param SmsFactory $smsFactory
     * @param Sms $sms
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param SmsCollectionFactory $smsCollectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customer
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
        AccountRedirect $accountRedirect
    ) {
        $this->customerRepository = $customer;
        $this->sms = $sms;
        $this->smsFactory = $smsFactory;
        $this->encryptor = $encryptor;
        $this->smsCollectionFactory = $smsCollectionFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $customerSession, $customerAccountManagement, $customerHelperData, $formKeyValidator, $accountRedirect);
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
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                    if($this->getScopeConfig()->getValue('glenands_sms/module/enabled') && $this->getScopeConfig()->getValue('glenands_sms/otp/enabled')) {
                        $customer = '';
                        $hasError = false;
                        try {
                            $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                        } catch(\Exception $e) {
                            $message = __(
                                'Invalid login or password!'
                            );
                            if (isset($message)) {
                                $this->messageManager->addErrorMessage($message);
                            }
                            $hasError = true;
                        }      

                        if(!$hasError) {
                            $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
			} else {
    			    $message = __(
                                'Invalid login or password!'
                            );
                            if (isset($message)) {
                                $this->messageManager->addErrorMessage($message);
                            }
                            $resultRedirect = $this->resultRedirectFactory->create();
                            $resultRedirect->setPath('*/*/');
                            return $resultRedirect;
			}
                        if($customer->getId()) {
                            $sms        = $this->smsFactory->create()->load($customer->getEmail(), 'user_name');
                            $sms->delete();
                            $sms        = $this->smsFactory->create()->load($customer->getEmail(), 'user_name');
                            $sms->setData('status', 1);
                            $sms->setData('user_name', $customer->getEmail());
                            $sms->setData('type', 'otp');
                            $sms->save();

                            $sms        = $this->smsFactory->create()->load($customer->getEmail(), 'user_name');
                            
                            $uniqueHash = $this->encryptor->getHash("username:".$login['username']."password:".$this->getCurrentPasswordHash($customer->getId())."time:".$sms->getData('created_at'), true);
                            $sms->setData('hashlogin', $uniqueHash);
                            $otp = $this->generateNumericOTP(self::OTP_DIGIT);
                            $email = $customer->getEmail();
                            if($customer->getCustomAttribute('phone_number')) {
                                $sms->setData('otp_for', $customer->getCustomAttribute('phone_number')->getValue());
                            } else {
                                $sms->save();
                                $params = array('hash' => $uniqueHash);
                                $resultRedirect = $this->resultRedirectFactory->create();
                                $resultRedirect->setPath('glenands/account/otp', $params);
                                return $resultRedirect;
                            }
                                
                            $sms->save();
                            do {
                                $smsGrid = $this->smsCollectionFactory->create()->addFieldToFilter('user_name', ['eq' => $email])->addFieldToFilter('otp_text', ['eq' => $otp]);
                                $otp = $this->generateNumericOTP(self::OTP_DIGIT);
                            } while (count($smsGrid) < 0);
                            $customerData = $this->customerRepository->get($customer->getEmail());
                            $response  = '';
                            //print_r($customerData->getCustomAttribute('phone_number')->getValue());die();
                            if(!empty($customerData->getCustomAttribute('phone_number'))) {
                                $response = $this->sms->sendOtp($customerData->getCustomAttribute('phone_number')->getValue(), $otp);
                            }
                            
                            if($response == true) {
                                $sms->setData('otp_text', $otp);
                                $sms->setData('status', 1);
                            } else {
                                $sms->setData('status', 0);
                            }
                            $sms->save();
                            if ($this->session->isLoggedIn()) {
                                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                                $resultRedirect = $this->resultRedirectFactory->create();
                                $resultRedirect->setPath('*/*/');
                                return $resultRedirect;
                            }
                            
                            $params = array('hash' => $uniqueHash);
                            
                            $resultRedirect = $this->resultRedirectFactory->create();
                            $resultRedirect->setPath('glenands/account/otp', $params);
                            return $resultRedirect;
                        }
                        
                    } else {
                        try {
                            $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                            $sms        = $this->smsFactory->create()->load($login['username'], 'user_name');
                            $sms->delete();
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
