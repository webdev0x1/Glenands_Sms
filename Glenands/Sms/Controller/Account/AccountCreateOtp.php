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
/**
 * Class Otp
 * @package Cinovic\Otplogin\Controller\Account
 */ 
class AccountCreateOtp extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface, HttpPostActionInterface
{
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
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
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
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_messageManager = $messageManager;
        $this->request = $request;
        $this->smsFactory = $smsFactory;
        $this->_storeManager = $storeManager;
        $this->helper = $helper;
        $this->redirect = $redirect;
        $this->collection = $collection;
        $this->session = $customerSession;
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
        $sms = $this->smsFactory->create()->load($hashLogin, 'hashlogin');
            if(!empty($sms) && $sms->getData('hashlogin') == $hashLogin) {
                /** @var \Magento\Framework\View\Result\Page $resultPage */
                $resultPage = $this->resultPageFactory->create();
                $resultPage->setHeader('OTP-Required', 'true');
                return $resultPage;
            } else {
                $message = 'Please try login again!';
                $this->_messageManager->addError($message);

                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('customer/account/login');
                return $resultRedirect;
            }
    }
}
