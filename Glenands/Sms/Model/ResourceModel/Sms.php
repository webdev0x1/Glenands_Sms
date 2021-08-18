<?php

namespace Glenands\Sms\Model\ResourceModel;

class Sms extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {       
        $this->_init('glenands_sms', 'id');
    }
}