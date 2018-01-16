<?php

/**
 * MemberCardPriceModelTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class MemberCardPriceModelTable extends PluginMemberCardPriceModelTable
{
  public function retrieveList()
  {
    return $this->createQuery('mcpm')
      ->leftJoin('mcpm.MemberCardType mct')
      ->leftJoin('mcpm.Price p');
  }
  
  public function checkIfExists(MemberCardPriceModel $mcpm)
  {
    return $this->createQuery('mcpm')
      ->andWhere('mcpm.member_card_type_id = ?', $mcpm->member_card_type_id)
      ->andWhere('mcpm.price_id = ?', $mcpm->price_id)
      ->andWhere('mcpm.event_id = ?', $mcpm->event_id)
      ->count();
  }
  
  public static function getInstance()
  {
      return Doctrine_Core::getTable('MemberCardPriceModel');
  }
}
