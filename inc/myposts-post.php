<?php

class Myposts_Post {
	protected $plugin_url;
	protected $plugin_path;

	public function __construct() {
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );

		require_once $this->plugin_path . '/simple_html_dom.php';

		add_action( 'wp_ajax_parse_url', array( &$this, 'ajax_parse' ) );
		add_action( 'wp_loaded', array( &$this, 'process_form' ) );
		add_shortcode( 'myposts_form', array( &$this, 'form_template' ) );
	}

	function process_form() {
		if ( isset( $_POST['myposts_nonce'] ) && wp_verify_nonce( $_POST['myposts_nonce'],
				'post' ) && $_POST['myposts_action'] == 'save_post'
		) {

			global $errors;
			$errors = new WP_Error();

			if ( $_POST['title'] == '' ) {
				$errors->add( 'title', __( 'The title field is required' ) );
			} else {
				$data['post_title'] = sanitize_text_field($_POST['title']);
			}

			if ( ! isset( $_POST['category'] ) || $_POST['category'] == '' ) {
				$errors->add( 'category', __( 'Choose a category' ) );
			} else {
				$data['post_category'] = (array) $_POST['category'];
			}

			if ( $_POST['url'] ) {
				if ( ! filter_var( $_POST['url'], FILTER_VALIDATE_URL ) ) {
					$errors->add( 'url', __( 'The url isn\'t a valid format' ) );
				}
				if ( ! @file_get_contents( $_POST['url'] ) ) {
					$errors->add( 'url', __( 'The url is not available', 'myposts' ) );
				}
				if ( ! $errors->get_error_messages() ) {
					$data['url']      = esc_url($_POST['url']);
					$data['provider'] = sanitize_text_field($_POST['provider']);
					$data['image']    = sanitize_text_field($_POST['image']);
				}
			}

			$data['post_status']  = 'publish';
			$data['post_content'] = wp_filter_post_kses($_POST['content']);

			if ( $errors->get_error_messages() ) {
				$out = '<div class="myposts-error"r>';
				foreach ( $errors->get_error_messages() as $error ) {
					$out .= '<p>' . $error . '</p>';
				}
				$out .= '</div>';
				echo $out;
			} else {
				$id = wp_insert_post( $data );

				if ( $_POST['url'] ) {
					$this->save_image( $data['image'], $id );
					update_post_meta( $id, 'url', $data['url'] );
					update_post_meta( $id, 'provider', $data['provider'] );
				}

				wp_redirect( get_post_permalink( $id ) );
				exit;
			}
		}
	}

	function form_template( $atts ) {

		// if( ! is_user_logged_in() ) {
		//     wp_safe_redirect( wp_login_url( get_permalink() ) );
		//     exit;
		// }

		$attributes = shortcode_atts( array(), $atts );
		ob_start();
		require_once $this->plugin_path . '../templates/post.php';

		return ob_get_clean();

	}

	/**
	 * Paring open graph data from a remote url
	 *
	 * @param $url
	 *
	 * @return array|bool
	 * @internal param $post
	 *
	 */
	private function parse( $url ) {

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$response = file_get_html( $url );

		if ( ! $response->find( 'title', 0 ) ) {
			return false;
		}

		$content     = $response->find( 'meta[property=og:description]', 0 );
		$description = $response->find( 'meta[name=description]', 0 );
		$provider    = $response->find( 'meta[property=og:site_name]', 0 );
		$image       = $response->find( 'meta[property=og:image]', 0 );

		return array(
			'post_title'   => trim( $response->find( 'title', 0 )->innertext ),
			'post_content' => $content ? $content->content : ( $description ? $description->content : '' ),
			'provider'     => $provider ? $provider->content : '',
			'image'        => $image ? $image->content : '',
			'url'          => $url
		);
	}

	/**
	 * Return ajax parsed url opengraph data
	 * @return string
	 */
	function ajax_parse() {
		$errors = new WP_Error();

		if ( ! filter_var( $_POST['url'], FILTER_VALIDATE_URL ) ) {
			$errors->add( 'content', __( 'The url isn\'t a valid format' ) );
		}
		if ( ! @file_get_contents( $_POST['url'] ) ) {
			$errors->add( 'content', __( 'The url is not available', 'myposts' ) );
		}

		if ( $errors->get_error_messages() ) {
			echo json_encode( $this->get_error_messages() );
			exit;
		}

		echo json_encode( $this->parse( $_POST['url'] ) );
		exit;
	}

	private function save_image( $url, $post_id ) {
		if ( $url == '' ) {
			return false;
		}

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			die( var_dump( $tmp->get_messages() ) );
		}

		$file_array = array();
		$desc       = '';

		// Set variables for storage
		// fix file filename for query strings
		preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches );
		$file_array['name']     = basename( $matches[0] );
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
			die( var_dump( $tmp->get_messages() ) );
		}

		// do the validation and storage stuff
		$id = media_handle_sideload( $file_array, $post_id, $desc );

		// If error storing permanently, unlink
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );

			return $id;
		}

		set_post_thumbnail( $post_id, $id );

		$src = wp_get_attachment_url( $id );
	}

}

new Myposts_Post();
