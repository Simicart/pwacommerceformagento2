<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/29/18
 * Time: 9:28 PM
 */

namespace Simi\Simipwa\Observer;

use Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;

class AddSiteMapToAPIGetStoreview implements ObserverInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface as ObjectManager
     */
    private $simiObjectManager;

    public function __construct(ObjectManager $simiObjectManager)
    {
        $this->simiObjectManager = $simiObjectManager;
    }

    /**
     * Add site map data to api get storeview
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $observerObject = $observer->getObject();
        $data = $observerObject->getData();
        if (isset($data['params']) && isset($data['params']['pwa'])) {
            $obj = $observer['object'];
            $info = $obj->storeviewInfo;
            /*
            $storeId = ($data['resourceid'] == 'default')?$this->simiObjectManager
                ->get('\Magento\Store\Model\StoreManagerInterface')
                ->getStore()->getId():$data['resourceid'];
            $siteMap = $this->simiObjectManager->get('\Simi\Simipwa\Helper\Data')->getSiteMaps($storeId);
            if ($siteMap && isset($siteMap['sitemaps'])) {
                $info['urls'] = $siteMap['sitemaps'];
            }
            */
            $scopeConfigInterface = $this->simiObjectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
            $info['pwa_configs'] = [
                'pwa_enable'=> $scopeConfigInterface->getValue('simipwa/general/pwa_enable'),
                'pwa_url'=> $scopeConfigInterface->getValue('simipwa/general/pwa_url'),
                'pwa_excluded_paths'=> $scopeConfigInterface->getValue('simipwa/general/pwa_excluded_paths'),
            ];
            $GATokenKey = $scopeConfigInterface->getValue('simipwa/general/ga_token_key');
            if ($GATokenKey) {
                $info['ga_token_key'] = $GATokenKey;
            }
            $obj->storeviewInfo = $info;
        }
    }
}
