(function($){
	var settings_panel = $('#glottybot-settings-panel'),
		l10n = glottybot_settings.l10n;
	
	function the_locale() {
		var country = $('.glottybot-country-code-input').val(),
			locale = $('.glottybot-language-code-input').val();
			
		if ( !! locale && !!country )
			locale += '_'+country;
		return locale;
	}
	
	$(document).on('change','.glottybot-select-language',function(event) {
		var countries = $(this).find(':selected').data('countries').split(' '),
			opts = $('.glottybot-select-country option').prop('disabled',true),
			ctr_sel = [], $ctr_sel = $('.glottybot-select-country' );
		countries.push('');
		if ( countries.length ) {
			for ( var i=0;i<countries.length;i++ )
				ctr_sel.push('.glottybot-select-country [value="'+countries[i]+'"]');
			ctr_sel.push('.glottybot-select-country :not([value])');
			
			$( ctr_sel.join(',') ).prop('disabled',false);
			
			$ctr_sel.val( countries.length ? countries[0] : $('.glottybot-select-country option:first' ).val() ).trigger('mouseup');
		}
		$ctr_sel.trigger("chosen:updated");
	}).on('change keyup focus blur','.glottybot-select-language,.glottybot-select-country,.glottybot-country-code-input,.glottybot-country-language-input',function(event){
		var locale = the_locale(),
			can_add = !!locale && ! $( '#translation-item-' + locale ).length;
		$('#add_language_button').prop( 'disabled' , ! can_add );
	}).on('keyup mouseup blur focus','[data-sync-value]',function( ) {
		$( $(this).data('sync-value') ).val( $(this).val() ).trigger("chosen:updated");
	});
	

	$(document).on( 'click' , '.translation-item .remove' , function(e){
		$(this).closest('.translation-item').remove();
		e.preventDefault();
		return false;
	});
	
	$(document).on( 'click' , '#add_language_button' , function(e){
		e.preventDefault();
		var language_code = $('.glottybot-language-code-input').val(),
			country_code = $('.glottybot-country-code-input').val(),
			locale = the_locale();
		var fill = {
			'language_code' : language_code,
			'country_code'  : country_code,
			'language_name' : $('.glottybot-select-language option[value="'+language_code+'"]').text() || language_code,
			'country_name'  : $('.glottybot-select-country option[value="'+country_code+'"]').text() || country_code,// $('.glottybot-select-country').val() ? $('.glottybot-select-country :selected').text() : '',
			'country_attr'  : '',
			'checked'       : '',
			'name'          : $('.glottybot-select-language [value!=""]:selected').text() || locale,
			'locale'        : locale
		};
		if ( !!fill.country_name && !!fill.country_code ) {
			fill.country_name = '('+fill.country_name+')';
			fill.country_attr = 'data-country="'+fill.country_code+'"';
			fill.locale_name += fill.country_name;
		}
		fill.slug = fill.locale;
		
		var html = $('#translation-item-template').html();
		for ( var s in fill )
			html = html.split( '%'+s+'%' ).join( fill[s] );
		
		$('#glottybot-translations').append(html);
		$('.glottybot-select-country' ).val('');
		$('.glottybot-select-language' ).val('');
		return false;
	} );
	$(document).ready(function(){
		$(".glottybot-select-locale select").chosen({
			'allow_single_deselect' : true,
			'no_results_text' : l10n.no_results_text,
			'display_disabled_options':false
		});
	
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
			}
		});
	});
	
})(jQuery);