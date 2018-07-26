<?php

/**
 * Connector Resource Collection
 */

namespace Simi\Simipwa\Model\ResourceModel\Customermap;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simipwa\Model\Customermap', 'Simi\Simipwa\Model\ResourceModel\Customermap');
    }
}
