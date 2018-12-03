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

class Config extends \Simi\Simipwa\Controller\Action
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $scopeConfigInterface = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $enable = (!$scopeConfigInterface->getValue('simipwa/general/pwa_enable'))?0:1;
        $build_time = $scopeConfigInterface->getValue('simipwa/general/build_time')?
            $scopeConfigInterface->getValue('simipwa/general/build_time') : 0;

        if(!$this->_getCheckoutSession()->getData('simiconnector_platform') ||
            $this->_getCheckoutSession()->getData('simiconnector_platform') != 'pwa') {
            $this->_getCheckoutSession()->setData('simiconnector_platform', 'pwa');
        }

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

    public function _getCheckoutSession()
    {
        return $this->_objectManager->create('Magento\Checkout\Model\Session');
    }

}
