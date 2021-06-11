<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Pwadevices extends Apiabstract
{

    public $DEFAULT_ORDER = 'device_id';

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Model\Device')->load($data['resourceid']);
        } else {
            $this->builderQuery = $this->simiObjectManager->get('Simi\Simiconnector\Model\Device')->getCollection();
        }
    }

    public function store()
    {
        $data               = $this->getData();
        $data               = $data['contents_array'];
        $agent = $this->simiObjectManager->get('Simi\Simipwa\Model\Device');
        if (!$data['endpoint']) {
            throw new \Exception(__('No Endpoint Sent'), 4);
        }

        if (!$agent->load($data['endpoint'], 'endpoint')->getId()) {
            $user_agent = '';
            if ($_SERVER["HTTP_USER_AGENT"]) {
                $user_agent = $_SERVER["HTTP_USER_AGENT"];
            }
            $ip = $_SERVER['REMOTE_ADDR'];
            $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
            $date = date('Y-m-d H:i:s');
            $endpoint = $data['endpoint'];
            $number = strrpos($data['endpoint'], '/');
            $endpoint_key = substr($data['endpoint'], $number + 1);
            $agent->setUserAgent($user_agent)
                ->setEndpoint($endpoint)
                ->setEndpointKey($endpoint_key)
                ->setData('p256dh_key', $data['keys']['p256dh'])
                ->setAuthKey($data['keys']['p256dh'])
                ->setCreatedAt($date)
                ->setCity(isset($details->city)?$details->city:'')
                ->setCountry(isset($details->country)?$details->country:'')
                ->save();
        }
        $this->builderQuery = $agent;
        return $this->show();
    }


    public function destroy()
    {
        $data               = $this->getData();
        $dataAgent               = $data['contents_array'];
        $result = [];
        if (!$dataAgent['endpoint']) {
            throw new \Exception(__('No Endpoint Sent'), 4);
        }
        $agent = $this->simiObjectManager->get('Simi\Simipwa\Model\Device')->load($dataAgent['endpoint'], 'endpoint');
        if ($agent->getId()) {
            try {
                $message = $this->simiObjectManager->get('Simi\Simipwa\Model\Notification')->load($agent->getId(), 'device_id');
                if ($message->getId()) {
                    $message->delete();
                }
                $agent->delete();

                $result = __('PWA Agent was removed successfully !');
            } catch (\Exception $e) {
                $error = $e->getMessage();
                throw new \Exception($error, 4);
            }
        }
        return ["message" => $result];
    }

    public function show()
    {
        $data               = $this->getData();
        $parameters = $data['params'];
        if ($parameters && isset($data['resourceid']) && $data['resourceid'] == 'message' && isset($parameters['endpoint'])) {
            $message = $this->simiObjectManager->get('Simi\Simipwa\Model\Notification')->getMessage();
            $message_info = $message->getData();
            $img = null;
            if ($message_info['type'] == 1) {
                $product = $this->simiObjectManager->get('Magento\Catalog\Model\Product')->load($message->getProductId());
                $message_info['notice_url'] = $product->getProductUrl();
            } elseif ($message_info['type'] == 2) {
                $message_info['notice_url'] = $this->simiObjectManager
                    ->get('\Magento\Catalog\Model\CategoryRepository')
                    ->get($message->getCategoryId())
                    ->getUrl();
            }
            if ($message_info['image_url']) {
                $img = $this->getMediaUrl($message_info['image_url']);
                $message_info['image_url'] = $img;
            }
            $scopeConfigInterface = $this->simiObjectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
            $message_info['logo_icon'] = $scopeConfigInterface->getValue('simipwa/notification/icon_url');
            $result = [
                "notification" => $message_info
            ];
            return $result;
        } elseif (isset($data['resourceid']) && $data['resourceid'] == 'config') {
            $scopeConfigInterface = $this->simiObjectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
            $enable = (!$scopeConfigInterface->getValue('simipwa/general/pwa_enable'))?0:1;
            $build_time = $scopeConfigInterface->getValue('simipwa/general/build_time')?
                $scopeConfigInterface->getValue('simipwa/general/build_time') : 0;

            if (!$this->_getCheckoutSession()->getData('simiconnector_platform') ||
                $this->_getCheckoutSession()->getData('simiconnector_platform') != 'pwa') {
                $this->_getCheckoutSession()->setData('simiconnector_platform', 'pwa');
            }

            $result = [
                'pwa' => [
                    //notification and offline
                    'enable_noti' => (int)$scopeConfigInterface->getValue('simipwa/notification/enable'),
                    //simicart advanced pwa
                    'enable' => $enable,
                    'build_time' => (int)$build_time,
                    'pwa_studio_client_ver_number' => $scopeConfigInterface->getValue('simiconnector/general/pwa_studio_client_ver_number'),
                ]
            ];
            return $result;
        }
        return parent::show();
    }

    public function _getCheckoutSession()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Session');
    }
}
