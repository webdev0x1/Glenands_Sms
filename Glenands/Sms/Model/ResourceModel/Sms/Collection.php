<?php
namespace Glenands\Sms\Model\ResourceModel\Sms;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    
    public function _construct()
    {
        $this->_init('Glenands\Sms\Model\Sms', 'Glenands\Sms\Model\ResourceModel\Sms');
    }
}