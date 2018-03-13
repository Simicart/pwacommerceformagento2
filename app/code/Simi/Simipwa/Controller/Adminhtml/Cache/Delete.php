<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/30/18
 * Time: 8:50 AM
 */

namespace Simi\Simipwa\Controller\Adminhtml\Cache;

use Magento\Backend\App\Action;

class Delete extends Action
{

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::simipwa_settings');
    }
    public function execute()
    {
        $this->_objectManager->get('Simi\Simipwa\Helper\Data')->clearAppCaches();
        $this->messageManager->addSuccess(__('Site map Synchronization completed.'));
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath(
            'adminhtml/system_config/edit',
            [
                'section' => 'simipwa'
            ]
        );
    }
}
