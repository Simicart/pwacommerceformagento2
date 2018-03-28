<?php


namespace Simi\Simipwa\Controller\Adminhtml\Pwa;

use Magento\Backend\App\Action;

class Build extends Action
{

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::simipwa_settings');
    }
    public function execute()
    {
        try {
            $scopeConfigInterface = $this->_objectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
            $token =  $scopeConfigInterface->getValue('simiconnector/general/token_key');
            $secret_key =  $scopeConfigInterface->getValue('simiconnector/general/secret_key');
            $logoUrlSetting = $scopeConfigInterface->getValue('simipwa/general/logo_url');
            $app_image_logo = ($logoUrlSetting && $logoUrlSetting!='')?
                $logoUrlSetting:
                $this->_objectManager->get('\Magento\Theme\Block\Html\Header\Logo')->getLogoSrc();

            if (!$token || !$secret_key || ($token == '') || ($secret_key == ''))
                throw new \Exception(__('Please fill your Token and Secret key on SimiCart connector settings'), 4);

            $config = file_get_contents("https://www.simicart.com/appdashboard/rest/app_configs/bear_token/".$token);
            if (!$config || (!$config = json_decode($config, 1)))
                throw new \Exception(__('We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'), 4);

            $buildFile = 'https://dashboard.simicart.com/pwa/package.zip';
            $fileToSave = './pwa/simi_pwa_package.zip';
            $directoryToSave = '/pwa/';
            $buildTime = time();
            $url = $config['app-configs'][0]['url'];
            
            if ($config['app-configs'][0]['ios_link']) {
                try {
                    $iosId = explode('id', $config['app-configs'][0]['ios_link']);
                    $iosId = $iosId[1];
                    $iosId = substr($iosId, 0, 10);
                }
                catch (\Exception $getIosUrlException) {

                }
            }

            if ($config['app-configs'][0]['android_link']) {
                try {
                    $androidId = explode('id=', $config['app-configs'][0]['android_link']);
                    $androidId = $androidId[1];
                    $androidId = explode('?', $androidId);
                    $androidId = $androidId[0];
                }
                catch (\Exception $getAndroidUrlException) {  
                
                }
            }

            //create directory
            $filePath = $this->_objectManager
                    ->get('\Magento\Framework\Filesystem\DirectoryList')->getRoot() . $directoryToSave;

            if (is_dir($filePath)) {
                $this->remover_dir($filePath);
            }
            mkdir($filePath, 0777, true);

            //download file
            file_get_contents($buildFile);
            if (!isset($http_response_header[0]) || !is_string($http_response_header[0]) ||
                (strpos($http_response_header[0],'200') === false)) {
                throw new \Exception(__('Sorry, we cannot get PWA package from SimiCart.'), 4);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $buildFile);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec ($ch);
            curl_close ($ch);
            $file = fopen($fileToSave, "w+");
            fputs($file, $data);
            fclose($file);

            //unzip file
            $zip = new \ZipArchive;
            $res = $zip->open($fileToSave);
            if ($res === TRUE) {
                $zip->extractTo('.'.$directoryToSave);
                $zip->close();
            } else {
                throw new \Exception(__('Sorry, we cannot extract PWA package.'), 4);
            }

            //move service worker out to root
            $path_to_file = './pwa/service-worker.js';
            file_put_contents('./service-worker.js',file_get_contents($path_to_file));

            //move favicon into pwa
            try {
                $path_to_file = './favicon.ico';
                if ($favicon_content = file_get_contents($path_to_file))
                    file_put_contents('./pwa/favicon.ico',$favicon_content);
            } catch (\Exception $faviconException) {

            }

            //update index.html file 
            $path_to_file = './pwa/index.html';
            $excludedPaths = $scopeConfigInterface->getValue('simipwa/general/pwa_excluded_paths');
            $excludedPaths = $excludedPaths. ',' .
                $this->_objectManager->get('Magento\Backend\Helper\Data')->getAreaFrontName();
            $file_contents = file_get_contents($path_to_file);
            $file_contents = str_replace('PAGE_TITLE_HERE',$config['app-configs'][0]['app_name'],$file_contents);
            $file_contents = str_replace('IOS_SPLASH_TEXT',$config['app-configs'][0]['app_name'],$file_contents);
            $file_contents = str_replace('"PWA_EXCLUDED_PATHS"','"'.$excludedPaths.'"',$file_contents);
            $file_contents = str_replace('PWA_BUILD_TIME_VALUE',$buildTime,$file_contents);
            if(isset($iosId) && $iosId && $iosId!==''){
                $file_contents = str_replace('IOS_APP_ID',$iosId,$file_contents);
            }
            if(isset($androidId) && $androidId && $androidId!==''){
                $file_contents = str_replace('GOOGLE_APP_ID',$androidId,$file_contents);
            }
            file_put_contents($path_to_file,$file_contents);


            //update version.js file
            $versionContent = '

    var PWA_BUILD_TIME = '.$buildTime.';
    var PWA_LOCAL_BUILD_TIME = localStorage.getItem("pwa_build_time");
    if(!PWA_LOCAL_BUILD_TIME || PWA_LOCAL_BUILD_TIME === null){
        localStorage.setItem(\'pwa_build_time\',PWA_BUILD_TIME);
        PWA_LOCAL_BUILD_TIME = PWA_BUILD_TIME;
    }else{
        PWA_LOCAL_BUILD_TIME = parseInt(PWA_LOCAL_BUILD_TIME,10);
        if(PWA_BUILD_TIME > PWA_LOCAL_BUILD_TIME){
            localStorage.setItem(\'pwa_build_time\',PWA_BUILD_TIME);
            PWA_LOCAL_BUILD_TIME = PWA_BUILD_TIME;
        }
    }
    var INDEX_LOCAL_BUILD_TIME = parseInt(localStorage.getItem("index_build_time"),10);
    if(PWA_LOCAL_BUILD_TIME !== INDEX_LOCAL_BUILD_TIME){
        use_pwa = false;
        if(PWA_LOCAL_BUILD_TIME > INDEX_LOCAL_BUILD_TIME){
            localStorage.setItem("index_build_time",PWA_LOCAL_BUILD_TIME);
        }else{
            localStorage.setItem("pwa_build_time",INDEX_LOCAL_BUILD_TIME);
        }
    }
    console.log(use_pwa);
    if (!use_pwa) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
         for(let registration of registrations) {
          registration.unregister()
        } });
        caches.keys().then(function(names) {
            for (let name of names)
                caches.delete(name);
        });
        window.location.reload();
    }
            ';

            
            $path_to_file = './pwa/js/config/version.js';
            file_put_contents($path_to_file, $versionContent);

            //update config.js file

            $mixPanelToken = $scopeConfigInterface->getValue('simiconnector/mixpanel/token');
            $mixPanelToken = ($mixPanelToken && $mixPanelToken!=='')?$mixPanelToken:'5d46127799a0614259cb4c733f367541';
            $zopimKey = $scopeConfigInterface->getValue('simiconnector/zopim/account_key');
            $baseName = $scopeConfigInterface->getValue('simipwa/general/pwa_enable')?'/':'pwa';
            $msConfigs = '
    var PWA_BUILD_TIME = "'.$buildTime.'";
	var SMCONFIGS = {
	    merchant_url: "'.$url.'",
	    api_path: "simiconnector/rest/v2/",
	    merchant_authorization: "'.$secret_key.'",
	    simicart_url: "https://www.simicart.com/appdashboard/rest/app_configs/",
	    simicart_authorization: "'.$token.'",
	    notification_api: "simipwa/index/",
	    zopim_key: "'.$zopimKey.'",
	    zopim_language: "en",
	    base_name: "'.$baseName.'",
	    show_social_login: {
	        facebook: 1,
	        google: 1,
	        twitter: 1
	    },

	    mixpanel: {
	        token_key: "'.$mixPanelToken.'"
	    },
	    logo_url: "'.$app_image_logo.'"
	};
	';

            foreach ($config['app-configs'] as $index=>$appconfig) {
                if ($appconfig['theme']) {
                    $theme = $appconfig['theme'];
                    $msConfigs.= "
	var DEFAULT_COLORS = {
	    key_color: '".$theme['key_color']."',
	    top_menu_icon_color: '".$theme['top_menu_icon_color']."',
	    button_background: '".$theme['button_background']."',
	    button_text_color: '".$theme['button_text_color']."',
	    menu_background: '".$theme['menu_background']."',
	    menu_text_color: '".$theme['menu_text_color']."',
	    menu_line_color: '".$theme['menu_line_color']."',
	    menu_icon_color: '".$theme['menu_icon_color']."',
	    search_box_background: '".$theme['search_box_background']."',
	    search_text_color: '".$theme['search_text_color']."',
	    app_background: '".$theme['app_background']."',
	    content_color: '".$theme['content_color']."',
	    image_border_color: '".$theme['image_border_color']."',
	    line_color: '".$theme['line_color']."',
	    price_color: '".$theme['price_color']."',
	    special_price_color: '".$theme['special_price_color']."',
	    icon_color: '".$theme['icon_color']."',
	    section_color: '".$theme['section_color']."',
	    status_bar_background: '".$theme['status_bar_background']."',
	    status_bar_text: '".$theme['status_bar_text']."',
	    loading_color: '".$theme['loading_color']."',
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
                $msConfigs.=
                    "
    var SMART_BANNER_CONFIGS = {
        ios_app_id: '".$iosId."',
        android_app_id: '".$androidId."',
        app_store_language: '', 
        title: '".$config['app-configs'][0]['app_name']."',
        author: '".$config['app-configs'][0]['app_name']."',
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
            
            $path_to_file = './pwa/js/config/config.js';
            file_put_contents($path_to_file, $msConfigs);

            $this->messageManager->addSuccess(__('PWA Application was Built Successfully. To review it, please go to '.$url.$baseName));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath(
            'adminhtml/system_config/edit',
            [
                'section' => 'simipwa'
            ]
        );
    }

    public function remover_dir($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it,
            \RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
