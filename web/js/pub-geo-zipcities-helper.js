$(document).ready(function(){
  /*
  if ( $('#contact_street_name').val() === '' && $('#contact_street_number').val() === '' ) {
    LI.lockAddress(false);
  } else {
    LI.lockAddress(true);
  }
  */
  var timeouts = [];
  $('.field.postalcode input').keyup(function(e){
    if ( $(this).val().length > 2 )
    {
      // clear previous timeouts;
      while ( (timeout = timeouts.shift()) !== undefined )
        clearTimeout(timeout);
      
      var elt = this;
      $(elt).addClass('waiting-wheel');
      
      // setTimeout to avoid burst requests
      timeouts.push(setTimeout(function(){
        $.ajax({
          url: $('.field.cities select').attr('data-url'),
          method: 'get',
          data: { q: $(elt).val() },
          dataType: 'json',
          complete: function(){
            $(elt).removeClass('waiting-wheel');
          },
          success: function(json){
            if ( window.location.hash == '#debug' )
              console.error('Zip results', json);
            
            $('.field.cities select').html('');
            $.each(json, function(key, val) {
              $('.field.cities select')
                .append($('<option>'+val+'</option>').val(key))
                .find('option:first-child').attr('selected',true);
            });
            $('.field.cities select option').click(function(){
              if ($(this).val() != '...') {
                var regxp = /\[(.*?)\]\[(.*?)\]\[(.*?)\]/g;
                var match = regxp.exec($(this).val());
                
                $('.field.postalcode input').val(match[2]);
                $('.field.city input').val(match[1]);
                /*
                // has streets ?
                if ( match[3] > 0 ) {
                  LI.lockAddress(true);
                  $('.field.street_name input').focus().keypress();  
                } else {
                  LI.lockAddress(false);
                }
                */
              }
            }).parent().change();
            
            if ( LI.zipcitiesOnZipLoaded != undefined )
            $.each(LI.zipcitiesOnZipLoaded, function(i, fct){
              if ( typeof(fct) != 'function' )
                return;
              fct(json);
            });
          }
        });
      },300));
    }
  });
});

if ( LI == undefined )
  var LI = {};
if ( LI.zipcitiesOnZipLoaded == undefined )
  LI.zipcitiesOnZipLoaded = [];
  
LI.lockAddress = function(lock) {
  if ( lock ) {
    $('.field.street_name, .field.street_number').show();
    $('#contact_address').prop('readonly', true);
    $('#contact_address').addClass('lock');
  } else {
    $('.field.street_name, .field.street_number').hide();
    $('#contact_address').prop('readonly', false);
    $('#contact_address').removeClass('lock');
  }
}
