(function($){

	var settings_panel = $('#glottybot-settings-panel');
	
	$(document).on('change','.glottybot-select-language',function(event) {
		var countries = $(this).find(':selected').data('countries').split(' '),
			opts = $('.glottybot-select-country option').prop('disabled',true),
			ctr_sel = [];
		if ( countries.length ) {
			for ( var i=0;i<countries.length;i++ )
				ctr_sel.push('.glottybot-select-country [value="'+countries[i]+'"]');
			ctr_sel.push('.glottybot-select-country :not([value])');
			
			$( ctr_sel.join(',') ).prop('disabled',false);
			
			$('.glottybot-select-country' ).val( countries.length ? countries[0] : $('.glottybot-select-country option:first' ).val() );
		}
		$('#add_language_button').prop('disabled',!$(this).val());
	});

	$(document).on( 'click' , '.translation-item .remove' , function(e){
		$(this).closest('.translation-item').remove();
		e.preventDefault();
		return false;
	});
	
	$(document).on( 'click' , '#add_language_button' , function(e){
		e.preventDefault();
		var fill = {
			'language_code' : $('.glottybot-select-language').val(),
			'country_code'  : $('.glottybot-select-country').val(),
			'language_name' : $('.glottybot-select-language :selected').text(),
			'country_name'  : $('.glottybot-select-country').val() ? $('.glottybot-select-country :selected').text() : '',
			'country_attr'  : '',
			'checked'  : '',
			'locale'        : false
		};
		fill.locale = fill.language_code;
		if ( fill.country_name && !!fill.country_code ) {
			fill.country_name = '('+fill.country_name+')';
			fill.locale += '_'+fill.country_code;
			fill.country_attr = 'data-country="'+fill.country_code+'"'
		}
		fill.slug = fill.locale;
		
		var html = $('#translation-item-template').html();
		for ( var s in fill )
			html = html.split( '%'+s+'%' ).join( fill[s] );
		console.log(html);
		
		$('#glottybot-translations').append(html);
		$('.glottybot-select-country' ).val('');
		$('.glottybot-select-language' ).val('');
		return false;
	} );
	$(document).ready(function(){
	$(".wp-list-table tbody").sortable({
		items: '> tr',
		cursor: 'move',
		axis: 'y',
		containment: 'table.widefat',
		cancel:	'input',
		distance: 2,
		opacity: .8,
		tolerance: 'pointer',
		start: function(e, ui){
			if ( typeof(inlineEditPost) !== 'undefined' ) {
				inlineEditPost.revert();
			}
			ui.placeholder.height(ui.item.height());
		},
		helper: function(e, ui) {
			var children = ui.children();
			for ( var i=0; i<children.length; i++ ) {
				var selector = jQuery(children[i]);
				selector.width( selector.width() );
			};
			return ui;
		},
		stop: function(e, ui) {
			// remove fixed widths
			ui.item.children().css('width','');
		}});
	});
})(jQuery);