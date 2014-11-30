(function($){
	var settings_panel = $('#post_babel-settings-panel');
	console.log('hereiam');
	
	$(document).on( 'click' , '.language-item .remove' , function(e){
		var rm_lang = $(this).closest('.language-item').find('input[type="hidden"]').val();
		
		$(this).closest('.language-item').remove();
		$('#add_language option').filter("[value='"+rm_lang+"']").removeAttr('disabled');
		e.preventDefault();
		return false;
	});
	
	$(document).on( 'click' , '#add_language_button' , function(e){
		var add_lang = $('#add_language').val();
		$('#add_language option').filter("[value='"+add_lang+"']").attr('disabled','disabled');
		e.preventDefault();
		var lang = post_babel_settings.available_translations[add_lang];
		var template = $('#language-item-template').html();
		
		var fill = {
			language_code : add_lang,
			english_name : lang.english_name,
			native_name : lang.native_name
		};
		
		var html = template;
		for ( var s in fill )
			html = html.replace( '%'+s+'%' , fill[s] );
		
		$('#additional-languages').append(html);
		return false;
	} );
})(jQuery);