<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/30/18
 * Time: 11:41 AM
 */

namespace Simi\Simipwa\Block\Adminhtml\Notification;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
    
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize cms page edit block
     *
     * @return void
     */
    public function _construct()
    {

        $this->_objectId = 'message_id';
        $this->_blockGroup = 'Simi_Simipwa';
        $this->_controller = 'adminhtml_notification';

        parent::_construct();
        if ($this->_isAllowedAction('Simi_Simipwa::save_notification')) {
            $this->buttonList->update('save', 'label', __('Save'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label'          => __('Save and Continue Edit'),
                    'class'          => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Simi_Simipwa::delete_notification')) {
            $this->buttonList->update('delete', 'label', __('Delete'));
        } else {
            $this->buttonList->remove('delete');
        }

        $this->buttonList->update('save', 'label', __('Send'));
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->coreRegistry->registry('notification')->getId()) {
            return __("Edit notification '%1'", $this->escapeHtml($this->coreRegistry->registry('notification')->NoticeTitle()));
        } else {
            return __('New Notification');
        }
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

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    public function _getSaveAndContinueUrl()
    {
        return $this->getUrl('simipwaadmin/*/save', ['_current' => true,
            'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function _prepareLayout()
    {
        $arrow_down_img = $this->getViewFileUrl('Simi_Simipwa::images/arrow_down.png');
        $arrow_up_img   = $this->getViewFileUrl('Simi_Simipwa::images/arrow_up.png');
        $arrow_down_img = $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png');
        $arrow_up_img   = $this->getViewFileUrl('Simi_Simiconnector::images/arrow_up.png');

        $deviceJsUpdateFunction = '
                    /*
                        device selecting functions
                    */
                   
                    function selectDevice(e) {
                        var vl = e.value;
                        if(e.checked == true){
                            if($("devices_pushed").value == "")
                                $("devices_pushed").value = e.value;
                            else {
                                removeValueFromField(vl);
                                $("devices_pushed").value = $("devices_pushed").value + ", "+e.value;
                            }
                        }else{
                            removeValueFromField(vl);
                        }
                    }
                   
                   
                    function removeValueFromField(vl){
                        if($("devices_pushed").value.search(vl) == 0){
                                if ($("devices_pushed").value.search(vl+", ") != -1)
                                    $("devices_pushed").value = $("devices_pushed").value.replace(vl+", ","");
                                else
                                    $("devices_pushed").value = $("devices_pushed").value.replace(vl,"");
                            }else{
                                $("devices_pushed").value = $("devices_pushed").value.replace(", "+ vl,"");
                            }
                    }

                    function checkboxDeviceAllChecked(el){
                        var device_grid_trs = document.querySelectorAll(".simi-device-checkbox");
                        for (var i=0; i< device_grid_trs.length; i++) {
                            var e = device_grid_trs[i];
                            if (e.id != "checkall_device_siminotification")
                                e.checked = el.checked;
                        }
                    }
                   
                    function toogleCheckAllDevice(){
                        var device_grid_trs = document.querySelectorAll(".simi-device-checkbox");
                        var el = device_grid_trs[0];
                        if(el.checked == true){
                            for (var i=0; i< device_grid_trs.length; i++) {
                                var e = device_grid_trs[i];
                                selectDevice(e);
                            }
                        }else{
                            for (var i=0; i< device_grid_trs.length; i++) {
                                var e = device_grid_trs[i];
                                selectDevice(e);
                            }
                        }
                    }
                   
                    /*
                        device listing functions
                    */

                    function clearDevices(){                   
                        $("deviceGrid").style.display == "none";
                        toggleMainDevices(2);
                    }
                    function updateNumberSeleced(){
                        $("note_devices_pushed_number").update($("devices_pushed").value.split(", ").size());
                    }
                   
                    function toggleMainDevices(check){
                        var cate = $("deviceGrid");
                        if($("deviceGrid").style.display == "none" || (check ==1) || (check == 2)){
                            var url = "'
            . $this->getUrl('simiconnector/*/devicegrid') . '?storeview_id="+$("storeview_selected").value;
                    if(check == 1){
                                $("devices_pushed").value = $("devices_all_ids").value;
                            }else if(check == 2){
                                $("devices_pushed").value = "";
                            }
                            var params = $("devices_pushed").value.split(", ");
                            var parameters = {"form_key": FORM_KEY,"selected[]":params };
                            var request = new Ajax.Request(url,
                                {
                                    evalScripts: true,
                                    parameters: parameters,
                                    onComplete:function(transport){
                                        $("deviceGrid").update(transport.responseText);
                                        $("deviceGrid").style.display = "block";
                                    }
                                });
                            if(cate.style.display == "none"){
                                cate.style.display = "";
                            }else{
                                cate.style.display = "none";
                            }
                        }else{
                            cate.style.display = "none";                   
                        }
                        updateNumberSeleced();
                    };
        ';

        $this->_formScripts[] = $deviceJsUpdateFunction . "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'page_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'page_content');
                }
            };

            document.addEventListener('DOMContentLoaded', function(){

                // event change Type
                changeType();

                // default: hidden product grid
                document.getElementById('product_grid').style.display = 'none';
               
                // hide Device Grid
                document.getElementById('deviceGrid').style.display = 'none';

            }, false);

            document.body.addEventListener('click', function(e){
                var product_grid_trs = document.querySelectorAll('#product_grid_table tbody tr');
                var trElement;
                var radioArray = [];
                for (var i = 0, j = 0; i < product_grid_trs.length; i++) {
                    trElement = product_grid_trs.item(i);
                    trElement.addEventListener('click', function(e){
                        var rd = this.getElementsByTagName('input')[0];
                        rd.checked = true;
                        document.getElementById('product_id').value = rd.value;
                        return false;
                    });
                }

            }, false);

            function toogleProduct(){
                var product_grid = document.getElementById('product_grid');
                var product_choose_img = document.getElementById('show_product_grid');

                if(product_grid.style.display == 'none'){
                    product_grid.style.display = 'block';
                    product_choose_img.src = '$arrow_up_img';
                } else {
                    product_grid.style.display = 'none';
                    product_choose_img.src = '$arrow_down_img';
                }
            }

            function changeType(){
                var banner_type = document.getElementById('type').value;
                switch (banner_type) {
                    case '1':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'block';
                        document.querySelectorAll('#product_id')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-category_id')[0].style.display = 'none';
                        document.querySelectorAll('#category_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-notice_url')[0].style.display = 'none';
                        document.querySelectorAll('#notice_url')[0].classList.remove('required-entry');
                        break;
                    case '2':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'none';
                        document.querySelectorAll('#product_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-category_id')[0].style.display = 'block';
                        document.querySelectorAll('#category_id')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-notice_url')[0].style.display = 'none';
                        document.querySelectorAll('#notice_url')[0].classList.remove('required-entry');
                        break;
                    case '3':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'none';
                        document.querySelectorAll('#product_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-category_id')[0].style.display = 'none';
                        document.querySelectorAll('#category_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-notice_url')[0].style.display = 'block';
                        document.querySelectorAll('#notice_url')[0].classList.add('required-entry');
                        break;
                    default:
                        document.querySelectorAll('.field-product_id')[0].style.display = 'block';
                        document.querySelectorAll('#product_id')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-category_id')[0].style.display = 'none';
                        document.querySelectorAll('#category_id')[0].classList.remove('required-entry');
                        
                        document.querySelectorAll('.field-notice_url')[0].style.display = 'none';
                        document.querySelectorAll('#notice_url')[0].classList.remove('required-entry');
                }
            }
            function toogleDevice(){
                var device_grid = document.getElementById('deviceGrid');
                var device_choose_img = document.getElementById('show_device_grid');

                if(device_grid.style.display == 'none'){
                    device_grid.style.display = 'block';
                    device_choose_img.src = '$arrow_up_img';
                } else {
                    device_grid.style.display = 'none';
                    device_choose_img.src = '$arrow_down_img';
                }
            }
        ";
        return parent::_prepareLayout();
    }
}
