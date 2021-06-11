<?php

namespace Simi\Simipwa\Controller\Adminhtml\Notification;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * Delete action
     *
     * @return void
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id             = $this->getRequest()->getParam('message_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $title = "";
            try {
                // init model and delete
                $simiobjectManager = $this->_objectManager;
                $model = $simiobjectManager->create('Simi\Simipwa\Model\Notification');
                $model->load($id);
                $title = $model->getTitle();
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('The data has been deleted.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['message_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a data to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::notification_manager');
    }
}
