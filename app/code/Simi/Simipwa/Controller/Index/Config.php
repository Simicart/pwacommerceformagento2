<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/30/18
 * Time: 3:47 PM
 */

namespace Simi\Simipwa\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class Config extends \Magento\Framework\App\Action\Action
{

    public $storeManager;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    }


    /**
     * @return mixed
     */
    public function execute()
    {
        $scopeConfigInterface = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $enable = (!$scopeConfigInterface->getValue('simipwa/general/pwa_enable'))?0:1;
        $build_time = $scopeConfigInterface->getValue('simipwa/general/build_time')?
            $scopeConfigInterface->getValue('simipwa/general/build_time') : 0;

        $result = array(
            'pwa' => array(
                //notification and offline
                'enable_noti' => (int)$scopeConfigInterface->getValue('simipwa/notification/enable'),
                //simicart advanced pwa
                'enable' => $enable,
                'build_time' => (int)$build_time
            )
        );

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(json_encode($result));
    }

}
