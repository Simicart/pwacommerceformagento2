<?php

namespace Simi\Simipwa\Controller\Adminhtml\Notification;

class Devicegrid extends \Simi\Simipwa\Controller\Adminhtml\Device\Grid
{

    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::notification_manager');
    }
}
