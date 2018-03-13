<?php

namespace Simi\Simipwa\Block\Adminhtml\Device\Edit\Tab;

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
     * @var \Simi\Simipwa\Model\Device
     */
    public $deviceFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    public $jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Simi\Simipwa\Helper\Website $websiteHelper
     * @param \Simi\Simipwa\Model\DeviceFactory $deviceFactory
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data \
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        \Simi\Simipwa\Model\DeviceFactory $deviceFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
    

        $this->deviceFactory = $deviceFactory;
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

        $model = $this->_coreRegistry->registry('device');

        /*
         * Checking if user have permissions to save information
         */
        $isElementDisabled = true;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Device Information')]);
        $data = $model->getData();
        if (isset($model) && $model->getId()) {
            $data['device_id'] = $data['agent_id'];
            $fieldset->addField('device_id', 'hidden', ['name' => 'device_id']);
        }


        $fieldset->addField(
            'user_agent',
            'label',
            [
                'name' => 'user_agent',
                'label' => __('PWA User Agent'),
                'title' => __('PWA User Agent'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );


        $fieldset->addField(
            'city',
            'label',
            [
                'name' => 'city',
                'label' => __('City'),
                'title' => __('City'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'country',
            'label',
            [
                'name' => 'country',
                'label' => __('Country'),
                'title' => __('Country'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        $fieldset->addField(
            'endpoint',
            'label',
            [
                'name' => 'endpoint',
                'label' => __('PWA Endpoint'),
                'title' => __('PWA Endpoint'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'endpoint_key',
            'label',
            [
                'name' => 'endpoint_key',
                'label' => __('PWA Endpoint Key'),
                'title' => __('PWA Endpoint Key'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'p256dh_key',
            'label',
            [
                'name' => 'p256dh_key',
                'label' => __('P256dh key'),
                'title' => __('P256dh key'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'auth_key',
            'label',
            [
                'name' => 'auth_key',
                'label' => __('Authentication key'),
                'title' => __('Authentication key'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'created_at',
            'label',
            [
                'name' => 'created_at',
                'label' => __('Created Date'),
                'title' => __('Created Date'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );

        $this->_eventManager->dispatch('adminhtml_device_edit_tab_main_prepare_form', ['form' => $form]);

        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Device Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Device Information');
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
