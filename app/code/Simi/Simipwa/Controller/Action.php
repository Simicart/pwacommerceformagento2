<?php

/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */

namespace Simi\Simipwa\Controller;

class Action extends \Magento\Framework\App\Action\Action
{

    public $data;
    public $simiObjectManager;
    public $storeManager;
    public $scopeConfigInterface;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
   
        parent::__construct($context);
        $this->simiObjectManager  = $context->getObjectManager();
        $this->cacheTypeList     = $cacheTypeList;
        $this->cacheState        = $cacheState;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory  = $resultPageFactory;
        $this->storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->scopeConfigInterface = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');

        // Read Magento\Framework\App\Request\CsrfValidator for reason
        if ($this->getRequest() && $this->getRequest()->isPost()) {
            $formKey = $this->simiObjectManager->get('\Magento\Framework\Data\Form\formKey')->getFormKey();
            $this->getRequest()->setParam('form_key', $formKey);
        }
    }
    public function execute()
    {
        $this->preDispatch();
    }
}
