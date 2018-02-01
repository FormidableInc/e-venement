// the global var that can be used everywhere as a "root"
if ( LI == undefined )
  var LI = {};

$(document).ready(function(){
  // AUTO VALIDATIONS
  $('[name="transaction[contact_id]"]'+
  ', [name="transaction[professional_id]"]'+
  ', [name="transaction[description]"]'+
  ', #li_transaction_field_more input[type=checkbox]'+
  '').change(function(){ $(this).closest('form').submit(); });

  var _currency = null;
  var _fr_style = null;
  LI.tckFormatCurrency = function(value, html){
    if ( _currency == undefined )
      _currency = LI.get_currency($('#li_transaction_field_close .payments .currency').html());
    if ( _fr_style == undefined )
      _style = LI.currency_style($('#li_transaction_field_close .payments .currency').html()) == 'fr';
    if ( html == undefined )
      html = true;
    return LI.format_currency(value, true, _fr_style, _currency);
  }

  LI.initContent();
  $('#li_transaction_field_content h2 a').click(function(){
    LI.initContent();
    return false;
  });
  $('#sf_admin_content form:not(.noajax)').submit(LI.formSubmit);

  // PLAYING W/ CART'S CONTENT
  // sliding content
  var settings = Cookie.has('tck.touchscreen.hidden-bunches')
    ? JSON.parse(Cookie.get('tck.touchscreen.hidden-bunches'))
    : {};
  $('#li_transaction_field_content h2').click(function(){
    var bunch = $(this).closest('.bunch');

    // it's a bit tricky to allow the CSS transition
    if ( !bunch.hasClass('small') )
      bunch.css('height', bunch.height()+'px');
    bunch.toggleClass('small');
    setTimeout(function(){ bunch.css('height', ''); }, 200);

    $(this).find('.ui-state-highlight').focusout();

    // cookies
    settings[bunch.attr('data-bunch-id')] = bunch.hasClass('small');
    Cookie.set('tck.touchscreen.hidden-bunches', JSON.stringify(settings));
  });
  $('#li_transaction_field_content h3').click(function(){
    $(this).closest('.family').find('.items').each(function(){
      var showing = $(this).is(':hidden');

      $(this).slideToggle();
      if ( !showing ) $(this).find('.ui-state-highlight').focusout();
    });
  });

  // retrieve focusout()s
  $('.highlight input, .highlight select, .highlight textarea')
    .focusout(function(){ return false; })
    .focusin (function(){ $(this).closest('.highlight').focusin(); return false; });
  $('#li_transaction_field_gift_coupon').focusin(function(){
    $('#li_transaction_field_payment_new').focusin();
  });

  // changing quantities
  $('#li_transaction_field_content .qty a').click(function(){
    var input = $(this).closest('.qty').find('input');
    var newval = parseInt(input.val(),10)+($(this).is(':first-child') ? -1 : 1)
    input.val(newval < 0 ? 0 : newval).change();
  });
  $('#li_transaction_field_content .qty input').focusout(function(){ return false; }).select(function(){
    if ( parseInt($(this).val(),10) > parseInt($(this).prop('max'),10) )
      $(this).val($(this).prop('max'));
    if ( parseInt($(this).val(),10) < parseInt($(this).prop('min'),10) )
      $(this).val($(this).prop('min'));
    $(this).prop('defaultValue',$(this).val());
  }).change(function(){
    if ( parseInt($(this).val(),10) > parseInt($(this).prop('max'),10) )
      $(this).val($(this).prop('max'));
    if ( parseInt($(this).val(),10) < parseInt($(this).prop('min'),10) )
      $(this).val($(this).prop('min'));
    if ( $(this).closest('.highlight.ui-state-highlight').length == 0 )
      $(this).closest('.highlight').focusin();
    if ( isNaN(parseInt($(this).val(),10)) || $(this).closest('.declination').is('.active.printed') )
      $(this).val($(this).prop('defaultValue'));

    if ( $(this).prop('defaultValue') !== $(this).val() )
    {
      var diff = $(this).val() - $(this).prop('defaultValue');
      var form = $('#li_transaction_field_price_new form.prices');
      var orig = form.find('[name="transaction[price_new][qty]"]').val();

      // if the tickets to process are integrated
      if ( $(this).closest('.declination').is('.active.integrated') )
      {
        // and the qty is increasing
        if ( diff > 0 )
        {
          $(this).val($(this).prop('defaultValue'));
          return;
        }
        else
          form.find('[name="transaction[price_new][state]"]').val('integrated');
      }

      $(this).select();

      // set values & submit
      form.find('[name="transaction[price_new][qty]"]').val(diff);
      form.find('[name="transaction[price_new][price_id]"]').val($(this).closest('.declination').attr('data-price-id'));
      form.find('[name="transaction[price_new][declination_id]"]').val(
        $(this).closest('.item').attr('data-'+$(this).closest('.item').attr('data-type')+'-id')
      );
      form.find('[name="transaction[price_new][type]"]').val($(this).closest('.item').attr('data-type'));
      form.find('[name="transaction[price_new][bunch]"]').val($(this).closest('.bunch').attr('data-bunch-id'));
      form.submit();

      // reinit
      form.find('[name="transaction[price_new][qty]"]').val(orig);
    }
  });

  // total calculation
  $('#li_transaction_field_content .item .total').select(LI.calculateTotals).select();

  // showing numerotation & ids
  $('#li_transaction_field_content .item .ids').click(function(){ $(this).closest('.declination').find('.price_name').click(); });
  $('#li_transaction_field_content .item .price_name').click(function(){
    $(this).closest('.declination').find('.ids').toggleClass('show');
    $(this).closest('.highlight').focusin();
  });

  // showing the gauges
  $('#li_transaction_field_content .item').focusin(function(){
    $('#li_transaction_field_product_infos *').remove(); // cleaning products infos

    if ( $(this).find('.data .gauge.raw').length > 0 )
    {
      if ( !$(this).find('.data .gauge.raw').text() )
      {
        var gauge = this;
        $.get($(this).find('.data .gauge.raw').prop('href'), function(data){
          $(gauge).find('.data .gauge.raw').text(JSON.stringify(data));
          switch ( $(gauge).closest('[data-type]').attr('data-type') ) {
          case 'gauge':
            LI.renderGauge(gauge);
            break;
          case 'declination':
            LI.renderStocks(gauge);
            break;
          }
        });
      }
      else
      {
        switch ( $(this).attr('data-type') ) {
        case 'gauge':
          LI.renderGauge(this);
          break;
        case 'declination':
          LI.renderStocks(this);
          break;
        }
      }
    }
  }).dblclick(function(){
    $(this).find('.data .gauge.raw').html('');
    $(this).find('.data .gauge.seated.picture').remove();
    $(this).focusin();
  });

  // refreshing the gauges if the document has lost focus
  $(window).blur(function(){
    if ( location.hash === '#debug' )
      return;
    $('#li_transaction_field_product_infos *').remove(); // cleaning products infos
    $('#li_transaction_field_content .item .data .gauge.raw').html(''); // cleaning cached raw gauge
    $('#li_transaction_field_content .item .data .gauge.seated.picture').remove(); // cleaning cached seated plan
  });

  // CONTACT CHANGE & INIT
  $.each([
    '#li_transaction_field_contact_id',
    '#li_transaction_field_professional_id'
  ], function(index, value){ LI.initTouchscreen(value); });

  // CONTACT CREATION
  $('#li_transaction_field_contact_id .create-contact').click(function(){
    var w = window.open($(this).prop('href')+'&name='+$('#li_transaction_field_contact_id [name="autocomplete_transaction[contact_id]"]').val(),'new_contact');

    w.onload = function(){
      setTimeout(function(){
        $(w.document).ready(function(){
          $(w.document).find('.sf_admin_actions_form .sf_admin_action_list, .sf_admin_actions_form .sf_admin_action_save_and_add')
            .remove();
        });
        w.onunload = LI.goBackToTransaction;
      },2500);
    };

    return false;
  });

  // CLICK WIDGETS
  $('#sf_admin_content .highlight').focusout(function(){
    $(this).removeClass('ui-state-highlight'); // removing any highlight
    return false; // to avoid the event to go up in the JS tree
  }).focusin(function(){
    $('#sf_admin_content .ui-state-highlight').focusout();
    $(this).addClass('ui-state-highlight');

    if ( $(this).hasClass('board-alpha') || $(this).closest('.board-alpha').length > 0 )
      $('#li_transaction_field_board').addClass('alpha');
    if ( !$(this).hasClass('board-alpha') || $(this).closest('.board-alpha').length == 0 )
      $('#li_transaction_field_board').removeClass('alpha');
    $('#li_transaction_field_board button').each(function(){
      if ( $(this).find('.num, .alpha').length > 0 )
        $(this).val($(this).closest('#li_transaction_field_board').hasClass('num') ? $(this).find('.num').html() : $(this).find('.alpha').html());
    });

    if ( !$(this).is('#li_transaction_field_professional_id, #li_transaction_field_contact_id, #li_transaction_field_more') )
    {
      $('#li_transaction_field_informations .vcard').slideUp();
      $('#content .inner-edition').remove();
      $('#sf_admin_container').css('width','100%');
      setTimeout(function(){ $(window).resize(); }, 1500); // because of the transition-duration
    }

    return false; // to avoid the event to go up in the JS tree
  }).click(function(){
    $(this).focusin();
  });

  // AVOIDING FOCUSIN IF A CLICK ON SOME PAYMENTS WIDGETS HAPPENS
  $('#li_transaction_field_payments_list [name=partial]').mousedown(function(){
    if ( $('#li_transaction_field_content .highlight.ui-state-highlight').length == 0 )
      return true;

    $(this).appendTo('body');
    elt = this;
    $(this).click();
    console.error('click');
    setTimeout(function(){ $(elt).insertBefore($('#li_transaction_field_payments_list input:last')); }, 250);
  });
  $('#li_transaction_field_payments_list [name=invoice]').mousedown(function(){
    if ( $('#li_transaction_field_content .highlight.ui-state-highlight').length == 0 )
      return true;

    form = $(this).closest('form');
    $(this).appendTo('body');
    form.submit();
    elt = this;
    setTimeout(function(){ $(elt).insertAfter($('#li_transaction_field_payments_list input:last')); }, 250);
  });
  
  // payment pictograms
  setTimeout(function(){
    $('#li_transaction_field_payment_new .field_payment_method_pictures li').each(function(){
    $('#li_transaction_field_simplified [name="simplified[payment_method_id]"][value="'+$(this).attr('data-payment-method-id')+'"], #li_transaction_field_payment_new [name="transaction[payment_new][payment_method_id]"][value="'+$(this).attr('data-payment-method-id')+'"]')
        .parent().find('button')
        .prepend($(this).find('img'));
    });
  }, 1000);

  // autosubmit direct postalcode
  $('#li_transaction_field_postalcode input').change(function(){
    $(this).closest('form').submit();
  });
  
  // vCard & co
  $('#li_transaction_field_professional_id, #li_transaction_field_postalcode, #li_transaction_field_contact_id, #li_transaction_field_more').click(function(event, callback = null){
    $('#li_transaction_field_professional_id, #li_transaction_field_postalcode, #li_transaction_field_contact_id, #li_transaction_field_more').addClass('ui-state-highlight');
    if ( $('#li_transaction_field_contact_id .data a').length > 0
      && $('#li_transaction_field_informations .vcard').length == 0 )
    {
      $.get($('#li_transaction_field_contact_id .data a').prop('href').replace('.html','')+'/vcf', function(data){
        vcard = vCard.initialize(data);
        data = $.parseHTML(vcard.to_html());

        $(data).find('.type').remove();
        $(data).find('.postal-code').each(function(){ $(this).insertBefore($(this).closest('address').find('.locality')); });
        $('#li_transaction_field_informations').prepend($(data));
        
        if ( callback ) 
          callback();
      });
    }
    else
    {
      $('#li_transaction_field_informations .vcard').slideDown('slow');
      
      if ( callback ) 
        callback();
    }

    // show the contact's file if the screen width is wide enough
    if ( $('#sf_admin_container').width() > 1400 && $('#li_transaction_field_contact_id .data a').length > 0 )
    {
      // do not show the contact if we are inside the simplified process
      if ( $('#li_fieldset_simplified').is(':visible') )
        return;

      $('#sf_admin_container').width($('#sf_admin_container').width()-800);
      setTimeout(function(){ $(window).resize(); }, 1500); // because of the transition-duration

      var iframe = $('<iframe></iframe>')
        .attr('src',$('#li_transaction_field_contact_id .data a').prop('href'))
        .hide()
        .load(function(){
          $('#transition .close').click();
          $(this).contents().find('html').addClass('tdp-iframe');
          $(this).contents().find('a[href]').prop('target', '_parent');
          $(this).parent().slideDown('slow', function(){ $('#tdp-content .inner-actions').fadeIn(); });
          $(this).contents().find('#tdp-side-bar').hide();
          $(this).contents().find('#tdp-content').css('margin-left', 0);
          $(this).fadeIn();
        })
      ;
      $('<div></div>')
        .addClass('inner-edition').addClass('ui-widget').addClass('ui-corner-all')
        .append(iframe)
        .appendTo($('#content'))
      ;
    }
  });

  // THE BOARD
  $('#li_transaction_field_board button').click(LI.boardClick)

  // ARROWS ON DOCUMENT / ON ITEMS
  $(document).keydown(function(e){
    if ( $('#li_transaction_field_content .item.ui-state-highlight').length == 0
      || $('#li_transaction_field_content .item').length == 1 )
      return true;

    if ( e.which != 38 && e.which != 40 )
      return true;

    var items = $('#li_transaction_field_content .families:not(.sample) .family:not(.total) .item:not(.total)').toArray();
    switch ( e.which ) {
    case 40: // arrow down
      for ( i = 0 ; i < items.length ; i++ )
      {
        if ( $(items[i]).is('.ui-state-highlight') )
        {
          $(items[++i]).focusin();
          break;
        }
      }
      break;
    case 38: // arrow up
      for ( i = 0 ; i < items.length ; i++ )
      {
        if ( $(items[i]).is('.ui-state-highlight') )
        {
          $(items[--i]).focusin();
          break;
        }
      }
      break;
    }

    return false;
  });

  // FLASHES
  // hide the flashes after a while
  setTimeout(
    function(){ $('.sf_admin_flashes > *').fadeOut('slow',function(){ $(this).remove(); }); }
    , 2500
  );

  // DISPLAYS A WARNING MESSAGE WHEN THE WINDOW ATTEMPS TO BE CLOSED
  $(window).on('beforeunload', LI.closeTransaction);

  // Before new transaction
  if ( LI.forceContact )
  $('#li_transaction_field_new_transaction a').unbind('click').click(function(){    
    if ( !$('#transaction_contact_id').attr('value') ) {
      LI.alert($('#li_transaction_field_close .print .force-contact').html());
      return false;
    }
  });

  // RESPONSIVE DESIGN
  LI.responsiveDesign();

  // NEW PAYMENT
  $('#li_transaction_field_payment_new [name="transaction[payment_new][payment_method_id]"]').each(function(){
    var $li = $(this).closest('li');
    var label = $li.find('label').text();
    $li.find('input, label').hide();
    $('<button />').text(label.replace('_EPT_', ''))
      .click(function(){
        $li.find('input').prop('checked',true);
      })
      .attr('data-ept', label.indexOf('_EPT_') === 0 ? 1 : 0)
      .appendTo($li);
  });
  $('#li_transaction_field_payment_new .submit').hide();

  // reset the current transaction + resend the confirmation email + access to the simplified gui
  $('#abandon, #resend-email, #pay-online, #simplified-gui, #direct-surveys').appendTo($('#sf_admin_container .ui-widget-header h1'));
  $('#resend-email').click(function(){
    $('#autocomplete_transaction_contact_id').trigger('click', function() {
      var anchor = '#resend-email';
      if ( !$.trim($('#li_transaction_field_informations .email a').text()) )
      {
        LI.alert($(anchor).attr('data-text-error'), 'error');
        return false;
      }
      window.open($(anchor).prop('href'));
    });
    return false;
  });
});

// check gauges for overbooking
LI.checkGauges = function(form, submitHandler){
  // if the current Transaction contains only products, go for the order
  if ( $('#li_transaction_field_content #li_transaction_manifestations .families:not(.sample) .item tbody .declination [name="qty"]').length == 0
    && $('#li_transaction_field_content #li_transaction_museum .families:not(.sample) .item tbody .declination [name="qty"]').length == 0
    && $('#li_transaction_field_content #li_transaction_store .families:not(.sample) .item tbody .declination [name="qty"]').length > 0 )
  {
    $(form).clone(true).removeAttr('onsubmit').appendTo('body').submit().remove();
    return;
  }
  
  // function that valids the form
  var submitForm = function(){
    $(form).clone(true).removeAttr('onsubmit').unbind('submit').appendTo('body')
      .submit(submitHandler).submit().remove();
    setTimeout(function(){ LI.initContent(); }, 1500);
  };

  // if there is only tickets for museums
  if ( $('#li_transaction_field_content #li_transaction_manifestations .families:not(.sample) .item tbody .declination [name="qty"]').length == 0
    && $('#li_transaction_field_content #li_transaction_museum .families:not(.sample) .item tbody .declination [name="qty"]').length > 0 )
    submitForm();

  var go = true;
  var loops = $('#li_transaction_field_content #li_transaction_manifestations .families:not(.sample) .item:not(.total)').length;
  $('#li_transaction_field_content #li_transaction_manifestations .families:not(.sample) .item:not(.total)').each(function(){
    if ( go == false )
      return;
    
    if ( $(this).find('tbody .declination:not(.printed):not(.integrated) [name="qty"]').length > 0 )
    {
      var gauge = this;
      $.get($(this).find('.data .gauge.raw').prop('href'), function(data){
        loops--;
        var elts = $(gauge).find('tbody .declination:not(.active) [name="qty"]');
        $(gauge).find('.data .gauge.raw').html(JSON.stringify(data));
        LI.renderGauge(gauge, true);

        // overbooking
        var total = 0;

        // a loophole for the tickets of the current transaction
        if ( $('#li_transaction_field_payments_list [name="cancel-order"]').css('visibility') == 'hidden'
          && $('#li_transaction_field_price_new .count-demands').length == 0 )
          elts.each(function(){ total += parseInt($(this).val(),10); });

        if ( data.free - total < 0 )
        {
          go = false;
          elts.addClass('blink');
          elts.each(function(){
            // simplified GUI
            $('#li_transaction_field_simplified [data-declination-id="'+$(this).closest('data-gauge-id').attr('data-gauge-id')+'"][data-price-id="'+$(this).closest('[data-price-id]').attr('data-price-id')+'"]').addClass('blink');
          });
          LI.blinkQuantities(elts, true);
        }

        // triggered when all loops/items have been processed
        if ( loops == 0 )
        {
          var type = $('#li_transaction_field_close .overbooking .type').attr('data-type');
          if ( go == false && type == 'block' )
          {
            // if the user cannot overbook, give him an alert
            LI.alert($('#li_transaction_field_close .overbooking .msg.'+type).html());
            return;
          }
          
          // if we can go or we force overbooking, valid the loop 
          if ( go || confirm($('#li_transaction_field_close .overbooking .msg.warn').text()) )
            submitForm();
        }
      });
    }
    else
    {
      loops--;
      // for simplified printing, which allows more than one print
      if ( $(this).find('tbody .declination:not(.printed) [name="qty"]').length > 0 )
      {
        submitForm();
        go = false; // avoids many loops and many forms submissions
      }
    }
  });
  return go;
};

LI.renderStocks = function(item)
{
  if ( typeof item != 'string' && $(item).find('.data .gauge.raw').length == 0 )
    return;
  return LI.posRenderStocks(JSON.parse(typeof item == 'string' ? item : $(item).find('.data .gauge.raw').text()), $('#li_transaction_field_product_infos'));
}

LI.renderGauge = function(item, only_inline_gauge)
{
  if ( only_inline_gauge == undefined )
    only_inline_gauge = false;

  // the small gauge
  if (!( typeof item != 'string' && $(item).find('.data .gauge.raw').length == 0 ))
  {
    var data = JSON.parse(typeof item == 'string' ? item : $(item).find('.data .gauge.raw').text());
    var total = data.total > data.booked.printed + data.booked.ordered + data.booked.asked
      ? data.total
      : data.booked.printed + data.booked.ordered + data.booked.asked;
    $('#li_transaction_field_product_infos *').remove();
    $('<div></div>').addClass('gauge').addClass('raw')
      .appendTo($('#li_transaction_field_product_infos'))
      .append($('<span></span>').addClass('printed').css('width', (data.booked.printed/total*100)+'%').html(data.booked.printed).prop('title',data.booked.printed))
      .append($('<span></span>').addClass('ordered').css('width', (data.booked.ordered/total*100)+'%').html(data.booked.ordered).prop('title',data.booked.ordered))
      .append($('<span></span>').addClass('asked')  .css('width', (data.booked.asked  /total*100)+'%').html(data.booked.asked).prop('title', data.booked.asked))
      .append($('<span></span>').addClass('free')   .css('width', ((data.free < 0 ? 0 : data.free)/total*100)+'%').html(data.free).prop('title',data.free))
      .prepend($('<span></span>').addClass('text').html('<span class="total">'+data.txt+'</span> <span class="details">'+data.booked_txt+'</span>'));
    ;
    $('#li_transaction_field_product_infos .gauge.raw > *').each(function(){
      if ( $(this).width() == 0 )
        $(this).hide();
    });
  }

  // gauge for seated plan
  if ( !only_inline_gauge && typeof item != 'string' && $(item).find('.data .gauge.seated').length > 0 )
  {
    if ( $(item).find('.data .gauge.seated.picture').length > 0 )
    {
      // cache
      $(item).find('.data .gauge.seated.picture').clone(true)
        .appendTo($('#li_transaction_field_product_infos'))
        .css('margin-bottom',(-$('#li_transaction_field_product_infos .gauge.seated.picture').height())+'px') // hack to avoid a stupid margin-bottom to be added
      ;
    }
    else
    {
      // remote loading
      var plan = $(item).find('.data .gauge.seated').clone(true).hide();
      plan.appendTo('#footer');
      //var scale = ($('#li_transaction_field_product_infos').width()-15)/plan.width();
      $(plan).addClass('picture').addClass('seated-plan')
        .appendTo($('#li_transaction_field_product_infos'))
        //.css('transform', 'scale('+scale+')') // the scale
      ;
      button = $('<button />')
        .html($('#li_transaction_field_close .show-seated-plan').text())
        .click(function(){
          LI.seatedPlanInitialization($('#li_transaction_field_product_infos'));
          $(this).hide();
        });
      $('<div />').addClass('show-seated-plan')
        .append(button)
        .appendTo($('#li_transaction_field_product_infos'));

      // caching
      LI.seatedPlanInitializationFunctions.push(function(){
        $(item).find('.data .gauge.seated.picture').remove(); // to ensure that we've got only one plan cached
        $('#li_transaction_field_product_infos .gauge.seated.picture')
          .show()
          .css('margin-bottom', 0)
          .clone(true).appendTo($(item).find('.data'));
      });
    }
  }
}

LI.responsiveDesign = function(){
  $(window).resize(function(){
    var margin;
    $('#sf_admin_content').css('transform', 'scale(1)');

    var scale = {
      x: $('#sf_admin_container').width()/$('#sf_admin_content').width(),
      y: ( $(window).height()
          - $('#sf_admin_content').position().top
          - 60
         )/$('#sf_admin_content').height()
    };

    // if gap between the two scales is too important, choose the smallest
    if ( scale.x / scale.y > 1.3 )
      scale.x = scale.y * 1.3;
    if ( scale.y / scale.x > 1.3 )
      scale.y = scale.x * 1.3;

    $('#sf_admin_content').css('transform', 'scale('+scale.x+','+scale.y+')');

    $('#sf_admin_container').height(
        $('#sf_admin_content').height()*scale.y + 20
      + $('.ui-widget-header').height()
      + $('#sf_admin_header').height()
    );
  }).resize();
}

LI.initContent = function(){
  $.each(LI.urls, function(id, url){
    $.get(url,function(data){
      if ( data.error && data.error[0] )
      {
        LI.alert(data.error[1],'error');
        return;
      }
      
      if ( !data.success )
        return;
      
      if (!( data.success.error_fields !== undefined && data.success.error_fields[id] === undefined ))
      {
        LI.alert(data.success.error_fields[id],'error');
        return;
      }

      if ( data.success.success_fields[id] !== undefined && data.success.success_fields[id].data !== undefined )
      {
        LI.completeContent(data.success.success_fields[id].data.content, id);
      }
    });
  });
}

// GENERIC FORMS INITIALIZATION
LI.initTouchscreen = function(elt)
{
  switch ( elt ) {
  case '#li_transaction_field_contact_id':
    if ( $(elt+' [name="transaction[contact_id]"]').val() == '' )
    {
      $(elt+' .data a').remove();
      $('#li_transaction_field_postalcode').fadeIn().css("display","inline-block");
      $('#li_transaction_field_contact_id').removeClass('loaded'); 
    }
    else
    {
      $(elt+' .data a').prepend('<span class="ui-icon ui-icon-person"></span>');
      $('#li_transaction_field_postalcode').fadeOut();
      if($('#li_transaction_field_contact_id').hasClass('simplified')) 
      {
          $('#li_transaction_field_contact_id').addClass('loaded');    
      } else {
          $('#li_transaction_field_contact_id').removeClass('loaded');  
      }
    }
    $(elt+' .li_touchscreen_new').toggle($(elt+' .data a').length == 0);
    $('#li_transaction_field_informations .vcard').remove();
    $(elt).click();
    break;

  case '#li_transaction_field_professional_id':
    if ( $(elt+' select option').length == 0 || $(elt+' select option').length == 1 && !$(elt+' select option:first').val() )
      $(elt+' select').fadeOut('fast');
    else
      $(elt+' select').fadeIn('medium');
    break;
  }
}

// parsing a string representating a i18n float to a real float
LI.parseFloat = function(string)
{
  return parseFloat(string.replace(',','.'));
}

// THE TOTALS
LI.calculateTotals = function()
{
  if ( $(this).closest('.families.sample').length > 0 )
   return;

  // remove totals if there is only one line
  if ( !$(this).closest('.family').is('.total')
    && $(this).closest('.declinations').length > 0
    && $(this).closest('.declinations').find('.declination').length <= 1 )
  {
    if ( $(this).closest('.declinations').is('.total') )
    {
      if ( $(this).closest('.items').find('.item:not(.total) .declinations').length <= 1 )
        $(this).closest('.item').hide();
    }
    else
      $(this).hide();
  }

  var elt = this;
  var totals = new Object;
  $(this).closest($(this).closest('.declinations.total').length > 0 ? '.items' : '.declinations').find('.declination .nb').each(function(){
    var val = $(this).is('.qty') ? $(this).find('input').val() : $(this).html();
    if ( !val ) val = '';

    if ( totals[$(this).attr('class')] == undefined )
      totals[$(this).attr('class')] = 0;

    i = LI.parseFloat(val);
    if ( !isNaN(i) )
      totals[$(this).attr('class')] += i;
  });

  $.each(totals, function(index, value){
    var total = $(elt).find('.'+index.replace(/\s+/g,'.'));
    if ( $(total).hasClass('money') )
      value = value ? LI.tckFormatCurrency(value) : '-';
    if ( total.is('.qty') )
      total.find('.qty').html(value);
    else
      total.html(value);
  });

  // megatotal
  var megaelt = $(this).closest('.families').find('.family.total');
  totals = new Object;
  $(this).closest('.families').find('.family:not(.total) .item.total .nb').each(function(){
    var val = $(this).is('.qty') ? $(this).find('.qty').html() : $(this).html();
    if ( totals[$(this).attr('class')] == undefined )
      totals[$(this).attr('class')] = 0;
    i = LI.parseFloat(val);
    if ( !isNaN(i) )
      totals[$(this).attr('class')] += i;
  });
  $.each(totals, function(index, value){
    var total = $(megaelt).find('.'+index.replace(/\s+/g,'.'));
    if ( $(total).hasClass('money') )
      value = value ? LI.tckFormatCurrency(value) : '';
    if ( total.is('.qty') )
      total.find('.qty').html(value);
    else
      total.html(value);
  });

  // total of totals
  var total = { pit: 0, vat: 0, tep: 0 };
  $('.family.total .item.total tr.total').each(function(){
    var family = this;
    $.each(total, function(index, value){
      switch ( index ){
      case 'pit':
        var tmp = LI.parseFloat($(family).find('.extra-taxes').html());
        if ( !isNaN(tmp) )
          total[index] += tmp;
      default:
        var tmp = LI.parseFloat($(family).find('.'+index).html());
        if ( !isNaN(tmp) )
          total[index] += tmp;
      }
    });
  });

  var changeData = false;
  $.each(total, function(index, value){
    var $elem = $('#li_transaction_field_payments_list .topay .'+index);
    var oldval = $elem.data('value');
    $elem
      .html(LI.tckFormatCurrency(value))
      .data('value', value);
    if ( index == 'pit' && value !== oldval)
      changeData = true;

    var tmp = LI.parseFloat($('#li_transaction_field_payments_list tfoot .total .sf_admin_list_td_list_value').html());
    tmp = isNaN(tmp) ? 0 : tmp;
    tmp = total[index] - tmp * total[index]/total.pit;
    tmp = isNaN(tmp) ? 0 : tmp;
    $elem = $('#li_transaction_field_payments_list .change .'+index);    
    oldval = $elem.data('value');    
    $elem
      .html(LI.tckFormatCurrency(tmp))
      .data('value', tmp);
    if ( index == 'pit' && tmp !== oldval) {
        if (tmp < 0) 
        {
            $('.payment_missing').hide();
            $('.payment_change').show();
            $('#li_transaction_field_payments_list .change').addClass('warning');
        } else {
            $('.payment_change').hide();
            $('.payment_missing').show();
            $('#li_transaction_field_payments_list .change').removeClass('warning');
        }  
        changeData = true; 
    }
       
  });
  if ( changeData )
    $('#li_transaction_field_payments_list .topay .pit').trigger('changeData');
};

// function to go back to the ticketting transaction from the contact window
LI.goBackToTransaction = function(){
  var w = this;
  setTimeout(function(){
    $(w.document).ready(function(){
    var contact_id = $(w.document).find('[name="contact[id]"]').val();
    var contact_name = $(w.document).find('[name="contact[name]"]').val()+' '+$(w.document).find('[name="contact[firstname]"]').val();
    if ( contact_id = $(w.document).find('[name="contact[id]"]').val() )
    {
      $('#li_transaction_field_contact_id [name="transaction[contact_id]"]').val(contact_id);
      $('#li_transaction_field_contact_id [name="autocomplete_transaction[contact_id]"]').val(contact_name);
      $('#li_transaction_field_contact_id form').submit();
      w.close();
    }
    else
    {
      // one level deeper through the dream layers
      $(w.document).ready(function(){
        $(w.document).find('.sf_admin_actions_form .sf_admin_action_list, .sf_admin_actions_form .sf_admin_action_save_and_add')
          .remove();
      });
      w.onunload = LI.goBackToTransaction;
    }
    });
  },2500);
}

// click on virtual keyboard
LI.clickBoard = function(){
  var elt = $('.li_fieldset .ui-state-highlight').find('textarea, input:not([type=hidden])');
  if ( $('.li_fieldset .ui-state-highlight').closest('#li_transaction_field_content').length == 1 )
    elt = $('#li_transaction_field_price_new').find('input[type=text]'); // case of qty of "products"

  if ( $(this).val().substring(0,1) != '_' )
  {
    if ( !$(this).closest('#li_transaction_field_board').hasClass('alpha') )
      elt.val(elt.val()+parseInt($(this).find('.num').html(),10)); // num
    else // alpha
    {
      var button = this; // init

      if ( $(button).hasClass('selected') )
      {
        // same button
        var index = $(button).val().indexOf($(button).prop('title'))+1;
        var letter = $(button).val().substring(index, index+1);
        if ( !letter )
          letter = $(button).val().substring(0,1);

        $(button).prop('title', letter);
      }
      else
      {
        // changing button
        if ( $('#li_transaction_field_board .selected').length > 0 )
          elt.val(elt.val()+$('#li_transaction_field_board .selected').prop('title'));
        $('#li_transaction_field_board .selected').removeClass('selected').prop('title',false);

        // recording the current one...
        $(button).addClass('selected').prop('title',$(button).val().substring(0,1));
      }

      // completion
      setTimeout(function(){
        if ( $(button).is('.selected') )
        {
          elt.val(elt.val()+$(button).prop('title'));
          $('#li_transaction_field_board .selected').removeClass('selected').prop('title',false);
        }
      },500);
    }
  }
  else
  {
    switch ( $(this).val() ) {
    case '_ACTION_':
      if ( elt.is('textarea') )
        elt.val(elt.val()+"\n");
      else
        elt.keydown();
        elt.closest('form').submit();
      break;
    case '_BACKSPACE_':
      elt.val(elt.val().substring(0,elt.val().length-1));
      break;
    }
  }
  elt.focus();
}

// DISPLAYS A WARNING MESSAGE WHEN THE WINDOW ATTEMPS TO BE CLOSED
LI.closeTransaction = function(event){
  LI.window_transition();
  var go = { ok: true, text: '' };

  $.ajax({
    url: $('#li_transaction_field_close form').prop('action'),
    type: $('#li_transaction_field_close form').prop('method'),
    data: $('#li_transaction_field_close form').serialize(),
    async: false,
    success: function(data){
      if ( data.error[0] )
      {
        go.ok   = error[0];
        go.text = error[1];
        return;
      }
      var buf = [];

      // success
      if ( data.success.success_fields.close !== undefined )
      {
        go.ok   = true;
        $.each(data.success.success_fields.close.data, function(index, text){
          buf.push(text);
        });
        go.text = buf.join("\n");
        return;
      }

      // error
      if ( data.success.error_fields.close !== undefined )
      {
        go.ok   = false;
        $.each(data.success.error_fields.close.data, function(index, text){
          buf.push(text);
        });
        buf.push('');
        buf.push($('#li_transaction_field_close .confirmation').text());
        go.text = buf.join("\n");
        return;
      }
    }
  });

  // the GUI behaviour
  if ( !go.ok ) //&& !confirm(go.text) )
  {
    var w = window.open('','confirm-dialog','height=120, width=450, location=no, menubar=no, resizable=no, scrollbars=no, status=no, toolbar=no, top='+(screen.height/2-200/2)+', left='+(screen.width/2-500/2));
    w.parent = window;
    w.document.write('<html><head></head><body onblur="javascript: window.close();"></body></html>');
    $(w.document).find('body').append(go.text.replace(/\n/g,'<br/>'));
    submit = $('<form class="submit" style="text-align: center;" onsubmit="javascript: window.opener.location = this.action; window.close(); return false;"></form>')
      .prop('action', window.location)
      .attr('method', 'get')
      .attr('target', '_parent')
      .append('<button onclick="javascript: window.close(); return false;">'+$('#li_transaction_field_close .messages .ok').html()+'</button><button id="focus">'+$('#li_transaction_field_close .messages .cancel').html()+'</button>')
    ;
    $(w.document).find('body').append(submit);
    $(w.document).find('#focus').focus();
  }
}
