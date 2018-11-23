(function($){

	$(document)
		.on('click','[type="radio"]',function(e){
			$(this).closest('.select-window').removeClass('active');
		})
		.on('click','.select-window:not(.active)',function(e){
			$(this).addClass('active');

			e.preventDefault();
		})
		;

})(jQuery)
