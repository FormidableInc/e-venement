<?php

/**
 * ticket actions.
 *
 * @package    symfony
 * @subpackage ticket
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ticketActions extends sfActions
{
  public function preExecute()
  {
    $this->dispatcher->notify(new sfEvent($this, 'pub.pre_execute', array('configuration' => $this->configuration)));
    parent::preExecute();
  }

  public function executeGetOrphans(sfWebRequest $request)
  {
    $options = array();
    foreach ( array('gauge_id', 'manifestation_id', 'seat_id', 'ticket_id') as $field )
      $options['$field'] = $request->getParameter($field, 'false');
    
    $this->debug($request);
    $this->json = array('error' => false, 'success' => false);
    $manif_details = true;
    
    try { $this->json['success']['orphans'] = $this->getContext()->getConfiguration()->getOrphans($this->getUser()->getTransaction(), $options); }
    catch ( liOnlineSaleException $e )
    { return $this->jsonError($e->getMessage(), $request); }
    
    $flat = array();
    foreach ( $this->json['success']['orphans'] as $gid => $data )
    foreach ( $data as $orphan )
      $flat[] = $orphan['seat_name'];
    
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $this->json['success']['message'] = count($flat) == 0
      ? __('Perfect, no orphans found!')
      : __('You need to do something to avoid those orphans (%%orphans%%)...', array('%%orphans%%' => implode(', ', $flat)))
    ;
    
    return 'Success';
  }
  public function executeAutoSeating(sfWebRequest $request)
  {
    return require(dirname(__FILE__).'/auto-seating.php');
  }
  
  public function executeModTickets(sfWebRequest $request)
  {
    return require(dirname(__FILE__).'/mod-tickets.php');
  }
  public function executeModNamedTickets(sfWebRequest $request)
  {
    return require(dirname(__FILE__).'/mod-named-tickets.php');
  }
  
  protected function checkForOrphansInJson(array $options)
  {
  }
  
  protected function jsonError($messages = array(), sfWebRequest $request)
  {
    if ( !is_array($messages) )
      $messages = array($messages);
    $this->json['error']['message'] = $messages;
    
    error_log('app: pub, module: ticket --> '.implode(' | ', $messages));
    $this->debug($request);
    return 'Error';
  }
  protected function debug(sfWebRequest $request, $no_get_param = false)
  {
    $this->raw_debug(sfConfig::get('sf_web_debug', false) && ($no_get_param || $request->hasParameter('debug')));
  }
  protected function raw_debug($bool)
  {
    if ( $bool )
    {
      $this->debug = true;
      $this->getResponse()->setContentType('text/html');
      $this->getResponse()->sendHttpHeaders();
      $this->setLayout('public');
    }
    else
      sfConfig::set('sf_web_debug', false);
  }
}
