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

    public function send($device_id)
    {
        $data = [];
        $public_key = 'BFn4qEo_D1R50vPl58oOPfkQgbTgaqmstMhIzWyVgfgbMQPtFk94X-ThjG0hfOTSAQUBcCBXpPHeRMN7cqDDPaE';
        $private_key = 'r2nph41fesUhfHitp1wbldZvIu_I51Aiy-S8w7fpv-U';
        //$api_key = 'AAAAwdkoDtM:APA91bGOxLHjmDeyzCj7Eix-8M1vHOkvhBUxFBUC_XWcUIksOrVtdI2vFYae-d1AlNRAmmb_RFHTCZw9CStzc-z2qJ50B1cCNhlpouO8Wkt_bBxzTq4HYq3IbxTqtolTMGJFBi4DPatv';
        $device = $this->load($device_id);
        if(!$device->getId()){
            return false;
        }
        $device =$device->getData();
        $data['subscription'] = [
            "endpoint" => $device['endpoint'],
            "expirationTime" => null,
            "keys" => [
                "p256dh" => $device['p256dh_key'],
                "auth" => $device['auth_key']
            ]
        ];
        $data['applicationKeys'] = [
            "public" => $public_key,
            "private" => $private_key
        ];
        $headers = [
            //'Authorization: key=' . $api_key,
            'Content-Type: application/json'
        ];
        $data = json_encode($data);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://web-push-codelab.glitch.me/api/send-push-msg');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if ($result['success']) {
            return true;
        }
        return false;
    }
}
