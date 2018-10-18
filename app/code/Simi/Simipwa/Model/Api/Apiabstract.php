<?php

namespace Simi\Simipwa\Model\Api;

abstract class Apiabstract
{

    public $FILTER_RESULT = true;

    const DEFAULT_DIR = 'asc';
    const DEFAULT_LIMIT = 15;
    const DIR = 'dir';
    const ORDER = 'order';
    const PAGE = 'page';
    const LIMIT = 'limit';
    const OFFSET = 'offset';
    const FILTER = 'filter';
    const ALL_IDS = 'all_ids';
    const LIMIT_COUNT = 200;
    const MEDIA_PATH = 'Simipwa';

    public $DEFAULT_ORDER = 'entity_id';
    public $simiObjectManager;
    public $storeManager;
    public $scopeConfig;
    public $resource;
    public $storeRepository;
    public $storeCookieManager;

    /**
     * Singular key.
     *
     * @var string
     */
    public $helper;

    /**
     * Singular key.
     *
     * @var string
     */
    public $singularKey;

    /**
     * Plural key.
     *
     * @var string
     */
    public $pluralKey;
    /**
     *
     */

    /**
     * @var collection Magento
     */
    public $builderQuery = null;
    public $data;
    public $eventManager;

    abstract public function setBuilderQuery();

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    )
    {
        $this->simiObjectManager = $simiObjectManager;
        $this->scopeConfig = $this->simiObjectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->storeRepository = $this->simiObjectManager->get('\Magento\Store\Api\StoreRepositoryInterface');
        $this->storeCookieManager = $this->simiObjectManager->get('\Magento\Store\Api\StoreCookieManagerInterface');
        $this->resource = $this->simiObjectManager->get('\Magento\Framework\App\ResourceConnection');
        $this->eventManager = $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface');
        return $this;
    }

    public function setDataValue($data)
    {
        $this->data = $data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Get singular key
     * @return string
     */
    public function getSingularKey()
    {
        return $this->singularKey;
    }

    /**
     * Set singular query
     * @return $this
     */
    public function setSingularKey($singularKey)
    {
        $this->singularKey = substr($singularKey, 0, -1);
        return $this;
    }

    /**
     * Get singular key
     * @return string
     */
    public function getPluralKey()
    {
        return $this->pluralKey;
    }

    /**
     * Set singular query
     * @return $this
     */
    public function setPluralKey($pluralKey)
    {
        $this->pluralKey = $pluralKey;
        return $this;
    }

    public function store()
    {
        return $this->getDetail([]);
    }

    public function index()
    {
        $collection = $this->builderQuery;
        $this->filter();
        $data = $this->getData();
        $parameters = $data['params'];
        $page = 1;
        if (isset($parameters[self::PAGE]) && $parameters[self::PAGE]) {
            $page = $parameters[self::PAGE];
        }

        $limit = $this->getDefaultLimit();
        if (isset($parameters[self::LIMIT]) && $parameters[self::LIMIT]) {
            $limit = $parameters[self::LIMIT];
        }

        $offset = $limit * ($page - 1);
        if (isset($parameters[self::OFFSET]) && $parameters[self::OFFSET]) {
            $offset = $parameters[self::OFFSET];
        }
        $collection->setPageSize($offset + $limit);

        $all_ids = [];
        $info = [];
        $total = $collection->getSize();

        if ($offset > $total) {
            throw new \Exception(__('Invalid method.'), 4);
        }

        $fields = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }

        $check_limit = 0;
        $check_offset = 0;

        foreach ($collection as $entity) {
            if (++$check_offset <= $offset) {
                continue;
            }
            if (++$check_limit > $limit) {
                break;
            }

            $info[] = $entity->toArray($fields);
            $all_ids[] = $entity->getId();
        }
        return $this->getList($info, $all_ids, $total, $limit, $offset);
    }

    //Limit - dir - order
    public function getDefaultLimit() {
        return self::DEFAULT_LIMIT;
    }
    public function getDefaultDir() {
        return self::DEFAULT_DIR;
    }
    public function getDefaultOrder() {
        return $this->DEFAULT_ORDER;
    }

    public function show()
    {
        $entity = $this->builderQuery;
        $data = $this->getData();
        $parameters = $data['params'];
        $fields = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $info = $entity->toArray($fields);
        return $this->getDetail($info);
    }

    public function update()
    {
        return $this->getDetail([]);
    }

    public function destroy()
    {
        return $this->getDetail([]);
    }

    public function getBuilderQuery()
    {
        return $this->builderQuery;
    }

    public function callApi($data)
    {
        $this->setDataValue($data);
        $this->setBuilderQuery(null);
        $this->setPluralKey($data['resource']);
        $this->setSingularKey($data['resource']);
        if ($data['is_method'] == 1) {
            if (isset($data['resourceid']) && $data['resourceid'] != '') {
                return $this->show($data['resourceid']);
            } else {
                return $this->index();
            }
        } elseif ($data['is_method'] == 2) {
            return $this->store();
        } elseif ($data['is_method'] == 3) {
            return $this->update($data['resourceid']);
        } elseif ($data['is_method'] == 4) {
            return $this->destroy($data['resourceid']);
        }
    }

    public function getList($info, $all_ids, $total, $page_size, $from)
    {
        return [
            'all_ids' => $all_ids,
            $this->getPluralKey() => $this->motifyFields($info),
            'total' => $total,
            'page_size' => $page_size,
            'from' => $from,
        ];
    }

    public function getDetail($info)
    {
        return [$this->getSingularKey() => $this->motifyFields($info)];
    }

    public function filter()
    {
        if (!$this->FILTER_RESULT) {
            return;
        }
        $data = $this->data;
        $parameters = $data['params'];
        $query = $this->builderQuery;
        $this->_whereFilter($query, $parameters);
        $this->_order($parameters);

        return $query;
    }

    public function _order($parameters)
    {
        $query = $this->builderQuery;
        $order = isset($parameters[self::ORDER]) ? $parameters[self::ORDER] : $this->getDefaultOrder();
        $order = str_replace('|', '.', $order);
        $dir = isset($parameters[self::DIR]) ? $parameters[self::DIR] : $this->getDefaultDir();
        $query->setOrder($order, $dir);
    }

    public function _whereFilter(&$query, $parameters)
    {
        if (isset($parameters[self::FILTER])) {
            foreach ($parameters[self::FILTER] as $key => $value) {
                if ($key == 'or') {
                    $filters = [];
                    foreach ($value as $k => $v) {
                        $filters[] = $this->_addCondition($k, $v, true);
                    }
                    if (count($filters)) {
                        $query->addAttributeToFilter($filters);
                    }
                } else {
                    $filter = $this->_addCondition($key, $value);
                    $query->addAttributeToFilter($key, $filter);
                }
            }
        }
    }

    public function _addCondition($key, $value, $isOr = false)
    {
        $key = str_replace('|', '.', $key);
        if (is_array($value)) {
            foreach ($value as $operator => $v) {
                if ($operator == 'in' || $operator == 'nin') {
                    return $isOr ?
                        ['attribute' => $key, $operator => explode(',', $v)] : [$operator => explode(',', $v)];
                } else {
                    return $isOr ? ['attribute' => $key, $operator => $v] : [$operator => $v];
                }
            }
        } else {
            if ($value && ($value != '')) {
                return $isOr ? ['attribute' => $key, 'eq' => $value] : ['eq' => $value];
            }
        }
    }

    /*
     * Get Store Configuration Value
     */

    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->storeManager->getStore()->getCode());
    }

    /**
     * @return string
     */
    public function getMediaUrl($media_path)
    {
        return $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . $media_path;
    }

    //Max update to get fields
    public function motifyFields($content)
    {
        $data = $this->getData();
        $parameters = $data['params'];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
            $motify = [];
            foreach ($content as $key => $item) {
                if (in_array($key, $fields)) {
                    $motify[$key] = $item;
                }
            }
            return $motify;
        } else {
            return $content;
        }
    }
}
