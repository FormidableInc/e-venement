<?php

/**
 * SlavePingTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class SlavePingTable extends PluginSlavePingTable
{
    /**
     * Returns an instance of this class.
     *
     * @return object SlavePingTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('SlavePing');
    }
}