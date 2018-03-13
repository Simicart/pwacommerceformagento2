<?php


namespace Simi\Simipwa\Block\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SyncButton extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->getButtonHtml();
    }
    
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'clear_mobile_cache',
                'label' => __('Sync Sitemaps'),
                'onclick' => 'setLocation(\'' . $this->getUrl('simipwaadmin/cache/delete') . '\')',
            ]
        );

        return $button->toHtml();
    }
}
