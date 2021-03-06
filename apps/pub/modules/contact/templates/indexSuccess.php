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
*    Copyright (c) 2006-2015 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2015 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php include_partial('global/flashes') ?>
<?php include_partial('global/ariane', array('active' => 0)) ?>
<h1><?php echo __('My account') ?></h1>
<?php include_partial('index_contact',array('contact' => $contact)) ?>

<?php if ( $active_member_cards->count() > 0 ): ?>
<?php include_partial('index_member_cards',array('show_events' => true, 'member_cards' => $active_member_cards, 'title' => sfConfig::get('app_member_cards_title',false) ? pubConfiguration::getText('app_member_cards_title') : __('Member card'))) ?>
<?php endif ?>

<?php if ( $used_member_cards->count() > 0 ): ?>
<?php include_partial('index_member_cards',array('show_events' => false, 'member_cards' => $used_member_cards, 'title' => sfConfig::get('app_member_cards_title',false) ? pubConfiguration::getText('app_member_cards_title') : __('Expired member card'))) ?>
<?php endif ?>

<?php if ( $products->count() > 0 ): ?>
<?php include_partial('index_products',array('products' => $products)) ?>
<?php endif ?>

<?php if ( $manifestations->count() > 0 ): ?>
<?php include_partial('index_manifestations',array('manifestations' => $manifestations)) ?>
<?php endif ?>

<?php if ( $contact->Transactions->count() > 0 ): ?>
<?php include_partial('index_transactions',array('transactions' => $transactions, 'current_transaction' => $current_transaction)) ?>
<?php endif ?>

