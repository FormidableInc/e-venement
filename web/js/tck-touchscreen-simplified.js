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

if ( LI == undefined )
  var LI = {};
if ( LI.touchscreenOnNewFamilyLoad == undefined )
  LI.touchscreenOnNewFamilyLoad = [];
LI.touchscreenSimplifiedCookie = {
  name: 'tck.touchscreen.simplified-gui',
  maxAge: 30*24*60*60 // 30 days expiration
};
  

$(document).ready(function(){
  // SWITCH BACK FROM / TO SIMPLIFIED GUI
  $('#simplified-gui').click(function(){
    $('#li_fieldset_simplified').fadeToggle(function(){
      if ( !$(this).is(':visible') )
      {
        Cookie.set(LI.touchscreenSimplifiedCookie.name, 'hide', { maxAge: LI.touchscreenSimplifiedCookie.maxAge }); // 30 days expiration
        return;
      }
      Cookie.set(LI.touchscreenSimplifiedCookie.name, 'show', { maxAge: LI.touchscreenSimplifiedCookie.maxAge });    // 30 days expiration
      
      // click on the last (or the first) tab...
      if ( !Cookie.get(LI.touchscreenSimplifiedCookie.bunch) )
        Cookie.set(LI.touchscreenSimplifiedCookie.bunch, $('#li_fieldset_simplified .products-types [data-bunch-id]').first().attr('data-bunch-id'), { maxAge: LI.touchscreenSimplifiedCookie.maxAge });
      $('#li_fieldset_simplified .products-types [data-bunch-id="'+Cookie.get(LI.touchscreenSimplifiedCookie.bunch)+'"]').click();
    });
    
    // THE CONTACT LINK...
    $('#li_transaction_field_contact_id').toggleClass('simplified');
    // THE NEW TRANSACTION LINK
    $('#li_transaction_field_new_transaction').toggleClass('simplified');
    
    return false;
  });
  
  // loading data
  $('#li_fieldset_simplified .products-types [data-bunch-id]').unbind('click').click(function(){
    if ( $(this).is('.selected') )
      return false;
    
    // remember the last chosen tab
    Cookie.set(LI.touchscreenSimplifiedCookie.bunch, $(this).attr('data-bunch-id'), { maxAge: LI.touchscreenSimplifiedCookie.maxAge });
    
    $('#li_fieldset_simplified .products-types .selected').removeClass('selected');
    $(this).addClass('selected');
    
    $('#li_fieldset_simplified .bunch')
      .attr('data-bunch-id', $(this).attr('data-bunch-id'));
    
    LI.touchscreenSimplifiedLoadData();
    return false;
  });

  if ( Cookie.get(LI.touchscreenSimplifiedCookie.name) == 'show' )
    $('#simplified-gui').click();
  
  // INIT PAYMENT METHODS FROM A COPY OF STANDARD GUI
  LI.touchscreenSimplifiedLoadPaymentMethods();
  
  // AVOID HEAVY SCROLL BARS
  $('#li_fieldset_simplified .simplified-top-block.content > ul').niceScroll();
  
  // USING THE NORMAL "PRINT" BUTTON IF CLICKING ON THE SIMPLIFIED ONE
  $('#li_fieldset_simplified .cart .print').click(function(){
    $('#li_fieldset_content .bunch').find('.print, .store-print').submit();
    setTimeout(function(){ $('#transition .close').click(); },1000);
  });
});

LI.touchscreenSimplifiedLoadPaymentMethods = function(){
  $('#li_transaction_field_payment_new .field_payment_method_id li').each(function(){
    var payment = $('<button></button>')
      .text($(this).find('label').text())
      .prop('name', 'simplified[payment_method_id]')
      .val($(this).find('input').val());
    $('<li></li>')
      .attr('data-payment-id', $(this).find('input').val())
      .append(payment)
      .appendTo($('#li_fieldset_simplified .payments'))
    ;
  });
  
  // click on a payment method
  $('#li_fieldset_simplified .payments button').click(function(){
    $('#li_transaction_field_payment_new [name="transaction[payment_new][payment_method_id]"][value="'+$(this).val()+'"]')
      .prop('checked', true);
    $('#li_transaction_field_payment_new [name="transaction[payment_new][value]"]').val($('#li_fieldset_simplified .payments [name="simplified[payment_value]"]').val());
    $('#li_fieldset_simplified .payments [name="simplified[payment_value]"]').val('')
    $('#li_transaction_field_payment_new form').submit();
    return false;
  });
}

LI.touchscreenSimplifiedLoadData = function(){
  LI.touchscreenSimplifiedData = {};
  
  // init:
  $('#li_fieldset_simplified .bunch > *').remove();
  $('#li_fieldset_simplified .bunch')
    .attr('data-bunch-id', $('#li_fieldset_simplified .products-types .selected').attr('data-bunch-id'))
    .addClass('in-progress');
  
  // get back distant initial data
  var form = $('#li_transaction_field_content [data-bunch-id="'+$('#li_fieldset_simplified .products-types .selected').attr('data-bunch-id')+'"] .new-family');
  $.ajax({
    url: $(form).prop('action'),
    type: $(form).prop('method'),
    data: { simplified: 1 /*, id: $('[name="transaction[close][id]"]').val() */ },
    success: function(data){
      $('#li_fieldset_simplified .bunch').removeClass('in-progress');
      
      var type = $(form).closest('[data-bunch-id]').attr('data-bunch-id');
      console.error('Simplified GUI: loading basic products ('+type+')');
      if (!( data.success != undefined && data.success.success_fields[type] != undefined ))
      {
        console.error('Simplified GUI: No data found for '+type);
        return;
      }
      if ( window.location.hash == '#debug' )
        console.error('Simplified GUI: Loading data for '+type);
      
      console.error(data.success.success_fields[type].data.content);
      LI.touchscreenSimplifiedData[type] = data.success.success_fields[type].data.content; // storing data in the global var
      var events = {};
      $.each(LI.touchscreenSimplifiedData[type], function(id, manif){
        if ( window.location.hash == '#debug' )
          console.error('Simplified GUI: Loading an item (#'+id+') from the '+type);
        
        var pdt;
        switch ( type ) {
        case 'museum':
        case 'manifestations':
          pdt = new Date(manif.happens_at.replace(' ','T')).toLocaleString().replace(/:\d\d( \w+){0,1}$/,'');
          break;
        
        case 'store':
          pdt = manif.name;
          break;
        
        default:
          pdt = '';
          break;
        }
        
        var gauges = $('<ul></ul>');
        $('<li></li>')
          .append('<span><span class="category">'+manif.category+'</span> <span class="product">'+pdt+'</span></span>')
          .append(gauges)
          .attr('data-family-id', manif.id)
          .appendTo($('#li_fieldset_simplified .bunch[data-bunch-id="'+type+'"]'))
        ;
        $.each(manif[manif.declinations_name], function(i, gauge){
          var li = $('<li></li>')
            .attr('data-'+gauge.type+'-id', gauge.id)
            .appendTo(gauges);
          $('<input type="radio" />')
            .val(gauge.id)
            .prop('name', 'simplified[declination_id]')
            .appendTo(li)
          ;
          li.append(' ');
          $('<span>'+gauge.name+'</span>').appendTo(li);
        });
      });
      
      LI.touchscreenSimplifiedBehavior(type);
    },
    error: function(){
      console.error('An error occurred when loading the simplified GUI...');
      $('#li_fieldset_simplified').fadeOut();
    }
  });
}

LI.touchscreenSimplifiedBehavior = function(type){
  // opens gauges for manifestation or equivalent
  $('#li_fieldset_simplified .bunch[data-bunch-id="'+type+'"] > li > :not(ul)').click(function(){
    $('#li_fieldset_simplified .prices > *').remove();
    var ul = $(this).closest('li').find('ul').slideToggle('fast');
    ul
      .find('.selected').removeClass('selected')
      .find(':checked').prop('checked', false)
    ;
    if ( ul.find('input').length == 1 )
      ul.find('input').closest('li').click();
    $(this).closest('.content').find('.bunch > li > ul').not($(this).closest('li').find('ul')).slideUp('fast');
  });
  
  // activating a particular gauge or equivalent
  $('#li_fieldset_simplified .bunch[data-bunch-id="'+type+'"] > li > ul > li').click(function(){
    var type = $(this).closest('.bunch').attr('data-bunch-id');
    
    // cleansing
    $(this).closest('.content').find('.selected').removeClass('selected');
    $(this).closest('.content').find(':checked').prop('checked', false);
    
    // select current gauge
    $(this).addClass('selected');
    $(this).find('input').prop('checked', true);
    
    // show related prices
    LI.touchscreenSimplifiedPrices(this, LI.touchscreenSimplifiedData[type]);
  });
}

LI.touchscreenSimplifiedPrices = function(gauge, data){
  var target = $('#li_fieldset_simplified .prices');
  target.find('> li').remove();
  var declinations_name = data[$(gauge).closest('[data-family-id]').attr('data-family-id')].declinations_name;
  var prices = data[$(gauge).closest('[data-family-id]').attr('data-family-id')][declinations_name][$(gauge).attr('data-'+declinations_name.slice(0,-1)+'-id')].available_prices;
  if ( prices == undefined )
  {
    console.error('Simplified GUI: no price found for manifestation #'+$(gauge).closest('[data-family-id]').attr('data-family-id')+' and gauge #'+$(gauge).attr('data-gauge-id'));
    return;
  }
  
  // add price widgets
  $.each(prices, function(i, price){
    var li = $('<li></li>')
      .attr('data-price-id', price.id)
      .appendTo(target);
    $('<button></button>')
      .text(price.name)
      .prop('name', 'simplified[price_id]')
      .prop('title', price.description+' → '+price.value)
      .val(price.id)
      .appendTo(li)
    ;
  });
  
  // click on a price button
  $(target).find('button').click(function(){
    var declname;
    $.each(LI.touchscreenSimplifiedData[$('#li_fieldset_simplified .bunch :checked').closest('.bunch').attr('data-bunch-id')], function(id, pdt){
      declname = pdt.declinations_name.slice(0,-1); // remove the last char "s"
    });
    
    var form = $('#li_transaction_field_price_new form.prices');
    $(form).find('[name="transaction[price_new][price_id]"]').val($(this).val());
    $(form).find('[name="transaction[price_new][declination_id]"]').val($('#li_fieldset_simplified .bunch :checked').val());
    $(form).find('[name="transaction[price_new][type]"]').val(declname);
    $(form).submit();
    return false;
  });
}

if ( LI.touchscreenSimplifiedContentLoad == undefined )
  LI.touchscreenSimplifiedContentLoad = [];
LI.touchscreenSimplifiedContentLoad.push(function(data, type){
  // every element on the .cart element is rendered here
  
  switch ( type ) {
  case 'payments':
    $('#li_fieldset_simplified .cart .paid .payment').remove();
    $('#li_fieldset_simplified .cart .paid .value').html(LI.format_currency(0)).attr('data-value', 0);
    $.each(data, function(id, payment){
      // do not display payments from other transcations
      if ( payment.translinked )
        return;
      
      $('<span></span>')
        .attr('data-id', payment.id)
        .addClass('payment')
        .dblclick(function(){
          var id = $(this).closest('[data-id]').attr('data-id');
          $('#li_transaction_field_payments_list [data-payment-id="'+id+'"] .sf_admin_action_delete form').submit();
          $(this).closest('[data-id]').remove();
          $('#li_fieldset_simplified .cart .paid .value').html(LI.format_currency(0)).attr('data-value', 0);
          LI.touchscreenSimplifiedTotal();
          return false;
        })
        .append('<span class="amount">'+LI.format_currency(payment.value)+'</span>')
        .append(' ')
        .append($('<span></span>').addClass('method').prop('title', payment.method).text(payment.method))
        .appendTo($('#li_fieldset_simplified .cart .paid .left'))
      ;
      $('#li_fieldset_simplified .cart .paid .right .value')
        .attr('data-value', parseFloat($('#li_fieldset_simplified .cart .paid .right .value').attr('data-value')) + parseFloat(payment.value))
        .html(LI.format_currency($('#li_fieldset_simplified .cart .paid .right .value').attr('data-value')))
      ;
    });
    
    LI.touchscreenSimplifiedTotal();
    
    break;
  
  case 'museum':
  case 'manifestations':
  case 'store':
    $.each(data, function(id, pdt){
      $.each(pdt[pdt.declinations_name], function(id, declination){
        // cancellations preprocessing
        var cancelling = [];
        $.each(declination.prices, function(id, price){
          if ( price.state != 'cancelling' )
            return;
          cancelling.push(price);
          delete declination.prices[id];
        });
        
        // normal tickets
        $.each(declination.prices, function(id, price){
          if ( window.location.hash == '#debug' )
            console.error('Simplified GUI: loading item #'+pdt.id+' sold/to sell of type '+type+'...');
          
          // clear data & recalculate totals
          $('#li_fieldset_simplified .cart .item.'+type+'[data-product-id="'+pdt.id+'"][data-declination-id="'+declination.id+'"][data-price-id="'+price.id+'"][data-state="'+price.state+'"]')
            .remove();
          LI.touchscreenSimplifiedTotal();
          
          // if nothing has to be displaid, return
          if ( price.qty == 0 )
            return;
          
          if ( window.location.hash == '#debug' )
            console.error('Simplified GUI: rendering item #'+pdt.id+' sold/to sell of type '+type+' with ids: '+price.ids.join()+'.');
          // if something needs to be displaid, display it one by one
          $.each(price.ids, function(i, pdtid){
            var name; switch ( type ) {
            case 'store':
              name = pdt.name;
              declname = 'declination';
              break;
            default:
              name = new Date(pdt.happens_at.replace(' ','T')).toLocaleString().replace(/:\d\d( \w+){0,1}$/,'');
              declname = 'gauge';
              break;
            }
            var left = $('<div></div>').addClass('left');
            var right = $('<div></div>').addClass('right');
            $('<li></li>')
              .addClass('item')
              .addClass(type)
              .addClass(price.state ? 'sold' : 'asked')
              .attr('data-product-id', pdt.id)
              .attr('data-declination-id', declination.id)
              .attr('data-price-id', price.id)
              .attr('data-state', price.state)
              .attr('data-qty', price.qty)
              .attr('data-value', (price.pit + price['extra-taxes']) / price.qty)
              .prop('title', '#'+pdtid+(price.numerotation[i] ? ' → '+price.numerotation[i] : ''))
              .append(left)
              .append(right)
              .insertAfter($('#li_fieldset_simplified .cart .topay'))
              .dblclick(function(){
                if ( $(this).is('.sold') )
                  return;
                $(str = '#li_transaction_field_content .bunch[data-bunch-id="'+type+'"] [data-family-id="'+pdt.id+'"] [data-'+declname+'-id="'+declination.id+'"] [data-price-id="'+price.id+'"] .qty.nb .ui-icon-minus')
                  .click();
                $(this).remove();
                LI.touchscreenSimplifiedTotal();
                return false;
              })
            ;
            left
              /*
              .append($('<a></a>').prop('href', pdt.category_url).text(pdt.category).addClass('category').prop('title', pdt.category))
              .append(' ')
              .append($('<a></a>').prop('href', pdt.product_url).text(name).addClass('product'))
              .append(' ')
              */
              .append($('<span></span>').text(pdt.category).addClass('category').prop('title', pdt.category))
              .append(' ')
              .append($('<span></span>').text(name).addClass('product'))
              .append(' ')
              .append($('<span></span>').text(price.name).addClass('price'))
              .append(' ')
              .append($('<span></span>').text(declination.name).addClass('declination').prop('title', declination.name))
            ;
            right
              .append($('<span></span>').attr('data-value', price.pit/price.qty).html(LI.format_currency(price.pit/price.qty)).addClass('value'))
              .append(' ')
              .append($('<span></span>').attr('data-extra-taxes', price['extra-taxes']/price.qty).html(LI.format_currency(price['extra-taxes'])).addClass('extra-taxes'))
            ;
          });
        });
        
        // cancelling post-processing
        $.each(cancelling, function(i, price){
          $(str = '#li_fieldset_simplified .cart .item.'+type+'[data-price-id="'+price.id+'"][data-declination-id="'+declination.id+'"][data-product-id="'+pdt.id+'"][data-value="'+(price.pit+price['extra-taxes'])/price.qty+'"]:not([data-state=asked]):not(.cancelled):first')
            .addClass('cancelled');
        });
      });
    });
    
    LI.touchscreenSimplifiedTotal();
    
    break;
    
    default:
      console.error('Simplified GUI: '+type+' is not yet implemented');
      break;
  }
});

LI.touchscreenSimplifiedTotal = function()
{
  // qty
  $('#li_fieldset_simplified .cart .total .qty')
    .attr('data-qty', $('#li_fieldset_simplified .cart .item').length)
    .text($('#li_fieldset_simplified .cart .item').length)
  ;
  
  // value
  $('#li_fieldset_simplified .cart .total .value').each(function(){
    $(this)
      .attr('data-value', 0)
      .html(LI.format_currency($(this).attr('data-value')))
    ;
  });
  $('#li_fieldset_simplified .cart .item').each(function(){
    var item = this;
    $('#li_fieldset_simplified .cart .total .value').each(function(){
      $(this)
        .attr('data-value', parseFloat($(this).attr('data-value')) + parseFloat($(item).find('.value').attr('data-value')) + parseFloat($(item).find('.extra-taxes').attr('data-extra-taxes')))
        .html(LI.format_currency($(this).attr('data-value')))
      ;
    });
  });
  
  var topay = $('#li_fieldset_simplified .cart .topay');
  var paid  = $('#li_fieldset_simplified .cart .paid');
  var total = $('#li_fieldset_simplified .cart .total');
  topay.find('.value').html(LI.format_currency(
    parseFloat(total.find('.value').attr('data-value'))
    -
    parseFloat(paid.find('.value').attr('data-value'))
  ));
}
