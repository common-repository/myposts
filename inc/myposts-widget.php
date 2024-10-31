<?php

class myposts_widget extends WP_Widget {

	function __construct() {
		parent::__construct( 'myposts_widget', __('Myposts', 'myposts') );
	}

	function form($instance) {

        // Check values
        if( $instance) {
             $title = esc_attr($instance['title']);
             $text = esc_attr($instance['text']);
             $textarea = esc_textarea($instance['textarea']);
        } else {
             $title = '';
             $text = '';
             $textarea = '';
        }
        ?>

        <p>
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
    <?php
	}

	function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags( $new_instance['title'] );
        return $instance;
	}

	function widget( $args, $instance ) {
        extract( $args );
        // these are the widget options
        $title = apply_filters( 'widget_title', $instance['title'] );
        $text = $instance['text'];
        $textarea = $instance['textarea'];
        echo $before_widget;
        echo '<div class="widget-text wp_widget_plugin_box">';

        if ( $title ) {
          echo $before_title . $title . $after_title;
        }

        do_action('myposts_view_links');

        echo '</div>';
        echo $after_widget;
	}
}
