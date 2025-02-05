(function ($) {
    $(document).ready(function() {

        function updateComputation(response, $parent){

            var $details = $parent.find('.carbon-calculator-details');

            if( $details.length ){

                var details = '';

				Object.keys(response['details']).forEach(function (stategy){
                    details += '<div class="carbon-calculator-strategy">'
                    details += '<h3>'+stategy+'</h3>'
                    details += '<div class="carbon-calculator-strategy-details">'
                    Object.keys(response['details'][stategy]).forEach(function (key){
                        if( key === 'co2PerPageview' || key === 'performanceScore')
                            details += '<span>'+key+'<b>'+(Math.round(response['details'][stategy][key]*100)/100)+'</b></span>'
                        else
                            details += '<span>'+key+'<b>'+response['details'][stategy][key]+'</b></span>'
                    })
                    details += '</div></div>'
                })

                $parent.find('.carbon-calculator-details').html(details)
            }

            if( !response['co2PerPageview'] )
                $parent.find('.carbon-calculator-progressinfo').text(website_carbon_calculator.reference+' g eq. CO²')
            else
                $parent.find('.carbon-calculator-progressinfo').text((Math.round(response['co2PerPageview']*100)/100)+' / '+website_carbon_calculator.reference+' g eq. CO²')

			if( !response['co2PerPageview'] ){

				$parent.find('.carbon-calculator-data--carbon b').text('Not available')
				$parent.find('.carbon-calculator-data--performance b').text('Not available')
			}
			else{

				var desktop_score = typeof response['details']['desktop']['performanceScore'] == 'undefined' ? response['performanceScore'] : response['details']['desktop']['performanceScore'];
				var $desktop = $parent.find('.carbon-calculator-data--desktop');

				if( $desktop.length ){

					$desktop.find('.wpcc-performance').removeClass('wpcc-performance--grey')
						.removeClass('wpcc-performance--orange')
						.removeClass('wpcc-performance--green')
						.removeClass('wpcc-performance--red')
						.addClass('wpcc-performance--'+(desktop_score < 0.5 ? "red" : ( desktop_score > 0.9 ? 'green': 'orange')))
						.css('--progress', desktop_score)

					$desktop.find('b').text(Math.round(desktop_score*100))
				}

				var mobile_score = typeof response['details']['mobile']['performanceScore'] == 'undefined' ? response['performanceScore'] : response['details']['mobile']['performanceScore'];
				var $mobile = $parent.find('.carbon-calculator-data--mobile')

				if( $mobile.length ){

					$mobile.find('.wpcc-performance').removeClass('wpcc-performance--grey')
						.removeClass('wpcc-performance--orange')
						.removeClass('wpcc-performance--green')
						.removeClass('wpcc-performance--red')
						.addClass('wpcc-performance--'+(mobile_score < 0.5 ? "red" : ( mobile_score > 0.9 ? 'green': 'orange')))
						.css('--progress', mobile_score)

					$mobile.find('b').text(Math.round(mobile_score*100))
				}

				$parent.find('.carbon-calculator-data--carbon .wpcc-badge').removeClass('wpcc-badge--grey')
					.removeClass('wpcc-badge--orange')
					.removeClass('wpcc-badge--green')
					.removeClass('wpcc-badge--red')
					.addClass('wpcc-badge--'+response['colorCode'])

				$parent.find('.carbon-calculator-data--carbon b').text((Math.round(response['co2PerPageview']*100)/100)+'g eq. CO²')
			}

            $parent.find('.carbon-calculator-progress').width((response['co2PerPageview']/website_carbon_calculator.reference*100)+'%')

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
                nonce: $button.data('nonce'),
                id: $button.data('id')
            };

            $button.addClass('is-busy').attr('disabled', true);

            $.post(website_carbon_calculator.ajax_url, data, function(response) {

                $button.removeClass('is-busy').attr('disabled', false);

                updateComputation(response, $parent)

            }).fail(function(xhr, status, error) {

                if( typeof xhr.responseJSON != 'undefined' ){

                    if( xhr.responseJSON.in_progress ){

                        setTimeout(function (){
                            doRequest($button, 'get_calculated_carbon')
                        }, 2000)
                    }
                    else{

                        $button.removeClass('is-busy').attr('disabled', false);
                        alert(xhr.responseJSON.error)
                    }
                }
                else{

                    alert(xhr.responseText)
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
                    nonce: $button.data('nonce'),
                    id: ids[current_index]
                };

                if( current_index >= ids.length ){

                    $button.hide()
                    return;
                }

                $.post(website_carbon_calculator.ajax_url, data, function(response) {

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
                nonce: $button.data('nonce'),
                id: $button.data('id')
            };

            $.post(website_carbon_calculator.ajax_url, data, function(response) {

                document.location.reload()
            });

        })

        if( wp && wp.data ){

            var currentPostLastRevisionId = null;

            wp.data.subscribe(() => {

                if( !wp.data.select('core/editor') || !$('#wpcc_calculator').length )
                    return;

                var currentPostRevisionId = wp.data.select('core/editor').getCurrentPostLastRevisionId()

                if (currentPostLastRevisionId !== currentPostRevisionId )
                    doRequest( $('.carbon-calculate'), 'get_calculated_carbon' );

                currentPostLastRevisionId = currentPostRevisionId
            });
        }
    });
})(jQuery);
