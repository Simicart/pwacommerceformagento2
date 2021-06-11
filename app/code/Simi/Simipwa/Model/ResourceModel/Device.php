<?php

namespace Simi\Simipwa\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Connector Resource Model
 */
class Device extends AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('simipwa_agent', 'agent_id');
    }
}
