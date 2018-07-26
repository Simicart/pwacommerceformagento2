<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */
namespace Simi\Simipwa\Model\Api;

use Simi\Simiconnector\Model\Api\Apiabstract;

class Sociallogins extends Apiabstract
{

    public $DEFAULT_ORDER = 'entity_id';
    public $RETURN_MESSAGE;

    public function setBuilderQuery()
    {

        $data = $this->getData();
        $customerModel = $this->simiObjectManager->get('Simi\Simipwa\Model\Customermap');
        $simiCustomerHelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer');
        $params = $data['params'];

        if (isset($params['hash']) && $params['hash'] !== '' && isset($params['email']) && $params['email'] !== '') {
            $customer = $customerModel->createCustomer($params);
            $simiCustomerHelper->loginByCustomer($customer);
        } else {
            $customer = $customerModel->getCustomerByProviderIdAndUId($params['providerId'], $params['uid']);
            $simiCustomerHelper->loginByCustomer($customer);
        }

        $this->builderQuery = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();

        if(!$this->builderQuery->getId()){
            throw new \Simi\Simiconnector\Helper\SimiException(__('Login Failed'), 4);
        }
    }

    /*
     * Register
     */

    public function index()
    {
        return $this->show();
    }


    public function getDetail($info)
    {
        return ['customer' => $this->motifyFields($info)];
    }

}
