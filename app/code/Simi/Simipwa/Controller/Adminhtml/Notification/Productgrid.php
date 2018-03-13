<?php

namespace Simi\Simipwa\Controller\Adminhtml\Notification;

class Productgrid extends \Magento\Catalog\Controller\Adminhtml\Product
{

    public $resultLayoutFactory;

    /**
     * @var $productBuilder
     */
    public $productBuilder;

    /**
     * Productgrid constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
    ) {
    

        parent::__construct($context, $productBuilder);
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    public function execute()
    {
        $this->productBuilder->build($this->getRequest());
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock('simiconnector.siminotification.edit.tab.productgrid')
            ->setProducts($this->getRequest()->getPost('products', null));
        return $resultLayout;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Simi_Simipwa::notification_manager');
    }
}
