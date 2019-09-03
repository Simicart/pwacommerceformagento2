<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/30/18
 * Time: 3:47 PM
 */

namespace Simi\Simipwa\Controller\Index;

use Simi\Simipwa\Helper\Data;

class Build extends \Simi\Simipwa\Controller\Action
{
    public function execute()
    {
        $build_result = 'Build success';
        try {
            $type = Data::BUILD_TYPE_LIVE;
            $scopeConfigInterface = $this->_objectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
            if (
                !$scopeConfigInterface->getValue('simipwa/pwa_package/frontend_ip_building') ||
                ($_SERVER['REMOTE_ADDR'] != $scopeConfigInterface->getValue('simipwa/pwa_package/frontend_ip_building'))
            )   {
                throw new \Exception(__('Your IP is not valid'), 4);
            }

            $token =  $scopeConfigInterface->getValue('simiconnector/general/token_key');
            $secret_key =  $scopeConfigInterface->getValue('simiconnector/general/secret_key');

            if (!$token || !$secret_key || ($token == '') || ($secret_key == ''))
                throw new \Exception(__('Please fill your Token and Secret key on SimiCart connector settings'), 4);
            if ($scopeConfigInterface->getValue('simipwa/pwa_package/use_local_config')) {
                $config = $scopeConfigInterface->getValue('simipwa/pwa_package/json_config_data');
                if (!$config || (!$config = json_decode($config, 1)))
                    throw new \Exception(__('Your local json config is not valid'), 4);
            } else {
                $config = file_get_contents("https://www.simicart.com/appdashboard/rest/app_configs/bear_token/".$token.'/pwa/1');
                if (!$config || (!$config = json_decode($config, 1)))
                    throw new \Exception(__('We cannot connect To SimiCart, please check your filled token, or check if 
                your server allows connections to SimiCart website'), 4);
            }

            $buildTime = time();
            
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

            if ($type != Data::BUILD_TYPE_SANDBOX) {
                //move service worker out to root
                $path_to_file = './pwa/simi-sw.js';
                file_put_contents('./simi-sw.js', file_get_contents($path_to_file));
            }

            // app image
            $app_images = $config['app-configs'][0]['app_images'];
            $app_splash_img_url = $scopeConfigInterface->getValue('simipwa/general/splash_img') ;
            if(!$app_splash_img_url){
                $app_splash_img_url = $app_images['splash_screen'];
            }
            $app_splash_img = 
                '<img src="'.$app_splash_img_url.'" alt="Splash Screen" style="width: 325px;height: auto">';

            $app_icon = $scopeConfigInterface->getValue('simipwa/manifest/logo');
            if(!$app_icon){
                $app_icon = $app_images['icon'];
            }
            $favicon = $scopeConfigInterface->getValue('simipwa/general/favicon');
            $favicon = $favicon ? $favicon : $app_icon;
            
            //update index.html file
            $path_to_file = './pwa/index.html';
            if ($type == Data::BUILD_TYPE_SANDBOX) {
                $path_to_file = './pwa_sandbox/index.html';
            }
            $excludedPaths = $scopeConfigInterface->getValue('simipwa/general/pwa_excluded_paths');
            $excludedPaths = $excludedPaths. ',' .
                $this->_objectManager->get('Magento\Backend\Helper\Data')->getAreaFrontName();
            $file_contents = file_get_contents($path_to_file);
            $file_contents = str_replace('PAGE_TITLE_HERE',$config['app-configs'][0]['app_name'],$file_contents);
            $file_contents = str_replace('IOS_SPLASH_TEXT',$config['app-configs'][0]['app_name'],$file_contents);
            $file_contents = str_replace('"PWA_EXCLUDED_PATHS"','"'.$excludedPaths.'"',$file_contents);
            $file_contents = str_replace('PWA_BUILD_TIME_VALUE',$buildTime,$file_contents);
            $file_contents = str_replace('<div id="splash-img"></div>', $app_splash_img, $file_contents);
            if ($head = $scopeConfigInterface->getValue('simipwa/general/custom_head')) {
                $file_contents = str_replace('<head>', '<head>'.$head, $file_contents);
            }

            if ($footerHtml = $scopeConfigInterface->getValue('simipwa/general/footer_html')) {
                $footerHtml = $this->_objectManager
                    ->get('Magento\Cms\Model\Template\FilterProvider')
                    ->getPageFilter()->filter($footerHtml);
                $file_contents = str_replace('</body>', $footerHtml.'</body>', $file_contents);
            }
            $file_contents = str_replace('/pwa/favicon.ico', $favicon, $file_contents);
            
            if(isset($iosId) && $iosId && $iosId!==''){
                $file_contents = str_replace('IOS_APP_ID', $iosId, $file_contents);
            }

            if(isset($androidId) && $androidId && $androidId!==''){
                $file_contents = str_replace('GOOGLE_APP_ID', $androidId, $file_contents);
            }
            
            if(isset($iosId) && $iosId && $iosId!==''){
                $file_contents = str_replace('IOS_APP_ID',$iosId,$file_contents);
            }
            if(isset($androidId) && $androidId && $androidId!==''){
                $file_contents = str_replace('GOOGLE_APP_ID',$androidId,$file_contents);
            }
            $iconUrl = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon');

            $file_contents = str_replace('/pwa/images/default_icon_512_512.png',$iconUrl,$file_contents);
            file_put_contents($path_to_file,$file_contents);

            $pwaHelper = $this->_objectManager->get('Simi\Simipwa\Helper\Data');

            //update manifest.jon
            if ($scopeConfigInterface->getValue('simipwa/homescreen/homescreen_enable')) {
                $pwaHelper->updateManifest($type);
            }

            //update config.js file
            $pwaHelper->updateConfigJsFile($config, $buildTime, $type);
        } catch (\Exception $e) {
            $build_result = $e->getMessage();
        }
        echo $build_result;
        exit();
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
