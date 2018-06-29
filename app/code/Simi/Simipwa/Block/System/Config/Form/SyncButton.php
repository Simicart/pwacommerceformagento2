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
                document.addEventListener("DOMContentLoaded", function(event) {
                    document.getElementById("simipwa_general-link").parentElement.parentElement.style.display = "none";
                    document.getElementById("simipwa_analytics-link").parentElement.parentElement.style.display = "none";
                });
            </script>';
        $actionHtml .= '
            <script type="text/javascript">
                function addHomeScreenWarning() {
                    simipwa_notification_enable = document.getElementById("simipwa_notification_enable");
                    if(simipwa_notification_enable.value == 0) {
                        addToHomeWarning = document.getElementById("add_to_home_warning");
                        if (!addToHomeWarning || typeof addToHomeWarning == "undefined") {
                            homescreen_enable = document.getElementById("row_simipwa_homescreen_homescreen_enable");
                            var addToHomeWarning = document.createElement("div");
                                addToHomeWarning.innerHTML = "Please enable Offline Mode to open Add to Home Screen feature";
                                addToHomeWarning.className = "add_to_home_warning";
                                addToHomeWarning.id = "add_to_home_warning";
                            homescreen_enable.parentNode.insertBefore(addToHomeWarning, homescreen_enable);   
                        } else {
                            addToHomeWarning.style.display = "block";
                        }
                    } else {
                        addToHomeWarning = document.getElementById("add_to_home_warning");
                        if (addToHomeWarning && typeof addToHomeWarning != "undefined")
                            addToHomeWarning.style.display = "none";
                    }
                }
                
                document.addEventListener("DOMContentLoaded", function(event) {
                    addHomeScreenWarning();
                    var simipwa_notification_enable = document.getElementById("simipwa_notification_enable");
                    simipwa_notification_enable.addEventListener("change", function() {
                        addHomeScreenWarning();
                    });
                });
            </script>
        ';
        return $actionHtml;
    }
}
