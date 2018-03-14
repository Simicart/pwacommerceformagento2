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
            $url = $config['app-configs'][0]['url'];

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

            //update index.html file 
            $path_to_file = './pwa/index.html';
            $file_contents = file_get_contents($path_to_file);
            $file_contents = str_replace('PAGE_TITLE_HERE',$config['app-configs'][0]['app_name'],$file_contents);
            $file_contents = str_replace('IOS_SPLASH_TEXT',$config['app-configs'][0]['app_name'],$file_contents);
            file_put_contents($path_to_file,$file_contents);

            //update config.js file
            
            $mixPanelToken = $scopeConfigInterface->getValue('simiconnector/mixpanel/token');
            $mixPanelToken = ($mixPanelToken && $mixPanelToken!=='')?$mixPanelToken:'5d46127799a0614259cb4c733f367541';
            $zopimKey = $scopeConfigInterface->getValue('simiconnector/zopim/account_key');
            $msConfigs = '
	var SMCONFIGS = {
	    merchant_url: "'.$url.'",
	    api_path: "simiconnector/rest/v2/",
	    merchant_authorization: "'.$secret_key.'",
	    simicart_url: "https://www.simicart.com/appdashboard/rest/app_configs/",
	    simicart_authorization: "'.$token.'",
	    notification_api: "simipwa/index/",
	    zopim_key: "'.$zopimKey.'",
	    zopim_language: "en",
	    base_name: "pwa",
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

            $path_to_file = './pwa/js/config/config.js';
            file_put_contents($path_to_file, $msConfigs);
            
            $this->messageManager->addSuccess(__('PWA Application was Built Successfully. Please go to '.$url.'pwa to check.'));
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
