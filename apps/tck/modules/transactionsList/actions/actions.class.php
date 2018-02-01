<?php

require_once dirname(__FILE__).'/../lib/transactionsListGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/transactionsListGeneratorHelper.class.php';

/**
 * transactionsList actions.
 *
 * @package    e-venement
 * @subpackage transactionsList
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class transactionsListActions extends autoTransactionsListActions
{
  public function executeNew(sfWebRequest $request)
  {
    $this->redirect('transaction/new');
  }
    
  public function executeBatchSendEmails(sfWebRequest $request)
  {
    $q = Doctrine::getTable('Transaction')->createQuery('t', 'booked', true)
        ->leftJoin('t.Payments p')
        ->select('t.id')
        ->andWhereIn('t.id', $request->getParameter('ids'))
        ->groupBy('t.id')
        ->having('count(tck.id) > 0 OR count(bp.id) > 0 OR count(p.id) > 0')
    ;
    $transactions = $q->fetchArray();
    
    $this->transactions = [];
    foreach ( $transactions as $transaction ) {
        $this->transactions[] = $transaction['id'];
    }
    
    if ( count($this->transactions) > 0 ) {
        $this->getContext()->getConfiguration()->loadHelpers('I18N');
        $this->getUser()->setFlash('notice', format_number_choice('[0]No transaction needed to be confirmed|[1]1 transaction has been confirmed by email to its backer|[1,+Inf]%%nb%% transactions have been confirmed by email to their backers', array('%%nb%%' => count($this->transactions)), count($this->transactions)));
        return 'SendEmailsSuccess';
    }
  }
  public function executeBatchPrintTickets(sfWebRequest $request)
  {
    $this->error = $this->success = array();
    $q = Doctrine::getTable('Transaction')->createQuery('t', 'asked')
      ->andWhereIn('t.id', $request->getParameter('ids'))
    ;
    foreach ( $q->execute() as $transaction )
    {
      $this->error[$transaction->id] = $transaction;
      
      // no ticket
      if ( $transaction->Tickets->count() == 0 )
        continue;
      
      // needs to be fully paid before any printing
      if ( sfConfig::get('app_transaction_force_payment_before_printing', false)
        && $transaction->getPaid() < $transaction->getPrice(true, true) )
        continue;
      
      // success
      $this->success[] = $transaction->id;
      unset($this->error[$transaction->id]);
    }
    
    if ( count($this->success) > 0 )
      $this->redirect('ticket/print?ids='.implode('-', $this->success));
    
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $this->getUser()->setFlash('error', __('No given transaction has any printable ticket.'));
  }
  public function executeShow(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers(array('CrossAppLink', 'Number'));
    parent::executeShow($request);
    $this->json = array('tickets' => array(), 'products' => array(), 'member_cards' => array());
    
    // tickets
    foreach ( $this->transaction->Tickets as $ticket )
    if ( $ticket->Duplicatas->count() == 0 )
      $this->json['tickets'][] = array(
        'id'          => $ticket->id,
        'id_url'      => url_for('ticket/show?id='.$ticket->id, true),
        'family'      => $ticket->Manifestation->Event->short_name ? $ticket->Manifestation->Event->short_name : (string)$ticket->Manifestation->Event,
        'family_url'  => cross_app_url_for('event', 'event/show?id='.$ticket->Manifestation->event_id, true),
        'product'     => $ticket->Manifestation->mini_date,
        'product_url' => cross_app_url_for('event', 'manifestation/show?id='.$ticket->manifestation_id, true),
        'declination' => (string)$ticket->Gauge,
        'transaction_id' => $ticket->transaction_id,
        'cancelled'   => $ticket->hasBeenCancelled(),
        'price_name'  => (string)$ticket->Price,
        'price_id'    => $ticket->price_id,
        'value'       => $ticket->value,
        'value_txt'   => format_currency($ticket->value,$this->getContext()->getConfiguration()->getCurrency()),
        'taxes'       => $ticket->taxes,
        'taxes_txt'   => format_currency($ticket->taxes,$this->getContext()->getConfiguration()->getCurrency()),
        'vat'         => $ticket->vat,
        'vat_txt'     => format_currency($ticket->vat*100, '%'),
        'seat_id'     => $ticket->seat_id,
        'seat_name'   => (string)$ticket->Seat,
        'contact_id'  => $ticket->contact_id,
        'contact'     => (string)$ticket->DirectContact,
        'contact_url' => cross_app_url_for('rp', 'contact/edit?id='.$ticket->contact_id, true),
        'sold'        => $ticket->isSold(),
      );
    
    // products
    foreach ( $this->transaction->BoughtProducts as $pdt )
    {
      if ( !isset($this->json['products'][$id = $pdt->price_id.' '.($pdt->isSold() ? 'sold' : '')]) )
        $this->json['products'][$id] = array(
          'family'      => (string)$pdt->Declination->Product->Category,
          'family_url'  => cross_app_url_for('pos', 'category/edit?id='.$pdt->Declination->Product->product_category_id, true),
          'product'     => $pdt->Declination->Product->short_name ? $pdt->Declination->Product->short_name : (string)$pdt->Declination->Product,
          'product_url' => cross_app_url_for('pos', 'product/edit?id='.$pdt->Declination->product_id, true),
          'declination' => (string)$pdt->Declination,
          'transaction_id' => $pdt->transaction_id,
          'price_name'  => (string)$pdt->Price,
          'price_id'    => $pdt->price_id,
          'value'       => $pdt->value,
          'value_txt'   => format_currency($pdt->value,$this->getContext()->getConfiguration()->getCurrency()),
          'vat'         => $pdt->vat,
          'vat_txt'     => format_currency($pdt->vat*100, '%'),
          'sold'        => $pdt->isSold(),
          'qty'         => 1,
          'total'       => $pdt->value,
          'total_txt'   => format_currency($pdt->value, $this->getContext()->getConfiguration()->getCurrency()),
        );
      else
      {
        $this->json['products'][$id]['qty']++;
        $this->json['products'][$id]['total'] += $pdt->value;
        $this->json['products'][$id]['total_txt'] = format_currency($this->json['products'][$id]['total'], $this->getContext()->getConfiguration()->getCurrency());
      }
    }
    
    // member_cards
    foreach ( $this->transaction->MemberCards as $mc )
    if ( $mc->active )
    {
      $this->json['member_cards'][] = array(
        'id'          => $mc->id,
        'product'     => (string)$mc->MemberCardType,
        'declination' => $mc->mini_date,
        'transaction_id' => $mc->transaction_id,
        'value'       => $pdt->value,
        'value_txt'   => format_currency($pdt->value,$this->getContext()->getConfiguration()->getCurrency()),
        'contact_id'  => $mc->contact_id,
        'contact'     => (string)$mc->Contact,
        'contact_url' => cross_app_url_for('rp', 'contact/edit?id='.$mc->contact_id, true),
      );
    }
    
    if (!( sfConfig::get('sf_web_debug', false) && $request->hasParameter('debug') ))
      return 'Json';
  }
}
