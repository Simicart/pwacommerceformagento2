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

        if (!$context->getVersion()) {
            //no previous version found, installation, InstallSchema was just executed
            //be careful, since everything below is true for installation !
        }

        $setup->endSetup();
    }
}
