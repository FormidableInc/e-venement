<?php

/**
 * WebOriginIpTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class WebOriginIpTable extends PluginWebOriginIpTable
{
    /**
     * Returns an instance of this class.
     *
     * @return object WebOriginIpTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('WebOriginIp');
    }
}