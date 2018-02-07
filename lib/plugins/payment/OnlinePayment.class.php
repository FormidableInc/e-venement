<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2012 Baptiste SIMON <baptiste.simon AT e-glop.net>
*
***********************************************************************************/
?>
<?php
  
  abstract class OnlinePayment implements OnlinePaymentInterface
  {
    protected $name = 'generic-payment';
    protected $url = array();
    protected $currency = 'EUR';
    protected $value = 0;
    protected $return, $transaction;
    public $BankPayment = NULL;
    
    protected function __construct(Transaction $transaction)
    {
      $this->transaction = $transaction;
      $this->value = $this->transaction->getPrice(true, true) - array_sum($this->transaction->Payments->toKeyValueArray('id', 'value'));

      // the currency
      $currency = sfConfig::get('project_internals_currency', array());
      $cur = sfConfig::get('app_payment_currency','978');
      if ( isset($_POST['currency']) && isset($currency['conversions'])
        && isset($currency['conversions'][$_POST['currency']]) && isset($currency['conversions'][$_POST['currency']]['rate']) )
      {
        $cur = $_POST['currency'];
        $this->value = round($this->value * $currency['conversions'][$_POST['currency']]['rate'], 2);
      }
      $this->currency = $cur;
    }
  }
