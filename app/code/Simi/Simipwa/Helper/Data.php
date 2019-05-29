<?php

/**
 * Simipwa Helper
 */

namespace Simi\Simipwa\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use \Magento\Framework\DataObject;
use \Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const BUILD_TYPE_SANDBOX = 'sandbox';
    const BUILD_TYPE_LIVE = 'live';

    public $directionList;
    public $objectManager;
    public $storeManager;
    public $httpFactory;
    public $countryCollectionFactory;
    public $fileUploaderFactory;
    public $filesystem;

    public function __construct(
        Context $context,
        ObjectManagerInterface $manager,
        DirectoryList $directoryList,
        StoreManager $storemanager,
        CountryCollectionFactory $countryCollectionFactory
    )
    {
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->directionList = $directoryList;
        $this->objectManager = $manager;
        $this->storeManager = $storemanager;
        $this->httpFactory = $this->objectManager->create('\Magento\Framework\HTTP\Adapter\FileTransferFactory');
        $this->fileUploaderFactory = $this->objectManager
            ->create('\Magento\MediaStorage\Model\File\UploaderFactory');
        $this->filesystem = $this->objectManager->create('\Magento\Framework\Filesystem');
        parent::__construct($context);
    }

    /**
     * Get site map Data and cache to file
     */
    public function getSiteMaps($storeId)
    {
        if (!is_numeric($storeId)) {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                if ($store->getCode() === $storeId) {
                    $storeId = $store->getId();
                }
            }
        }
        $storePath = md5($storeId);
        $filePath = $this->directionList->getPath(DirectoryList::APP) . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'Simi' .
            DIRECTORY_SEPARATOR . 'Simipwa' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . $storePath . DIRECTORY_SEPARATOR;
        if (!is_dir($filePath)) {
            try {
                mkdir($filePath, 0777, true);
            } catch (\Exception $e) {
            }
        }
        $filePath .= 'sitemaps.json';
        if (file_exists($filePath)) {
            $sitemaps = file_get_contents($filePath);
            if (!$sitemaps) {
                $sitemaps = $this->getDataSiteMaps($storeId);
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }
            return json_decode($sitemaps, true);
        } else {
            $file = @fopen($filePath, 'w+');
            $sitemaps = $this->getDataSiteMaps($storeId);
            if ($file) {
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }
            return json_decode($sitemaps, true);
        }
    }

    /**
     * Get Product link, Category link and CMS from site mapp
     * @param $storeId
     * @return string
     */

    public function getDataSiteMaps($storeId)
    {
        $urls = [];
        // get categories
        $collection = $this->objectManager->get('Simi\Simipwa\Model\Catmap')->getCollection($storeId);
        $categories = new DataObject();
        $categories->setItems($collection);
        $categories_url = [];
        foreach ($categories->getItems() as $item) {
            $categories_url[] = [
                'id' => $item->getId(),
                'url' => $item->getUrl(),
                'hasChild' => $item->getChild() ? true : false,
            ];
        }
        $urls['categories_url'] = $categories_url;
        unset($collection);

        // get products
        $collection = $this->objectManager->get('Magento\Sitemap\Model\ResourceModel\Catalog\Product')->getCollection($storeId);
        $products = new DataObject();
        $products->setItems($collection);
        $products_url = [];
        foreach ($products->getItems() as $item) {
            $products_url[] = [
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            ];
        }
        $urls['products_url'] = $products_url;
        unset($collection);

        // // get cms pages
        $cms_url = [];
        $collection = $this->objectManager->get('Magento\Sitemap\Model\ResourceModel\Cms\Page')->getCollection($storeId);
        foreach ($collection as $item) {
            $cms_url[] = [
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            ];
        }
        $urls['cms_url'] = $cms_url;
        unset($collection);

        $result = [];
        $result['sitemaps'] = $urls;
        return json_encode($result);
    }

    /**
     * Clear the mobile caches
     */
    public function clearAppCaches()
    {
        $result = [];
        $stores = $this->storeManager->getStores();
        // clear site map
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $storeName = $store->getName();
            $flag = $this->_clearSiteMap($storeId);
            if ($flag) {
                $result[] = ['status' => 1, 'message' => "Clear site map store $storeName successfull!"];
            } else {
                $result[] = ['status' => 0, 'message' => "Clear site map store $storeName fail!"];
            }
        }

        return $result;
    }

    /**
     * clear site map
     * @param $storeId
     * @return bool
     */
    private function _clearSiteMap($storeId)
    {
        $storePath = md5($storeId);
        $filePath = $this->directionList->getPath(DirectoryList::APP) . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'Simi' .
            DIRECTORY_SEPARATOR . 'Simipwa' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . $storePath . DIRECTORY_SEPARATOR . "sitemaps.json";
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $file = @fopen($filePath, 'w+');
        $sitemaps = $this->getDataSiteMaps($storeId);
        if ($file) {
            file_put_contents($filePath, $sitemaps);
            return true;
        }

        return false;
    }

    public function countArray($array)
    {
        return count($array);
    }

    /**
     * Upload image and return uploaded image file name or false
     *
     * @throws Mage_Core_Exception
     * @param string $scope the request key for file
     * @return bool|string
     */
    public function uploadImage($scope)
    {
        $adapter = $this->httpFactory->create();
        if ($adapter->isUploaded($scope)) {
            if (!$adapter->isValid($scope)) {
                throw new \Simi\Simipwa\Helper\SimiException(__('Uploaded image is not valid.'));
            }
            $uploader = $this->fileUploaderFactory->create(['fileId' => $scope]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);
            $ext = $uploader->getFileExtension();
            if ($uploader->save($this->getBaseDir(), $scope . time() . '.' . $ext)) {
                return 'Simipwa/' . $uploader->getUploadedFileName();
            }
        }
        return false;
    }

    public function getBaseDir()
    {
        $path = $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath('Simipwa');
        return $path;
    }

    public function getCountryCollection()
    {
        return $this->countryCollectionFactory->create();
    }

    public function updateConfigJsFile($config, $buildTime, $type = self::BUILD_TYPE_SANDBOX)
    {
        if (!$buildTime)
            $buildTime = time();

        $scopeConfigInterface = $this->objectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');

        $pub_path = $scopeConfigInterface->getValue('simipwa/general/has_pub', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ? '/pub' : '';
        $token = $scopeConfigInterface->getValue('simipwa/general/dashboard_token_key');
        $token = $token?$token:$scopeConfigInterface->getValue('simiconnector/general/token_key');

        if (!$token || ($token == ''))
            throw new \Exception(__('Please fill your Token on SimiCart connector settings'), 4);

        $url = $config['app-configs'][0]['url'];
        if ($config['app-configs'][0]['ios_link']) {
            try {
                $iosId = explode('id', $config['app-configs'][0]['ios_link']);
                $iosId = $iosId[1];
                $iosId = substr($iosId, 0, 10);
            } catch (\Exception $getIosUrlException) {

            }
        }

        if ($config['app-configs'][0]['android_link']) {
            try {
                $androidId = explode('id=', $config['app-configs'][0]['android_link']);
                $androidId = $androidId[1];
                $androidId = explode('?', $androidId);
                $androidId = $androidId[0];
            } catch (\Exception $getAndroidUrlException) {

            }
        }

        $mixPanelToken = $scopeConfigInterface->getValue('simiconnector/mixpanel/token');
        $mixPanelToken = ($mixPanelToken && $mixPanelToken !== '') ? $mixPanelToken : '5d46127799a0614259cb4c733f367541';

        $gaToken = $scopeConfigInterface->getValue('simipwa/general/ga_token_key');
        $gaToken = $gaToken ? $gaToken : '';
        $zopimKey = $scopeConfigInterface->getValue('simiconnector/zopim/account_key');

        $baseName = 'pwa_sandbox';
        if ($type != self::BUILD_TYPE_SANDBOX)
            $baseName = $scopeConfigInterface->getValue('simipwa/general/pwa_main_url_site') ? '/' : 'pwa';

        // app image
        $app_images = $config['app-configs'][0]['app_images'];
        $app_image_logo = $scopeConfigInterface->getValue('simipwa/general/logo_url');
        if (!$app_image_logo) {
            $app_image_logo = $app_images['logo'];
        }

        $app_splash_img_url = $scopeConfigInterface->getValue('simipwa/general/splash_img');
        if (!$app_splash_img_url) {
            $app_splash_img_url = $app_images['splash_screen'];
        }

        $dashboard_url = $scopeConfigInterface->getValue('simipwa/general/dashboard_url');
        $dashboard_url = $dashboard_url?$dashboard_url:'https://www.simicart.com';

        $msConfigs = '
    var PWA_CONFIG_BUILD_TIME = ' . $buildTime . ';
	var SMCONFIGS = {
	    merchant_url: "' . $url . '",
	    api_path: "simiconnector/rest/v2/",
        simicart_url: "' . $dashboard_url . '/appdashboard/rest/app_configs/",
	    simicart_authorization: "' . $token . '",
	    notification_api: "simipwa/index/",
	    zopim_key: "' . $zopimKey . '",
	    zopim_language: "en",
	    base_name: "' . $baseName . '",
	    show_social_login: {
	        facebook: 1,
	        google: 1,
	        twitter: 1
	    },
        google_analytics:{
            google_analytics_key: "' . trim($gaToken) . '"
        },
	    mixpanel: {
	        token_key: "' . $mixPanelToken . '"
	    },
        logo_url: "' . $app_image_logo . '",
        splash_screen : "' . $app_splash_img_url . '"
	};
	';

        foreach ($config['app-configs'] as $index => $appconfig) {
            if ($appconfig['theme']) {
                $theme = $appconfig['theme'];
                $msConfigs .= "
	var DEFAULT_COLORS = {
	    key_color: '" . $theme['key_color'] . "',
	    top_menu_icon_color: '" . $theme['top_menu_icon_color'] . "',
	    button_background: '" . $theme['button_background'] . "',
	    button_text_color: '" . $theme['button_text_color'] . "',
	    menu_background: '" . $theme['menu_background'] . "',
	    menu_text_color: '" . $theme['menu_text_color'] . "',
	    menu_line_color: '" . $theme['menu_line_color'] . "',
	    menu_icon_color: '" . $theme['menu_icon_color'] . "',
	    search_box_background: '" . $theme['search_box_background'] . "',
	    search_text_color: '" . $theme['search_text_color'] . "',
	    app_background: '" . $theme['app_background'] . "',
	    content_color: '" . $theme['content_color'] . "',
	    image_border_color: '" . $theme['image_border_color'] . "',
	    line_color: '" . $theme['line_color'] . "',
	    price_color: '" . $theme['price_color'] . "',
	    special_price_color: '" . $theme['special_price_color'] . "',
	    icon_color: '" . $theme['icon_color'] . "',
	    section_color: '" . $theme['section_color'] . "',
	    status_bar_text: '" . $theme['status_bar_text'] . "',
	    loading_color: '" . $theme['loading_color'] . "',
	};
			";
                break;
            }
        }
        if (isset($androidId) || isset($iosId)) {
            if (!isset($androidId))
                $androidId = '';
            if (!isset($iosId))
                $iosId = '';
            $msConfigs .=
                "
    var SMART_BANNER_CONFIGS = {
        ios_app_id: '" . $iosId . "',
        android_app_id: '" . $androidId . "',
        app_store_language: '', 
        title: '" . $config['app-configs'][0]['app_name'] . "',
        author: '" . $config['app-configs'][0]['app_name'] . "',
        button_text: 'View',
        store: {
            ios: 'On the App Store',
            android: 'In Google Play',
            windows: 'In Windows store'
        },
        price: {
            ios: 'FREE',
            android: 'FREE',
            windows: 'FREE'
        },
    }; 
        ";
        }
        $configJson = json_encode($config);
        $msConfigs .=
            "
                    var Simicart_Api = $configJson;
                ";

        $path_to_file = BP . $pub_path . '/pwa/js/config/config.js';
        if ($type == self::BUILD_TYPE_SANDBOX)
            $path_to_file = BP . $pub_path . '/pwa_sandbox/js/config/config.js';

        file_put_contents($path_to_file, $msConfigs);

        if ($type == self::BUILD_TYPE_SANDBOX)
            $this->objectManager
                ->get('Magento\Framework\App\Config\Storage\WriterInterface')
                ->save('simipwa/general/build_time_sandbox', $buildTime);
        else
            $this->objectManager
                ->get('Magento\Framework\App\Config\Storage\WriterInterface')
                ->save('simipwa/general/build_time', $buildTime);

        $this->objectManager
            ->get('Magento\Framework\App\Cache\TypeListInterface')
            ->cleanType('config');
    }

    public function updateManifest($type = self::BUILD_TYPE_SANDBOX)
    {
        $scopeConfigInterface = $this->objectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $pub_path = $scopeConfigInterface->getValue('simipwa/general/has_pub', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ? '/pub' : '';

        $name = $scopeConfigInterface->getValue('simipwa/homescreen/app_name')?
            $scopeConfigInterface->getValue('simipwa/homescreen/app_name') : 'Progressive Web App';
        $short_name = $scopeConfigInterface->getValue('simipwa/homescreen/app_short_name')?
            $scopeConfigInterface->getValue('simipwa/homescreen/app_short_name') : 'PWA';
        $default_icon = '/pwa/images/default_icon_512_512.png';
        $icon =  $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon')?
            $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon') : $default_icon;
        $start_url = 'pwa_sandbox';
        if ($type != self::BUILD_TYPE_SANDBOX)
            $start_url = $scopeConfigInterface->getValue('simipwa/general/pwa_main_url_site')?
            '/' : '/pwa/';

        $theme_color = $scopeConfigInterface->getValue('simipwa/homescreen/theme_color')?
            $scopeConfigInterface->getValue('simipwa/homescreen/theme_color') : '#3399cc';
        $background_color = $scopeConfigInterface->getValue('simipwa/homescreen/background_color')?
            $scopeConfigInterface->getValue('simipwa/homescreen/background_color') : '#ffffff';
        $content = "{
              \"short_name\": \"$short_name\",
              \"name\": \"$name\",
              \"icons\": [
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"192x192\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"256x256\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"384x384\",
                  \"type\": \"image/png\"
                },
                {
                  \"src\": \"$icon\",
                  \"sizes\": \"512x512\",
                  \"type\": \"image/png\"
                }
              ],
              \"start_url\": \"$start_url\",
              \"display\": \"standalone\",
              \"theme_color\": \"$theme_color\",
              \"background_color\": \"$background_color\",
              \"gcm_sender_id\" : \"832571969235\"
            }";
        if ($type == self::BUILD_TYPE_SANDBOX) {
            $this->updateFile(BP . $pub_path . '/pwa_sandbox/simi-manifest.json', $content);
        } else {
            $this->updateFile(BP . $pub_path . '/pwa/simi-manifest.json', $content);
        }
    }

    public function updateFile($url,$content){
        $filePath = $url;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $file = @fopen($filePath, 'w+');
        if ($file) {
            file_put_contents($filePath, $content);
        }
    }

    public function getBrowser() {
        $u_agent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'';
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";
        // First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        // Next get the name of the useragent yes seperately and for good reason
        $ub = '';
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif(preg_match('/Firefox/i',$u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif(preg_match('/Chrome/i',$u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif(preg_match('/Safari/i',$u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif(preg_match('/Opera/i',$u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif(preg_match('/Netscape/i',$u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }
        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }
        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version = isset($matches['version'][0])?$matches['version'][0]:'';
            } else {
                $version = isset($matches['version'][1])?$matches['version'][1]:'';
            }
        } else {
            $version = isset($matches['version'][0])?$matches['version'][0]:'';
        }
        // check if we have a number
        if ($version==null || $version=="") {$version="?";}
        return array(
            'userAgent' => $u_agent,
            'name'      => $bname,
            'browser' => $matches['browser'],
            'version'   => $matches['version'],
            'platform'  => $platform,
            'pattern'    => $pattern
        );
    }

    public function checkUserAgent(){
        $agent = $this->getBrowser();
        $excludedBrowser = array('Opera');
        if(in_array($agent['name'], $excludedBrowser)) return false;

        // check version Chrome : support pwa > 40
        $checkVersion = true;
        foreach ($agent['browser'] as $key => $value) {
            if($value == 'Chrome'){
                $version = $agent['version'][$key];
                $version = explode('.', $version);
                $version = (int)$version[0];
                if($version < 40){
                    $checkVersion = false;
                    break;
                }
            }
        }
        return $checkVersion;
    }
}
