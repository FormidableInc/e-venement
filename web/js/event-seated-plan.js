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
  // the global var that can be used everywhere as a "root"
  if ( LI == undefined )
    var LI = {};


  // transforms a simple HTML call into a seated plan widget (seated-plan.css is also needed)
  // you can use something as simple as <a href="<?php echo url_for('seated_plan/getSeats?id='.$seated_plan->id.'&gauge_id='.$gauge->id') ?>" class="picture seated-plan"><?php echo $seated_plan->Picture->getHtmlTag(array('title' => $seated_plan->Picture, 'width' => $seated_plan->ideal_width ? $seated_plan->ideal_width : '')) ?></a>
  if ( LI.seatedPlanInitializationFunctions == undefined )
    LI.seatedPlanInitializationFunctions = [];
  LI.seatedPlanInitializationFunctions.push(function(selector){
    $(selector).addClass('done');
  });
  LI.seatedPlanInitialization = function(root)
  {
    if ( root == undefined || root == null )
      var root = $('body');
    
    // kicking 3G proxies and bad ISPs
    if ( $(root).find('.picture.seated-plan img').attr('data-src') != $(root).find('.picture.seated-plan img').attr('src') )
      $(root).find('.picture.seated-plan img').attr('src', $(root).find('.picture.seated-plan img').attr('data-src'));
    $(root).find('.picture.seated-plan img').each(function(){
      var widget = $(this).closest('.seated-plan');
      
      var id = widget.prop('id');
      var elt = $('<span></span>')
        .prop('class',widget.prop('class'))
        .prop('id', id)
        .attr('style',widget.attr('style'))
        .append($('<div></div>').addClass('anti-handling'))
        .prepend($(this))
      ;
      
      // URLs
      var urls = [];
      if ( widget.prop('href') || widget.attr('data-href') )
      {
        urls.push($('<a></a>')
          .addClass('seats-url')
          .prop('href', widget.prop('href') ? widget.prop('href') : widget.attr('data-href'))
        );
      }
      else
      {
        widget.find('.seats-url').each(function(){
          urls.push($(this).clone());
        });
      }
      $.each(urls, function(i, url){
        url.appendTo(elt);
      });
      
      widget.replaceWith(elt);
      
      // loads the content/data
      if ( !$(this).closest('.seated-plan').is('.on-demand') )
        $(this).ready(function(){
          elt.find('.seats-url').each(function(){
            LI.seatedPlanLoadData($(this).prop('href'), id ? '#'+id : '', true);
          });
        });
      else
        $(this).closest('.seated-plan').removeClass('on-demand');
      
      // to avoid graphical bugs, relaunch the box resizing
      $(this).unbind('load').load(function(){
        // hacking some browsers that have poor rendering engines (especially for SVGs)
        if ( $(this).height() == 0 )
        {
          // TODO
          //$(this).width(7584).height(2934); // TEMP !! DEV
          
          // display and remove a clone of the current image simply to get its sizes
          var clone = $(this).clone()
            //.css('position', 'absolute')
            //.width('100%')
            .appendTo('body');
          
          var proportions = [];
          for ( i = 0 ; i < 2 ; i++ )
          {
            proportions[i] = {
              width: clone.width(),
              height: clone.height()
            };
            clone.css('width', 'auto');
          }
          clone.css('width', '');
          
          // specific Safari...
          if ( proportions[0].height == proportions[1].height )
          {
            console.log('Seated Plans: Safari resizing...');
            clone.height(proportions[1].height * proportions[0].width/proportions[1].width);
            clone.width(proportions[0].width);
          }
          
          $(this).height(clone.height()).width(clone.width());
          clone.remove();
        }
        
        // the seated-plan scale
        if ( $(this).attr('width') )
        {
          $(this).width(parseInt($(this).attr('width'),10)); // for browser compatibility, this is safer
          $(this).removeAttr('width');
        }
        width = ($(elt).parent().width() > 100 ? $(elt).parent().width() : $(window).width()) -50; // -50 is to keep a padding on the right
        var scale = width/$(this).width();
        if ( $(elt).closest('.full-seating').length > 0 ) // only for online stuff
        {
          var alternate = (+$(window).height()-$(this).position().top-15)/$(this).height();
          if ( scale > alternate ) scale = alternate; // security for graphical bugs
        }
        scale = scale > 1.25 ? 1.25 : scale; // avoiding very big zooms
        elt.css('transform', 'scale('+(scale)+')')
           .attr('data-scale', scale)
           .attr('data-scale-init', scale);
        if ( scale < 1 )
          elt.css('margin-bottom', $(this).height()*(scale-1) + 50);
        
        // box resizing
        $(this).parent()
          .css('display', 'inline-block')
          .width($(this).width())
          .height($(this).height())
        ;
        $(this).closest('.plan')
          .height($(this).height()*$(this).parent().attr('data-scale'))
          .addClass('done')
        ;
        
        // other functions
        if ( LI.seatedPlanImageLoaded != undefined && LI.seatedPlanImageLoaded.length > 0 )
        $.each(LI.seatedPlanImageLoaded, function(id, fct){
          fct();
        });
      }).each(function(){
        // a hack to call "load()" even on cached images
        if ( this.complete )
          $(this).load();
      });
    });
  }
  
  LI.seatedPlanLoadData = function(url, extra_selector, no_reset)
  {
    var selector = '.picture.seated-plan';
    if ( extra_selector )
    {
      if ( typeof(extra_selector) == 'string' )
        selector = $(extra_selector+selector);
      else if ( typeof(extra_selector) == 'object' )
        selector = $(extra_selector);
    }
    
    if ( typeof(LI.window_transition) == 'function' )
      LI.window_transition();
    $(selector).find('.seat').remove();
    $.get(url,function(json){
      if ( !no_reset )
        $(selector).find('.seat').remove();
      for ( i = 0 ; i < json.length ; i++ )
      {
        var data = json[i];
        data.object = $(selector);
        LI.seatedPlanMouseup(data);
      }
      
      // triggers, wait few milliseconds to let the browser display the complexe data...
      setTimeout(function(){
        for ( var i = 0 ; fct = LI.seatedPlanInitializationFunctions[i] ; i++ )
          fct(selector);
      },200);
      
      $('#transition .close').click();
    });
    
    setTimeout(function(){ $('#transition .close').click(); }, 1500);
  }

  // the function that add a seat on every click (mouseup) or on data loading
  LI.seatedPlanMouseup = function(data) { setTimeout(function() // optimization !?
  {
    // removing pre-seat and pre-seat behaviour
    $('.picture.seated-plan .pre-seat').remove();
    
    var position = data.position;
    var ref = $(data.object);
    var id = data.id;
    var seated_plan_id = data.seated_plan_id;
    var gauge_id = data.gauge_id;
    var name = data.name;
    var extra_class = data['class'];
    var diameter = data.diameter == undefined ? $(ref).closest('form').find('[name="seated_plan[seat_diameter]"]').val() : data.diameter;
    var occupied = data.occupied == undefined ? false : data.occupied;
    
    // the seat's name
    if ( name == undefined && $('.sf_admin_form_field_show_picture').length > 0 )
    {
      if ( $('.sf_admin_form_field_show_picture .donotask').is(':checked')
        && $('.sf_admin_form_field_show_picture .seat:first').length > 0 )
      {
        name =
          $('.sf_admin_form_field_show_picture .seat:first').attr('data-num').replace(/\d+/,'')+
          (parseInt($('.sf_admin_form_field_show_picture .seat:first').attr('data-num').replace(new RegExp($('.sf_admin_form_field_show_picture input.regexp').val()),''))+parseInt($('.sf_admin_form_field_show_picture .hop').val()));
      }
      else
      {
        name = prompt(
          $('.js_seated_plan_useful .prompt_seat_name').html(),
          $('.sf_admin_form_field_show_picture .seat:first').length == 0
          ? ''
          : $('.sf_admin_form_field_show_picture .seat:first').attr('data-num').replace(/\d+/,'')+
            (parseInt($('.sf_admin_form_field_show_picture .seat:first').attr('data-num').replace(new RegExp($('.sf_admin_form_field_show_picture input.regexp').val()),''))+parseInt($('.sf_admin_form_field_show_picture .hop').val()))
        );
      }
    }
    
    // avoid white space ending or beginning
    if ( name != undefined )
      $.trim(name);
    // need a non empty string
    if ( !name )
      return false;
      
    // then verify its unicity for this seated plan
    if ( data.record && $('.sf_admin_form_field_show_picture').length > 0 )
    if ( $('.sf_admin_form_field_show_picture .seat .txt[value="'+name+'"]').length > 0 )
    {
      LI.alert($('.js_seated_plan_useful .alert_seat_duplicate').html());
      return false;
    }
    
    // adding the seat / plot
    var seat = $('<div class="seat"><input class="txt" type="hidden" value="'+name+'" /><input class="id" type="hidden" value="'+id+'" /></div>')
      .attr('title', name
        + (data.info ? ' - '+data.info : '')
        + (data.rank ? ' ('+($('.tools .rank label').length > 0 ? $('.tools .rank label').text() : 'rank')+': '+data.rank+')' : '')
        + (occupied && occupied['transaction_id'] ? ' ('+occupied.transaction_id+(occupied.spectator ? ', '+occupied.spectator : '')+' #'+occupied.ticket_id+')' : '')
      )
      .attr('data-num', name).attr('data-rank', data.rank)
      .attr('data-id', id)
      .addClass('seat-'+id)
      .attr('data-seated-plan-id', seated_plan_id)
      .addClass('seated-plan-'+seated_plan_id)
      .width(diameter).height(diameter)
      .each(function(){
        if ( extra_class )
          $(this).addClass('seat-extra-'+extra_class)
        // width/2 to find the center
        $(this)
          .css('left', (x = Math.round(position['x']-($(this).width())/2))+'px')
          .css('top',  (y = Math.round(position['y']-($(this).width())/2))+'px')
        ;
        if ( occupied )
        {
          $(this)
            .addClass(occupied.type)
            .attr('data-ticket-id', occupied['ticket_id'])
            .attr('data-price-id', occupied['price_id'])
            .attr('data-gauge-id', occupied['gauge_id'])
          ;
        }
      }).prependTo(ref)
      .clone(true).addClass('txt').prependTo(ref)
      
      // avoiding triggering the mouseup() & mousedown() Event on .picture
      .mouseup(function(event){
        event.stopPropagation();
        return false;
      })
      .mousedown(function(event){
        event.stopPropagation();
        return false;
      })
      
      // plot removal
      .dblclick(function(event){
        // reset a seat allocation ...
        if ( $('.sf_admin_form_field_show_picture').length == 0
          && $(this).is('.in-progress')
          && !$(this).is('.printed')
          && $('.reset-a-seat').length > 0 )
        {
          LI.seatedPlanUnallocatedSeat(this);
          return; // ... and this only
        }
        
        // DB removal
        var seat = this;
        $('.js_seated_plan_useful .seat_del').each(function(){
          $(this).find('[name="seat[id]"]').val($(seat).attr('data-id'));
          $.ajax({
            url: $(this).prop('action'),
            data: $(this).serialize(),
            success: function(json){
              // message
              console.error(json);
              if ( json.message )
                LI.alert(json.message, json.success ? 'success' : 'error');
              
              // graphical removal
              $(seat).parent().find('.seat[data-id='+$(seat).attr('data-id')+']').remove();
              $('.sf_admin_form_field_show_picture .pre-seat').remove();  // cleaning current stuff
            }
          });
        });
      })
      
      // the right click to set the seat's rank
      .contextmenu(function(e){
        var seat = this;
        var rank;
        if ( rank = prompt(($('.tools .rank label').length > 0 ? $('.tools .rank label').text() : '')+' - '+$(seat).attr('title'), $(seat).attr('data-rank')) )
        $.ajax({
          url: $('.tools .rank a.ajax').prop('href'),
          data: {
            'seat[id]': $(seat).find('input.id').val(),
            'seat[rank]': rank,
          },
          method: 'get',
          success: function(data){
            $(seat).attr('title',
              $(seat).attr('title').replace(
                /\((\w+: )\d+\)/g
                ,
                '($1'+rank+')'
              )
            ).attr('data-rank', rank);
          }
        });
        return false;
      })
    ;
    
    // DB seat recording
    if ( data.record && $('.sf_admin_form_field_show_picture').length > 0 )
    $('.js_seated_plan_useful .seat_add').each(function(){
      $(this).find('[name="seat[name]"]').val(name);
      $(this).find('[name="seat[x]"]').val(position['x']);
      $(this).find('[name="seat[y]"]').val(position['y']);
      $(this).find('[name="seat[diameter]"]').val($('#seated_plan_seat_diameter').val());
      $(this).find('[name="seat[class]"]').val($('.sf_admin_form_field_show_picture .class input').val());
      $.ajax({
        url: $(this).prop('action'),
        data: $(this).serialize(),
        error: function(json){
          LI.alert($('.js_seated_plan_useful .save_error').html());
          $('.sf_admin_form_field_show_picture .seat.txt:first').dblclick();
        },
        success: function(json){
          if (!( json.success && json.success.id ))
          {
            LI.alert($('.js_seated_plan_useful .save_error').html());
            $('.sf_admin_form_field_show_picture .seat.txt:first').dblclick();
            return;
          }
          seat.parent().find('[data-num='+seat.attr('data-num')+']').attr('data-id', json.success.id);
        }
      });
    });
  }, 1); // end of setTimeout (optimization)
  }

  LI.seatedPlanUnallocatedSeat = function(seat)
  {
    if ( $('#todo').length == 0 && !confirm($('form.reset-a-seat:first .confirm').html()) )
      return false;
    
    $('form.reset-a-seat:first [name="ticket[numerotation]"]').val($(seat).find('input').val());
    $('form.reset-a-seat:first').unbind().submit(function(){
      $.ajax({
        url: $('form.reset-a-seat:first').prop('action'),
        type: $('form.reset-a-seat:first').prop('method'),
        data: $('form.reset-a-seat:first').serialize(),
        success: function(json){
          if ( !json['reset-seat-id'] )
            return;
          var seat = $('.seated-plan [data-id='+json['reset-seat-id']+']');
          seat.removeClass('in-progress').removeClass('asked').removeClass('ordered');
          seat.attr('data-ticket-id', null);
          $('#done [name="ticket[numerotation]"], #done [name="ticket[id]"]').val('');
          $('#done [name=ticket_numerotation][value="'+seat.attr('data-num')+'"]').val('')
            //.closest('.ticket').find('[name=ticket_id]').val('') // done on 2016-01-08 by beta on the advise of Brest Metropole
            .closest('.ticket').prependTo('#todo')
          ;
          $('#done .total').text(parseInt($('#done .total').text())-1);
          $('#todo .total').text(parseInt($('#todo .total').text())+1);
        },
      });
      return false;
    }).submit();
  }
  
  LI.print_seated_plan = function(sp){
    $('.gauge.active .seated-plan-parent').height(
      ($('.gauge.active .seated-plan.picture .anti-handling').width() * $('.gauge.active .seated-plan.picture').attr('data-scale'))
      +'px'
    );
    window.print();
    $('.gauge.active .seated-plan-parent').css('height', '');
  }
  
  $(document).ready(function(){
    // automagically loads plans when the HTML call is in the page
    LI.seatedPlanInitialization();
    
    // background
    $('#seated_plan_background').change(function(){
      $('.sf_admin_form_field_show_picture .picture')
        .css('background-color', $(this).val());
    }).change();
    
    // seat pre-plots
    $('.sf_admin_form_field_show_picture .picture').mousedown(function(event){
      // left click
      if ( event.which != 1 )
        return;
      
      var ref = $(this);
      
      if ( scale == undefined )
        var scale = $(this).attr('data-scale') ? parseFloat($(this).attr('data-scale')) : 1;
      var position = {
        x: Math.round((event.pageX-ref.position().left)/scale),
        y: Math.round((event.pageY-ref.position().top) /scale)
      };
      
      // the graphical pre-seat
      $('<div class="pre-seat"></div>')
        .addClass('pre-seat-'+$('.sf_admin_form_field_show_picture .seat').length)
        .css('width', $('#seated_plan_seat_diameter').val()+'px')
        .css('height', $('#seated_plan_seat_diameter').val()+'px')
        .each(function(){
          // the extra class defined in web/css/event-seated-plan.css
          if ( $('.sf_admin_form_field_show_picture .class input').val() )
            $(this).addClass('seat-extra-'+$('.sf_admin_form_field_show_picture .class input').val());
          $(this)
            .css('left', (x = Math.round(position['x']-$(this).width()/2))+'px')
            .css('top',  (y = Math.round(position['y']-$(this).width()/2))+'px');
        }).prependTo(ref);
      
      // adding a behaviour to pre-seat
      $('.sf_admin_form_field_show_picture .picture').mousemove(function(event){
        var ref = $(this);
        
        if ( scale == undefined )
          var scale = ref.attr('data-scale') ? parseFloat(ref.attr('data-scale')) : 1;
        position = {
          x: Math.round((event.pageX-ref.position().left)/scale),
          y: Math.round((event.pageY-ref.position().top) /scale)
        };
        
        // moving
        ref.find('.pre-seat').each(function(){
          $(this)
            .css('left', (x = Math.round(position['x']-$(this).width()/2))+'px')
            .css('top',  (y = Math.round(position['y']-$(this).width()/2))+'px');
        });
      });
    });
    
    // seat plots
    $('.sf_admin_form_field_show_picture .picture').mouseup(function(event){
      var ref = $(this);
      
      // left click
      if ( event.which != 1 )
        return;
      
      if ( scale == undefined )
        var scale = ref.attr('data-scale') ? parseFloat(ref.attr('data-scale')) : 1;
      
      if ( location.hash == '#debug' )
        console.log('scale: '+scale+', x: '+(event.pageX-ref.position().left)/scale+', y: '+(event.pageY-ref.position().top) /scale);
      
      return LI.seatedPlanMouseup({
        position: {
          x: Math.round((event.pageX-ref.position().left)/scale),
          y: Math.round((event.pageY-ref.position().top) /scale),
          diameter: $('#seated_plan_seat_diameter').val()
        },
        object: $(this),
        'class': $('.sf_admin_form_field_show_picture .class input').val() ? $('.sf_admin_form_field_show_picture .class input').val() : '',
        record: true,
      });
    });
    
    // removing last plot
    $(document).keypress(function(event){
      if ( event.which == 122 && (event.ctrlKey || event.metaKey) )
        $('.sf_admin_form_field_show_picture .seat.txt:first').dblclick();
    });
  });
