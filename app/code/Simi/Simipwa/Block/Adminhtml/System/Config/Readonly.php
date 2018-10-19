<?php
/**
 * Created by PhpStorm.
 * User: codynguyen
 * Date: 5/23/18
 * Time: 3:36 PM
 */

namespace Simi\Simipwa\Block\Adminhtml\System\Config;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Readonly extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('readonly',1);
        $html = $element->getElementHtml();
        $value = $element->getValue();
        if ($value &&
            $this->isValidTimeStamp($value) &&
            $time = date('Y-m-d h:i:s A', $value)) {
            $html .=
                '<p class="note">
                    <span>
                        '.$time.'
                    </span>
                </p>';
        }
        return $html;
    }

    function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }
}