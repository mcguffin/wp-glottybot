// for whatever reason no self executing anonymous function possible here
jQuery(document).ready(function($){
	$('#glottybot-add-menu-item').click( function( e ) {
		console.log('handle');
		e.preventDefault();
		var send_data = $.extend(true, {}, glottybot.ajax),
			$self, $spinner,$checked_items;
		
		send_data.languages = [];
		$checked_items = $('#glottybot-languages-menu-items li :checked').each(function(){
			send_data.languages.push( $( this ).val() );
		});
		$self = $(this);
		$spinner = $self.prop('disabled',true).next('.spinner').show();
		
		$.post(
			glottybot.ajaxurl,
			send_data,
			function( response ) {
				$( '#menu-to-edit' ).append( response );
				$spinner.hide();
				$checked_items.prop('checked',false);
				$self.prop('disabled',false);
			}
		);
	});
});

