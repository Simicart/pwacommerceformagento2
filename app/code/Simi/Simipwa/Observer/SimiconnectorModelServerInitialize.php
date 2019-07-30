<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/29/18
 * Time: 9:23 PM
 */

namespace Simi\Simipwa\Observer;

use Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class SimiconnectorModelServerInitialize implements ObserverInterface
{
    /**
     * change api
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $observerObject = $observer->getObject();
        $observerObjectData = $observerObject->getData();

        if ($observerObjectData['resource'] == 'simipwas' || $observerObjectData['resource'] == 'sitemaps') {
            $observerObjectData['module'] = 'simipwa';
            $observerObject->setData($observerObjectData);
        } else {
            $className = 'Simi\Simipwa\Model\Api\\' . ucfirst($observerObjectData['resource']);
            if (class_exists($className)) {
                $observerObjectData['module'] = "simipwa";
                $observerObject->setData($observerObjectData);
            }
        }
    }
}
