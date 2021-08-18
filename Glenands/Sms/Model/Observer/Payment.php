<?php
namespace Glenands\Sms\Model\Observer;

use Glenands\Sms\Model\Sms\Sms;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

class Payment implements ObserverInterface
{
    protected $sms;
    protected $customerRepository;
    protected $order;

    public function __construct(Sms $sms, CustomerRepository $customerRepository,\Magento\Sales\Api\Data\OrderInterface $order) {
        $this->sms = $sms;
        $this->order = $order;
        $this->customerRepository = $customerRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $customerId = $event->getOrder()->getCustomerId();
        $customer = '';

        try {
        $customer = $this->customerRepository->getById($customerId);
        } catch(\Exception $e) {

        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $url = $storeManager->getStore()->getBaseUrl();
        $orderids = $observer->getEvent()->getOrderIds();
        $order = $this->order->load($event->getOrder()->getData('entity_id'));
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');

       // foreach($orderids as $orderid) {
         //   echo $customer->getCustomAttribute('phone_number')->getValue()."<br>".$customer->getFirstname().$customer->getLastname()."<br>".$orderid."<br>".$event->getOrder()->getAmountAuthorized()."<br>".$url."customer/account/";
         if($customer && $customer->getCustomAttribute('phone_number') && $customer->getCustomAttribute('phone_number')->getValue() && $customer->getId()){
            $this->sms->sendOrderPaymentNotification($customer->getCustomAttribute('phone_number')->getValue(), $customer->getFirstname().'%20'.$customer->getLastname(), $event->getOrder()->getData('increment_id'), $priceHelper->currency($order->getPayment()->getAmountAuthorized(), true, false), $url."customer/account/");
            $this->sms->sendOrderNotification($customer->getCustomAttribute('phone_number')->getValue(), $event->getOrder()->getData('increment_id'));
         } else {
            if(!$observer->getOrder()->getBillingAddress()->getTelephone() && $observer->getOrder()->getShippingAddress()->getTelephone()) {
                $this->sms->sendOrderPaymentNotification($observer->getOrder()->getShippingAddress()->getTelephone(), $observer->getOrder()->getShippingAddress()->getFirstname().'%20'.$observer->getOrder()->getShippingAddress()->getLastname(), $event->getOrder()->getData('increment_id'), $priceHelper->currency( $order->getPayment()->getAmountAuthorized(), true, false), $url."customer/account/");
                $this->sms->sendOrderNotification($observer->getOrder()->getShippingAddress()->getTelephone(), $event->getOrder()->getData('increment_id'));
            }

            if(!$observer->getOrder()->getShippingAddress()->getTelephone() && $observer->getOrder()->getBillingAddress()->getTelephone()) {
                $this->sms->sendOrderPaymentNotification($observer->getOrder()->getBillingAddress()->getTelephone(), $observer->getOrder()->getBillingAddress()->getFirstname().'%20'.$observer->getOrder()->getBillingAddress()->getLastname(), $event->getOrder()->getData('increment_id'), $priceHelper->currency( $order->getPayment()->getAmountAuthorized(), true, false), $url."customer/account/");
                $this->sms->sendOrderNotification($observer->getOrder()->getBillingAddress()->getTelephone(), $event->getOrder()->getData('increment_id'));
            }
         }
        //} 0o9aq
    }
}
