<?php

/**
 * Copyright © 2016 Simi . All rights reserved.
 *
 */

namespace Simi\Simipwa\Block;

/**
 * Abstract product block context
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Context extends \Magento\Framework\View\Element\Template\Context
{

    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * @var \Simi\Simipwa\Model\Config
     */
    public $config;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $simiObjectManager;

    /**
     * @var \Magento\Framework\UrlFactory
     */
    public $urlFactory;

    /**
     * Context constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Framework\Session\SidResolverInterface $sidResolver
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\View\ConfigInterface $viewConfig
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\View\FileSystem $viewFileSystem
     * @param \Magento\Framework\View\TemplateEnginePool $enginePool
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\Page\Config $pageConfig
     * @param \Magento\Framework\View\Element\Template\File\Resolver $resolver
     * @param \Magento\Framework\View\Element\Template\File\Validator $validator
     * @param \Magento\Framework\Registry $registry
     * @param \Simi\Simipwa\Model\Config $config
     * @param \Magento\Framework\ObjectManagerInterface $simiObjectManager
     * @param \Magento\Framework\UrlFactory $urlFactory
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        \Magento\Framework\View\TemplateEnginePool $enginePool,
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Magento\Framework\View\Element\Template\File\Resolver $resolver,
        \Magento\Framework\View\Element\Template\File\Validator $validator,
        \Magento\Framework\Registry $registry,
        \Simi\Simipwa\Model\Config $config,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\UrlFactory $urlFactory
    ) {

        $this->registry = $registry;
        $this->config = $config;
        $this->simiObjectManager = $simiObjectManager;
        $this->urlFactory = $urlFactory;
        parent::__construct(
            $request,
            $layout,
            $eventManager,
            $urlBuilder,
            $cache,
            $design,
            $session,
            $sidResolver,
            $scopeConfig,
            $assetRepo,
            $viewConfig,
            $cacheState,
            $logger,
            $escaper,
            $filterManager,
            $localeDate,
            $inlineTranslation,
            $filesystem,
            $viewFileSystem,
            $enginePool,
            $appState,
            $storeManager,
            $pageConfig,
            $resolver,
            $validator
        );
    }

    /**
     * Function for getting registry object
     * @return \Magento\Framework\Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * Function for getting model config object
     * @return \Simi\Simipwa\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Function for getting object manager object
     * @return \Magento\Framework\ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->simiObjectManager;
    }

    /**
     * Function for getting UrlFactory object
     * @return \Magento\Framework\UrlFactory
     */
    public function getUrlFactory()
    {
        return $this->urlFactory;
    }
}
