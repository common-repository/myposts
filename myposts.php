<?php

/*
Plugin Name: MyPosts
Plugin URI: https://wordpress.org/plugins/myposts/
Description: News aggregation and content ranking
Author: Zoltan Varkonyi
Version: 1.1
*/

class Myposts {

	protected $plugin_dir;
	protected $options;

	function __construct() {
		$this->plugin_dir = plugin_dir_path( __FILE__ );

		include( $this->plugin_dir . 'inc/myposts-install.php' );
		include( $this->plugin_dir . 'inc/myposts-post.php' );
		include( $this->plugin_dir . 'inc/myposts-admin.php' );
		include( $this->plugin_dir . 'inc/myposts-widget.php' );

		// Filters
		add_filter( 'posts_orderby', array( &$this, 'order_by_view' ) );
		add_filter( 'the_content', array( &$this, 'add_voting_buttons_to_content' ) );
		add_filter( 'the_excerpt', array( &$this, 'add_voting_buttons_to_content' ) );
		add_filter( 'the_content', array( &$this, 'add_link_to_content' ) );

		add_filter( 'posts_fields', array( &$this, 'select' ) );
		add_filter( 'the_posts', array( &$this, 'get_posts_votes' ) );
		add_filter( 'query_vars', array( &$this, 'set_query_vars' ) );
		add_filter( 'pre_get_posts', array( &$this, 'posts_date_interval' ) );

		// Actions
		add_action( 'init', array( &$this, 'init_scripts' ) );
		add_action( 'init', array( &$this, 'load_options' ) );
		add_action( 'plugins_loaded', array( &$this, 'load_translation' ) );
		add_action( 'loop_start', array( &$this, 'get_view_links' ) );
		add_action( 'wp_ajax_myposts_vote', array( &$this, 'ajax_vote' ) );
		add_action( 'before_delete_post', array( &$this, 'delete_vote' ) );
		add_action( 'myposts_view_links', array( &$this, 'get_view_links' ) );

		// Widget
		add_action( 'widgets_init', array( &$this, 'myposts_widget' ) );
	}

	/**
	 * Load the widget class
	 */
	function myposts_widget() {
		register_widget( "myposts_widget" );
	}

	/**
	 * Load plugin textdomain.
	 *
	 */
	function load_translation() {
		load_plugin_textdomain( 'myposts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Set the date interval for the posts
	 *
	 * @param  object $query
	 *
	 * @return void
	 */
	function posts_date_interval( $query ) {
		$view = get_query_var( 'view' );

		if ( ! $query->is_single() && $view !== '' ) {
			switch ( $view ) {
				case 'today' :
					$query->set( 'date_query', array( 'after' => 'today' ) );
					break;
				case 'thisweek' :
					$query->set( 'date_query', array( 'after' => '- 7 day' ) );
					break;
				case 'thismonth' :
					$query->set( 'date_query', array( 'after' => '-1 month' ) );
					break;
			}
		}
	}

	/**
	 * Add `view` to query vars
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	function set_query_vars( $vars ) {
		$vars[] = 'view';

		return $vars;
	}

	/**
	 * Load the plugin options
	 *
	 * @return void
	 */
	function load_options() {
		$this->options = get_option( 'myposts_options' );
	}

	/**
	 * Initialize scripts for the frontend
	 *
	 * @return void
	 */
	function init_scripts() {
		wp_enqueue_script( 'myposts', plugin_dir_url( __FILE__ ) . 'assets/js/myposts.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'jquery.validate', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.validate.min.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'myposts', plugin_dir_url( __FILE__ ) . 'assets/css/myposts.css', array(), '1.0' );
		wp_localize_script( 'myposts', 'myposts', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'messages' => array(
					'parse_error' => __( 'The url is not available', 'myposts' )
				),
				'nonce'    => wp_create_nonce( 'myposts_none' ),
			)
		);
	}

	/**
	 * Query vote_count and add to database select fields
	 *
	 * @param  string $select
	 *
	 * @return string
	 */
	function select( $select ) {
		global $wpdb;
		$select .= ", (SELECT COUNT(id) FROM {$wpdb->prefix}votes WHERE {$wpdb->prefix}votes.post_id = {$wpdb->prefix}posts.id GROUP BY {$wpdb->prefix}votes.post_id) as vote_count";

		return $select;
	}

	/**
	 * Order posts by vote_count on database order, if the view is presented
	 *
	 * @param  string $order
	 *
	 * @return string
	 */
	function order_by_view( $order ) {
		global $wpdb;
		$view = get_query_var( 'view' );
		switch ( $view ) {
			case 'alltime'  :
			case 'today'    :
			case 'thisweek' :
			case 'thismonth':
				$order = "vote_count DESC, post_date DESC";
				break;
		}

		return $order;
	}

	/**
	 * Vote by ajax call
	 *
	 * @return string The vote count for the post
	 */
	function ajax_vote() {

		if ( ( ! $this->is_user_voted( $_POST['id'] ) ) ) {
			$this->create_vote( $_POST['id'] );
		} else {
			$this->delete_vote( $_POST['id'] );
		}
		echo get_post_meta( $_POST['id'], 'vote_count', true );
		exit;
	}

	/**
	 * Create a vote for a post
	 *
	 * @param $post_id int
	 *
	 * @return bool|int
	 * @internal param int $user_id
	 */
	private function create_vote( $post_id ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'votes', array(
			'created_at' => date( 'Y-m-d H:i:s' ),
			'user_id'    => get_current_user_id(),
			'post_id'    => $post_id
		), array(
			'%s',
			'%d',
			'%d'
		) );

		$count = get_post_meta( $post_id, 'vote_count', true );
		if ( $count == false ) {
			$count = 0;
		}

		return update_post_meta( $post_id, 'vote_count', $count + 1 );
	}

	/**
	 * Delete vote
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	private function delete_vote( $post_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'votes', array(
			'user_id' => get_current_user_id(),
			'post_id' => $post_id
		), array(
			'%d',
			'%d'
		) );

		$count = (int) get_post_meta( $post_id, 'vote_count', true );
		if ( $count !== false && $count > 0 ) {
			update_post_meta( $post_id, 'vote_count', $count - 1 );
		}
	}

	/**
	 * Checks if the given user has already voted for a post
	 *
	 * @param $post_id
	 *
	 * @return object|null
	 */
	private function is_user_voted( $post_id ) {
		global $wpdb;
		$user_id = get_current_user_id();
		$result  = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}votes WHERE user_id = %d AND post_id = %d",
			$user_id, $post_id ), OBJECT );

		// die(var_dump($result, $user_id));
		return $result;
	}

	/**
	 * Populate the loop posts with the `user_voted` parameter
	 * If the user already voted, its true
	 *
	 * @param $posts
	 *
	 * @return array
	 */
	function get_posts_votes( $posts ) {
		global $wpdb;

		$post_ids = implode( ', ', array_map( function ( $post ) {
			return esc_sql( $post->ID );
		}, $posts ) );

		$votes = array();
		if ( "" !== $post_ids ) {
			$votes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}votes WHERE post_id IN ($post_ids)", OBJECT );
		}

		$posts = array_map( function ( $post ) use ( $votes ) {
			$post->user_voted = false;
			if ( $votes ) {
				foreach ( $votes as $vote ) {
					if ( $vote->post_id == $post->ID ) {
						$post->user_voted = true;
					}
				}
			}

			return $post;
		}, $posts );

		return $posts;
	}

	/**
	 * Create the voting buttons html for a post
	 * Position can be edited on settings page: before content, after content
	 *
	 * @param $element
	 * @param string $type
	 *
	 * @return string
	 * @internal param string $content
	 *
	 */
	function add_voting_buttons( $element, $type = 'content' ) {

		global $post;

		if ( is_single() || ! in_array( get_post_type(), $this->options['post_types'] ) ) {
			return $element;
		}

		$vote_count = get_post_meta( $post->ID, 'vote_count', true );

		if ( $vote_count == 0 ) {
			$vote_count = '';
		}

		$user_voted = $post->user_voted;
		$id         = $post->ID;

		$return = '<div class="myposts-wrapper' . esc_attr( $user_voted ? ' voted' : '' ) . '">';
		$return .= '<span class="myposts-count' . esc_attr( $user_voted ? '' : ' empty' ) . '">' . $vote_count . '</span></span>';
		$return .= '<a href="#" class="myposts-vote" data-id="' . esc_attr( $id ) . '">' . __( 'Upvote',
				'myposts' ) . '</a> ';
		$return .= '</div>';

		$return = apply_filters( 'get_vote_template', $return, $vote_count, $user_voted, $id );

		$position = $this->options['position'];

		if ( $position == 'before_content' || $position == 'before_title' ) {
			$element = $return . $element;
		} else {
			$element = $element . $return;
		}

		return $element;
	}

	/**
	 * the_title() filter: Add the voting buttons to the post content
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	function add_voting_buttons_to_content( $content ) {
		if ( $this->options['position'] == 'before_content' || $this->options['position'] == 'after_content' ) {
			return $this->add_voting_buttons( $content );
		}

		return $content;
	}

	/**
	 * Add a link to visit remote url on the content
	 * if the post has one
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	function add_link_to_content( $content ) {
		global $post;
		if ( $link = get_post_meta( $post->ID, 'url', true ) ) {
			$link = '<div><a href="' . $link . '" target="_blank" class="myposts-visit-link">' . __( 'Visit', 'myposts' ) . '</a></div>';

			return $link . $content;
		}

		return $content;
	}

	/**
	 * Generates the order buttons before the loop
	 *
	 * @param  object $query WP_Query
	 *
	 * @return string
	 */
	function get_view_links( $query = null ) {
		if ( ! $query || $query->is_main_query() && ! is_single() && ! is_page() ) {
			global $wp;

			$used_views = $this->options['date_intervals'];

			if ( ! isset( $this->options['date_interval_visible'] ) && $query ) {
				return;
			}

			$available_views = array(
				'today'     => __( 'Today', 'myposts' ),
				'thisweek'  => __( 'This week', 'myposts' ),
				'thismonth' => __( 'This month', 'myposts' ),
				'alltime'   => __( 'All time', 'myposts' ),
			);

			$view   = get_query_var( 'view', 'fresh' );
			$return = '<div class="myposts-views"><div class="myposts-views-inner">';
			$return .= '<span class="myposts-views-label">' . __( 'View', 'myposts' ) . ': </span>';

			$return .= '<a href="' . home_url( add_query_arg( array(),
					$wp->request ) ) . '" class="' . ( $view == 'fresh' || ! $view ? 'active' : '' ) . '">' . __( 'Fresh',
					'myposts' ) . '</a>';

			foreach ( $available_views as $key => $available_view ) {
				if ( in_array( $key, $used_views ) ) {
					$return .= '<a href="' . home_url( add_query_arg( array( 'view' => $key ),
							$wp->request ) ) . '" class="' . ( $view == $key ? 'active' : '' ) . '">' . $available_view . '</a>';
				}
			}

			$return .= '</div></div>';
			echo $return;
		}
	}
}

new Myposts();
