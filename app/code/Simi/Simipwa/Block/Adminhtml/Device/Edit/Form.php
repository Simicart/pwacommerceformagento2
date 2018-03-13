<?php

namespace Simi\Simipwa\Block\Adminhtml\Device\Edit;

/**
 * Adminhtml connector edit form block
 *
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form',
                'action' => $this->getData('action'), 'method' => 'post', 'enctype' => 'multipart/form-data']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
