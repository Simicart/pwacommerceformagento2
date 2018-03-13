<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/30/18
 * Time: 4:27 PM
 */

namespace Simi\Simipwa\Model\Api;

class Simipwas extends \Simi\Simiconnector\Model\Api\Apiabstract
{

    protected $_DEFAULT_ORDER = 'agent_id';
    public $storeManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $simiObjectManager)
    {
        parent::__construct($simiObjectManager);
        $this->storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
    }

    public function setBuilderQuery()
    {
        $data = $this->getData();
        $parameters = $data['params'];

        //echo json_encode($data);die;
        if (isset($data['resourceid']) && $data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager->get('Simi\Simipwa\Model\Device')->load($data['resourceid']);
        } else {
            if (isset($parameters['endpoint'])) {
                $endpoint = $parameters['endpoint'];
                if ($endpoint) {
                    $this->builderQuery = $this->simiObjectManager->get('Simi\Simipwa\Model\Notification')->getMessage($endpoint);
                }
            } else {
                $this->builderQuery = $this->simiObjectManager->get('Simi\Simipwa\Model\Device')->getCollection();
            }
        }
    }

    public function index()
    {
        $data = $this->getData();
        $message = $this->builderQuery;
        $message_info = $message->getData();
        $img = null;
        if ($message_info['type'] == 1) {
            $product = $this->simiObjectManager->get('Magento\Catalog\Model\Product')->load($message->getProductId());
            $message_info['notice_url'] = $product->getUrlPath();
        }
        if ($message_info['type'] == 2) {
            $cate = $this->simiObjectManager->get('Magento\Catalog\Model\Category')->load($message->getCategoryId());
            $message_info['notice_url'] = $cate->getUrlPath();
        }
        if ($message_info['image_url']) {
            $img = $this->getMediaUrl($message_info['image_url']);
            $message_info['image_url'] = $img;
        }
        return [
            "notification" => $message_info
        ];
    }

    public function store()
    {
        $data = $this->getData();
        $dataAgent = (array)$data['contents'];
        $agent = $this->simiObjectManager->get('Simi\Simipwa\Model\Device');
        if (!$dataAgent['endpoint']) {
            throw new \Exception(__('No Endpoint Sent'), 4);
        }


        if (!$agent->load($dataAgent['endpoint'], 'endpoint')->getId()) {
            $user_agent = '';
            if ($_SERVER["HTTP_USER_AGENT"]) {
                $user_agent = $_SERVER["HTTP_USER_AGENT"];
            }
            $endpoint = $dataAgent['endpoint'];
            $number = strrpos($dataAgent['endpoint'], '/');
            $endpoint_key = substr($dataAgent['endpoint'], $number + 1);
            $agent->setUserAgent($user_agent)
                ->setEndpoint($endpoint)
                ->setEndpointKey($endpoint_key)
                ->setP256dhKey($dataAgent['keys']->p256dh)
                ->setAuthKey($dataAgent['keys']->auth)
                ->setCreatedAt(now())
                ->save();
        }
        return $this->show();
    }

    public function destroy()
    {
        $data = $this->getData();
        $result = [];
        $dataAgent = (array)$data['contents'];
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

                $result[] = __('PWA Agent was removed successfully !');
            } catch (\Exception $e) {
                $error = $e->getMessage();
                throw new \Exception($error, 4);
            }
        }
        return [
            "message" => $result
        ];
    }

    public function getMediaUrl($media_path)
    {

        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . $media_path;
    }
}
