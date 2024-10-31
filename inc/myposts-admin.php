<?php

/**
 * Post types: checkbox list
 * Downvote: checkbox
 * Position: before title, after title, before content, after content
 */

class Myposts_Admin {

    protected $options;

    function __construct() {
        add_action( 'admin_menu', array( &$this, 'options_page' ) );
        add_action( 'admin_init', array( &$this, 'settings_init' ) );
    }

    function options_page() {
        add_submenu_page( 'tools.php', __('MyPosts', 'myposts'), __('MyPosts Options', 'myposts'), 'manage_options', 'myposts', array( &$this, 'options_page_html' ) );
    }

    function options_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error( 'myposts_messages', 'myposts_message', __('Settings Saved', 'myposts'), 'updated' );
        }

        settings_errors('myposts_messages');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'myposts' );
                do_settings_sections( 'myposts' );
                submit_button( __( 'Save Settings' ) );
                ?>
            </form>
        </div>
        <?php
    }

    function settings_init() {

        $this->options = get_option('myposts_options');

        if( ! isset( $this->options['post_types'] ) ) {
            $this->options['post_types'] = array();
        }
        if( ! isset( $this->options['date_intervals'] ) ) {
            $this->options['date_intervals'] = array();
        }
        if( ! isset( $this->options['date_interval_visible'] ) ) {
            $this->options['date_interval_visible'] = 0;
        }
        
        register_setting('myposts', 'myposts_options');

        add_settings_section(
            'myposts_main',
            '',
            array( &$this, 'section_main' ),
            'myposts'
        );

        add_settings_field(
            'field_post_types',
            __( 'Post types', 'myposts' ),
            array( &$this, 'field_post_types' ),
            'myposts',
            'myposts_main'
        );

        add_settings_field(
            'field_date_interval_visible',
            __('Date range buttons before posts', 'myposts'),
            array( &$this, 'field_date_interval_visible' ),
            'myposts',
            'myposts_main'
        );

        add_settings_field(
            'field_position',
            __('Voting position', 'myposts'),
            array( &$this, 'field_vote_position' ),
            'myposts',
            'myposts_main'
        );

        add_settings_field(
            'field_date_intervals',
            __('Available views', 'myposts'),
            array( &$this, 'field_date_intervals' ),
            'myposts',
            'myposts_main'
        );
    }

    function section_main() {
        ?>
        <p><?php esc_html__('', 'myposts'); ?></p>
        <?php
    }

    function field_post_types() {
        foreach( get_post_types( array( 'public' => true ), 'object') as $post_type ) :
        ?>
        <div>
            <input type="checkbox" name="myposts_options[post_types][]" value="<?php esc_attr_e( $post_type->name ) ?>" <?php $this->checked( $this->options['post_types'], $post_type->name ) ?>>
            <?php esc_html_e( $post_type->label ); ?>
        </div>
        <?php endforeach; ?>
        <p class="description">
            <?php esc_html_e( 'Select post types to enable voting', 'myposts' ); ?>
        </p>
        <?php
    }

    function field_date_interval_visible() {
        ?>
        <input type="checkbox" name="myposts_options[date_interval_visible]" value="1" <?php checked( $this->options['date_interval_visible'], 1 ) ?>>
        <?php
    }

    function field_vote_position() {
        ?>
        <select name="myposts_options[position]">
            <option <?php selected( $this->options['position'], 'before_content' ) ?> value="before_content"><?php _e( 'Before content', 'myposts' ) ?></option>
            <option <?php selected( $this->options['position'], 'after_content' ) ?> value="after_content"><?php _e( 'After content', 'myposts' ) ?></option>
        </select>
        <?php
    }

    function field_date_intervals() {
        ?>
        <div>
            <input type="checkbox" name="myposts_options[date_intervals][]" value="today" <?php $this->checked( $this->options['date_intervals'], 'today' ) ?>>
            <?php _e( 'Today', 'myposts' ); ?>
        </div>
        <div>
            <input type="checkbox" name="myposts_options[date_intervals][]" value="thisweek" <?php $this->checked( $this->options['date_intervals'], 'thisweek' ) ?>>
            <?php _e( 'This week', 'myposts' ); ?>
        </div>
        <div>
            <input type="checkbox" name="myposts_options[date_intervals][]" value="thismonth" <?php $this->checked( $this->options['date_intervals'], 'thismonth' ) ?>>
            <?php _e( 'This month', 'myposts' ); ?>
        </div>
        <div>
            <input type="checkbox" name="myposts_options[date_intervals][]" value="alltime" <?php $this->checked( $this->options['date_intervals'], 'alltime' ) ?>>
            <?php _e( 'All time', 'myposts' ); ?>
        </div>
        <p class="description">
            <?php esc_html_e( 'Set the available date intervals for top posts', 'myposts' ); ?>
        </p>
        <?php
    }

    function checked( $value1, $value2 ) {
        if( is_array( $value1 ) && in_array( $value2, $value1) || $value1 == $value2 ) {
            echo 'checked="checked"';
        }
    }

}

new Myposts_Admin();
