<?php
namespace Glenands\Sms\Ui\Component\Listing\Column;
 
use Magento\Catalog\Helper\Image;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
 
class Status extends Column
{
     public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Image $imageHelper,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
        )
     {
          $this->imageHelper = $imageHelper;
          $this->urlBuilder = $urlBuilder;
          parent::__construct($context, $uiComponentFactory, $components, $data);
     }
 
     public function prepareDataSource(array $dataSource)
     {
          if(isset($dataSource['data']['items']))
          {
               $fieldName = $this->getData('name');
               foreach($dataSource['data']['items'] as & $item)
               {
                    if($item[$fieldName] == 1)
                    {
                        $item[$fieldName] = 'Sent';
                    } else {
                        $item[$fieldName] = 'Failed';
                    }
               }
          }
 
          return $dataSource;
     }
 
}