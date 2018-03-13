<?php

namespace Simi\Simipwa\Block\Adminhtml\Notification\Edit\Tab;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Devicegrid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Simi\Simipwa\Model\Device
     */
    public $deviceFactory;

    /**
     * @var \Simi\Simipwa\Model\ResourceModel\Device\CollectionFactory
     */
    public $collectionFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    public $moduleManager;

    /**
     * @var order model
     */
    public $resource;

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    public $websiteHelper;
    public $simiObjectManager;
    public $storeview_id;

    /**
     * Devicegrid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Simi\Simipwa\Model\DeviceFactory $deviceFactory
     * @param \Simi\Simipwa\Model\ResourceModel\Device\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Simi\Simiconnector\Helper\Website $websiteHelper
     * @param \Magento\Framework\ObjectManagerInterface $simiObjectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Simi\Simipwa\Model\DeviceFactory $deviceFactory,
        \Simi\Simipwa\Model\ResourceModel\Device\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
    

        $this->simiObjectManager = $simiObjectManager;
        $this->collectionFactory = $collectionFactory;
        $this->moduleManager = $moduleManager;
        $this->resource = $resourceConnection;
        $this->deviceFactory = $deviceFactory;
        $this->websiteHelper = $websiteHelper;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('deviceGrid');
        $this->setDefaultSort('agent_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    public function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function _prepareColumns()
    {
        $this->addColumn(
            'in_devices',
            [
                'type' => 'checkbox',
                'html_name' => 'devices_id',
                'required' => true,
                'values' => $this->_getSelectedDevices(),
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select',
                'renderer' => '\Simi\Simipwa\Block\Adminhtml\Notification\Edit\Tab\Devicerender',
            ]
        );

        $this->addColumn('agent_id', [
            'header' => __('ID'),
            'index' => 'agent_id',
        ]);


        $this->addColumn('user_agent', [
            'header' => __('User Agent'),
            'index' => 'user_agent',
            'width'=>'400px'
        ]);

        $this->addColumn('city', [
            'header' => __('City'),
            'index' => 'city',
        ]);


        $this->addColumn('country', [
            'type' => 'options',
            'header' => __('Country'),
            'index' => 'country',
            'options' => $this->deviceFactory->create()->toOptionCountryHash(),
        ]);

        return parent::_prepareColumns();
    }

    /**
     * Row click url
     *
     * @param \Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->_getData(
            'grid_url'
        ) ? $this->_getData(
            'grid_url'
        ) : $this->getUrl(
            'simipwaadmin/*/devicegrid',
            ['_current' => true, 'message_id' => $this->getRequest()->getParam('message_id')]
        );
    }

    /**
     * @return array
     */
    public function _getSelectedDevices()
    {
        $devices = array_keys($this->getSelectedDevices());
        return $devices;
    }

    /**
     * @return array
     */
    public function getSelectedDevices()
    {
        $noticeId = $this->getRequest()->getParam('message_id');
        if (!isset($noticeId)) {
            $noticeId = 0;
        }

        $notification = $this->simiObjectManager->get('Simi\Simipwa\Model\Notification')->load($noticeId);
        $devices = [];

        if ($notification->getId()) {
            $devices = explode(',', str_replace(' ', '', $notification->getData('devices_pushed')));
        }

        $proIds = [];

        foreach ($devices as $device) {
            $proIds[$device] = ['id' => $device];
        }
        return $proIds;
    }
}
