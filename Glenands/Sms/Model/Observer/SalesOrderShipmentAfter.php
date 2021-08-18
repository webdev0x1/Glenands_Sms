<?php
namespace Glenands\Sms\Model\Observer;

use Glenands\Sms\Model\Sms\Sms;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

class SalesOrderShipmentAfter implements ObserverInterface
{
    protected $sms;
    protected $customerRepository;

    public function __construct(Sms $sms, CustomerRepository $customerRepository) {
        $this->sms = $sms;
        $this->customerRepository = $customerRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $customerId = $event->getShipment()->getCustomerId();
        $customer = '';
        
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch(\Exception $e) {
        }

        if($customer) {
            $this->sms->sendShimentNotification($customer->getCustomAttribute('phone_number')->getValue());
        } else {
            if(!$observer->getShipment()->getBillingAddress()->getTelephone() && $observer->getShipment()->getShippingAddress()->getTelephone()) {
                $this->sms->sendShimentNotification($observer->getShipment()->getShippingAddress()->getTelephone());
            }

            if(!$observer->getShipment()->getShippingAddress()->getTelephone() && $observer->getShipment()->getBillingAddress()->getTelephone()) {
                $this->sms->sendShimentNotification($observer->getShipment()->getBillingAddress()->getTelephone());
            }
        }
    }
}
