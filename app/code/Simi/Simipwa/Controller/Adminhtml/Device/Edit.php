<?php

namespace Simi\Simipwa\Controller\Adminhtml\Device;

use Magento\Backend\App\Action;

class Edit extends \Magento\Backend\App\Action
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
    

        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Init actions
     *
     * @return $this
     */
    private function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            'Simi_Simipwa::device_manage'
        )->addBreadcrumb(
            __('Device'),
            __('Device')
        )->addBreadcrumb(
            __('Manage Device'),
            __('Manage Device')
        );
        return $resultPage;
    }

    /**
     * Edit CMS page
     *
     * @return void
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('agent_id');
        $simiObjectManager = $this->_objectManager;
        $model = $simiObjectManager->create('Simi\Simipwa\Model\Device');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This device no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        // 3. Set entered data if was error when we do save
        $data = $simiObjectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        $this->coreRegistry->register('device', $model);

        // 5. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Device') : __('New Device'),
            $id ? __('Edit Device') : __('New Device')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Device'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getId() : __('New Device'));
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::device_manager');
    }
}
