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

        $enable = (int) $scopeConfigInterface->getValue('simipwa/general/pwa_enable');
        if (!$enable)
            return;
        $enable = (int) $scopeConfigInterface->getValue('simipwa/general/pwa_main_url_site');
        if (!$enable)
            return;

        $storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $urlInterface = $this->simiObjectManager->get('\Magento\Framework\UrlInterface');
        
        $redirectIps = $scopeConfigInterface->getValue('simipwa/general/pwa_redirect_ips');
        if ($redirectIps && $redirectIps!='' &&
            !in_array($_SERVER['REMOTE_ADDR'], explode(',', $redirectIps), true))
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
                if ($prerenderedHeader = $this->prerenderHeader()) {
                    $pwaContent = str_replace('<head>', '<head>'.$prerenderedHeader, $pwaContent);
                }
                $response->setHeader('Content-type', 'text/html; charset=utf-8', true);
                $response->setBody($pwaContent);
            }
        }
    }

    public function prerenderHeader() {
        try {
            $objectManager = $this->simiObjectManager;
            $homeJs = null;
            $productsJs = null;
            $manifestContent = file_get_contents('./pwa/assets-manifest.json');
            if ($manifestContent && $manifestJsFiles = json_decode($manifestContent, true)) {
                if (isset($manifestJsFiles['Products.js'])) {
                    $productsJs = $manifestJsFiles['Products.js'];
                }
                if (isset($manifestJsFiles['Product.js'])) {
                    $productJs = $manifestJsFiles['Product.js'];
                }
                if (isset($manifestJsFiles['HomeBase.js'])) {
                    $homeJs = $manifestJsFiles['HomeBase.js'];
                }
            }

            $preloadData = array('preload_js'=>array());

            
            $uri = $_SERVER['REQUEST_URI'];

            $uriparts = explode("pwa/", $uri);
            if ($uriparts && isset($uriparts[1]))
                $uri = $uriparts[1];
            $uriparts = explode("?", $uri);
            if ($uriparts && isset($uriparts[1]))
                $uri = $uriparts[0];
            $store = $objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStore();
            $storeId = $store->getId();
            $finder = $objectManager->get('Magento\UrlRewrite\Model\UrlFinderInterface');
            $match = $finder->findOneByData([
                'request_path' => ltrim($uri, '/'),
                'store_id' => $storeId,
            ]);
            if ($match && $match->getEntityType()) {
                if ($match->getEntityType() == 'product') {
                    $product = $objectManager->get('Magento\Catalog\Model\Product')->load($match->getEntityId());
                    if ($product->getId()) {
                        $preloadData['meta_title'] = $product->getMetaTitle()?$product->getMetaTitle():$product->getName();
                        $preloadData['meta_description'] = $product->getMetaDescription()?$product->getMetaDescription():substr($product->getDescription(), 0, 255);
                        $preloadData['preload_js'][] = $productJs;
                    }
                } else if ($match->getEntityType() == 'category') {
                    $category = $objectManager->get('Magento\Catalog\Model\Category')->load($match->getEntityId());
                    if ($category->getId()) {
                        $collection = $category->getResourceCollection();
                        $pathIds = array_reverse($category->getPathIds());
                        $collection->addAttributeToSelect('name');
                        $collection->addAttributeToFilter('entity_id', array('in' => $pathIds));

                        $group = $objectManager->get('\Magento\Store\Model\Group')->load($store->getGroupId());
                        $catNamearray = [];
                        foreach ($collection as $cat) {
                            $catNamearray[$cat->getId()] = $cat->getName();
                        }
                        $metaTitle = [];
                        foreach ($pathIds as $index=>$path) {
                            if ($path == $group->getData('root_category_id'))
                                break;
                            $metaTitle[] = $catNamearray[$path];
                        }
                        $metaTitle = implode(' - ', $metaTitle);
                        $preloadData['meta_title'] = $metaTitle?$metaTitle:$category->getName();
                        $preloadData['meta_description'] = $preloadData['meta_title'];
                        $preloadData['preload_js'][] = $productsJs;
                    }
                }
            }
        }catch (\Exception $e) {

        }

        $headerString = '';
        $preloadedHomejs = false;
        if (isset($preloadData['meta_title'])) {
            $headerString .= '<title>'.$preloadData['meta_title'].'</title>';
        }

        if (isset($preloadData['meta_description'])){
            $headerString .= '<meta name="description" content="'.$preloadData['meta_description'].'"/>';
        }
        if (!count($preloadData['preload_js'])) {
            $preloadedHomejs = true;
            $preloadData['preload_js'][] = $homeJs;
        }

        if (count($preloadData['preload_js'])) {
            foreach ($preloadData['preload_js'] as $preload_js) {
                if ($preload_js)
                    $headerString.= '<link rel="preload" as="script" href="/pwa/' . $preload_js . '">';
            }
        }

        try {
            //Add Storeview API
            $storeviewModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Storeviews');
            $data = [
                'resource'       => 'storeviews',
                'resourceid'     => 'default',
                'params'         => ['email'=>null, 'password'=>null],
                'contents_array' => [],
                'is_method'      => 1, //GET
                'module'         => 'simiconnector'
            ];
            $storeviewModel->setData($data);
            $storeviewModel->setBuilderQuery();
            $storeviewModel->setSingularKey('storeviews');
            $storeviewModel->setPluralKey('storeviews');
            $storeviewApi = json_encode($storeviewModel->show());
            $headerString .= '
            <script type="text/javascript">
                var SIMICONNECTOR_STOREVIEW_API = '.$storeviewApi.';
            </script>';

            //Add HOME API
            if (false) {
            //if ($preloadedHomejs) {
                $homeModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Homes');
                $data = [
                    'resource'       => 'homes',
                    'resourceid'     => 'lite',
                    'params'         => ['email'=>null, 'password'=>null, 'get_child_cat'=>true],
                    'contents_array' => [],
                    'is_method'      => 1, //GET
                    'module'         => 'simiconnector'
                ];
                $homeModel->setData($data);
                $homeModel->setBuilderQuery();
                $homeModel->setSingularKey('homes');
                $homeModel->setPluralKey('homes');
                $homeAPI = json_encode($homeModel->show());
                $headerString .= '
            <script type="text/javascript">
                var SIMICONNECTOR_HOME_API = '.$homeAPI.';
            </script>';
            }
        }catch (\Exception $e) {
            
        }

        return $headerString;
    }

}
