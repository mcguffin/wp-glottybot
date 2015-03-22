<?php

class GlottyBotLanguageWidget extends WP_Widget {
	
	function __construct() {
		parent::__construct(
			'glottybot_language_widget',
			__( 'Language Switcher', 'wp-glottybot' ), // Name
			array( 'description' => __( 'Language switcher', 'wp-glottybot' ), )
		);	
	}
	public function widget( $args , $instance ) {
		$label_format   = ! empty( $instance['label_format'] ) ? $instance['label_format'] : __( '%language_name%', 'text_domain' );
		$switcher_style = ! empty( $instance['switcher_style'] ) ? $instance['switcher_style'] : 'list';
		switch ( $switcher_style ) {
			case 'list':
				$switcher_args = array(
					'container_format' => '<nav class="language-switcher"><ul>%items%</ul></nav>',
					'item_format' => '<li class="language-item %classnames% %active_item%"><a rel="alternate" href="%href%">'.$label_format.'</a></li>',
					'active_item' => 'active',
				);
				break;
			case 'select':
				$id = $this->get_field_id( 'switcher_style' );
				$switcher_args = array(
					'container_format' => '<select id="'. $id .'" onchange="document.location.href=this.value" name="locale" class="language-switcher">%items%</select>',
					'item_format' => '<option %active_item% value="%href%">'.$label_format.'</option>',
					'active_item' => 'selected="selected"',
				);
				break;
			
		}
		GlottyBotTemplate::print_language_switcher( $switcher_args );
	}
	public function form( $instance ) {
		$label_format   = ! empty( $instance['label_format'] ) ? $instance['label_format'] : '%locale_name%';
		$switcher_style = ! empty( $instance['switcher_style'] ) ? $instance['switcher_style'] : 'list';
		
		$locale 		= GlottyBotAdmin()->get_locale();
		$locale_object 	= GlottyBotLocales::get_language_country( $locale );
		$locale_names 	= GlottyBot()->get_locale_names( );
					// %language_name%, %language_native_name%, %language_code%, %country_name%, %country_code%, %locale%
		$locale_data = array(
			'%language_name%'			=> $locale_object->language->name ,
			'%language_native_name%'	=> $locale_object->language->native_name,
			'%language_code%'			=> $locale_object->language->code,
			'%country_name%'			=> $locale_object->country->name,
			'%country_native_name%'		=> GlottyBotLocales::get_country_native_name($locale),
			'%country_code%'			=> $locale_object->country->code,
			'%locale%'					=> $locale,
			'%locale_name%'				=> isset($locale_names[$locale]) ? $locale_names[$locale] : $locale_object->language->name,
		);
		?><div id="<?php echo $this->get_field_id( 'glottybot-switcher-widget' ); ?>"><?php
			?><div class="glottybot-switcher-widget-form"><?php
				?><div class="glottybot-switcher-label-format-buttons"><?php
					?><h3><?php _e('Style','wp-glottybot') ?></h3><?php
					// style: list, select
					?><label for="<?php echo $this->get_field_id( 'switcher_style' ); ?>-list"><?php
						?><input type="radio" <?php checked('list',$switcher_style,true) ?> <?php  ?> id="<?php echo $this->get_field_id( 'switcher_style' ); ?>-list" name="<?php echo $this->get_field_name( 'switcher_style' ); ?>" value="list" /><?php
						_e( 'List' , 'wp-glottybot' );
					?></label> <?php
				
					?><label for="<?php echo $this->get_field_id( 'switcher_style' ); ?>-select"><?php
						?><input type="radio" <?php checked('select',$switcher_style,true) ?> id="<?php echo $this->get_field_id( 'switcher_style' ); ?>-select" name="<?php echo $this->get_field_name( 'switcher_style' ); ?>" value="select" /><?php
						_e( 'Selectbox' , 'wp-glottybot' );
					?></label><?php
				
				?></div><?php
				?><div class="glottybot-switcher-label-format-buttons"><?php
					?><h3><?php _e('Label format','wp-glottybot') ?></h3><?php
					?><h4><?php _e('Insert placeholder','wp-glottybot') ?></h4><?php
					?><p><?php
						?><a class="button btngrp-left" href="#" data-value="%language_name%"><?php 
							?><strong><?php _e('Language','wp-glottybot'); ?></strong><?php
						?></a><?php
						?><a class="button btngrp-middle" href="#" data-value="%language_native_name%"><?php 
							_e('Native name','wp-glottybot');
						?></a><?php
						?><a class="button btngrp-right" href="#" data-value="%language_code%"><?php 
							_e('Code','wp-glottybot');
						?></a><?php
					
					
					?></p><?php
				
					?><p><?php
						?><a class="button btngrp-left" href="#" data-value="%country_name%"><?php 
							?><strong><?php _e('Country','wp-glottybot'); ?></strong><?php
						?></a><?php
						?><a class="button btngrp-middle" href="#" data-value="%country_native_name%"><?php 
							_e('Native name','wp-glottybot');
						?></a><?php
						?><a class="button btngrp-right" href="#" data-value="%country_code%"><?php 
							_e('Code','wp-glottybot');
						?></a><?php
					
					?></p><?php
					?><p><?php
					
						?><a class="button btngrp-left" href="#" data-value="%locale_name%"><?php 
							?><strong><?php _e('Locale','wp-glottybot'); ?></strong><?php
						?></a><?php
						?><a class="button btngrp-right" href="#" data-value="%locale%"><?php 
							_e('code','wp-glottybot');
						?></a><?php
										
						?><a class="button btngrp-left" href="#" data-value="<?php esc_attr_e( GlottyBotTemplate::i18n_item( array('%language_code%','%country_code%') , true , true ) ) ?>"><?php 
							echo GlottyBotTemplate::i18n_item( $locale , true , true );
						?></a><?php
						?><a class="button btngrp-middle" href="#" data-value="<?php esc_attr_e( GlottyBotTemplate::i18n_item( array('%language_code%','%country_code%') , true , false ) ) ?>"><?php 
							echo GlottyBotTemplate::i18n_item( $locale , true , false );
						?></a><?php
						?><a class="button btngrp-right" href="#" data-value="<?php esc_attr_e( GlottyBotTemplate::i18n_item( array('%language_code%','%country_code%') , false , true ) ) ?>"><?php 
							echo GlottyBotTemplate::i18n_item( $locale , false , true );
						?></a><?php
					?></p><?php
				
				?></div><?php
				?><div class="glottybot-switcher-label-format"><?php
					?><textarea class="widefat glottybot-switcher-format" id="<?php echo $this->get_field_id( 'label_format' ); ?>" name="<?php echo $this->get_field_name( 'label_format' ); ?>"><?php echo esc_attr( $label_format ); ?></textarea><?php
					?><script type="text/javascript">
	// 				if ( 'undefined' == (typeof glottybot_widget_edit_inited) ) {
						console.log(typeof glottybot_widget_edit_inited);
						glottybot_widget_edit_inited = true;
						(function($){
							$('#<?php echo $this->get_field_id( 'glottybot-switcher-widget' ); ?>').on( 'click' , '.glottybot-switcher-label-format-buttons .button' , function(e){
								var $inp = $('#<?php echo $this->get_field_id( 'label_format' ); ?>')
									.replaceSelectedText($(this).data('value')).trigger('change');
								e.preventDefault();
								// set result
							} ).on('keyup change','#<?php echo $this->get_field_id( 'label_format' ); ?>',function() {
								var s, sample = $('#<?php echo $this->get_field_id( 'label_format' ); ?>').val(),
									$sample = $('#<?php echo $this->get_field_id( 'label_format' ); ?>-sample'),
									fill = <?php echo json_encode($locale_data); ?>;
								for ( s in fill )
									sample = sample.split(s).join(fill[s])
								$sample.html(sample);
							}).on('keyup focus blur change','#<?php echo $this->get_field_id( 'label_format' ); ?>',function(){
								$(this).height(1);
								$(this).height( $(this)[0].scrollHeight - parseInt($(this).css('padding-top')) - parseInt($(this).css('padding-bottom')) );
							});
		// 					$('#<?php echo $this->get_field_id( 'label_format' ); ?>').trigger('change');
						})(jQuery);
	// 				}
					</script><?php
				?></div><?php
				?><div title="<?php _e('Result:','wp-glottybot') ?>" class="switcher-format-sample" id="<?php echo $this->get_field_id( 'label_format' ); ?>-sample"><?php 
					$sample = $label_format;
					echo strtr( $sample , $locale_data );
				?></div><?php
			?></div><?php
		?></div><?php
		
	}
	public function update( $new_instance, $old_instance ) {
		$allowed_html = wp_kses_allowed_html( 'data' );
		if ( ! isset($allowed_html['span']) )
			$allowed_html['span'] = array();
		$allowed_html['span']['class'] = array();
		$allowed_html['span']['data-country'] = array();
		$allowed_html['span']['data-language'] = array();
		$allowed_html['span']['id'] = array();
		$instance = wp_parse_args( $new_instance , array(
			'label_format' => '%locale_name%',
			'switcher_style' => 'list',
		) );
		$instance['label_format'] = wp_kses( $new_instance['label_format'] , $allowed_html );
		$instance['switcher_style'] = $new_instance['switcher_style'] == 'select' ? 'select' : 'list';
		
		return $instance;
	}
	
}


