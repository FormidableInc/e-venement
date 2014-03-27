<?php

/**
 * InvoiceTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class InvoiceTable extends PluginInvoiceTable
{
    /**
     * Returns an instance of this class.
     *
     * @return object InvoiceTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Invoice');
    }
    public function fetchOneById($id)
    {
      return $this->createQuery('a')->andWhere('id = ?',$id)->fetchOne();
    }
    public function retrieveList()
    {
      return $this->createQuery('i')
        ->leftJoin('i.Transaction t')
        ->leftJoin('t.Contact c')
        ->leftJoin('t.Professional p')
        ->leftJoin('p.Organism o')
      ;
    }
}
