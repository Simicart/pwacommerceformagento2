<?php

namespace Simi\Simipwa\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Simi\Simipwa\Helper\Data;

class UpdateConfig extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $buildTime = time();
        $scopeConfigInterface = $this->_objectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        //update index.html file
        $prev_time = $scopeConfigInterface->getValue('simipwa/general/build_time');
        $path_to_file = './pwa/index.html';
        $file_contents = file_get_contents($path_to_file);
        $file_contents = str_replace("?v=$prev_time", "?v=$buildTime", $file_contents);
        file_put_contents($path_to_file, $file_contents);

        //update config file
        $token =  $scopeConfigInterface->getValue('simiconnector/general/token_key');
        $config = file_get_contents("https://www.simicart.com/appdashboard/rest/app_configs/bear_token/".$token.'/pwa/1');
        if (!$config || (!$config = json_decode($config, 1)))
            throw new \Exception(__('We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'), 4);
        $this->_objectManager->get('Simi\Simipwa\Helper\Data')->updateConfigJsFile($config, $buildTime, Data::BUILD_TYPE_LIVE);

        $result = array(
            "pwa" => array('success' => true)
        );
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(json_encode($result));
    }
}
