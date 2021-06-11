<?php

/**
 * Adminhtml connector list block
 *
 */

namespace Simi\Simipwa\Block\Adminhtml;

class Device extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_device';
        $this->_blockGroup = 'Simi_Simipwa';
        $this->_headerText = __('Device');
        $this->_addButtonLabel = __('Add New Device');
        parent::_construct();
        $this->buttonList->remove('add');
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
