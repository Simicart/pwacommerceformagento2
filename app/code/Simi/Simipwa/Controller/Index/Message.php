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

class Message extends \Magento\Framework\App\Action\Action
{

    public $storeManager;
    public  $scopeConfigInterface;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->scopeConfigInterface = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $message = $this->_objectManager->get('Simi\Simipwa\Model\Notification')->getMessage();
        $message_info = $message->getData();
        $img = null;
        if ($message_info['type'] == 1) {
            $product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($message->getProductId());
            $message_info['notice_url'] = $product->getProductUrl();
        } else if ($message_info['type'] == 2) {
            $message_info['notice_url'] = $this->_objectManager
                ->get('\Magento\Catalog\Model\CategoryRepository')
                ->get($message->getCategoryId())
                ->getUrl();
        }
        if ($message_info['image_url']) {
            $img = $this->getMediaUrl($message_info['image_url']);
            $message_info['image_url'] = $img;
        }
        $message_info['logo_icon'] = $this->scopeConfigInterface->getValue('simipwa/notification/icon_url');
        if($this->scopeConfigInterface->getValue('simipwa/general/pwa_enable')){
            $message_info['pwa_url'] = $this->scopeConfigInterface->getValue('simipwa/general/pwa_url');
        }

        $result = [
            "notification" => $message_info
        ];
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }

    public function getMediaUrl($media_path)
    {
        return $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . $media_path;
    }
}
