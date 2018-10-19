<?php


namespace Simi\Simipwa\Block\System\Config\Form;
use Simi\Simipwa\Helper\Data;

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
            $sandboxBuildButton = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'id' => 'build_sandbox_pwa',
                    'label' => __('Build Sandbox PWA'),
                    'onclick' => 'setLocation(\'' . $this->getUrl('simipwaadmin/pwa/build',['build_type' => Data::BUILD_TYPE_SANDBOX]) . '\')',
                ]
            );
            $actionHtml .= $sandboxBuildButton->toHtml();

            $buildButton = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'id' => 'build_pwa',
                    'label' => __('Build Live PWA'),
                    'class'   => 'primary',
                    'onclick' => '
                        var r = confirm("'.__('Are you sure to Build and go Live? This will change your public Website PWA').'");
                        if (r == true) {
                            setLocation(\'' . $this->getUrl('simipwaadmin/pwa/build',['build_type' => Data::BUILD_TYPE_LIVE]) . '\')
                        }
                    ',
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
                    simipwa_general_pwa_enable = document.getElementById("simipwa_general_pwa_enable");
                    
                    if(simipwa_notification_enable.value == 0 &&
                     simipwa_general_pwa_enable && 
                     typeof simipwa_general_pwa_enable != "undefined" &&
                     simipwa_general_pwa_enable.value == 0) {
                        updateDisplayHomeScreenFields(false);
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
                        updateDisplayHomeScreenFields(true);
                        if (addToHomeWarning && typeof addToHomeWarning != "undefined")
                            addToHomeWarning.style.display = "none";
                    }
                }
                
                function updateDisplayHomeScreenFields(display) {
                    if (!display) {
                        document.getElementById("row_simipwa_homescreen_homescreen_enable").style.display = "none";
                        document.getElementById("row_simipwa_homescreen_app_name").style.display = "none";
                        document.getElementById("row_simipwa_homescreen_app_short_name").style.display = "none";
                        document.getElementById("row_simipwa_homescreen_home_screen_icon").style.display = "none";
                        document.getElementById("row_simipwa_homescreen_theme_color").style.display = "none";
                        document.getElementById("row_simipwa_homescreen_background_color").style.display = "none";
                    } else {
                        document.getElementById("row_simipwa_homescreen_homescreen_enable").style.removeProperty("display");
                        document.getElementById("row_simipwa_homescreen_app_name").style.removeProperty("display");
                        document.getElementById("row_simipwa_homescreen_app_short_name").style.removeProperty("display");
                        document.getElementById("row_simipwa_homescreen_home_screen_icon").style.removeProperty("display");
                        document.getElementById("row_simipwa_homescreen_theme_color").style.removeProperty("display");
                        document.getElementById("row_simipwa_homescreen_background_color").style.removeProperty("display");
                    }
                }
                
                document.addEventListener("DOMContentLoaded", function(event) {
                    var simipwa_notification_enable = document.getElementById("simipwa_notification_enable");
                    simipwa_notification_enable.addEventListener("change", function() {
                        addHomeScreenWarning();
                    });
                    simipwa_general_pwa_enable = document.getElementById("simipwa_general_pwa_enable");
                    simipwa_general_pwa_enable.addEventListener("change", function() {
                        addHomeScreenWarning();
                    });
                    addHomeScreenWarning();
                });
            </script>
        ';
        return $actionHtml;
    }
}
