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

class Frontendcontrollerpredispatch implements ObserverInterface
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
        if ($this->simiObjectManager
            ->get('\Magento\Framework\Registry')
            ->registry('simipwa_checked_redirecting_once'))
            return;
        $this->simiObjectManager
            ->get('\Magento\Framework\Registry')
            ->register('simipwa_checked_redirecting_once', true);
        $scopeConfigInterface = $this->simiObjectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $urlInterface = $this->simiObjectManager->get('\Magento\Framework\UrlInterface');
        $enable = (int) $scopeConfigInterface->getValue('simipwa/general/pwa_enable');
        if (!$enable)
            return;

        $tablet_browser = 0;
        $mobile_browser = 0;

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $tablet_browser++;
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $mobile_browser++;
        }

        if (isset($_SERVER['HTTP_ACCEPT']) && isset($_SERVER['HTTP_X_WAP_PROFILE']) && isset($_SERVER['HTTP_PROFILE']))
            if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) && isset($_SERVER['HTTP_PROFILE'])))) {
                $mobile_browser++;
            }
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda ','xda-');

        if (in_array($mobile_ua,$mobile_agents)) {
            $mobile_browser++;
        }

        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'opera mini') !== false) {
            $mobile_browser++;
            //Check for tablets on opera mini alternative headers
            $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])?$_SERVER['HTTP_X_OPERAMINI_PHONE_UA']:(isset($_SERVER['HTTP_DEVICE_STOCK_UA'])?$_SERVER['HTTP_DEVICE_STOCK_UA']:''));
            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
                $tablet_browser++;
            }
        }
        if(($tablet_browser == 0) && ($mobile_browser == 0))
            return;

        $uri = $_SERVER['REQUEST_URI'];
        $baseUrl = $storeManager->getStore()->getBaseUrl();
        $currentUrl = $urlInterface->getCurrentUrl();

        if (strpos($currentUrl, $baseUrl) !== false) {
            $uri = '/'.str_replace($baseUrl, '', $currentUrl);
        }

        $excludedUrls = array('admin', 'simiconnector', 'simicustompayment', 'payfort', 'simipwa', 'rest/v2');

        $excludedPaths = str_replace(' ', '', $scopeConfigInterface->getValue('simipwa/general/pwa_excluded_paths'));
        $excludedPaths = explode(',', $excludedPaths);

        $excludedUrls = array_merge($excludedUrls, $excludedPaths);

        $isExcludedCase = false;

        foreach ($excludedUrls as $key => $excludedUrl) {
            if ($excludedUrl != '' && (strpos($uri, $excludedUrl) !== false)) {
                $isExcludedCase = true;
            }
        }
        if((($tablet_browser > 0)||($mobile_browser > 0)) && !$isExcludedCase){
            if (($pwaContent = @file_get_contents('./pwa/index.html')) &&
                ($response = $observer->getResponse())
            ) {
                $response->setHeader('Content-type', 'text/html; charset=utf-8', true);
                $response->setBody($pwaContent);
            }
        }
    }
}
