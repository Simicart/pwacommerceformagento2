<?php

/**
 * Adminhtml connector list block
 *
 */

namespace Simi\Simipwa\Block\Adminhtml;

class Notification extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_notification';
        $this->_blockGroup = 'Simi_Simipwa';
        $this->_headerText = __('Notification');
        $this->_addButtonLabel = __('Add New Notification');
        parent::_construct();
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return true;
    }
}
