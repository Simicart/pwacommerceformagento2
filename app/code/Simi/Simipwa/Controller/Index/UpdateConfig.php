<?php

namespace Simi\Simipwa\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class UpdateConfig extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $scopeConfigInterface = $this->_objectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $token =  $scopeConfigInterface->getValue('simiconnector/general/token_key');
        $config = file_get_contents("https://www.simicart.com/appdashboard/rest/app_configs/bear_token/".$token);
        if (!$config || (!$config = json_decode($config, 1)))
            throw new \Exception(__('We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'), 4);
        $this->_objectManager->get('Simi\Simipwa\Helper\Data')->updateConfigJsFile($config);
        $result = array(
            "pwa" => array('success' => true)
        );
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(json_encode($result));
    }
}
