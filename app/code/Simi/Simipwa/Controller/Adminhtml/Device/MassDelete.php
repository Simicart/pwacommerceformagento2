<?php

namespace Simi\Simipwa\Controller\Adminhtml\Device;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public $simiObjectManager;
    public $filter;

    public function __construct(
        Context $context,
        Filter $filterObject
    ) {
   
        $this->simiObjectManager = $context->getObjectManager();
        $this->filter            = $filterObject;
        parent::__construct($context);
    }

    public function execute()
    {
        $deviceIds     = $this->getRequest()->getParam('massaction');
        $collection    = $this->simiObjectManager->get('Simi\Simipwa\Model\Device')
                        ->getCollection()->addFieldToFilter('agent_id', ['in', $deviceIds]);
        $deviceDeleted = 0;
        foreach ($collection->getItems() as $device) {
            $this->deleteDevice($device);
            $deviceDeleted++;
        }
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $deviceDeleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
    
    private function deleteDevice($deviceModel)
    {
        $deviceModel->delete();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::device_manager');
    }
}
