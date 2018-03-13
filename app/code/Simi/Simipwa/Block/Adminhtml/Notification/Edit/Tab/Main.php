<?php

namespace Simi\Simipwa\Block\Adminhtml\Notification\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    public $systemStore;

    /**
     * @var \Simi\Simipwa\Helper\Website
     * */
    public $websiteHelper;

    /**
     * @var \Simi\Simipwa\Model\Notification
     */
    public $siminotificationFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    public $jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;
    public $simiObjectManager;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Simi\Simipwa\Helper\Website $websiteHelper
     * @param \Simi\Simipwa\Model\ResourceModel\NotificationFactory $notificationFactory
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\ObjectManagerInterface $simiObjectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        \Simi\Simipwa\Model\NotificationFactory $notificationFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
    
        $this->simiObjectManager = $simiObjectManager;
        $this->siminotificationFactory = $notificationFactory;
        $this->websiteHelper = $websiteHelper;
        $this->systemStore = $systemStore;
        $this->jsonEncoder = $jsonEncoder;
        $this->categoryFactory = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('notification');
        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Simi_Simipwa::save_notification')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Notification Information')]);

        if ($model->getId()) {
            $fieldset->addField('message_id', 'hidden', ['name' => 'message_id']);
        }
        $data = $model->getData();

        if (isset($data['device_id'])) {
            $data['devices_pushed'] = $data['device_id'];
        }

        $fieldset->addField(
            'notice_title',
            'text',
            [
                'name' => 'notice_title',
                'label' => __('Title'),
                'title' => __('Title'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'notice_content',
            'textarea',
            [
                'name' => 'notice_content',
                'label' => __('Message'),
                'title' => __('Message'),
                'required' => false,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'type',
            'select',
            [
                'name' => 'type',
                'label' => __('Direct viewers to'),
                'title' => __('Direct viewers to'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $this->siminotificationFactory->create()->toOptionTypeHash(),
                'onchange' => 'changeType(this.value)',
            ]
        );

        $fieldset->addField(
            'product_id',
            'text',
            [
                'name' => 'product_id',
                'label' => __('Product ID'),
                'title' => __('Product ID'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;">'
                    . '<img id="show_product_grid" src="'
                    . $this->getViewFileUrl('Simi_Simipwa::images/arrow_down.png')
                    . '" title="" /></a>'
                    . $this->getLayout()
                        ->createBlock('Simi\Simipwa\Block\Adminhtml\Notification\Edit\Tab\Productgrid')->toHtml()
            ]
        );

        $fieldset->addField('category_id', 'select', [
            'name' => 'category_id',
            'label' => __('Category'),
            'title' => __('Category'),
            'required' => true,
            'values' => $this->getChildCatArray(),
        ]);

        $fieldset->addField(
            'notice_url',
            'textarea',
            [
                'name' => 'notice_url',
                'label' => __('Url'),
                'title' => __('Url'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );

        $_fieldset = $form->addFieldset('device_location', ['legend' => __('Notification Device Select')]);

        $_fieldset->addField(
            'devices_pushed',
            'textarea',
            [
                'name' => 'devices_pushed',
                'label' => __('Device IDs'),
                'title' => __('Device IDs'),
                'note'     => __('Leave this empty to push to All devices'),
                'disabled' => $isElementDisabled,
                'after_element_html' => '<a href="#" title="Show Device Grid" onclick="toogleDevice();return false;">'
                    . '<img id="show_device_grid" src="'
                    . $this->getViewFileUrl('Simi_Simipwa::images/arrow_down.png') . '" title="" /></a>'
                    . $this->getLayout()
                        ->createBlock('Simi\Simipwa\Block\Adminhtml\Notification\Edit\Tab\Devicegrid')->toHtml()
            ]
        );

        $this->_eventManager->dispatch('adminhtml_notification_edit_tab_main_prepare_form', ['form' => $form]);

        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public $categoryArray;

    public function getChildCatArray($level = 0, &$optionArray = [], $parent_id = 0)
    {
        if (!$this->categoryArray) {
            $this->categoryArray = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                ->getCollection()->addAttributeToSelect('name')->toArray();
        }
        $beforeString = '';
        for ($i = 0; $i < $level; $i++) {
            $beforeString .= '  --  ';
        }
        $level += 1;
        foreach ($this->categoryArray as $category) {
            if ($category['level'] != $level) {
                continue;
            }
            if (($parent_id == 0) || (($parent_id != 0) && ($category['parent_id'] == $parent_id))) {
                $optionArray[] = ['value' => $category['entity_id'], 'label' => $beforeString . $category['name']];
                $this->getChildCatArray($level, $optionArray, $category['entity_id']);
            }
        }
        return $optionArray;
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Notification Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Siminotification Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
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
