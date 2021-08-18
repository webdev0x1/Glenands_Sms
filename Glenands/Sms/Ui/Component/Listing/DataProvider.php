<?php
/**
 * Copyright © Empye Technologies LLP. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Glenands\Sms\Ui\Component\Listing;

use Glenands\Sms\Model\ResourceModel\Sms\Grid\Collection as GridCollection;
use Glenands\Sms\Model\ResourceModel\Sms\CollectionFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Custom DataProvider for customer addresses listing
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface $request,
     */
    private $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->request = $request;
    }

    /**
     *
     * @return array
     */
    public function getData(): array
    {
        /** @var GridCollection $collection */
        $collection = $this->getCollection();
        $data['items'] = [];
        
        if ($this->request->getParam('id')) {
            //echo $this->request->getParam('id');
            $collection->addFieldToFilter('id', $this->request->getParam('id'));
            $data = $collection->toArray();
        }
        return $data;
    }
}
