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
        $actionHtml = '';
        if (class_exists('Simi\Simiconnector\Controller\Rest\V2')) {
            $button = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'id' => 'clear_mobile_cache',
                    'label' => __('Sync Sitemaps'),
                    'onclick' => 'setLocation(\'' . $this->getUrl('simipwaadmin/cache/delete') . '\')',
                ]
            );
            $actionHtml .=  $button->toHtml();
        
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
            $actionHtml.= '
            <script type="text/javascript">
                document.getElementById("simipwa_general-link").parentElement.parentElement.style.display = "none";
            </script>';
        
        return $actionHtml;
    }
}
