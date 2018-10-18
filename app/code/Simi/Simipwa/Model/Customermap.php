<?php

namespace Simi\Simipwa\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Simi\Simipwa\Model\ResourceModel\Customermap as CustomermapRM;
use Simi\Simipwa\Model\ResourceModel\Customermap\Collection;

/**
 * Simipwa Model
 *
 * @method \Simi\Simipwa\Model\Resource\Page _getResource()
 * @method \Simi\Simipwa\Model\Resource\Page getResource()
 */
class Customermap extends AbstractModel
{

    /**
     * @var \Simi\Simipwa\Helper\Website
     * */
    public $simiObjectManager;

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
        CustomermapRM $resource,
        Collection $resourceCollection
    )
    {


        $this->simiObjectManager = $simiObjectManager;
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
        $this->_init('Simi\Simipwa\Model\ResourceModel\Customermap');
    }


    public function createCustomer($params)
    {
        $customer = $this->simiObjectManager->create('Magento\Customer\Model\Customer')
            ->setFirstname($params['firstname'])
            ->setLastname($params['lastname'])
            ->setEmail($params['email']);


        $data = new \Magento\Framework\DataObject();
        $data->setData('firstname', $params['firstname']);
        $data->setData('lastname', $params['lastname']);
        $data->setData('email', $params['email']);

        $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer')->applyDataToCustomer($customer, $data);
        $encodeMethod = 'md5';
        $password = 'simipassword'
            . rand(pow(10, 9), pow(10, 10)) . substr($encodeMethod(microtime()), rand(0, 26), 5);

        if (isset($params['hash']) && $params['hash'] !== '') {
            $password = $params['hash'];
        }

        $customer->setPassword($password);
        $customer->save();

        $dataMap = array(
            'customer_id' => $customer->getId(),
            'social_user_id' => $params['uid'],
            'provider_id' => $params['providerId']
        );

        $this->setData($dataMap)->save();

        return $customer;
    }

    public function getCustomerByProviderIdAndUId($providerId, $uid)
    {
        $customerMap = $this->getCollection()
            ->addFieldToFilter('provider_id', array('eq' => $providerId))
            ->addFieldToFilter('social_user_id', array('eq' => $uid))
            ->getFirstItem();
        if ($customerMap->getId()) {
            return $this->simiObjectManager->create('Magento\Customer\Model\Customer')->load($customerMap->getCustomerId());
        }
        throw new \Exception(__('Can not find customer'), 4);
    }
}
