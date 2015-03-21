(function($,exports){
	$(document).on('click','button.copy-post',function(e){
		var $self = $(this);
		e.preventDefault();
		exports.glottybot.clone_post.apply( this , [ exports.glottybot.clone_post_replace_trigger_element ] );
		$self.prev('.spinner').show();
		return false;
	});
	exports.glottybot = {};
	exports.glottybot.clone_post = function( complete ) {
		var self = this;
		$.post(ajaxurl,{
			'action'        : $(this).data('ajax-action'),
			'_wpnonce'      : $(this).data('ajax-nonce'),
			'post_id'       : $(this).data('post-id'),
			'post_locale' : $(this).data('post-locale')
		}, function(response) { if ( !! complete.apply ) complete.apply( self , [response] ) } );
		return false;
	}
	exports.glottybot.clone_post_replace_trigger_element = function( response ) {
		var $self = $(this);
		$self.prev('.spinner').hide();
		if ( response.success )
			$self.closest('td').html( response.post_edit_link );
		else if ( response.message )
			$self.after( '<span class="error">'+response.message+'</span>' );
	}
	exports.glottybot.clone_post_redirect = function( response ) {
		if ( response.success ) {
			document.location.href= response.post_edit_uri;
		}
		
	}
// 	
// 	// we do this later
// 	$(document)
// 		.on('dragover','.ui-droptarget',function(event){
// 			event.preventDefault();
// 		})
// 		.on('dragenter','.ui-droptarget',function(event){
// 			event.preventDefault();
// 			$(this).addClass('active');
// 		})
// 		.on('dragleave','.ui-droptarget',function(event){
// 			$(this).removeClass('active');
// 		})
// 		.on('drop','.ui-droptarget',function(event){
// 			console.log(event);
// 			event.preventDefault();
// 			var $self = $(this),
// 				edit_post         = event.originalEvent.dataTransfer.getData('post-id'),
// 				target_post	      = $self.data('post-id'),
// 				ajax_nonce        = event.originalEvent.dataTransfer.getData('ajax-nonce'),
// 				target_locale     = $self.data('post-locale'),
// 				ajax_data = {
// 					'action' : 'set_post_locale',
// 					'post_id' : edit_post,
// 					'locale' : target_locale,
// 					'target_post_id' : target_post,
// 					'_wpnonce' : ajax_nonce
// 				};
// 			
// 			$.post(ajaxurl, ajax_data, function(response) {
// 				if ( response.success ) {
// 					// replace drop target
// 					$self.replaceWith( response.translationButtonHtml );
// 					
// 					if ( target_post != target_post )
// 						$('tr#post-'+edit_post).fadeOut(function(){$(this).remove();});
// 				}
// 			});
// 		
// 		
// 		})
// 		.on('dragstart','.ui-draggable',function(event){
// 			event.originalEvent.dataTransfer.setData( 'post-id' , $(this).data('post-id') );//preventDefault();
// 			event.originalEvent.dataTransfer.setData( 'ajax-nonce' , $(this).data('ajax-nonce') );//preventDefault();
// 		});
	
})(jQuery,document);