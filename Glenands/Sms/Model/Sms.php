<?php

namespace Glenands\Sms\Model;

class Sms extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Glenands\Sms\Model\ResourceModel\Sms');
    }
}
