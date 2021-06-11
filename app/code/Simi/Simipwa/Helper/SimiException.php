<?php
/**
 * Localized Exception
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Simi\Simpwa\Helper;

/**
 * @api
 */
class SimiException extends \Exception
{
    public function voidFunction()
    {
        return $this;
    }
}
