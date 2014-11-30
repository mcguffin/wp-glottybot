(function($,exports){
	$(document).on('click','button.copy-post',function(e){
		var $self = $(this);
		e.preventDefault();
		exports.postbabel.clone_post.apply( this , [
			$self.data('post-id') , 
			$self.data('post-language'), 
			$self.data('ajax-nonce'),
			exports.postbabel.clone_post_replace_trigger_element ]
		);
		$self.prev('.spinner').show();
		return false;
	});
	
	exports.postbabel = {};
	exports.postbabel.clone_post = function( post_id , language , nonce , complete ) {
		var self = this;
		$.post(ajaxurl,{
			'action':'postbabel_copy_post',
			'post_id':post_id,
			'post_language' : language,
			'ajax_nonce': nonce
		}, function(response) { if ( !! complete.apply ) complete.apply( self , [response] ) } );
		return false;
	}
	exports.postbabel.clone_post_replace_trigger_element = function( response ) {
		var $self = $(this);
		$self.prev('.spinner').hide();
		if ( response.success )
			$self.replaceWith( response.post_edit_link );
		else if ( response.message )
			$self.after( '<span class="error">'+response.message+'</span>' );
	}
	exports.postbabel.clone_post_redirect = function( response ) {
		if ( response.success ) {
			document.location.href= response.post_edit_uri;
		}
		
	}
})(jQuery,document);