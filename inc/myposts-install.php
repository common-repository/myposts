<?php

class Myposts_Install {

    private $current_db_version = 10;

    function __construct() {
        register_activation_hook( __FILE__, array( &$this, 'install_plugin' ) );
        register_uninstall_hook( __FILE__, 'uninstall_plugin' );

        $this->votes_db_version = get_option( 'votes_db_version' );

        if ( ! $this->votes_db_version ) {
            $this->install_plugin();
        }

        if( $this->votes_db_version && $this->votes_db_version < $this->current_db_version ) {
            $this->upgrade_plugin();
        }
    }

    function install_plugin() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $sql = "CREATE TABLE {$wpdb->prefix}votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            tag varchar(255) DEFAULT '',
            UNIQUE KEY id (id),
            INDEX post_id_i (post_id),
            INDEX user_id_i (user_id)
        ) $charset_collate;";

        dbDelta( $sql );
        add_option( 'votes_db_version', 10 );
    }

    function uninstall_plugin() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}votes");
        remove_option('votes_db_version');
    }

//  function upgrade_plugin() {
//        global $wpdb;
//        $wpdb->query("ALTER TABLE {$wpdb->prefix}votes ADD testing VARCHAR(20) AFTER post_id");
//        update_option('votes_db_version', 10);
// }
}

new Myposts_Install();
