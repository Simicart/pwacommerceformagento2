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

class Delete extends \Magento\Framework\App\Action\Action
{

    public $storeManager;
    public $zendRequest;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->zendRequest = $this->_objectManager->get('Simi\Simipwa\Helper\RequestHttp');
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Exception
     */
    public function execute()
    {
        $data = $this->zendRequest->getRawBody();
        $dataAgent = (array)json_decode($data);
        $result = [];
        if (!$dataAgent['endpoint']) {
            throw new \Exception(__('No Endpoint Sent'), 4);
        }
        $agent = $this->_objectManager->get('Simi\Simipwa\Model\Device')->load($dataAgent['endpoint'], 'endpoint');
        if ($agent->getId()) {
            try {
                $message = $this->_objectManager->get('Simi\Simipwa\Model\Notification')->load($agent->getId(), 'device_id');
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
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode([
            "message" => $result
        ]));
    }
}
