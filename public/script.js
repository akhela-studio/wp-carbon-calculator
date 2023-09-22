(function ($) {
    $(document).ready(function() {

        function updateComputation(response, $parent){

            var $details = $parent.find('.carbon-calculator-details');

            if( $details.length ){

                var details = '';

                Object.keys(response).forEach(function (key){
                    if( key !== 'co2PerPageview' )
                        details += '<span>'+key+' : <b>'+response[key]+'</b></span>'
                })

                $parent.find('.carbon-calculator-details').html('<span>'+details+'</span>')
            }

            if( !response['co2PerPageview'] )
                $parent.find('.carbon-calculator-progressinfo').text(wp_carbon_calculator.reference+' g eq. CO²')
            else
                $parent.find('.carbon-calculator-progressinfo').text((Math.round(response['co2PerPageview']*100)/100)+' / '+wp_carbon_calculator.reference+' g eq. CO²')

            $parent.find('.carbon-calculator-display').text((Math.round(response['co2PerPageview']*100)/100)+'g eq. CO²')
            $parent.find('.carbon-calculator-progress').width((response['co2PerPageview']/wp_carbon_calculator.reference*100)+'%')

            $parent.removeClass('carbon-calculator--grey')
                .removeClass('carbon-calculator--orange')
                .removeClass('carbon-calculator--green')
                .removeClass('carbon-calculator--red')
                .addClass('carbon-calculator--'+response['colorCode'])
        }

        function doRequest($button, method){

            var $parent = $button.parent('.carbon-calculator')

            var data = {
                action: method,
                type: $button.data('type'),
                id: $button.data('id')
            };

            $button.addClass('is-busy').attr('disabled', true);

            $.post(wp_carbon_calculator.ajax_url, data, function(response) {

                $button.removeClass('is-busy').attr('disabled', false);

                updateComputation(response, $parent)

            }).fail(function(xhr, status, error) {

                if( xhr.responseJSON.in_progress ){

                    setTimeout(function (){
                        doRequest($button, 'get_calculated_carbon')
                    }, 2000)
                }
                else{

                    $button.removeClass('is-busy').attr('disabled', false);
                    alert(xhr.responseJSON.error)
                }
            });
        }

        $('.carbon-calculate-estimate').click(function (){

            doRequest( $(this), 'carbon_calculate' );
        })

        $('.carbon-calculator-display').click(function (){

            var $parent = $(this).parent('.carbon-calculator')
            $(this).toggleClass('is-active');
            $parent.find('.carbon-calculator-details').slideToggle()
        })

        $('.carbon-calculator-complete').click(function (){

            var $button = $(this)
            var $estimation = $button.prev()

            var ids = $button.data('ids').toString().split(',')

            var completed = parseInt($button.data('completed'))
            var total = parseInt($button.data('total'))
            var current_index = 0

            $button.addClass('is-loading');
            function completeComputation(){

                var data = {
                    action: 'carbon_calculate',
                    type: $button.data('type'),
                    id: ids[current_index]
                };

                if( current_index >= ids.length ){

                    $button.hide()
                    return;
                }

                $.post(wp_carbon_calculator.ajax_url, data, function(response) {

                    current_index++
                    completed++;
                    $estimation.html(Math.round(completed/total*100)+'%')
                    completeComputation()

                }).fail(function(xhr, status, error) {

                    current_index++
                    completeComputation()
                });
            }

            completeComputation()
        })

        $('.carbon-calculator-reset').click(function (){

            var $button = $(this)
            $button.addClass('is-loading');

            var data = {
                action: 'reset_carbon_calculation',
                type: $button.data('type'),
                id: $button.data('id')
            };

            $.post(wp_carbon_calculator.ajax_url, data, function(response) {

                document.location.reload()
            });

        })

        if( wp && wp.data ){

            var currentPostLastRevisionId = null;

            wp.data.subscribe(() => {

                if( !wp.data.select('core/editor') )
                    return;

                var currentPostRevisionId = wp.data.select('core/editor').getCurrentPostLastRevisionId()

                if (currentPostLastRevisionId !== currentPostRevisionId )
                    doRequest( $('.carbon-calculate'), 'get_calculated_carbon' );

                currentPostLastRevisionId = currentPostRevisionId
            });
        }
    });
})(jQuery);
