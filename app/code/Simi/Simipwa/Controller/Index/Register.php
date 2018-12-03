<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/30/18
 * Time: 3:47 PM
 */

namespace Simi\Simipwa\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class Register extends \Simi\Simipwa\Controller\Action
{
    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Exception
     */
    public function execute()
    {
        $data = $this->getRequest()->getContent();
        $data = (array)json_decode($data);
        $agent = $this->_objectManager->get('Simi\Simipwa\Model\Device');
        if (!$data['endpoint']) {
            throw new \Exception(__('No Endpoint Sent'), 4);
        }

        try {
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
                    ->setData('p256dh_key', $data['keys']->p256dh)
                    ->setAuthKey($data['keys']->auth)
                    ->setCreatedAt($date)
                    ->setCity($details->city)
                    ->setCountry($details->country)
                    ->save();
            }
            $this->getResponse()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode($agent->getData()));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 4);
        }
    }

    public function getMediaUrl($media_path)
    {
        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . $media_path;
    }
}
