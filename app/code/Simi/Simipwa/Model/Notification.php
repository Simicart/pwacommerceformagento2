<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/30/18
 * Time: 10:54 AM
 */

namespace Simi\Simipwa\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Simi\Simipwa\Model\ResourceModel\Notification as NotificationRM;
use Simi\Simipwa\Model\ResourceModel\Notification\Collection;
use Simi\Simiconnector\Helper\Website;

class Notification extends AbstractModel
{
    /**
     * @var \Simi\Simipwa\Helper\Website
     * */
    public $websiteHelper;
    public $simiObjectManager;

    /**
     * Notification constructor.
     * @param Context $context
     * @param ObjectManagerInterface $simiObjectManager
     * @param Registry $registry
     * @param NotificationRM $resource
     * @param Collection $resourceCollection
     * @param Website $websiteHelper
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $simiObjectManager,
        Registry $registry,
        /**/
        NotificationRM $resource,
        Collection $resourceCollection,
        Website $websiteHelper
    ) {
    

        $this->simiObjectManager = $simiObjectManager;
        $this->websiteHelper = $websiteHelper;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection
        );
    }

    public function toOptionTypeHash()
    {
        $platform = [
            '1' => __('Product In-app'),
            '2' => __('Category In-app'),
            '3' => __('Website Page'),
        ];
        return $platform;
    }

    public function getMessage()
    {
        $message = $this->getCollection()
                    ->getLastItem();
        return $message;
    }
}
