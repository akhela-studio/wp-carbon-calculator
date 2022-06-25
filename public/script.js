(function ($) {
    $(document).ready(function() {

        $('#carbon-calculate').click(function (){

            var $button = $(this);

            var data = {
                action: 'carbon_calculate',
                type: $button.data('type'),
                id: $button.data('id')
            };

            $button.addClass('is-loading');

            $.post(wp_carbon_calculator.ajax_url, data, function(response) {

                $button.removeClass('is-loading');

                var details = '';
                Object.keys(response).forEach(function (key){
                    details += '<span>'+key+' : <b>'+response[key]+'</b></span>'
                })
                $('#carbon-calculator-display').text((Math.round(response['co2PerPageview']*100)/100)+'g eq. CO²')
                $('#carbon-calculator-details').html('<span>'+details+'</span>')

                var color_code = 'orange'
                if( response['co2PerPageview']*2 > parseFloat(wp_carbon_calculator.reference) )
                    color_code = 'red'
                else if( response['co2PerPageview']/2 < parseFloat(wp_carbon_calculator.reference) )
                    color_code = 'green'

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
