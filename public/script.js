(function ($) {
    $(document).ready(function() {

        $('#carbon-calculate').click(function (){

            var $button = $(this);
            var $details = $('#carbon-calculator-details');

            var data = {
                action: 'carbon_calculate',
                type: $button.data('type'),
                id: $button.data('id')
            };

            $button.addClass('is-loading');

            $.post(wp_carbon_calculator.ajax_url, data, function(response) {

                $button.removeClass('is-loading');

                if( $details.length ){

                    var details = '';

                    Object.keys(response).forEach(function (key){
                        if( key !== 'co2PerPageview' )
                            details += '<span>'+key+' : <b>'+response[key]+'</b></span>'
                    })

                    $('#carbon-calculator-details').html('<span>'+details+'</span>')
                }

                $('#carbon-calculator-display').text((Math.round(response['co2PerPageview']*100)/100)+'g eq. CO²')

                var color_code = 'orange'
                if( parseFloat(response['co2PerPageview']) <= parseFloat(wp_carbon_calculator.reference)/2 )
                    color_code = 'green'
                else if( parseFloat(response['co2PerPageview']) >= parseFloat(wp_carbon_calculator.reference)*2 )
                    color_code = 'red'

                $('#carbon-calculator').removeClass('carbon-calculator--grey')
                    .removeClass('carbon-calculator--orange')
                    .removeClass('carbon-calculator--green')
                    .removeClass('carbon-calculator--red')
                    .addClass('carbon-calculator--'+color_code)
            });
        })

        $('#carbon-calculator-display').click(function (){

            $(this).toggleClass('is-active');
            $('#carbon-calculator-details').slideToggle()
        })

    });
})(jQuery);
