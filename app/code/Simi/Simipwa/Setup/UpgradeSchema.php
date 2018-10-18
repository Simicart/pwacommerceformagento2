<?php

/**
 * Copyright © 2018 Simi. All rights reserved.
 */

namespace Simi\Simipwa\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //handle all possible upgrade versions

        if(!$context->getVersion()) {
            //no previous version found, installation, InstallSchema was just executed
            //be careful, since everything below is true for installation !
        }

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $setup->run("DROP TABLE IF EXISTS {$setup->getTable('simipwa_social_customer_mapping')};");
            $setup->run("
                CREATE TABLE {$setup->getTable('simipwa_social_customer_mapping')} (
                    `id` int(11) unsigned NOT NULL auto_increment,
                    `customer_id` int(11) NULL default 0,
                    `social_user_id` VARCHAR(255) NULL DEFAULT  '',
                    `provider_id` VARCHAR(255) NULL DEFAULT  '',
                    PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        }

        $setup->endSetup();
    }
}