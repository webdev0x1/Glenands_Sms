<?php
/**
 * Empye Technologies LLP.
 */

namespace Glenands\Sms\Controller\Account;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Glenands\Sms\Model\SmsFactory;
use Magento\Framework\Controller\ResultFactory;
use Glenands\Sms\Model\ResourceModel\Sms\CollectionFactory as SmsCollectionFactory;
use Glenands\Sms\Model\Sms\Sms;

/**
 * Class Otp
 * @package Cinovic\Otplogin\Controller\Account
 */ 
class Resend extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface, HttpPostActionInterface
{
    CONST OTP_DIGIT = 6;

    /**
     * \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var smsFactory
     */
    protected $smsFactory;
    
    /**
     * @var smsFactory
     */
    protected $smsCollectionFactory;

    /**
     * @var Sms
     */
    protected $sms;

    protected $customerRepository;

    /**
     * Otp constructor
     * @param \Magento\Framework\App\Action\Context                $context            [description]
     * @param \Magento\Framework\Session\SessionManagerInterface   $session            [description]
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param \Glenands\Sms\Helper\Data                        $helper             [description]
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Framework\Controller\Result\JsonFactory     $resultJsonFactory  [description]
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $collection    [description]    
     * @param \Magento\Framework\App\Request\Http $request
     * @param SmsFactory $smsFactory
     * @param Sms $sms
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $session,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Glenands\Sms\Helper\Data $helper,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\ResourceModel\Customer\Collection $collection,
        \Magento\Framework\App\Request\Http $request,
        SmsFactory $smsFactory,
        Sms $sms,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        SmsCollectionFactory $smsCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerRepository = $customer;
        $this->sms = $sms;
        $this->_messageManager = $messageManager;
        $this->request = $request;
        $this->smsFactory = $smsFactory;
        $this->_storeManager = $storeManager;
        $this->helper = $helper;
        $this->redirect = $redirect;
        $this->collection = $collection;
        $this->session = $customerSession;
        $this->smsCollectionFactory = $smsCollectionFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_sessionManager = $session;
        parent::__construct($context);
    }

    /**
     * @return PageFactory
     */
    public function execute()
    {
        if ($this->session->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
        
        $hashLogin = $this->request->getParam('hash');
        $phone = $this->getRequest()->getParam('phone_number');
        $phone = trim($phone);
        $sms = $this->smsFactory->create()->load($hashLogin, 'hashlogin');
        if(!empty($sms) && $sms->getData('hashlogin') == $hashLogin) {
            $sms        = $this->smsFactory->create()->load($sms->getData('user_name'), 'user_name');
            $sms->setData('status', 1);
            $sms->setData('user_name', $sms->getData('user_name'));
            $sms->setData('type', 'otp');
            $sms->setData('hashlogin', $hashLogin);
            $otp = $this->generateNumericOTP(self::OTP_DIGIT);
            $email = $sms->getData('user_name');

            do {
                $smsGrid = $this->smsCollectionFactory->create()->addFieldToFilter('user_name', ['eq' => $email])->addFieldToFilter('otp_text', ['eq' => $otp]);
                $otp = $this->generateNumericOTP(self::OTP_DIGIT);
            } while (count($smsGrid) < 0);
            $isCustomer = true;
            $customerData = '';
            $response  = false;
            try {
                $customerData = $this->customerRepository->get($sms->getData('user_name'));
            } catch(\Exception $e) {
                $isCustomer = false;
            }
            $sms->setData('otp_text', $otp);
            $sms->save();
            
            if($customerData && empty($customerData->getCustomAttribute('phone_number')) && empty($phone)) {
                $this->messageManager->addErrorMessage(
                    __('Please update your phone number!')
                );
                
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/');
                return $resultRedirect;
            } else if($isCustomer && $customerData && !empty($customerData->getCustomAttribute('phone_number'))) {
                $response = $this->sms->sendOtp($customerData->getCustomAttribute('phone_number')->getValue(), $otp);
            } else if(!empty($phone)) {
                $response = $this->sms->sendOtp($phone, $otp);
            }

            if($response == true) {
                $sms->setData('status', 1);
                $sms->setData('otp_for', $phone);
                $message = 'OTP generated Successfully!';
                $this->_messageManager->addSuccess($message);
            } else {
                $sms->setData('status', 0);
                $message = 'Error while generating OTP! Please try again!';
                $this->_messageManager->addErrorMessage($message);
            }

            $sms->save();
            $params = [];
            if(!empty($phone)) {
                $params = array('hash' => $hashLogin, 'phone_number' => $phone);
                if($sms->getData('firstname')) {
                    $params['firstname'] = $sms->getData('firstname');
                }
                if($sms->getData('user_name')) {
                    $params['email'] = $sms->getData('user_name');
                }
                if($sms->getData('lastname')) {
                    $params['lastname'] = $sms->getData('lastname');
                }
                if($sms->getData('is_subscribed')) {
                    $params['is_subscribed'] = $sms->getData('is_subscribed');
                }
            } else {
                $params = array('hash' => $hashLogin);
            }

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('glenands/account/accountcreateotp', $params);
            return $resultRedirect;
        } else {
            $message = 'Please try login again!';
            $this->_messageManager->addError($message);
            if(!empty($this->redirect->getRefererUrl())) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);    
                $resultRedirect->setUrl('/customer/account/login/');
                return $resultRedirect;
            } 

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
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
