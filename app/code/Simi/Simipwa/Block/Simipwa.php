<?php

/**
 * Copyright © 2016 Simi . All rights reserved.
 */

namespace Simi\Simipwa\Block;

use Magento\Framework\UrlFactory;

class Simipwa extends \Magento\Framework\View\Element\Template
{
    public $config;

    /**
     * @param \Simi\Simipwa\Block\Context $context
     * @param \Magento\Framework\UrlFactory $urlFactory
     */
    public function __construct(\Simi\Simipwa\Block\Context $context)
    {
        $this->config        = $context->getConfig();
        parent::__construct($context);
    }


    public function getConfigValue($path)
    {
        return $this->config->getCurrentStoreConfigValue($path);
    }

	public function IsEnableForWebsite(){
  		return $this->getConfigValue('simipwa/notification/enable');
	}

}
