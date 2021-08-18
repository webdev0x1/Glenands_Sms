<?php

declare(strict_types=1);

namespace Glenands\Sms\Ui\Component;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Glenands\Sms\Model\ResourceModel\Sms\Collection;
use Glenands\Sms\Model\ResourceModel\Sms\CollectionFactory;

/**
 * Class DataProvider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Glenands\Sms\Model\ResourceModel\Sms\CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return [
            // return the form data here
        ];
    }
}