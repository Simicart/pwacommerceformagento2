<?php

namespace Simi\Simipwa\Controller\Adminhtml\Notification;

class NewAction extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Backend\Model\View\Result\Forward
     */
    public $resultForwardFactory;

    /**
     * NewAction constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
    

        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::notification_manager');
    }
}
