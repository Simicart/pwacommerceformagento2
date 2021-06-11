<?php

namespace Simi\Simipwa\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Simi\Simipwa\Model\ResourceModel\Device as DeviceRM;
use Simi\Simipwa\Model\ResourceModel\Device\Collection;

/**
 * Simipwa Model
 *
 * @method \Simi\Simipwa\Model\Resource\Page _getResource()
 * @method \Simi\Simipwa\Model\Resource\Page getResource()
 */
class Device extends AbstractModel
{

    /**
     * @var \Simi\Simipwa\Helper\Website
     * */
    public $websiteHelper;
    public $simiObjectManager;
    public $countryCollectionFactory;

    /**
     * /**
     * Device constructor.
     * @param Context $context
     * @param ObjectManagerInterface $simiObjectManager
     * @param Registry $registry
     * @param DeviceRM $resource
     * @param Collection $resourceCollection
     * @param Website $websiteHelper
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $simiObjectManager,
        Registry $registry,
        DeviceRM $resource,
        Collection $resourceCollection,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
    ) {


        $this->simiObjectManager = $simiObjectManager;
        $this->countryCollectionFactory = $countryCollectionFactory;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simipwa\Model\ResourceModel\Device');
    }

    public function toOptionCountryHash()
    {
        $country_collection = $this->countryCollectionFactory->create();
        $list = [];
        if (count($country_collection) > 0) {
            foreach ($country_collection as $country) {
                $list[$country->getId()] = $country->getName();
            }
        }
        return $list;
    }
}
