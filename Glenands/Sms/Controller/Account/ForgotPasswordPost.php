<?php
namespace Glenands\Sms\Controller\Account;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Glenands\Sms\Model\Sms\Sms;
use Glenands\Sms\Model\ResourceModel\Sms\CollectionFactory as SmsCollectionFactory;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Glenands\Sms\Model\SmsFactory;
/**
 * ForgotPasswordPost controller
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ForgotPasswordPost extends \Magento\Customer\Controller\AbstractAccount implements HttpPostActionInterface
{

    CONST OTP_DIGIT = 6;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var Sms
     */
    protected $sms;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var smsFactory
     */
    protected $smsFactory;

    /**
     * @var smsCollectionFactory
     */
    protected $smsCollectionFactory;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerRepository $customerRepository,
        Sms $sms,
        Encryptor $encryptor,
        SmsFactory $smsFactory,
        SmsCollectionFactory $smsCollectionFactory,
        Escaper $escaper
    ) {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper = $escaper;
        $this->sms = $sms;
        $this->smsFactory = $smsFactory;
        $this->smsCollectionFactory = $smsCollectionFactory;
        $this->encryptor = $encryptor;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
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

    /**
     * Forgot customer password action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $email = (string)$this->getRequest()->getPost('email');
        if($this->getScopeConfig()->getValue('glenands_sms/module/enabled') && $this->getScopeConfig()->getValue('glenands_sms/otp/enabled')) {
            if($this->getRequest()->getParam('email') && empty($this->getRequest()->getParam('otp'))) {
                if ($email) {
                    if (!\Zend_Validate::is($email, \Magento\Framework\Validator\EmailAddress::class)) {
                        $this->session->setForgottenEmail($email);
                        $this->messageManager->addErrorMessage(
                            __('The email address is incorrect. Verify the email address and try again.')
                        );
                        return $resultRedirect->setPath('*/*/forgotpassword');
                    }
                }
                try {
                    $customer = $this->customerRepository->get($this->getRequest()->getParam('email'));
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(
                        __('The email address is not exist. Please enter your email!')
                    );
                    return $resultRedirect->setPath('*/*/forgotpassword');
                }
                
                if($customer->getId()) {
                    if($customer->getCustomAttribute('phone_number') && $customer->getCustomAttribute('phone_number')->getValue()) {
                            $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                            $sms->delete();
                            $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                            $sms->setData('status', 1);
                            $sms->setData('user_name', $this->getRequest()->getParam('email'));
                            $sms->setData('type', 'otp');
                            $sms->save();

                            $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                            
                            $uniqueHash = $this->encryptor->getHash("username:".$this->getRequest()->getParam('email')."time:".$sms->getData('created_at'), true);
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
                            if(!empty($customer->getCustomAttribute('phone_number'))) {
                                $response = $this->sms->sendOtp($customer->getCustomAttribute('phone_number')->getValue(), $otp);
                            }

                            if($response == true) {
                                $sms->setData('otp_text', $otp);
                                $sms->setData('status', 1);
                            } else {
                                $sms->setData('status', 0);
                            }
                            
                            $sms->save();
                            
                            $params = array('hash' => $uniqueHash, 'phone_number' => $customer->getCustomAttribute('phone_number')->getValue(), 'email' => $email);
                            
                            $resultRedirect = $this->resultRedirectFactory->create();
                            $resultRedirect->setPath('glenands/account/frpotp', $params);
                            return $resultRedirect;
                    }
                }
            } else {
                $sms        = $this->smsFactory->create()->load($this->getRequest()->getParam('email'), 'user_name');
                if(!empty($sms) && !empty($sms->getData('otp_text'))) {
                    if($this->getRequest()->getParam('email') == $sms->getData('user_name') && $this->getRequest()->getParam('otp') == $sms->getData('otp_text')) {
                            if ($email) {
                                if (!\Zend_Validate::is($email, \Magento\Framework\Validator\EmailAddress::class)) {
                                    $this->session->setForgottenEmail($email);
                                    $this->messageManager->addErrorMessage(
                                        __('The email address is incorrect. Verify the email address and try again.')
                                    );
                                    return $resultRedirect->setPath('glenands/account/forgotpassword');
                                }

                                try {
                                    $this->customerAccountManagement->initiatePasswordReset(
                                        $email,
                                        AccountManagement::EMAIL_RESET
                                    );
                                } catch (NoSuchEntityException $exception) {
                                    // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
                                } catch (SecurityViolationException $exception) {
                                    $this->messageManager->addErrorMessage($exception->getMessage());
                                    return $resultRedirect->setPath('glenands/account/forgotpassword');
                                } catch (\Exception $exception) {
                                    $this->messageManager->addExceptionMessage(
                                        $exception,
                                        __('We\'re unable to send the password reset email.')
                                    );
                                    return $resultRedirect->setPath('glenands/account/forgotpassword');
                                }
                                $this->messageManager->addSuccessMessage($this->getSuccessMessage($email));
                                return $resultRedirect->setPath('customer/account/login');
                            } else {
                                $this->messageManager->addErrorMessage(__('Please enter your email.'));
                                return $resultRedirect->setPath('glenands/account/forgotpassword');
                            }
                        }
                    }
                }
            }
    }

    /**
     * Retrieve success message
     *
     * @param string $email
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($email)
    {
        return __(
            'If there is an account associated with %1 you will receive an email with a link to reset your password.',
            $this->escaper->escapeHtml($email)
        );
    }
}
