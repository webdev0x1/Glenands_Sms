<?php
/**
 * @category   Glenands
 * @package    Glenands_Sms
 * @author     Glenands
 * @copyright  Copyright (c) 2021 Empye Technologies LLP (https://www.empye.org/)
 */ 
namespace Glenands\Sms\Controller\Send;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $_resultJsonFactory;
    /**
    * @var Customer register mobile number
    */
    protected $_registerNumber;
    /**
     * @var \Glenands\Sms\Helper\Data
     */
    protected $_helper;
	/**
     * @var phone country code
     */
    protected $_countryCode;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Glenands\Sms\Helper\Data $helper
    )
    {

        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;  
        $this->_helper = $helper;
    }
    
    public function execute()
    {
        die();
        $resultJson = $this->_resultJsonFactory->create();
        if ($this->getRequest()->getPost()) {
            $messageData = $this->getRequest()->getPost();
            $this->_registerNumber = $messageData['phone_number'];
            $this->_countryCode = $messageData['country'];
            if ($messageData['request'] == 'generate') {
	            $result = $this->_helper->_sendSMS($this->_registerNumber, $this->_countryCode);
            } else {
                $result = $this->_helper->_verifyOtp($this->_registerNumber, $messageData['otp'], $this->_countryCode);
            }
        } else {
        	$result = [
            	'status' => false,
            	'message' => __("Required data is missing.")
            ];
        }
        $resultJson->setData($result);
        return $resultJson;
    }
}
