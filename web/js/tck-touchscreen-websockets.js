/**********************************************************************************
 *
 *           This file is part of e-venement.
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
 *    Copyright (c) 2006-2016 Baptiste SIMON <baptiste.simon AT e-glop.net>
 *    Copyright (c) 2006-2016 Libre Informatique [http://www.libre-informatique.fr/]
 *
 ***********************************************************************************/

if ( LI === undefined )
  var LI = {};

$(document).ready(function () {
  var connector = new EveConnector('https://localhost:8164', function () {
    // *T* here we are after the websocket first connection is established
    getAvailableUsbDevices().then(getAvailableSerialDevices).then(function(){
      if ( LI.activeEPT )
        configureEPT();
    });
 
    connector.onError = function () {
      $('#li_transaction_museum .print [name=direct], #li_transaction_manifestations .print [name=direct]')
        .remove();
      $('#li_transaction_museum .print').prop('title', null);
    };
  });
  
  // AVAILABLE DEVICES ******************
  
  function getAvailableDevices(type)
  {
    if (['usb', 'serial'].indexOf(type) === -1)
      return Promise.reject('Wrong device type: ' + type);

    // Get all the configured devices ids
    connector.log('info', 'Configured ' + type + ' devices: ', LI[type]);
    if (LI[type] === undefined)
      return;
    var devices = [];
    ['printers', 'displays', 'epts'].forEach(function(family){
      if (LI[type][family] !== undefined)
        for (var name in LI[type][family])
          devices.push(LI[type][family][name][0]);
    });  

    return connector.areDevicesAvailable({type: type, params: devices}).then(function(data){
      // data contains the list of the available devices
      connector.log('info', 'Available ' + type + ' devices:', data.params);

      // Check if we have an available USB printer device and store it in LI.activeDirectPrinter global variable
      var foundPrinter = false;
      if ( type === "usb" && LI.usb.printers !== undefined ) {
        foundPrinter = data.params.some(function(device){
          for ( var name in LI.usb.printers )
            if ( LI.usb.printers[name][0].vid === device.vid && LI.usb.printers[name][0].pid === device.pid ) {
              LI.activeDirectPrinter = {type: type, params: device}; // global var
              connector.log('info', 'LI.activeDirectPrinter:', LI.activeDirectPrinter);
              return true;
            }
          return false;
        });
      }
      foundPrinter && configureDirectPrint();

      // Check if we have an available SERIAL display device and store it in LI.activeDisplay global variable
      LI.activeDisplay = false; // global var
      if ( type === "serial" && LI.serial.displays !== undefined ) {
        data.params.some(function(device){
          for ( var name in LI.serial.displays )
            if ( device.pnpId.includes(LI.serial.displays[name][0].pnpId) ) {
              LI.activeDisplay = {type: type, params: device}; 
              connector.log('info', 'LI.activeDisplay:', LI.activeDisplay);
              return true;
            }
          return false;
        });
      }
      if (LI.activeDisplay) {
        configureDisplay();
        displayTotals();
      }

      // Check if we have an available serial EPT device and store it in LI.activeEPT global variable
      LI.activeEPT = false; // global var
      if ( type === "serial" && LI.serial.epts !== undefined ) {
        data.params.some(function(device){
          for ( var name in LI.serial.epts )
            if ( device.pnpId.includes(LI.serial.epts[name][0].pnpId) ) {
              LI.activeEPT = {type: type, params: device}; // global var
              connector.log('info', 'LI.activeEPT:', LI.activeEPT);
              return true;
            }
          return false;
        });
      }          
    }).catch(function(error){
      connector.log('error', error);          
    });

  }; // END getAvailableDevices()

  function getAvailableUsbDevices()
  { 
    return getAvailableDevices('usb'); 
  };

  function getAvailableSerialDevices()
  { 
    return getAvailableDevices('serial'); 
  };     
  
  // LCD DISPLAY ******************

  var lastDisplay = {date: Date.now(), lines: []};
  var displayTotalsTimeoutId;

  // configure the form for handling LCD display (if there is an available LCD display)
  function configureDisplay()
  {
    if ( LI.activeDisplay === undefined ) 
      return;

    // Refresh Display when totals change
    $('#li_transaction_field_payments_list .topay .pit').on("changeData", displayTotals);      
    $('#li_transaction_field_payments_list .change .pit').on("changeData", displayTotals);

    // Display totals when page (or tab) is selected
    document.addEventListener("visibilitychange", function(evt){
      visible = !this.hidden;  
      if ( visible )
        displayTotals(500, true);
      else
        displayDefaultMsg();
    });      

    // Display default message when leaving the page (or tab closed...)
    $(window).on("beforeunload", function(){
      $('#li_transaction_field_payments_list .topay .pit').unbind("changeData", displayTotals);
      $('#li_transaction_field_payments_list .change .pit').unbind("changeData", displayTotals);
      displayDefaultMsg(true);    
    });
  };

  // outputs totals on LCD display
  function displayTotals(delay, force) {
    var Display = LIDisplay(LI.activeDisplay, connector);
    if (!Display) 
      return;      

    var total = $('#li_transaction_field_payments_list .topay .pit').data('value');
    total = LI.format_currency(total, false).replace('€', 'E');
    var total_label = $('.displays .display-total').text().trim();
    var total_spaces = ' '.repeat(Display.width - total.length - total_label.length);
    var left = $('#li_transaction_field_payments_list .change .pit').data('value');
    left = LI.format_currency(left, false).replace('€', 'E');
    var left_label = $('.displays .display-left').text().trim();
    var left_spaces = ' '.repeat(Display.width - left.length - left_label.length);      

    var lines = [
      total_label + total_spaces + total, 
      left_label + left_spaces + left
    ];

    clearTimeout(displayTotalsTimeoutId);
    if ( !force && lines.join('||') === lastDisplay.lines.join('||') ) {
      return;
    }

    var now = Date.now();
    if ( delay === undefined )
      delay = (now - lastDisplay.date < 500) ? 500 : 0;
    displayTotalsTimeoutId = setTimeout(function(){
      lastDisplay.date = now;
      lastDisplay.lines = lines;
      Display.write(lines);
    }, delay);

  };

  // outputs default message on USB display
  function displayDefaultMsg(force) {
    var Display = LIDisplay(LI.activeDisplay, connector);
    if (!Display) 
      return;      

    var msg = $('.displays .display-default').text();
    msg = msg || "...";
    var lines = [msg, ''];

    clearTimeout(displayTotalsTimeoutId);
    if ( lines.join('||') === lastDisplay.lines.join('||') ) {
      return;
    }

    var now = Date.now();
    var delay = (now - lastDisplay.date < 500) ? 500 : 0;
    if ( force ) {
      lastDisplay.date = now;
      lastDisplay.lines = lines;
      Display.write(lines);
    }
    else displayTotalsTimeoutId = setTimeout(function(){
      lastDisplay.date = now;
      lastDisplay.lines = lines;
      Display.write(lines);
    }, delay);
  };

  // DIRECT PRINTING ******************

  // configure the form for direct printing (if there is an available direct printer)
  function configureDirectPrint()
  {
    if ( LI.activeDirectPrinter === undefined ) 
      return;

    var usbParams = LI.activeDirectPrinter.params;

    var dp_title = $('#li_transaction_field_close .print .direct-printing-info').length > 0 ?
      $('#li_transaction_field_close .print .direct-printing-info').text() :
      ( LI.directPrintTitle ? LI.directPrintTitle : "Direct Print" );

    $('#li_transaction_museum .print, #li_transaction_manifestations .print')
      .each(function () {
        $(this)
          .append($('<input type="hidden" />').prop('name', 'direct').val(JSON.stringify(usbParams)))
          .prop('title', dp_title);
      })
      .attr('onsubmit', null)
      .unbind('submit')
      .submit(function () {
        // *T* here we are when the print form is submitted
        connector.log('info', 'Submitting direct print form...');
        LI.printTickets(this, false, directPrint);
        return false;
      });

    // Partial print
    $('form.print.partial-print')
      .append($('<input type="hidden" />').prop('name', 'direct').val(JSON.stringify(usbParams)))
      .prop('title', dp_title)
      .unbind('submit')
      .submit(directPrint);      
  };

  function directPrint(event) {
    var form = event.target;
    if (LI.activeDirectPrinter === undefined) {
      LI.alert('Direct printer is undefined', 'error');
      return false;
    }
    $.ajax({
      method: $(form).prop('method'),
      url: $(form).prop('action'),
      data: $(form).serialize(),
      error: function (error) {
        console.error('directPrint ajax error', error);
      },
      success: function (data) {
        // *T* here we are when we have got the base64 data representing tickets ready to be printed
        if (!data) {
          connector.log('info', 'Empty data, nothing to send');
          return;
        }

        // send data to the printer through the connector then reads the printer answer
        connector.log('info', 'Sending data...');
        connector.log('info', data);
        var Printer = LIPrinter(LI.activeDirectPrinter, connector);
        if (!Printer) {
          LI.alert('Direct printer not configured', 'error');
          return;
        }
        var logData = {
          transaction_id: LI.transactionId,
          duplicate: $(form).find('[name=duplicate]').prop('checked'),
          printer: JSON.stringify(LI.activeDirectPrinter)
        };
        Printer.print(data).then(function(res){
          connector.log('info', 'Print OK', res);
          LI.alert('Print OK');
          logData.error = false;
          logData.status = res.statuses.join(' | ');
          logData.raw_status = res.raw_status;
        }).catch(function (err) {
          connector.log('error', 'Print error:', err);
          for ( var i in err.statuses ) LI.alert(err.statuses[i], 'error');
          logData.error = true;
          logData.status = err.statuses.join(' | ');
          logData.raw_status = err.raw_status;
        }).then(function(){
          // log direct print result in DB
          $.ajax({
            type: "GET",
            url: LI.directPrintLogUrl,
            data: {directPrint: logData},
            dataType: 'json',
            success: function () {
              connector.log('info', 'directPrintLog success', LI.closePrintWindow);
              typeof LI.closePrintWindow === "function" && LI.closePrintWindow();
            },
            error: function (err) {
              console.error(err);
            }            
          });
        });
      }
    });
    return false;
  };
  
  // ELECTRONIC PAYMENT TERMINAL ******************
  
  // configure the form for EPT handling (if there is an available EPT)
  function configureEPT() {
    if ( LI.activeEPT === undefined ) 
      return;
    $('#li_transaction_field_payment_new button[data-ept=1]').click(startEPT);
    $('.cancel-ept-transaction').click(cancelEPT);    
  }
  
  // toggle between the payment form and the EPT screen
  function toggleEPTtransaction()
  {
    $('#li_transaction_field_payment_new form').toggle();
    $('#li_transaction_field_simplified .payments button[name="simplified[payment_method_id]"], #li_transaction_field_simplified .payments input').toggle()
    $('#ept-transaction').toggle();
    $('#ept-transaction-simplified').toggle();
  }

  function getCentsAmount(value) {
    value = value + '';
    var amount = value.replace(",", ".").trim();
      if( /^(\-|\+)?[0-9]+(\.[0-9]+)?$/.test(amount) )
        return Math.round(amount * 100);
    return 'Not a number';
  };

  // Initiate a transaction with the EPT
  function startEPT(event) {
    var EPT = LIEPT(LI.activeEPT, connector);
    if ( !EPT )
      return true; // submits the payment form
    
    var value = $('#transaction_payment_new_value').val().trim();
    if ( value === '' )
      value = LI.parseFloat($('#li_transaction_field_payments_list tfoot .change .sf_admin_list_td_list_value.pit').html());

    var amount = getCentsAmount(value);
    if ( isNaN(amount) || amount <= 0 ) {
      alert('Wrong amount'); // TODO: translate this
      return false;
    }

    var transaction_id = $("#transaction_close_id").val().trim();
    if ( ! transaction_id ) {
      alert('Transaction ID not found'); // TODO: translate this
      return false;
    }

    // replace new payment form by EPT transaction message
    toggleEPTtransaction();
    
    // Find out if we need to wait for the EPT transaction end
    var wait = ( LI.ept_wait_transaction_end !== undefined ) ? LI.ept_wait_transaction_end : false;
    // Send the amount to the EPT
    EPT.sendAmount(amount, {wait: wait}).then(function(res){
      connector.log('info', res);

      var errorMessage = $('.js-i18n[data-source="ept_failure"]').data('target');      

      if ( res.status === 'accepted' || res.status === 'handled')
        $('#li_transaction_field_payment_new form').submit();
        // TODO: check integrity (pos, amount, currency) and read priv and rep fields
      else {
        alert(errorMessage);
      }

      toggleEPTtransaction();      
    }).catch(function(err){
      connector.log('error', err);
      alert(errorMessage);
      toggleEPTtransaction();      
    });

    // prevent new payment form submit
    return false;
  }

  function cancelEPT() {
    // replace EPT transaction message by new payment form
    toggleEPTtransaction();
  }  
  
});


