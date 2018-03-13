<?php
namespace Simi\Simipwa\Helper;

class RequestHttp extends \Zend_Controller_Request_Http
{
    public function voidFunction()
    {
        return $this;
    }
}
