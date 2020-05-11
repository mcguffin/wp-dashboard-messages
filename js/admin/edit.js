(function($){

	$(document)
		.on('click','[type="radio"]',function(e){
			$(this).closest('.select-window').removeClass('active');
		})
		.on('click','.select-window:not(.active)',function(e) {
			$(this).addClass('active');

			e.preventDefault();
		})
		.on('click', '.dashboard-message-dismiss', function(e) {
			e.preventDefault();
			var id = $(this).closest('.postbox').attr('id');
			$('label[for="'+id+'-hide"]').find('[type="checkbox"]').trigger('click')
		})
		;

})(jQuery)
