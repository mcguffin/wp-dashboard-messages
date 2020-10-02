(function($){
	var closeDashOnEsc = function(e) {
		if ( e.which === 27 ) {
			closeDash();
		}
	}
	var closeDash = function() {
		$('.dashboard-messages-icons.select-window').removeClass('active');
		$('.dashboard-messages-icons [type="search"]').val('');
		$(document).off('keyup',closeDashOnEsc)
	}
	var openDash = function() {
		$('.dashboard-messages-icons.select-window').addClass('active');
		$(document).on('keyup',closeDashOnEsc);
	}
	$(document)
		// dashicon select
		.on('click','.dashboard-messages-icons [type="radio"]',function(e){
			// hide
			closeDash();			
		})
		.on('click','.dashboard-messages-icons:not(.active)',function(e) {
			openDash();
			e.preventDefault();
		})
		.on('keyup change','.dashboard-messages-icons [type="search"]', function(e) {
			var s = $(this).val().toLowerCase();
			$('.dashboard-messages-icons .select label').each( function(i,el) {
				$(this).toggleClass( 
					'-off', 
					s !== '' && $(this).text().toLowerCase().indexOf(s) === -1 
				);
			})
		})
		.on('change','[name="_dashboard_layout"]',function(e){
			$(this).closest('[data-value]').attr( 'data-value', $('[name="_dashboard_layout"]:checked').val() )
		})
		// dismiss massage
		.on('click', '.dashboard-message-dismiss', function(e) {
			e.preventDefault();
			var id = $(this).closest('.postbox').attr('id');
			$('label[for="'+id+'-hide"]').find('[type="checkbox"]').trigger('click')
		})
		;

})(jQuery)
