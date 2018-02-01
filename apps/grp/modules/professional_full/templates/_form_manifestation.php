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
*    Copyright (c) 2006-2012 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
          <a class="event" href="<?php echo cross_app_url_for('event','event/show?id='.$me->Manifestation->Event->id) ?>"><?php echo $me->Manifestation->Event ?></a>
          <br/>
          <a class="manifestation" href="<?php echo cross_app_url_for('event','manifestation/show?id='.$me->Manifestation->id) ?>">
            <?php echo format_date($me->Manifestation->happens_at,'EEE, dd MMM yyyy HH:mm') ?>
          </a>
          <br/>
          <a class="location" href="<?php echo cross_app_url_for('event','location/show?id='.$me->Manifestation->location_id) ?>">
            <?php echo $me->Manifestation->Location ?>
          </a>
          <br/>
          <a class="sf_admin_action_extract_accepted fg-button-mini fg-button ui-state-default fg-button-icon-left" href="<?php echo url_for('event/accepted?manifestation_id='.$me->id.'&id='.$me->Manifestation->Event->id) ?>" target="_blank">
            <?php echo __('Extract accepted') ?>
          </a><a class="sf_admin_action_extract_refused fg-button-mini fg-button ui-state-default fg-button-icon-left" href="<?php echo url_for('event/refused?manifestation_id='.$me->id.'&id='.$me->Manifestation->Event->id) ?>" target="_blank">
            <?php echo __('Extract refused') ?>
          </a><a class="sf_admin_action_export fg-button-mini fg-button ui-state-default fg-button-icon-left" href="<?php echo url_for('event/export?manifestation_id='.$me->id.'&id='.$me->Manifestation->Event->id) ?>">
            <?php echo __('Export accepted') ?>
          </a>
          <?php if ( !sfConfig::get('app_options_remove_gauges_on_contact', false) ): ?>
          <div class="count" data-manifestation-id="<?php echo $me->id ?>"></div>
          <?php endif ?>
          <!--
          <a class="fg-button-mini fg-button ui-state-default fg-button-icon-left" href="<?php echo url_for('manifestation_entry/del?id='.$me->id) ?>" onclick="javascript: return confirm('<?php echo __('Are you sure?','','sf_admin') ?>">
            <span class="ui-icon ui-icon-trash"></span>
            <?php echo __('Delete',array(),'sf_admin') ?>
          </a>
          -->
