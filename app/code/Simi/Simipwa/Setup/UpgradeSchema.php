<?php

namespace Simi\Simistorelocator\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Simi\Simistorelocator\Setup\InstallSchema as StorelocatorShema;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addNewTableCustomerMap($installer);
        }

        $installer->endSetup();
    }

    public function addNewTableCustomerMap(SchemaSetupInterface $installer)
    {
        $installer->run("DROP TABLE IF EXISTS {$installer->getTable('simipwa_social_customer_mapping')};");
        $installer->run("
            CREATE TABLE {$installer->getTable('simipwa_social_customer_mapping')} (
                `id` int(11) unsigned NOT NULL auto_increment,
                `customer_id` int(11) NULL default 0,
                `social_user_id` VARCHAR(255) NULL DEFAULT  '',
                `provider_id` VARCHAR(255) NULL DEFAULT  '',
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

}
