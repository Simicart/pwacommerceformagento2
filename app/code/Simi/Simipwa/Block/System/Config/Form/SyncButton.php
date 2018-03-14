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
        $actionHtml =  $button->toHtml();
        
        if (class_exists('Simi\Simiconnector\Controller\Rest\V2')) {
            $buildButton = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'id' => 'build_pwa',
                    'label' => __('Build PWA'),
                    'onclick' => 'setLocation(\'' . $this->getUrl('simipwaadmin/pwa/build') . '\')',
                ]
            );
            $actionHtml .= $buildButton->toHtml();
        } else
            $actionHtml.= '<p>Visit https://www.simicart.com/pwa.html '.
            'to get details and build Advanced Progressive Web App</p>';
        
        return $actionHtml;
    }
}
