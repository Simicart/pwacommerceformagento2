<?php


namespace Simi\Simipwa\Controller\Adminhtml\Pwa;

use Magento\Backend\App\Action;
use Simi\Simipwa\Helper\Data;

class Build extends Action
{

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::simipwa_settings');
    }

    public function createPackage($type, $config, $scopeConfigInterface)
    {
        $getFileFromLocal = false;
        $pub_path = $scopeConfigInterface->getValue('simipwa/general/has_pub', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ? '/pub' : '';
        if ($scopeConfigInterface->getValue('simipwa/pwa_package/use_uploaded_package_file', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) &&
            $path = $scopeConfigInterface->getValue('simipwa/pwa_package/upload_pwa_package_file', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        ) {
            $buildFile = BP . '/pub/media/pwa/packages/' . $path;
            $getFileFromLocal = true;
        } else {
            $buildFile = 'https://dashboard.simicart.com/pwa/package.php?app_id='.$config['app-configs'][0]['app_info_id'];
        }
        $fileToSave = BP . $pub_path . '/pwa/simi_pwa_package.zip';
        $directoryToSave = BP . $pub_path . '/pwa/';
        if ($type == Data::BUILD_TYPE_SANDBOX) {
            if ($scopeConfigInterface->getValue('simipwa/pwa_package/use_uploaded_package_file_sandbox', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) &&
                $path = $scopeConfigInterface->getValue('simipwa/pwa_package/upload_pwa_package_file_sandbox', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ) {
                $buildFile = BP . '/pub/media/pwa/packages/sandbox/' . $path;
                $getFileFromLocal = true;
            } else {
                $buildFile = 'https://dashboard.simicart.com/pwa/sandbox_package.php?app_id='.$config['app-configs'][0]['app_info_id'];
            }
            $fileToSave = BP . $pub_path . '/pwa_sandbox/simi_pwa_package.zip';
            $directoryToSave = BP . $pub_path . '/pwa_sandbox/';
        }

        //create directory
        $filePath = $directoryToSave;

        if (is_dir($filePath)) {
            $this->remover_dir($filePath);
        }
        mkdir($filePath, 0777, true);

        if ($getFileFromLocal) {
            copy($buildFile, $fileToSave);
        } else {
            //download file
            file_get_contents($buildFile);
            if (!isset($http_response_header[0]) || !is_string($http_response_header[0]) ||
                (strpos($http_response_header[0], '200') === false)) {
                throw new \Exception(__('Sorry, we cannot get PWA package from SimiCart.'), 4);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $buildFile);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            curl_close($ch);
            $file = fopen($fileToSave, "w+");
            fputs($file, $data);
            fclose($file);
        }

        //unzip file
        $zip = new \ZipArchive;
        $res = $zip->open($fileToSave);
        if ($res === true) {
            $zip->extractTo($directoryToSave);
            $zip->close();
        } else {
            throw new \Exception(__('Sorry, we cannot extract PWA package.'), 4);
        }

        try {
            $execFunc = 'exec';
            $execFunc("find " . $directoryToSave . " -type d -exec chmod 775 {} +");
            $execFunc("find " . $directoryToSave . "  -type f -exec chmod 775 {} +");
        } catch (\Exception $e) {

        }
    }

    public function execute()
    {
        try {
            $type = $this->getRequest()->getParam('build_type');
            if (!$type) {
                $type = Data::BUILD_TYPE_SANDBOX;
            }

            $scopeConfigInterface = $this->_objectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
            $token = $scopeConfigInterface->getValue('simipwa/general/dashboard_token_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $token = $token?$token:$scopeConfigInterface->getValue('simiconnector/general/token_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if (!$token || ($token == '')) {
                throw new \Exception(__('Please fill your Token and Secret key on SimiCart connector settings'), 4);
            }
            if ($scopeConfigInterface->getValue('simipwa/pwa_package/use_local_config', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                $config = $scopeConfigInterface->getValue('simipwa/pwa_package/json_config_data', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                if (!$config || (!$config = json_decode($config, 1))) {
                    throw new \Exception(__('Your local json config is not valid'), 4);
                }
            } else {
                $dashboard_url = $scopeConfigInterface->getValue('simipwa/general/dashboard_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $dashboard_url = $dashboard_url?$dashboard_url:'https://www.simicart.com';
                $config = file_get_contents($dashboard_url . "/appdashboard/rest/app_configs/bear_token/".$token.'/pwa/1');
                if (!$config || (!$config = json_decode($config, 1))) {
                    throw new \Exception(__('We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'), 4);
                }
            }

            $this->createPackage($type, $config, $scopeConfigInterface);

            $buildTime = time();

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


            if ($type != Data::BUILD_TYPE_SANDBOX) {
                //move service worker out to root
                $path_to_file =  BP . '/pwa/simi-sw.js';
                file_put_contents(BP . '/simi-sw.js', file_get_contents($path_to_file));
            }

            // app image
            $app_images = $config['app-configs'][0]['app_images'];
            $app_splash_img_url = $scopeConfigInterface->getValue('simipwa/general/splash_img', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ;
            if (!$app_splash_img_url) {
                $app_splash_img_url = $app_images['splash_screen'];
            }
            $app_splash_img =
                '<img src="'.$app_splash_img_url.'" alt="Splash Screen" style="width: 325px;height: auto">';

            $app_icon = $scopeConfigInterface->getValue('simipwa/manifest/logo', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if (!$app_icon) {
                $app_icon = $app_images['icon'];
            }
            $favicon = $scopeConfigInterface->getValue('simipwa/general/favicon', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $favicon = $favicon ? $favicon : $app_icon;

            $config['app-configs'][0]['add_home_screen'] = $scopeConfigInterface->getValue('simipwa/homescreen/homescreen_enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            //update index.html file
            $path_to_file = BP . '/pwa/index.html';
            if ($type == Data::BUILD_TYPE_SANDBOX) {
                $path_to_file = BP . '/pwa_sandbox/index.html';
            }
            $excludedPaths = $scopeConfigInterface->getValue('simipwa/general/pwa_excluded_paths', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $excludedPaths = $excludedPaths. ',' .
                             $this->_objectManager->get('Magento\Backend\Helper\Data')->getAreaFrontName();
            $file_contents = file_get_contents($path_to_file);
            $file_contents = str_replace('PAGE_TITLE_HERE', $config['app-configs'][0]['app_name'], $file_contents);
            $file_contents = str_replace('IOS_SPLASH_TEXT', $config['app-configs'][0]['app_name'], $file_contents);
            $file_contents = str_replace('"PWA_EXCLUDED_PATHS"', '"'.$excludedPaths.'"', $file_contents);
            $file_contents = str_replace('PWA_BUILD_TIME_VALUE', $buildTime, $file_contents);
            $file_contents = str_replace('<div id="splash-img"></div>', $app_splash_img, $file_contents);
            if ($head = $scopeConfigInterface->getValue('simipwa/general/custom_head', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                $file_contents = str_replace('<head>', '<head>'.$head, $file_contents);
            }

            if ($footerHtml = $scopeConfigInterface->getValue('simipwa/general/footer_html', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                $footerHtml = $this->_objectManager
                    ->get('Magento\Cms\Model\Template\FilterProvider')
                    ->getPageFilter()->filter($footerHtml);
                $file_contents = str_replace('</body>', $footerHtml.'</body>', $file_contents);
            }
            $file_contents = str_replace('/pwa/favicon.ico', $favicon, $file_contents);
            if ($type == Data::BUILD_TYPE_SANDBOX) {
                $file_contents = str_replace('/pwa_sandbox/favicon.ico', $favicon, $file_contents);
            }

            if (isset($iosId) && $iosId && $iosId!=='') {
                $file_contents = str_replace('IOS_APP_ID', $iosId, $file_contents);
            }

            if (isset($androidId) && $androidId && $androidId!=='') {
                $file_contents = str_replace('GOOGLE_APP_ID', $androidId, $file_contents);
            }

            if (isset($iosId) && $iosId && $iosId!=='') {
                $file_contents = str_replace('IOS_APP_ID', $iosId, $file_contents);
            }
            if (isset($androidId) && $androidId && $androidId!=='') {
                $file_contents = str_replace('GOOGLE_APP_ID', $androidId, $file_contents);
            }
            $iconUrl = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $file_contents = str_replace('/pwa/images/default_icon_512_512.png', $iconUrl, $file_contents);
            file_put_contents($path_to_file, $file_contents);

            $pwaHelper = $this->_objectManager->get('Simi\Simipwa\Helper\Data');

            //update manifest.jon
            if ($scopeConfigInterface->getValue('simipwa/homescreen/homescreen_enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                $pwaHelper->updateManifest($type);
            }

            //update config.js file
            $pwaHelper->updateConfigJsFile($config, $buildTime, $type);

            if ($type == Data::BUILD_TYPE_SANDBOX) {
                $url = $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/pwa_sandbox";
                $this->messageManager->addSuccess(__('Sandbox PWA was Built Successfully!'). '<br/>Please go to '.$url.' to review.');
            } else {
                $this->messageManager->addSuccess(__('Progressive Web App was Built Successfully.'));
            }
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
        $files = new \RecursiveIteratorIterator(
            $it,
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
