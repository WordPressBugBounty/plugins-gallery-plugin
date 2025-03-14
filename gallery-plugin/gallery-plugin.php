<?php
/**
Plugin Name: Gallery by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/gallery/
Description: Add beautiful galleries, albums & images to your WordPress website in few clicks.
Author: BestWebSoft
Text Domain: gallery-plugin
Domain Path: /languages
Version: 4.7.5
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
 */

/**
© Copyright 2023  BestWebSoft  ( https://support.bestwebsoft.com )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/includes/class-gllr-widgets.php';
require_once dirname( __FILE__ ) . '/includes/class-gllr-media-table.php';

if ( ! function_exists( 'add_gllr_admin_menu' ) ) {
	/**
	 * Add WordPress page 'bws_panel' and sub-page of this plugin to admin-panel.
	 */
	function add_gllr_admin_menu() {
		global $submenu, $gllr_options, $gllr_plugin_info, $wp_version;

		if ( empty( $gllr_options ) ) {
			gllr_settings();
		}
		if ( ! is_plugin_active( 'gallery-plugin-pro/gallery-plugin-pro.php' )
			&& ! is_plugin_active( 'gallery-plus/gallery-plus.php' ) ) {
			$settings = add_submenu_page( 'edit.php?post_type=' . $gllr_options['post_type_name'], __( 'Gallery Settings', 'gallery-plugin' ), __( 'Global Settings', 'gallery-plugin' ), 'manage_options', 'gallery-plugin.php', 'gllr_settings_page' );

			add_submenu_page( 'edit.php?post_type=' . $gllr_options['post_type_name'], 'BWS Panel', 'BWS Panel', 'manage_options', 'gllr-bws-panel', 'bws_add_menu_render' );

			/*pls */
			if ( isset( $submenu[ 'edit.php?post_type=' . $gllr_options['post_type_name'] ] ) ) {
				$submenu[ 'edit.php?post_type=' . $gllr_options['post_type_name'] ][] = array(
					'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'gallery-plugin' ) . '</span>',
					'manage_options',
					'https://bestwebsoft.com/products/wordpress/plugins/gallery/?k=63a36f6bf5de0726ad6a43a165f38fe5&pn=79&v=' . $gllr_plugin_info['Version'] . '&wp_v=' . $wp_version,
				);
			}
			add_action( 'load-' . $settings, 'gllr_add_tabs' );
			add_action( 'load-post-new.php', 'gllr_add_tabs' );
			add_action( 'load-post.php', 'gllr_add_tabs' );
			add_action( 'load-edit.php', 'gllr_add_tabs' );
		}
	}
}

if ( ! function_exists( 'gllr_plugins_loaded' ) ) {
	/**
	 * Internationalization
	 */
	function gllr_plugins_loaded() {
		load_plugin_textdomain( 'gallery-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'gllr_register_galleries' ) ) {
	/**
	 * Register the plugin post type and update rewrite rules
	 * in order to make the plugin permalinks work.
	 *
	 * @since 4.5.2
	 * @see https://codex.wordpress.org/Function_Reference/register_post_type#Flushing_Rewrite_on_Activation
	 */
	function gllr_register_galleries() {
		global $gllr_options;
		if ( empty( $gllr_options ) ) {
			$gllr_options = get_option( 'gllr_options' );
			if ( empty( $gllr_options ) ) {
				gllr_settings();
			}
		}
		gllr_post_type_images( true );
	}
}

if ( ! function_exists( 'gllr_init' ) ) {
	/**
	 * Plugin initialization
	 */
	function gllr_init() {
		global $gllr_plugin_info, $pagenow, $gllr_options;

		require_once dirname( __FILE__ ) . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( ! $gllr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version  */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $gllr_plugin_info, '4.5' );

		/* Call register settings function */
		gllr_settings();
		/* Register post type */
		gllr_post_type_images();

		if ( ! is_admin() ) {
			/* Add template for gallery pages */
			add_action( 'template_include', 'gllr_template_include' );
		}

		/* Add media button to the gallery post type */
		if (
			( isset( $_GET['post'] ) && get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) === $gllr_options['post_type_name'] ) ||
			( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && $gllr_options['post_type_name'] === $_GET['post_type'] )
		) {
			add_action( 'edit_form_after_title', 'gllr_media_custom_box' );
		}

		/* demo data */
		$demo_options = get_option( 'gllr_demo_options' );
		if ( ! empty( $demo_options ) || ( isset( $_GET['page'] ) && 'gallery-plugin.php' === $_GET['page'] ) ) {
			gllr_include_demo_data();
		}
	}
}

if ( ! function_exists( 'gllr_admin_init' ) ) {
	/**
	 * Admin init
	 */
	function gllr_admin_init() {
		global $bws_plugin_info, $gllr_plugin_info, $bws_shortcode_list, $pagenow, $gllr_options;
		/* Add variable for bws_menu */
		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id'      => '79',
				'version' => $gllr_plugin_info['Version'],
			);
		}

		/* add gallery to global $bws_shortcode_list */
		$bws_shortcode_list['gllr'] = array(
			'name'        => 'Gallery',
			'js_function' => 'gllr_shortcode_init',
		);

		/*pls */
		if ( 'plugins.php' === $pagenow ) {
			/* Install the option defaults */
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				bws_plugin_banner_go_pro( $gllr_options, $gllr_plugin_info, 'gllr', 'gallery', '01a04166048e9416955ce1cbe9d5ca16', '79', 'gallery-plugin' );
			}
		}
		add_filter( 'manage_' . $gllr_options['post_type_name'] . '_posts_columns', 'gllr_change_columns' );
		add_action( 'manage_' . $gllr_options['post_type_name'] . '_posts_custom_column', 'gllr_custom_columns', 10, 2 );

		if ( isset( $_POST['gllr_export_submit'] ) ) {
			export_gallery_to_csv();
		}
		if ( isset( $_POST['gllr_import_submit'] ) ) {
			import_gallery_from_csv();
		}
	}
}

if ( ! function_exists( 'export_gallery_to_csv' ) ) {
	function export_gallery_to_csv() {
		global $wp_filesystem, $wpdb, $gllr_options;
		/* Check user capabilities */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/* Check nonce for security */
		if ( ! isset( $_POST['gllr_export_nonce'] ) || ! wp_verify_nonce( $_POST['gllr_export_nonce'], 'gllr_export' ) ) {
			return;
		}

		WP_Filesystem();

		$upload_dir = wp_upload_dir();
		$file_name = wp_tempnam( 'tmp', $upload_dir['path'] . '/' );
		if ( ! $file_name ) {
			return false;
		}

		$export_str = '';

		/* Query galleries (assuming custom post type 'gallery') */
		$args = array(
			'post_type'      => 'bws-gallery',
			'posts_per_page' => -1
		);
		$galleries = new WP_Query( $args );

		$export_header = array(
			'Gallery Title',
			'Gallery Content',
			'Gallery Category',
			'Gallery Featured Image',
			'Images URL',
			'Images Title',
			'Images Alt',
			'Images Text',
			'Images Order',
			'Images Shortpixel'
		);

		$export_str = $wpdb->prepare( '%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;' . PHP_EOL, $export_header );

		/* Loop through galleries and output the data */
		if ( $galleries->have_posts() ) {
			while ( $galleries->have_posts() ) {
				$galleries->the_post();
				$title    = get_the_title();
				$content  = get_the_content();
				$terms    = wp_get_post_terms( get_the_ID(), 'gallery_categories' );

				$featured_id    = get_post_meta( get_the_ID(), '_thumbnail_id', true );
				$featured_image = '';
				if ( ! empty( $featured_id ) ) {
					$featured_image = wp_get_attachment_url( $featured_id );
				}

				$categories = array();

				foreach ( $terms as $term ) {
					$album_order = get_term_meta( absint( $term->ID ), 'gllr_album_order', true );
					if ( empty( $album_order ) ) {
						$album_order = isset( $gllr_options['album_order_by_category_option'] ) ? $gllr_options['album_order_by_category_option'] : 'default';
					}
					$categories[] = array( $term->name, $term->slug, $term->description, $term->parent, $album_order );
				}

				$images       = get_post_meta( get_the_ID(), '_gallery_images', true );
				$images_url   = array();
				$images_title = array();
				$images_alt   = array();
				$images_text  = array();
				$images_order = array();
				$images_shortpixel = array();

				if ( ! empty( $images ) ) {
					$images = explode(',', $images );
					foreach ( $images as $image ) {
						$images_url[]        = wp_get_attachment_url( $image );
						$images_title[]      = get_the_title( $image );
						$image_postmeta      = get_post_meta( $image );
						$images_alt[]        = $image_postmeta[ '_gallery_order_' . get_the_ID() ][0];
						$images_text[]       = isset( $image_postmeta['gllr_image_alt_tag'][0] ) ? $image_postmeta['gllr_image_alt_tag'][0] : '';
						$images_order[]      = isset( $image_postmeta['gllr_image_text'][0] ) ? $image_postmeta['gllr_image_text'][0] : '';
						$images_shortpixel[] = isset( $image_postmeta['gallery_images_shortpixel'] ) ? $image_postmeta['gallery_images_shortpixel'][0] : '';
					}
				}
				$export_str .= $wpdb->prepare( '%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;' . PHP_EOL, $title, $content, json_encode( $categories ), $featured_image, json_encode( $images_url ), json_encode( $images_title ), json_encode( $images_alt ), json_encode( $images_text ), json_encode( $images_order ), json_encode( $images_shortpixel ) );
			}
		}

		$result = $wp_filesystem->put_contents( $file_name, $export_str );
		if ( ! $result ) {
			return false;
		}

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="galleries_export.csv"' );
		echo wp_kses_post( $wp_filesystem->get_contents( $file_name ) );
		unlink( $file_name );
		exit();
	}
}


if ( ! function_exists( 'import_gallery_from_csv' ) ) {
	function import_gallery_from_csv() {
		global $wp_filesystem, $wpdb, $gllr_upload_errors;
		/* Check user capabilities */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/* Check nonce for security */
		if ( ! isset( $_POST['gllr_import_nonce'] ) || ! wp_verify_nonce( $_POST['gllr_import_nonce'], 'gllr_import' ) ) {
			return;
		}

		WP_Filesystem();

		$upload_dir = wp_upload_dir();
		if ( empty( $_FILES['gllr_csv_file']['tmp_name'] ) ) {
			$gllr_upload_errors = __( 'Select a file to import data', 'gallery-plugin' );
			return;
		}
		$file_tmp_name = sanitize_text_field( wp_unslash( $_FILES['gllr_csv_file']['tmp_name'] ) );
		$file_name     = sanitize_text_field( wp_unslash( $_FILES['gllr_csv_file']['name'] ) );
		$file_type     = sanitize_text_field( wp_unslash( $_FILES['gllr_csv_file']['type'] ) );

		$validate_file_type = wp_check_filetype( $file_name, array( 'csv' => 'text/csv' ) );

		if ( false === $validate_file_type['type'] || false === $validate_file_type['ext'] ) {
			$gllr_upload_errors = __( 'File type is not allowed, you can only upload a csv file.', 'gallery-plugin' ) . '<br />';
		} else {
			$csv_as_array = $wp_filesystem->get_contents( $file_tmp_name );
			$csv_as_array = explode( PHP_EOL, $csv_as_array );
			foreach( $csv_as_array as $key => $value ) {
				$csv_as_array[ $key ] = explode( "';'", trim( $value, "';" ) );
			}
			if ( isset( $csv_as_array[0] ) && isset( $csv_as_array[0][0] ) && 'Gallery Title' != $csv_as_array[0][0] ) {
				$gllr_upload_errors = __( 'An invalid file was uploaded to import gallery data.', 'gallery-plugin' );
				return;
			}
			unset( $csv_as_array[0] );
			foreach( $csv_as_array as $csv_string ) {
				if ( empty( $csv_string[0] ) ) {
					continue;
				}
				$post = array(
					'comment_status'  => 'closed',
					'ping_status'     => 'closed',
					'post_status'     => 'publish',
					'post_type'       => 'bws-gallery',
					'post_title'      => $csv_string[0],
					'post_content'    => $csv_string[1],
				);
				$post_id = wp_insert_post( $post );
				$gallery_terms = array();
				$categories    = json_decode( stripslashes_deep( $csv_string[2] ) );
				if ( ! empty( $categories ) ) {
					foreach( $categories as $term ) {
						$term_exists = term_exists( $term[0], 'gallery_categories' );
						if ( ! $term_exists ) {
							$term_id = wp_insert_term(
								$term[0],
								'gallery_categories',
								array(
									'description' => $term[2],
									'slug'        => $term[1],
									'parent'      => $term[3],
								)
							);
							$term_exists['term_id'] = $term_id;
						}
						$album_order = get_term_meta( absint( $term_exists['term_id'] ), 'gllr_album_order', true );
						if ( empty( $album_order ) ) {
							update_term_meta( absint( $term_exists['term_id'] ), 'gllr_album_order', sanitize_text_field( wp_unslash( $term[4] ) ) );
						}
						$gallery_terms[] = $term[1];
					}
				}
				wp_set_object_terms( $post_id, $gallery_terms, 'gallery_categories' );
				$featured_image = stripslashes_deep( $csv_string[3] );

				$images_url   = json_decode( stripslashes_deep( $csv_string[4] ) );
				$images_title = json_decode( stripslashes_deep( $csv_string[5] ) );
				$images_alt   = json_decode( stripslashes_deep( $csv_string[6] ) );
				$images_text  = json_decode( stripslashes_deep( $csv_string[7] ) );
				$images_order = json_decode( stripslashes_deep( $csv_string[8] ) );
				$images_shortpixel = json_decode( stripslashes_deep( $csv_string[9] ) );

				if ( empty( $images_url ) ) {
					$gllr_upload_errors = __( 'Import completed successfully. It is possible that a file created in a previous version of the plugin was imported and the gallery image data will not be fully loaded.', 'gallery-plugin' );
				}

				$attach_images = array();

				if ( ! empty( $images_url ) ) {
					$wp_upload_dir = wp_upload_dir();
					require_once ABSPATH . 'wp-admin/includes/image.php';
					foreach( $images_url as $key => $image ) {
						$http     = new WP_Http();
						$response = $http->request( $image );
						if ( is_wp_error( $response ) || ! isset( $response['response']['code'] ) || 200 !== $response['response']['code'] ) {
							$gllr_upload_errors = esc_html__( 'Errors occurred when uploading images, not all images were uploaded to the galleries', 'gallery-plugin' );
							continue;
						}

						$upload = wp_upload_bits( basename( $image ), null, $response['body'] );
						if ( ! empty( $upload['error'] ) ) {
							$gllr_upload_errors = esc_html__( 'Errors occurred when uploading images, not all images were uploaded to the galleries', 'gallery-plugin' );
							continue;
						}

						$file_path        = $upload['file'];
						$file_name        = basename( $file_path );
						$file_type        = wp_check_filetype( $file_name, null );
						$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );

						$post_info = array(
							'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
							'post_mime_type' => $file_type['type'],
							'post_title'     => $images_title[ $key ],
							'post_content'   => '',
							'post_status'    => 'inherit',
							'post_parent'    => $post_id
						);

						$attach_id   = wp_insert_attachment( $post_info, $file_path );
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
						
						$attach_images[] = $attach_id;

						wp_update_attachment_metadata( $attach_id, $attach_data );

						if ( ! empty( $featured_image ) && $image === $featured_image ) {
							set_post_thumbnail( $post_id, $attach_id );
							$featured_image = '';
						}
						
						add_post_meta( $attach_id, '_gallery_order_' . $post_id, $images_order[ $key ] );
						if ( ! empty( $images_alt[ $key ] ) ) {
							add_post_meta( $attach_id, 'gllr_image_alt_tag', $images_alt[ $key ] );
						}					
						if ( ! empty( $images_text[ $key ] ) ) {
							add_post_meta( $attach_id, 'gllr_image_text', $images_text[ $key ] );
						}					
						if ( ! empty( $images_shortpixel[ $key ] ) ) {
							add_post_meta( $attach_id, 'gallery_images_shortpixel', $images_shortpixel[ $key ] );
						}
					}
				}

				if ( ! empty( $attach_images ) ) {
					add_post_meta( $post_id, '_gallery_images', implode( ',', $attach_images ) );
				}

				if ( ! empty( $featured_image ) ) {
					$http     = new WP_Http();
					$response = $http->request( $featured_image );
					if ( 200 !== $response['response']['code'] ) {
						$gllr_upload_errors = esc_html__( 'Errors occurred when uploading images, not all images were uploaded to the galleries', 'gallery-plugin' );
						continue;
					}

					$upload = wp_upload_bits( basename( $featured_image ), null, $response['body'] );
					if ( ! empty( $upload['error'] ) ) {
						$gllr_upload_errors = esc_html__( 'Errors occurred when uploading images, not all images were uploaded to the galleries', 'gallery-plugin' );
						continue;
					}

					$file_path        = $upload['file'];
					$file_name        = basename( $file_path );
					$file_type        = wp_check_filetype( $file_name, null );
					$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );

					$post_info = array(
						'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
						'post_mime_type' => $file_type['type'],
						'post_title'     => $images_title[ $key ],
						'post_content'   => '',
						'post_status'    => 'inherit',
						'post_parent'    => $post_id
					);

					$attach_id   = wp_insert_attachment( $post_info, $file_path );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

					wp_update_attachment_metadata( $attach_id, $attach_data );

					set_post_thumbnail( $post_id, $attach_id );
					$featured_image = '';
				}
			}
		}
	}
}

if ( ! function_exists( 'gllr_plugin_activate' ) ) {
	/**
	 * Function for activation
	 */
	function gllr_plugin_activate() {
		gllr_register_galleries();
		/* registering uninstall hook */
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			register_uninstall_hook( __FILE__, 'gllr_plugin_uninstall' );
			restore_current_blog();
		} else {
			register_uninstall_hook( __FILE__, 'gllr_plugin_uninstall' );
		}
	}
}

if ( ! function_exists( 'gallery_plugin_status' ) ) {
	/**
	 * Check plugin status
	 *
	 * @param   array $plugins        BWS plugins.
	 * @param   array $all_plugins    All plugins on the site.
	 * @param   bool  $is_network     Flag for network.
	 *
	 * @return array $result
	 */
	function gallery_plugin_status( $plugins, $all_plugins, $is_network ) {
		$result = array(
			'status'      => '',
			'plugin'      => '',
			'plugin_info' => array(),
		);
		foreach ( (array) $plugins as $plugin ) {
			if ( array_key_exists( $plugin, $all_plugins ) ) {
				if (
					( $is_network && is_plugin_active_for_network( $plugin ) ) ||
					( ! $is_network && is_plugin_active( $plugin ) )
				) {
					$result['status']      = 'actived';
					$result['plugin']      = $plugin;
					$result['plugin_info'] = $all_plugins[ $plugin ];
					break;
				} else {
					$result['status']      = 'deactivated';
					$result['plugin']      = $plugin;
					$result['plugin_info'] = $all_plugins[ $plugin ];
				}
			}
		}
		if ( empty( $result['status'] ) ) {
			$result['status'] = 'not_installed';
		}
		return $result;
	}
}

if ( ! function_exists( 'gllr_export_slider' ) ) {
	/**
	 * Export gallery to the slider
	 */
	function gllr_export_slider() {
		global $wpdb;

		check_ajax_referer( plugin_basename( __FILE__ ), 'gllr_ajax_nonce_field' );

		$id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '';
		$title = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';

		$select = $wpdb->get_results(
			$wpdb->prepare(
				'
				SELECT meta_value 
				FROM ' . $wpdb->prefix . 'postmeta
				WHERE post_id = %s
				AND meta_key = "_gallery_images"
				',
				$id
			),
			ARRAY_A
		);

		if ( $select ) {

			$get_meta_val     = $select[0]['meta_value'];
			$explode_meta_val = explode( ',', $get_meta_val );

			$sldr_settings = array(
				'loop'                 => false,
				'nav'                  => false,
				'dots'                 => false,
				'items'                => 1,
				'autoplay'             => false,
				'autoplay_timeout'     => 2000,
				'autoplay_hover_pause' => false,
			);

			$serialize_sldr_settings = serialize( $sldr_settings );

			$wpdb->insert(
				$wpdb->prefix . 'sldr_slider',
				array(
					'title'    => $title,
					'datetime' => date( 'Y-m-d' ),
					'settings' => $serialize_sldr_settings,
				)
			);

			$slider_id = $wpdb->get_results(
				$wpdb->prepare(
					'
					SELECT slider_id 
					FROM ' . $wpdb->prefix . 'sldr_slider
					WHERE title = %s
					',
					$title
				),
				ARRAY_A
			);

			$data_slide    = array();
			$data_relation = array();
			$slice         = array_slice( $slider_id, -1 );
			$slice         = array_values( $slice );
			$slice         = $slice[0];
			$i             = 0;

			foreach ( $explode_meta_val as $val ) {
				$i++;
				$data_slide[] = $wpdb->prepare(
					'(%d, %d)',
					absint( $val ),
					absint( $i )
				);

				$data_relation[] = $wpdb->prepare(
					'(%d, %d)',
					absint( $slice['slider_id'] ),
					absint( $val )
				);
			}

			$implode_data_slide = implode( ', ', $data_slide );

			$implode_data_relation = implode( ', ', $data_relation );

			$check_duplicate = $wpdb->get_results(
				$wpdb->prepare(
					'
					SELECT slide_id 
					FROM ' . $wpdb->prefix . 'sldr_slide
					WHERE attachment_id = %d
					',
					$get_meta_val
				),
				ARRAY_A
			);

			if ( ! $check_duplicate ) {
				$wpdb->query(
					'
					INSERT INTO ' . $wpdb->prefix . 'sldr_slide ( `attachment_id`, `order` )
					VALUES ' . $implode_data_slide . '
					'
				);
			}

			$wpdb->query(
				'
				INSERT INTO ' . $wpdb->prefix . 'sldr_relation ( `slider_id`, `attachment_id` )
				VALUES ' . $implode_data_relation . '
				'
			);
		}
	}
}

if ( ! function_exists( 'gllr_settings' ) ) {
	/**
	 * Register settings function
	 */
	function gllr_settings() {
		global $gllr_options, $gllr_plugin_info;

		if ( empty( $gllr_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}
		/* Install the option defaults */
		if ( ! get_option( 'gllr_options' ) ) {
			$option_defaults = gllr_get_options_default();
			add_option( 'gllr_options', $option_defaults );
		}

		/* Get options from the database */
		$gllr_options = get_option( 'gllr_options' );

		/* Array merge incase this version has added new options */
		if ( isset( $gllr_options['plugin_option_version'] ) && $gllr_options['plugin_option_version'] !== $gllr_plugin_info['Version'] ) {

			$option_defaults = gllr_get_options_default();

			$option_defaults['display_demo_notice']     = 0;
			$option_defaults['display_settings_notice'] = 0;

			$gllr_options                          = array_merge( $option_defaults, $gllr_options );
			$gllr_options['plugin_option_version'] = $gllr_plugin_info['Version'];

			/* show pro features */
			$gllr_options['hide_premium_options'] = array();

			update_option( 'gllr_options', $gllr_options );
		}

		if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'album-thumb', $gllr_options['custom_size_px']['album-thumb'][0], $gllr_options['custom_size_px']['album-thumb'][1], true );
			add_image_size( 'photo-thumb', $gllr_options['custom_size_px']['photo-thumb'][0], $gllr_options['custom_size_px']['photo-thumb'][1], true );
		}
	}
}

if ( ! function_exists( 'gllr_get_options_default' ) ) {
	/**
	 * Get Plugin default options
	 */
	function gllr_get_options_default() {
		global $gllr_plugin_info;

		$option_defaults = array(
			/* internal general */
			'plugin_option_version'                  => $gllr_plugin_info['Version'],
			'first_install'                          => strtotime( 'now' ),
			'suggest_feature_banner'                 => 1,
			'display_settings_notice'                => 1,
			/* internal */
			'display_demo_notice'                    => 1,
			'flush_rewrite_rules'                    => 1,
			/* settings */
			'custom_size_px'                         => array(
				'album-thumb' => array( 120, 80 ),
				'photo-thumb' => array( 160, 120 ),
			),
			'custom_image_row_count'                 => 3,
			'image_size_photo'                       => 'thumbnail',
			'image_text'                             => 0,
			'border_images'                          => 1,
			'border_images_width'                    => 10,
			'border_images_color'                    => '#F1F1F1',
			'order_by'                               => 'meta_value_num',
			'order'                                  => 'ASC',
			'return_link'                            => 0,
			'return_link_url'                        => '',
			'return_link_text'                       => __( 'Return to all albums', 'gallery-plugin' ),
			'return_link_shortcode'                  => 0,
			/* cover */
			'page_id_gallery_template'               => '',
			'galleries_layout'                       => 'column',
			'galleries_column_alignment'             => 'left',
			'image_size_album'                       => 'medium',
			'cover_border_images'                    => 1,
			'cover_border_images_width'              => 10,
			'cover_border_images_color'              => '#F1F1F1',
			'album_order_by'                         => 'date',
			'album_order_by_category_option'         => 'default',
			'album_order_by_shortcode_option'        => 'default',
			'album_order'                            => 'DESC',
			'read_more_link_text'                    => __( 'See images &raquo;', 'gallery-plugin' ),
			/* lightbox */
			'enable_lightbox'                        => 1,
			'enable_image_opening'                   => 0,
			'start_slideshow'                        => 0,
			'slideshow_interval'                     => 2000,
			'lightbox_download_link'                 => 0,
			'lightbox_arrows'                        => 0,
			'single_lightbox_for_multiple_galleries' => 0,
			/* misc */
			'post_type_name'                         => 'bws-gallery',
			/* gallery_category */
			'default_gallery_category'               => '',
			/* disable 3rd-party fancybox */
			'disable_foreing_fancybox'               => 0,
		);

		$option_defaults = apply_filters( 'gllr_get_additional_options_default', $option_defaults );

		return $option_defaults;
	}
}

if ( ! function_exists( 'gllr_include_demo_data' ) ) {
	/**
	 * Plugin include demo
	 *
	 * @return void
	 */
	function gllr_include_demo_data() {
		global $gllr_bws_demo_data;
		require_once plugin_dir_path( __FILE__ ) . 'includes/demo-data/class-bws-demo-data.php';
		$args               = array(
			'plugin_basename'  => plugin_basename( __FILE__ ),
			'plugin_prefix'    => 'gllr_',
			'plugin_name'      => 'Gallery',
			'plugin_page'      => 'gallery-plugin.php&bws_active_tab=import-export',
			'install_callback' => 'gllr_plugin_upgrade',
			'demo_folder'      => plugin_dir_path( __FILE__ ) . 'includes/demo-data/',
		);
		$gllr_bws_demo_data = new Bws_Demo_Data( $args );

		/* filter for image url from demo data */
		add_filter( 'wp_get_attachment_url', array( $gllr_bws_demo_data, 'bws_wp_get_attachment_url' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( $gllr_bws_demo_data, 'bws_wp_get_attachment_image_attributes' ), 10, 3 );
		add_filter( 'wp_update_attachment_metadata', array( $gllr_bws_demo_data, 'bws_wp_update_attachment_metadata' ), 10, 2 );
	}
}
if ( ! function_exists( 'gllr_plugin_upgrade' ) ) {
	/**
	 * Function for update all gallery images to new version ( Stable tag: 4.3.6 )
	 *
	 * @param bool $is_demo Flag for demo status.
	 */
	function gllr_plugin_upgrade( $is_demo = true ) {
		global $wpdb, $gllr_options;

		$all_gallery_attachments = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p1.ID, p1.post_parent, p1.menu_order
				FROM ' . $wpdb->posts . ' p1, ' . $wpdb->posts . ' p2
				WHERE p1.post_parent = p2.ID
				AND p1.post_mime_type LIKE %s
				AND p1.post_type = "attachment"
				AND p1.post_status = "inherit"
				AND p2.post_type = %s',
				'image%',
				$gllr_options['post_type_name']
			),
			ARRAY_A
		);

		if ( ! empty( $all_gallery_attachments ) ) {
			$attachments_array = array();
			foreach ( $all_gallery_attachments as $key => $value ) {
				$post       = $value['post_parent'];
				$attachment = $value['ID'];
				$order      = $value['menu_order'];
				if ( ! isset( $attachments_array[ $post ] ) || ( isset( $attachments_array[ $post ] ) && ! in_array( $attachment, $attachments_array[ $post ] ) ) ) {
					$attachments_array[ $post ][] = $attachment;
					if ( false === $is_demo ) {
						update_post_meta( $attachment, '_gallery_order_' . $post, $order );
					}
				}
			}
			foreach ( $attachments_array as $key => $value ) {
				update_post_meta( $key, '_gallery_images', implode( ',', $value ) );
			}
			/* set gallery category for demo data */
			if ( function_exists( 'gllrctgrs_add_default_term_all_gallery' ) ) {
				gllrctgrs_add_default_term_all_gallery();
			}
		}
	}
}

if ( ! function_exists( 'gllr_post_type_images' ) ) {
	/**
	 * Create post type and taxonomy for Gallery
	 *
	 * @param bool $force_flush_rules Flag for flush rules.
	 */
	function gllr_post_type_images( $force_flush_rules = false ) {
		global $gllr_options;

		register_post_type(
			$gllr_options['post_type_name'],
			array(
				'labels'               => array(
					'name'              => __( 'Galleries', 'gallery-plugin' ),
					'singular_name'     => __( 'Gallery', 'gallery-plugin' ),
					'add_new'           => __( 'Add New Gallery', 'gallery-plugin' ),
					'add_new_item'      => __( 'Add New Gallery', 'gallery-plugin' ),
					'edit_item'         => __( 'Edit Gallery', 'gallery-plugin' ),
					'new_item'          => __( 'New Gallery', 'gallery-plugin' ),
					'view_item'         => __( 'View Gallery', 'gallery-plugin' ),
					'search_items'      => __( 'Search Galleries', 'gallery-plugin' ),
					'not_found'         => __( 'No Gallery found', 'gallery-plugin' ),
					'parent_item_colon' => '',
					'menu_name'         => __( 'Galleries', 'gallery-plugin' ),
				),
				'public'               => true,
				'publicly_queryable'   => true,
				'exclude_from_search'  => true,
				'query_var'            => true,
				'rewrite'              => true,
				'menu_icon'            => 'dashicons-format-gallery',
				'capability_type'      => 'post',
				'has_archive'          => false,
				'hierarchical'         => true,
				'supports'             => array( 'title', 'editor', 'thumbnail', 'author', 'page-attributes', 'comments' ),
				'register_meta_box_cb' => 'gllr_init_metaboxes',
				'taxonomy'             => array( 'gallery_categories' ),
			)
		);

		register_taxonomy(
			'gallery_categories',
			$gllr_options['post_type_name'],
			array(
				'hierarchical' => true,
				'labels'       => array(
					'name'                  => __( 'Gallery Categories', 'gallery-plugin' ),
					'singular_name'         => __( 'Gallery Category', 'gallery-plugin' ),
					'add_new'               => __( 'Add Gallery Category', 'gallery-plugin' ),
					'add_new_item'          => __( 'Add New Gallery Category', 'gallery-plugin' ),
					'edit'                  => __( 'Edit Gallery Category', 'gallery-plugin' ),
					'edit_item'             => __( 'Edit Gallery Category', 'gallery-plugin' ),
					'new_item'              => __( 'New Gallery Category', 'gallery-plugin' ),
					'view'                  => __( 'View Gallery Category', 'gallery-plugin' ),
					'view_item'             => __( 'View Gallery Category', 'gallery-plugin' ),
					'search_items'          => __( 'Find Gallery Category', 'gallery-plugin' ),
					'not_found'             => __( 'No Gallery Categories found', 'gallery-plugin' ),
					'not_found_in_trash'    => __( 'No Gallery Categories found in Trash', 'gallery-plugin' ),
					'parent'                => __( 'Parent Gallery Category', 'gallery-plugin' ),
					'items_list_navigation' => __( 'Gallery Categories list navigation', 'gallery-plugin' ),
					'items_list'            => __( 'Gallery Categories list', 'gallery-plugin' ),
				),
				'rewrite'      => true,
				'show_ui'      => true,
				'query_var'    => true,
				'sort'         => true,
				'map_meta_cap' => true,
			)
		);

		if ( empty( $gllr_options['default_gallery_category'] ) ) {
			$terms = get_terms(
				'gallery_categories',
				array(
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( ! empty( $terms ) && is_array( $terms ) ) {
				$def_term_id = min( $terms );
			} else {
				$def_term = 'Default';
				if ( ! term_exists( $def_term, 'gallery_categories' ) ) {
					$def_term_info = wp_insert_term(
						$def_term,
						'gallery_categories',
						array(
							'description' => '',
							'slug'        => 'default',
						)
					);
				}
				if ( is_array( $def_term_info ) ) {
					$def_term_id = ( array_shift( $def_term_info ) );
				}
			}
			if ( empty( $gllr_options['default_gallery_category'] ) ) {
				$gllr_options['default_gallery_category'] = absint( $def_term_id );
			}
		}
		$rewrite_rules = get_option( 'rewrite_rules' );
		if ( is_array( $rewrite_rules ) && ! empty( $rewrite_rules ) && ! in_array( 'bws-gallery', $rewrite_rules ) || $force_flush_rules || ( isset( $gllr_options['flush_rewrite_rules'] ) && 1 === absint( $gllr_options['flush_rewrite_rules'] ) ) ) {
			flush_rewrite_rules();
			$gllr_options['flush_rewrite_rules'] = 0;
			update_option( 'gllr_options', $gllr_options );
		}
	}
}

if ( ! function_exists( 'gllr_init_metaboxes' ) ) {
	/**
	 * Add metabox for gallery post type
	 */
	function gllr_init_metaboxes() {
		global $gllr_options;
		add_meta_box( 'Gallery-Shortcode', __( 'Gallery Shortcode', 'gallery-plugin' ), 'gllr_post_shortcode_box', $gllr_options['post_type_name'], 'side', 'high' );
	}
}

if ( ! function_exists( 'gllr_post_shortcode_box' ) ) {
	/**
	 * Create shortcode meta box for gallery post type
	 *
	 * @param string $obj Object for shortcode.
	 * @param string $box Box for shortcode.
	 */
	function gllr_post_shortcode_box( $obj = '', $box = '' ) {
		global $post; ?>
		<div>
			<?php
			esc_html_e( 'Add a single gallery with images to your posts, pages, custom post types or widgets by using the following shortcode:', 'gallery-plugin' );
			bws_shortcode_output( '[print_gllr id=' . $post->ID . ']' );
			?>
		</div>
		<div style="margin-top: 5px;">
			<?php
			esc_html_e( 'Add a gallery cover including featured image, description, and a link to your single gallery using the following shortcode:', 'gallery-plugin' );
			bws_shortcode_output( '[print_gllr id=' . $post->ID . ' display=short]' );
			?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'gllr_save_postdata' ) ) {
	/**
	 * Save gallery info
	 *
	 * @param number $post_id Post ID.
	 * @param number $post    Post object.
	 */
	function gllr_save_postdata( $post_id, $post ) {

		if ( isset( $post ) ) {
			if ( isset( $_POST['gllr_nonce_field'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gllr_nonce_field'] ) ), 'gllr_action' )
			) {
				if ( isset( $_POST[ '_gallery_order_' . $post->ID ] ) ) {
					$i = 1;
					foreach ( $_POST[ '_gallery_order_' . $post->ID ] as $post_order_id => $order_id ) {
						update_post_meta( absint( $post_order_id ), '_gallery_order_' . $post->ID, $i );
						$i++;
					}
					update_post_meta( $post->ID, '_gallery_images', implode( ',', array_keys( $_POST[ '_gallery_order_' . $post->ID ] ) ) );
				}

				if ( ( ( isset( $_POST['action-top'] ) && 'delete' === $_POST['action-top'] ) ||
					( isset( $_POST['action-bottom'] ) && 'delete' === $_POST['action-bottom'] ) ) &&
					isset( $_POST['media'] ) ) {
					$gallery_images       = get_post_meta( $post_id, '_gallery_images', true );
					$gallery_images_array = explode( ',', $gallery_images );
					$gallery_images_array = array_flip( $gallery_images_array );
					foreach ( $_POST['media'] as $delete_id ) {
						delete_post_meta( absint( $delete_id ), '_gallery_order_' . $post->ID );
						unset( $gallery_images_array[ absint( $delete_id ) ] );
					}
					$gallery_images_array = array_flip( $gallery_images_array );
					$gallery_images       = implode( ',', $gallery_images_array );
					update_post_meta( $post->ID, '_gallery_images', $gallery_images );
				}
				if ( isset( $_REQUEST['gllr_image_text'] ) ) {
					foreach ( $_REQUEST['gllr_image_text'] as $gllr_image_text_key => $gllr_image_text ) {
						$value = sanitize_text_field( wp_unslash( $gllr_image_text ) );
						if ( get_post_meta( absint( $gllr_image_text_key ), 'gllr_image_text', false ) ) {
							/* Custom field has a value and this custom field exists in database */
							update_post_meta( absint( $gllr_image_text_key ), 'gllr_image_text', $value );
						} elseif ( $value ) {
							/* Custom field has a value, but this custom field does not exist in database */
							add_post_meta( absint( $gllr_image_text_key ), 'gllr_image_text', $value );
						}
					}
				}
				if ( isset( $_REQUEST['gllr_link_url'] ) ) {
					foreach ( $_REQUEST['gllr_link_url'] as $gllr_link_url_key => $gllr_link_url ) {
						$value = esc_url_raw( wp_unslash( trim( $gllr_link_url ) ) );
						if ( filter_var( $value, FILTER_VALIDATE_URL ) === false ) {
							$value = '';
						}
						if ( get_post_meta( absint( $gllr_link_url_key ), 'gllr_link_url', false ) ) {
							/* Custom field has a value and this custom field exists in database */
							update_post_meta( absint( $gllr_link_url_key ), 'gllr_link_url', $value );
						} elseif ( $value ) {
							/* Custom field has a value, but this custom field does not exist in database */
							add_post_meta( absint( $gllr_link_url_key ), 'gllr_link_url', $value );
						}
					}
				}
				if ( isset( $_REQUEST['gllr_image_alt_tag'] ) ) {
					foreach ( $_REQUEST['gllr_image_alt_tag'] as $gllr_image_alt_tag_key => $gllr_image_alt_tag ) {
						$value = sanitize_text_field( wp_unslash( $gllr_image_alt_tag ) );
						if ( get_post_meta( absint( $gllr_image_alt_tag_key ), 'gllr_image_alt_tag', false ) ) {
							/* Custom field has a value and this custom field exists in database */
							update_post_meta( absint( $gllr_image_alt_tag_key ), 'gllr_image_alt_tag', $value );
						} elseif ( $value ) {
							/* Custom field has a value, but this custom field does not exist in database */
							add_post_meta( absint( $gllr_image_alt_tag_key ), 'gllr_image_alt_tag', $value );
						}
					}
				}
			}
		}
	}
}

if ( ! class_exists( 'Gllr_CategoryDropdown' ) ) {
	class Gllr_CategoryDropdown extends Walker_CategoryDropdown {
		/**
		 * Start the element output.
		 *
		 * @param string $output   Passed by reference. Used to append additional content.
		 * @param object $term   Category data object.
		 * @param int    $depth    Depth of category in reference to parents. Default 0.
		 * @param array  $args     An array of arguments.
		 * @param int    $id       ID of the current term.
		 */
		public function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 ) {
			$term_name = apply_filters( 'list_cats', $term->name, $term );
			$output   .= '<option class="level-' . $depth . '" value="' . $term->slug . '"';
			if ( $term->slug === $args['selected'] ) {
				$output .= ' selected="selected"';
			}
			$output .= '>';
			$output .= str_repeat( '&nbsp;', $depth * 3 ) . $term_name;
			if ( $args['show_count'] ) {
				$output .= '&nbsp;( ' . $term->count . ' )';
			}
			$output .= '</option>';
		}
	}
}

if ( ! function_exists( 'gllr_add_notice_below_table' ) ) {
	/**
	 * Add notices on taxonomy page about insert shortcode
	 */
	function gllr_add_notice_below_table() {
		global $gllr_options;
		if ( ! empty( $gllr_options['default_gallery_category'] ) ) {
			$def_term = get_term( $gllr_options['default_gallery_category'], 'gallery_categories' );
			if ( ! empty( $def_term ) ) {
				$def_term_name = $def_term->name;
				if ( ! empty( $def_term_name ) ) {
					echo '<div class="form-wrap"><p><i><strong>' . esc_html__( 'Note', 'gallery-plugin' ) . ':</strong> ' . sprintf( esc_html__( 'When deleting a category, the galleries that belong to this category will not be deleted. These galleries will be moved to the category %s.', 'gallery-plugin' ), '<strong>' . esc_html( $def_term_name ) . '</strong>' ) . '</i></p></div>';
				}
			}
		}
	}
}

if ( ! function_exists( 'gllr_additive_field_in_category' ) ) {
	/**
	 * Add Sort Galleries in Category dropdown
	 */
	function gllr_additive_field_in_category() {
		global $gllr_options;
		?>
		<div class="form-field term-description-wrap">
			<label for="tag-description"><?php esc_html_e( 'Sort Galleries in Category by', 'gallery-plugin' ); ?></label>
			<select name="album_order_by_category_option">
				<option value="ID"><?php esc_html_e( 'Gallery ID', 'gallery-plugin' ); ?></option>
				<option value="title"><?php esc_html_e( 'Title', 'gallery-plugin' ); ?></option>
				<option value="date"><?php esc_html_e( 'Date', 'gallery-plugin' ); ?></option>
				<option value="modified"><?php esc_html_e( 'Last modified date', 'gallery-plugin' ); ?></option>
				<option value="comment_count"><?php esc_html_e( 'Comment count', 'gallery-plugin' ); ?></option>
				<option value="menu_order"><?php esc_html_e( '"Order" field on the gallery edit page', 'gallery-plugin' ); ?></option>
				<option value="author"><?php esc_html_e( 'Author', 'gallery-plugin' ); ?></option>
				<option value="rand" class="bws_option_affect" data-affect-hide=".gllr_album_order"><?php esc_html_e( 'Random', 'gallery-plugin' ); ?></option>
				<option value="default" selected="selected" class="bws_option_affect" data-affect-hide=".gllr_album_order"><?php esc_html_e( 'Plugin Settings', 'gallery-plugin' ); ?></option>
			</select>
			<p id="description-description"><?php echo sprintf( esc_html__( 'Select galleries sorting order in your category. The sorting direction you can select in the %s', 'gallery-plugin' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=bws-gallery&page=gallery-plugin.php' ) ) . '">' . esc_html__( 'Cover Settings', 'gallery-plugin' ) . '</a>' ); ?></p>
		</div>
		<?php wp_nonce_field( 'gllr_category_action', 'gllr_category_nonce_field' ); ?>
		<?php
	}
}

if ( ! function_exists( 'gllr_additive_field_in_category_edit' ) ) {
	/**
	 * Add Sort Galleries in Category dropdown
	 */
	function gllr_additive_field_in_category_edit() {
		$album_order = get_term_meta( absint( $_GET['tag_ID'] ), 'gllr_album_order', true );
		if ( empty( $album_order ) ) {
			$album_order = isset( $gllr_options['album_order_by_category_option'] ) ? $gllr_options['album_order_by_category_option'] : 'default';
		}
		?>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Sort Galleries in Category by', 'gallery-plugin' ); ?></th>
			<td>
				<select name="album_order_by_category_option">
					<option value="ID" <?php selected( 'ID', $album_order ); ?>><?php esc_html_e( 'Gallery ID', 'gallery-plugin' ); ?></option>
					<option value="title" <?php selected( 'title', $album_order ); ?>><?php esc_html_e( 'Title', 'gallery-plugin' ); ?></option>
					<option value="date" <?php selected( 'date', $album_order ); ?>><?php esc_html_e( 'Date', 'gallery-plugin' ); ?></option>
					<option value="modified" <?php selected( 'modified', $album_order ); ?>><?php esc_html_e( 'Last modified date', 'gallery-plugin' ); ?></option>
					<option value="comment_count" <?php selected( 'comment_count', $album_order ); ?>><?php esc_html_e( 'Comment count', 'gallery-plugin' ); ?></option>
					<option value="menu_order" <?php selected( 'menu_order', $album_order ); ?>><?php esc_html_e( '"Order" field on the gallery edit page', 'gallery-plugin' ); ?></option>
					<option value="author" <?php selected( 'author', $album_order ); ?>><?php esc_html_e( 'Author', 'gallery-plugin' ); ?></option>
					<option value="rand" <?php selected( 'rand', $album_order ); ?> class="bws_option_affect" data-affect-hide=".gllr_album_order"><?php esc_html_e( 'Random', 'gallery-plugin' ); ?></option>
					<option value="default" <?php selected( 'default', $album_order ); ?> class="bws_option_affect" data-affect-hide=".gllr_album_order"><?php esc_html_e( 'Plugin Settings', 'gallery-plugin' ); ?></option>
				</select>
				<div class="bws_info"><?php echo sprintf( esc_html__( 'Select galleries sorting order in your category. The sorting direction you can select in the %s', 'gallery-plugin' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=bws-gallery&page=gallery-plugin.php' ) ) . '">' . esc_html__( 'Cover Settings', 'gallery-plugin' ) . '</a>' ); ?></p></div>
			</td>
		</tr>
		<?php wp_nonce_field( 'gllr_category_action', 'gllr_category_nonce_field' ); ?>
		<?php
	}
}

if ( ! function_exists( 'gllr_save_category_additive_field' ) ) {
	/**
	 * Save Sort Galleries in Category dropdown
	 */
	function gllr_save_category_additive_field() {
		global $gllr_options;
		if ( isset( $_POST['album_order_by_category_option'] )
			&& isset( $_POST['gllr_category_nonce_field'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gllr_category_nonce_field'] ) ), 'gllr_category_action' )
		) {
			update_term_meta( absint( $_POST['tag_ID'] ), 'gllr_album_order', sanitize_text_field( wp_unslash( $_POST['album_order_by_category_option'] ) ) );
		}
	}
}

if ( ! function_exists( 'gllr_add_column' ) ) {
	/**
	 * Function for adding column in taxonomy
	 *
	 * @param array $column Columns array for taxonomy table.
	 * @return array $column
	 */
	function gllr_add_column( $column ) {
		$column['shortcode'] = __( 'Shortcode', 'gallery-plugin' );
		return $column;
	}
}

if ( ! function_exists( 'gllr_fill_column' ) ) {
	/**
	 * Function for filling column in taxonomy
	 *
	 * @param string $out    Info for display.
	 * @param string $column Column name for display info.
	 * @param number $id     Column id.
	 * @return string $out
	 */
	function gllr_fill_column( $out, $column, $id ) {
		if ( 'shortcode' === $column ) {
			$out = bws_shortcode_output( '[print_gllr cat_id=' . $id . ']' );
		}
		return $out;
	}
}

if ( ! function_exists( 'gllr_default_term' ) ) {
	/**
	 * Function assignment of default term for new gallery while updated post
	 *
	 * @param number $post_ID Post ID.
	 */
	function gllr_default_term( $post_ID ) {
		global $gllr_options;
		$post = get_post( $post_ID );
		if ( $post->post_type === $gllr_options['post_type_name'] ) {
			if ( ! has_term( '', 'gallery_categories', $post ) ) {
				wp_set_object_terms( $post->ID, $gllr_options['default_gallery_category'], 'gallery_categories' );
			}
		}
	}
}

if ( ! function_exists( 'gllr_taxonomy_filter' ) ) {
	/**
	 * Function for adding taxonomy filter in gallery
	 */
	function gllr_taxonomy_filter() {
		global $typenow, $gllr_options;
		if ( $typenow === $gllr_options['post_type_name'] ) {
			$current_taxonomy = isset( $_GET['gallery_categories'] ) ? sanitize_text_field( wp_unslash( $_GET['gallery_categories'] ) ) : '';
			$terms            = get_terms( 'gallery_categories' );
			if ( 0 < count( $terms ) ) {
				?>
				<select name="gallery_categories" id="gallery_categories">
					<option value=''><?php esc_html_e( 'All Gallery Categories', 'gallery-plugin' ); ?></option>
					<?php foreach ( $terms as $term ) { ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>"<?php selected( $current_taxonomy, $term->slug ); ?>><?php echo esc_html( $term->name ) . ' ( ' . esc_html( $term->count ) . ' ) '; ?></option>
					<?php } ?>
				</select>
				<?php
			}
		}
	}
}

if ( ! function_exists( 'gllr_hide_delete_link' ) ) {
	/**
	 * Function for hide delete link ( protect default category from deletion )
	 *
	 * @param array  $actions Actions array.
	 * @param object $tag     Term taxonomy object.
	 * @return array $actions Post ID.
	 */
	function gllr_hide_delete_link( $actions, $tag ) {
		global $gllr_options;
		if ( absint( $gllr_options['default_gallery_category'] ) === $tag->term_id ) {
			unset( $actions['delete'] );
		}
		return $actions;
	}
}

if ( ! function_exists( 'gllr_hide_delete_cb' ) ) {
	/**
	 * Function for hide delete chekbox ( protect default category from deletion )
	 */
	function gllr_hide_delete_cb() {
		global $gllr_options;
		if ( ! isset( $_GET['taxonomy'] ) || sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) !== 'gallery_categories' ) {
			return;
		}
		?>
		<style type="text/css">
			input[value="<?php echo esc_html( $gllr_options['default_gallery_category'] ); ?>"] {
				display: none;
			}
		</style>
		<?php
	}
}

if ( ! function_exists( 'gllr_delete_term' ) ) {
	/**
	 * Function for reassignment categories after delete any category,
	 * Protect default category from deletion
	 *
	 * @param number $tt_id Term taxonomy ID.
	 */
	function gllr_delete_term( $tt_id ) {
		global $post, $tag_ID, $gllr_options;
		$term = get_term_by( 'term_taxonomy_id', $tt_id, 'gallery_categories' );
		if ( ! empty( $term ) ) {
			$terms = get_terms(
				'gallery_categories',
				array(
					'orderby'    => 'count',
					'hide_empty' => 0,
					'fields'     => 'ids',
				)
			);
			if ( ! empty( $terms ) ) {
				$args      = array(
					'post_type'      => $gllr_options['post_type_name'],
					'posts_per_page' => -1,
					'tax_query'      => array(
						array(
							'taxonomy' => 'gallery_categories',
							'field'    => 'id',
							'terms'    => $terms,
							'operator' => 'NOT IN',
						),
					),
				);
				$new_query = new WP_Query( $args );
				if ( $new_query->have_posts() ) {
					$posts = $new_query->posts;
					foreach ( $posts as $post ) {
						wp_set_object_terms( $post->ID, $gllr_options['default_gallery_category'], 'gallery_categories' );
					}
				}
				wp_reset_postdata();
			}
			if ( absint( $gllr_options['default_gallery_category'] ) === $tag_ID ) {
				wp_die( esc_html__( "You can't delete default gallery category.", 'gallery-plugin' ) );
			}
		}
	}
}

if ( ! function_exists( 'gllr_custom_permalinks' ) ) {
	/**
	 * Add custom permalinks for pages with 'gallery' template attribute
	 *
	 * @param array $rules Permalink rules.
	 */
	function gllr_custom_permalinks( $rules ) {
		global $gllr_options;

		$newrules = array();

		if ( empty( $gllr_options ) ) {
			$gllr_options = get_option( 'gllr_options' );
			if ( empty( $gllr_options ) ) {
				gllr_settings();
			}
		}

		if ( ! empty( $gllr_options['page_id_gallery_template'] ) ) {
			$parent = get_post( $gllr_options['page_id_gallery_template'] );
			if ( ! empty( $parent ) ) {
				if ( ! isset( $rules[ '(.+)/' . $parent->post_name . '/([^/]+)/?$' ] ) || ! isset( $rules[ $parent->post_name . '/([^/]+)/?$' ] ) ) {
					$newrules[ '(.+)/' . $parent->post_name . '/([^/]+)/?$' ] = 'index.php?post_type=' . $gllr_options['post_type_name'] . '&name=$matches[2]&posts_per_page=-1';
					$newrules[ $parent->post_name . '/([^/]+)/?$' ]           = 'index.php?post_type=' . $gllr_options['post_type_name'] . '&name=$matches[1]&posts_per_page=-1';
					$newrules[ $parent->post_name . '/page/([^/]+)/?$' ]      = 'index.php?pagename=' . $parent->post_name . '&paged=$matches[1]';

					/* redirect from archives by gallery categories to the galleries list page */
					$newrules['gallery_categories/([^/]+)/page/([^/]+)/?$'] = 'index.php?pagename=' . $parent->post_name . '&gallery_categories=$matches[1]&paged=$matches[2]';
					$newrules['gallery_categories/([^/]+)/?$']              = 'index.php?pagename=' . $parent->post_name . '&gallery_categories=$matches[1]';
				}
			}
		}

		/* fix feed permalink (<link rel="alternate" type="application/rss+xml" ... >) on the attachment single page (if the attachment is Attached to the gallery page) */
		if ( ! empty( $gllr_options['post_type_name'] ) ) {
			$newrules[ $gllr_options['post_type_name'] . '/.+?/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?attachment=$matches[1]&feed=$matches[2]';
			$newrules[ $gllr_options['post_type_name'] . '/.+?/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' ]      = 'index.php?attachment=$matches[1]&feed=$matches[2]';
		}

		return $newrules + $rules;
	}
}

if ( ! function_exists( 'gllr_template_include' ) ) {
	/**
	 * Load a template. Handles template usage so that plugin can use own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder.
	 * overrides in /{theme}/bws-templates/ by default.
	 *
	 * @param mixed $template Template name.
	 * @return string
	 */
	function gllr_template_include( $template ) {
		global $gllr_options, $wp_query;

		if ( function_exists( 'is_embed' ) && is_embed() ) {
			return $template;
		}
		if ( ! is_search() ) {
			$post_type = get_post_type();
			if ( is_single() && $gllr_options['post_type_name'] === $post_type ) {
				$file = 'gallery-single-template.php';
			} elseif (
				( $gllr_options['post_type_name'] === $post_type || isset( $wp_query->query_vars['gallery_categories'] ) ) ||
				( ! empty( $gllr_options['page_id_gallery_template'] ) && is_page( $gllr_options['page_id_gallery_template'] ) )
			) {
				$file = 'gallery-template.php';
			}

			if ( isset( $file ) ) {
				$find     = array( $file, 'bws-templates/' . $file );
				$template = locate_template( $find );

				if ( ! $template ) {
					$template = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' . $file;
				}
			}
		}

		return $template;
	}
}

if ( ! function_exists( 'gllr_template_title' ) ) {
	/**
	 * This function returns title for gallery post type template
	 */
	function gllr_template_title() {
		global $wp_query;
		if ( isset( $wp_query->query_vars['gallery_categories'] ) ) {
			$term = get_term_by( 'slug', $wp_query->query_vars['gallery_categories'], 'gallery_categories' );
			return __( 'Gallery Category', 'gallery-plugin' ) . ':&nbsp;' . $term->name;
		} else {
			return get_the_title();
		}
	}
}

if ( ! function_exists( 'gllr_template_content' ) ) {
	/**
	 * This function prints content for gallery post type template and returns array of pagination args and second query
	 */
	function gllr_template_content() {
		global $post, $wp_query, $request, $gllr_options, $gllr_plugin_info;
		if ( ! $gllr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}
		wp_register_script( 'gllr_js', plugins_url( 'js/frontend_script.js', __FILE__ ), array( 'jquery' ), $gllr_plugin_info['Version'], true );

		if ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
			$paged = get_query_var( 'page' );
		} else {
			$paged = 1;
		}

		$per_page = get_option( 'posts_per_page' );

		$args = array(
			'post_type'      => $gllr_options['post_type_name'],
			'post_status'    => 'publish',
			'orderby'        => $gllr_options['album_order_by'],
			'order'          => $gllr_options['album_order'],
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);

		if ( get_query_var( 'gallery_categories' ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'gallery_categories',
					'field'    => 'slug',
					'terms'    => get_query_var( 'gallery_categories' ),
				),
			);
		}

		$second_query = new WP_Query( $args );
		$request      = $second_query->request;

		printf(
			'<ul class="gllr-list %s">',
			( 'column' === $gllr_options['galleries_layout'] && in_array( $gllr_options['galleries_column_alignment'], array( 'left', 'right', 'center' ) ) ) ? 'gllr-display-column gllr-column-align-' . esc_attr( $gllr_options['galleries_column_alignment'] ) : 'gllr-display-inline'
		);
		if ( $second_query->have_posts() ) {
			/* get width and height for image_size_album */
			if ( 'album-thumb' !== $gllr_options['image_size_album'] ) {
				$width  = absint( get_option( $gllr_options['image_size_album'] . '_size_w' ) );
				$height = absint( get_option( $gllr_options['image_size_album'] . '_size_h' ) );
			} else {
				$width  = $gllr_options['custom_size_px']['album-thumb'][0];
				$height = $gllr_options['custom_size_px']['album-thumb'][1];
			}

			if ( 1 === absint( $gllr_options['cover_border_images'] ) ) {
				$border        = 'border-width: ' . $gllr_options['cover_border_images_width'] . 'px; border-color:' . $gllr_options['cover_border_images_color'] . '; padding:0;';
				$border_images = $gllr_options['cover_border_images_width'] * 2;
			} else {
				$border        = 'padding:0;';
				$border_images = 0;
			}

			while ( $second_query->have_posts() ) {
				$second_query->the_post();
				$attachments    = get_post_thumbnail_id( $post->ID );
				$featured_image = false;

				if ( empty( $attachments ) ) {
					$images_id   = get_post_meta( $post->ID, '_gallery_images', true );
					$attachments = get_posts(
						array(
							'showposts'      => 1,
							'what_to_show'   => 'posts',
							'post_status'    => 'inherit',
							'post_type'      => 'attachment',
							'orderby'        => $gllr_options['order_by'],
							'order'          => $gllr_options['order'],
							'post__in'       => explode( ',', $images_id ),
							'meta_key'       => '_gallery_order_' . $post->ID,
						)
					);
					if ( ! empty( $attachments[0] ) ) {
						$first_attachment = $attachments[0];
						$image_attributes = wp_get_attachment_image_src( $first_attachment->ID, $gllr_options['image_size_album'] );
					} else {
						$image_attributes = array( '' );
					}
				} else {
					$featured_image = wp_get_attachment_image_src( $attachments, $gllr_options['image_size_album'] );
				}
				$featured_image = ( false === $featured_image ) ? $image_attributes : $featured_image;

				$title = get_the_title();
				printf(
					'<li%s>',
					( 'rows' === $gllr_options['galleries_layout'] ) ? wp_kses_post( ' style="width: ' . ( absint( $width ) + absint( $border_images ) ) . 'px;" data-gllr-width="' . ( absint( $width ) + absint( $border_images ) ) . '"' ) : ''
				);
				if ( ! empty( $featured_image[0] ) ) {
					$width       = ! empty( $width ) ? 'width="' . $width . '"' : '';
					$height      = ! empty( $height ) ? 'height="' . $height . '"' : '';
					$width_style = ! empty( $width ) ? 'width:' . $width . 'px;' : '';
					?>
					<a rel="bookmark" href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_html( $title ); ?>">
						<?php
						printf(
							'<img %1$s %2$s style="%3$s %4$s" alt="%5$s" title="%5$s" src="%6$s" /><div class="clear"></div>',
							esc_html( $width ),
							esc_html( $height ),
							esc_html( $width_style ),
							esc_html( $border ),
							esc_html( $title ),
							esc_url( $featured_image[0] )
						);
						?>
						<div class="clear"></div>
					</a>
				<?php } ?>
					<div class="gallery_detail_box">
						<div class="gllr_detail_title"><?php echo esc_html( $title ); ?></div>
						<div class="gllr_detail_excerpt"><?php gllr_the_excerpt_max_charlength( 100 ); ?></div>
						<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( $gllr_options['read_more_link_text'] ); ?></a>
					</div><!-- .gallery_detail_box -->
					<div class="gllr_clear"></div>
				</li>
				<?php
			}
		}
		?>
		</ul>

		<?php
		$count_all_albums = $second_query->found_posts;
		wp_reset_postdata();
		$request = $wp_query->request;
		$pages   = absint( $count_all_albums / $per_page );
		if ( $count_all_albums % $per_page > 0 ) {
			++$pages;
		}
		$range = 100;
		if ( ! $pages ) {
			$pages = 1;
		}
		return array(
			'second_query' => $second_query,
			'pages'        => $pages,
			'paged'        => $paged,
			'per_page'     => $per_page,
			'range'        => $range,
		);
	}
}

if ( ! function_exists( 'gllr_template_pagination' ) ) {
	/**
	 * This function prints pagination for gallery post type template
	 *
	 * @param array $args Arguments for paginations.
	 */
	function gllr_template_pagination( $args ) {
		extract( $args );
		for ( $i = 1; $i <= $pages; $i++ ) {
			if ( 1 !== $pages && ( ! ( $i >= ( $paged + $range + 1 ) || $i <= ( $paged - $range - 1 ) ) || $pages <= $per_page ) ) {
				echo ( $paged === $i ) ? "<span class='page-numbers current'>" . esc_attr( $i ) . '</span>' : "<a class='page-numbers inactive' href='" . esc_url( get_pagenum_link( $i ) ) . "'>" . esc_html( $i ) . '</a>';
			}
		}
	}
}

if ( ! function_exists( 'gllr_single_template_content' ) ) {
	/**
	 * This function prints content for single gallery template
	 */
	function gllr_single_template_content() {
		global $post, $wp_query, $gllr_options, $gllr_vars_for_inline_script, $gllr_plugin_info;
		if ( ! $gllr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}
		wp_register_script( 'gllr_js', plugins_url( 'js/frontend_script.js', __FILE__ ), array( 'jquery' ), $gllr_plugin_info['Version'], true );

		$args         = array(
			'post_type'      => $gllr_options['post_type_name'],
			'post_status'    => 'publish',
			'name'           => $wp_query->query_vars['name'],
			'posts_per_page' => 1,
			'p'              => $wp_query->query_vars['p'],
		);
		$second_query = new WP_Query( $args );

		if ( $second_query->have_posts() ) {

			/* get width and height for image_size_photo */
			if ( 'photo-thumb' !== $gllr_options['image_size_photo'] ) {
				$width  = absint( get_option( $gllr_options['image_size_photo'] . '_size_w' ) );
				$height = absint( get_option( $gllr_options['image_size_photo'] . '_size_h' ) );
			} else {
				$width  = $gllr_options['custom_size_px']['photo-thumb'][0];
				$height = $gllr_options['custom_size_px']['photo-thumb'][1];
			}

			while ( $second_query->have_posts() ) {
				$second_query->the_post();
				?>
				<header class="entry-header  <?php echo ( 'Twenty Twenty' === wp_get_theme()->get( 'Name' ) ) ? 'has-text-align-center' : ''; ?>">
					<h1 class="home_page_title entry-title"><?php the_title(); ?></h1>
				</header>
				<div class="gallery_box_single entry-content">
					<div class="gllr_page_content">
					<?php
					if ( ! post_password_required() ) {
						do_action( 'loop_start', $wp_query );
						if ( '' !== $post->post_content ) {
							the_content();
						}

						$images_id = get_post_meta( $post->ID, '_gallery_images', true );

						$posts = get_posts(
							array(
								'showposts'      => -1,
								'what_to_show'   => 'posts',
								'post_status'    => 'inherit',
								'post_type'      => 'attachment',
								'orderby'        => $gllr_options['order_by'],
								'order'          => $gllr_options['order'],
								'post__in'       => explode( ',', $images_id ),
								'meta_key'       => '_gallery_order_' . $post->ID,
							)
						);
						if ( count( $posts ) > 0 ) {
							if ( '' === $post->post_content ) {
								ob_start();
							}
							$count_image_block = 0;
							?>
							<div class="gallery gllr_grid" data-gllr-columns="<?php echo esc_attr( $gllr_options['custom_image_row_count'] ); ?>" data-gllr-border-width="<?php echo esc_attr( $gllr_options['border_images_width'] ); ?>">
								<?php
								foreach ( $posts as $attachment ) {
									$image_attributes       = wp_get_attachment_image_src( $attachment->ID, $gllr_options['image_size_photo'] );
									$image_attributes_large = wp_get_attachment_image_src( $attachment->ID, 'large' );
									$image_attributes_full  = wp_get_attachment_image_src( $attachment->ID, 'full' );
									if ( 1 === absint( $gllr_options['border_images'] ) ) {
										$border        = 'border-width: ' . $gllr_options['border_images_width'] . 'px; border-color:' . $gllr_options['border_images_color'] . ';border: ' . $gllr_options['border_images_width'] . 'px solid ' . $gllr_options['border_images_color'];
										$border_images = $gllr_options['border_images_width'] * 2;
									} else {
										$border        = '';
										$border_images = 0;
									}
									$url_for_link  = get_post_meta( $attachment->ID, 'gllr_link_url', true );
									$image_text    = get_post_meta( $attachment->ID, 'gllr_image_text', true );
									$image_alt_tag = get_post_meta( $attachment->ID, 'gllr_image_alt_tag', true );

									if ( 0 === $count_image_block % $gllr_options['custom_image_row_count'] ) {
										?>
										<div class="gllr_image_row">
									<?php } ?>
										<div class="gllr_image_block">
											<p style="
											<?php
											if ( $width ) {
												echo 'width:' . ( esc_attr( $width + $border_images ) ) . 'px;';
											} if ( $height ) {
												echo 'height:' . ( esc_attr( $height + $border_images ) ) . 'px;';
											}
											?>
											">
												<?php
												$width_html   = ! empty( $width ) ? 'width="' . $width . '"' : '';
												$height_html  = ! empty( $height ) ? 'height="' . $height . '"' : '';
												$width_style  = ! empty( $width ) ? 'width:' . $width . 'px;' : '';
												$height_style = ! empty( $height ) ? 'height:' . $height . 'px;' : '';
												if ( ! empty( $url_for_link ) ) {
													?>
													<a href="<?php echo esc_url( $url_for_link ); ?>" title="<?php echo esc_html( $image_text ); ?>" target="_blank">
														<?php
														printf(
															'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%7$s" src="%8$s" />',
															esc_html( $width_html ),
															esc_html( $height_html ),
															esc_html( $width_style ),
															esc_html( $height_style ),
															esc_html( $border ),
															esc_html( $image_alt_tag ),
															esc_html( $image_text ),
															esc_url( $image_attributes[0] )
														);
														?>
													</a>
													<?php
												} else {
													if ( 1 !== absint( $gllr_options['enable_image_opening'] ) ) {
														?>
														<a data-fancybox="gallery_fancybox<?php echo 0 === absint( $gllr_options['single_lightbox_for_multiple_galleries'] ) ? '_' . esc_attr( $post->ID ) : ''; ?>" href="<?php echo esc_url( $image_attributes_large[0] ); ?>" title="<?php echo esc_html( $image_text ); ?>" >
															<?php
															printf(
																'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%7$s" src="%8$s" rel="#" />',
																esc_html( $width_html ),
																esc_html( $height_html ),
																esc_html( $width_style ),
																esc_html( $height_style ),
																esc_html( $border ),
																esc_html( $image_alt_tag ),
																esc_html( $image_text ),
																esc_url( $image_attributes_full[0] )
															);
															?>
														</a>
													<?php } else { ?>
														<a data-fancybox="gallery_fancybox<?php echo 0 === absint( $gllr_options['single_lightbox_for_multiple_galleries'] ) ? '_' . esc_attr( $post->ID ) : ''; ?>" href="#" style="pointer-events: none;" title="<?php echo esc_html( $image_text ); ?>" >
															<?php
															printf(
																'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%7$s" src="%8$s" rel="#" />',
																esc_html( $width_html ),
																esc_html( $height_html ),
																esc_html( $width_style ),
																esc_html( $height_style ),
																esc_html( $border ),
																esc_html( $image_alt_tag ),
																esc_html( $image_text ),
																esc_url( $image_attributes[0] )
															);
															?>
														</a>
														<?php
													}
												}
												?>
											</p>
											<?php if ( 1 === absint( $gllr_options['image_text'] ) ) { ?>
												<div 
												<?php
												if ( $width ) {
													echo 'style="width:' . ( esc_attr( $width + $border_images ) ) . 'px;"';
												}
												?>
												class="gllr_single_image_text gllr_single_image_text_under"><?php echo esc_html( $image_text ); ?>&nbsp;</div>
											<?php } ?>
										</div><!-- .gllr_image_block -->
									<?php if ( absint( $gllr_options['custom_image_row_count'] ) - 1 === $count_image_block % $gllr_options['custom_image_row_count'] ) { ?>
											<div class="clear"></div>
										</div><!-- .gllr_image_row -->
										<?php
									}
									$count_image_block++;
								}
								if ( $count_image_block > 0 && 0 !== $count_image_block % $gllr_options['custom_image_row_count'] ) {
									?>
									</div><!-- .gllr_image_row -->
								<?php } ?>
							</div><!-- .gallery.clearfix -->
							<?php
							if ( '' === $post->post_content ) {
								$output = ob_get_contents();
								ob_end_clean();
								echo wp_kses_post( apply_filters( 'the_content', $output ) );
							}
						}
						if ( 1 === absint( $gllr_options['return_link'] ) ) {
							if ( empty( $gllr_options['return_link_url'] ) ) {
								if ( ! empty( $gllr_options['page_id_gallery_template'] ) ) {
									?>
									<div class="gllr_clear"></div>
									<div class="return_link gllr_return_link"><a href="<?php echo esc_url( get_permalink( $gllr_options['page_id_gallery_template'] ) ); ?>"><?php echo esc_html( $gllr_options['return_link_text'] ); ?></a></div>
									<?php
								}
							} else {
								?>
								<div class="gllr_clear"></div>
								<div class="return_link gllr_return_link"><a href="<?php echo esc_url( $gllr_options['return_link_url'] ); ?>"><?php echo esc_html( $gllr_options['return_link_text'] ); ?></a></div>
								<?php
							}
						}
						do_action( 'loop_end', $wp_query );
						if ( $gllr_options['enable_lightbox'] ) {

							$gllr_vars_for_inline_script['single_script'][] = apply_filters( 'gllr_options_for_inline_script', array( 'post_id' => $post->ID ) );

							if ( defined( 'BWS_ENQUEUE_ALL_SCRIPTS' ) ) {
								gllr_echo_inline_script();
							}
						}
					} else {
						?>
						<p><?php echo get_the_password_form(); ?></p>
					<?php } ?>
					</div><!-- .gllr_page_content -->
				</div><!-- .gallery_box_single -->
				<?php
			}
		} else {
			?>
			<div class="gallery_box_single">
				<p class="not_found"><?php esc_html_e( 'Sorry, nothing found.', 'gallery-plugin' ); ?></p>
			</div><!-- .gallery_box_single -->
			<?php
		}
	}
}

if ( ! function_exists( 'gllr_add_pdf_print_content' ) ) {
	/**
	 * This function returns custom content with images for PDF&Print plugin in Gallery post
	 *
	 * @param string $content Content for print button.
	 * @param array  $params  Params for content of buttons.
	 */
	function gllr_add_pdf_print_content( $content, $params = '' ) {
		global $post, $wp_query, $gllr_options;
		$current_post_type = get_post_type();
		$custom_content    = '';

		/* Displaying PDF&PRINT custom content for single gallery */
		if ( $gllr_options['post_type_name'] === $current_post_type && ! get_query_var( 'gallery_categories' ) ) {

			if ( 'photo-thumb' !== $gllr_options['image_size_photo'] ) {
				$width  = absint( get_option( $gllr_options['image_size_photo'] . '_size_w' ) );
				$height = absint( get_option( $gllr_options['image_size_photo'] . '_size_h' ) );
			} else {
				$width  = $gllr_options['custom_size_px']['photo-thumb'][0];
				$height = $gllr_options['custom_size_px']['photo-thumb'][1];
			}

			$custom_content .= "
				<style type='text/css'>
					.gllr_grid,
					.gllr_grid td {
						border: none;
						vertical-align: top;
					}
					.gllr_grid {
						table-layout: fixed;
						margin: 0 auto;
					}
					.gllr_grid td img {
						width: {$width}px;
						height: {$height}px;
					}
				</style>\n";

			if ( 1 === absint( $gllr_options['border_images'] ) ) {
				$image_style = "border: {$gllr_options['border_images_width']}px solid {$gllr_options['border_images_color']};";
			} else {
				$image_style = 'border: none;';
			}
			$image_style .= 'margin: 0;';

			$args         = array(
				'post_type'      => $gllr_options['post_type_name'],
				'post_status'    => 'publish',
				'name'           => $wp_query->query_vars['name'],
				'posts_per_page' => 1,
			);
			$second_query = new WP_Query( $args );
			if ( $second_query->have_posts() ) {
				while ( $second_query->have_posts() ) {
					$second_query->the_post();
					$custom_content .= '<div class="gallery_box_single entry-content">';
					if ( ! post_password_required() ) {
						$images_id = get_post_meta( $post->ID, '_gallery_images', true );
						$posts     = get_posts(
							array(
								'showposts'      => -1,
								'what_to_show'   => 'posts',
								'post_status'    => 'inherit',
								'post_type'      => 'attachment',
								'orderby'        => $gllr_options['order_by'],
								'order'          => $gllr_options['order'],
								'post__in'       => explode( ',', $images_id ),
								'meta_key'       => '_gallery_order_' . $post->ID,
							)
						);

						if ( count( $posts ) > 0 ) {
							$count_image_block = 0;
							$custom_content   .= '<table class="gallery clearfix gllr_grid">';
							foreach ( $posts as $attachment ) {
								$image_attributes = wp_get_attachment_image_src( $attachment->ID, $gllr_options['image_size_photo'] );
								if ( 0 === $count_image_block % $gllr_options['custom_image_row_count'] ) {
										$custom_content .= '<tr>';
								}
								$custom_content .= '<td class="gllr_image_block">
									<div>
										<img src="' . $image_attributes[0] . '" style="' . $image_style . '" />
									</div>';
								if ( 1 === absint( $gllr_options['image_text'] ) ) {
									$custom_content .= '<div class="gllr_single_image_text gllr_single_image_text_under">' . get_post_meta( $attachment->ID, 'gllr_image_text', true ) . '</div>';
								}
								$custom_content .= "</td><!-- .gllr_image_block -->\n";
								if ( $count_image_block % $gllr_options['custom_image_row_count'] === $gllr_options['custom_image_row_count'] - 1 ) {
									$custom_content .= "</tr>\n";
								}
								$count_image_block++;
							}
							if ( $count_image_block > 0 && 0 !== $count_image_block % $gllr_options['custom_image_row_count'] ) {
								while ( 0 !== $count_image_block % $gllr_options['custom_image_row_count'] ) {
									$custom_content .= '<td class="gllr_image_block"></td>';
									$count_image_block++;
								}
								$custom_content .= '</tr>';
							}
								$custom_content .= '</table><!-- .gallery.clearfix -->';
						}
					}
					$custom_content .= '</div><!-- .gallery_box_single -->';
				}
			} else {
				$custom_content .= '<div class="gallery_box_single">
					<p class="not_found">' . __( 'Sorry, nothing found.', 'gallery-plugin' ) . '</p>
				</div><!-- .gallery_box_single -->';
			}
			$custom_content .= '<div class="gllr_clear"></div>';
		} elseif ( absint( $gllr_options['page_id_gallery_template'] ) === $post->ID ) {
			if ( 'album-thumb' !== $gllr_options['image_size_album'] ) {
				$width  = absint( get_option( $gllr_options['image_size_album'] . '_size_w' ) );
				$height = absint( get_option( $gllr_options['image_size_album'] . '_size_h' ) );
			} else {
				$width  = $gllr_options['custom_size_px']['album-thumb'][0];
				$height = $gllr_options['custom_size_px']['album-thumb'][1];
			}
			/* Displaying PDF&PRINT custom content for gallery pro template */
			$custom_content .= "<style type='text/css'>
				.gllr-list {
					list-style: none;
					margin-left: 0;
					padding: 0;
				}
				.gllr-list li > a > img {
					width: {$width}px;
					height: {$height}px;
					margin: 10px 0;
				}
				#gallery_pagination > span,
				#gallery_pagination > a {
					display: inline-block;
					padding: 5px;
				}
			</style>";
			$custom_content .= '<ul class="gllr-list">';
				global $request;
			if ( get_query_var( 'paged' ) ) {
				$paged = get_query_var( 'paged' );
			} elseif ( get_query_var( 'page' ) ) {
				$paged = get_query_var( 'page' );
			} else {
				$paged = 1;
			}

			$per_page  = get_option( 'posts_per_page' );
			$showitems = $per_page;

			$args = array(
				'post_type'      => $gllr_options['post_type_name'],
				'post_status'    => 'publish',
				'orderby'        => $gllr_options['album_order_by'],
				'order'          => $gllr_options['album_order'],
				'posts_per_page' => $per_page,
				'paged'          => $paged,
			);
			if ( isset( $wp_query->query_vars['gallery_categories'] ) && ( ! empty( $wp_query->query_vars['gallery_categories'] ) ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'gallery_categories',
						'field'    => 'slug',
						'terms'    => $wp_query->query_vars['gallery_categories'],
					),
				);
			}
			$second_query = new WP_Query( $args );
			$request      = $second_query->request;

			if ( $second_query->have_posts() ) {
				while ( $second_query->have_posts() ) {
					$second_query->the_post();
					$attachments = get_post_thumbnail_id( $post->ID );
					if ( empty( $attachments ) ) {
						$images_id   = get_post_meta( $post->ID, '_gallery_images', true );
						$attachments = get_posts(
							array(
								'showposts'      => 1,
								'what_to_show'   => 'posts',
								'post_status'    => 'inherit',
								'post_type'      => 'attachment',
								'orderby'        => $gllr_options['order_by'],
								'order'          => $gllr_options['order'],
								'post__in'       => explode( ',', $images_id ),
								'meta_key'       => '_gallery_order_' . $post->ID,
							)
						);
						if ( ! empty( $attachments[0] ) ) {
							$first_attachment = $attachments[0];
							$image_attributes = wp_get_attachment_image_src( $first_attachment->ID, $gllr_options['image_size_album'] );
						} else {
							$image_attributes = array( '' );
						}
					} else {
						$image_attributes = wp_get_attachment_image_src( $attachments, $gllr_options['image_size_album'] );
					}
					if ( 1 === absint( $gllr_options['cover_border_images'] ) ) {
						$border = 'border: ' . $gllr_options['cover_border_images_width'] . 'px solid ' . $gllr_options['cover_border_images_color'] . '; padding:0;';
					} else {
						$border = 'padding:0;';
					}
					$custom_content .= '<li>';
					$excerpt         = wp_strip_all_tags( get_the_content() );
					if ( strlen( $excerpt ) > 100 ) {
						$excerpt = substr( $excerpt, 0, strripos( substr( $excerpt, 0, 100 ), ' ' ) ) . '...';
					}
					$custom_content .= '<img width="' . $width . '" height="' . $height . '" style="width:' . $width . 'px; height:' . $height . 'px;' . $border . '" src="' . $image_attributes[0] . '" />
						<div class="gallery_detail_box">
							<div class="gllr_detail_title">' . get_the_title() . '</div>
							<div class="gllr_detail_excerpt">' . $excerpt . '</div>';
					if ( 0 === absint( $gllr_options['hide_single_gallery'] ) ) {
						$custom_content .= '<a href="' . get_permalink() . '">' . $gllr_options['read_more_link_text'] . '</a>';
					}
					$custom_content .= '</div><!-- .gallery_detail_box -->
						<div class="gllr_clear"></div>
					</li>';
				}
			}
			$custom_content .= '</ul>';
		}

		/* Displaying PDF&PRINT custom content for shortcode */
		if ( ! empty( $params ) && 'array' === gettype( $params ) ) {
			extract(
				shortcode_atts(
					array(
						'id'      => '',
						'display' => 'full',
						'cat_id'  => '',
					),
					$params
				)
			);

			$old_wp_query = $wp_query;

			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( ! empty( $cat_id ) ) {
				global $post, $wp_query;
				$term = get_term( $cat_id, 'gallery_categories' );
				if ( ! empty( $term ) ) {
					$args            = array(
						'post_type'          => $gllr_options['post_type_name'],
						'post_status'        => 'publish',
						'posts_per_page'     => -1,
						'gallery_categories' => $term->slug,
						'orderby'            => $gllr_options['album_order_by'],
						'order'              => $gllr_options['album_order'],
					);
					$second_query    = new WP_Query( $args );
					$custom_content .= "<style type='text/css'>
						.gallery_box ul {
							list-style: none outside none !important;
							margin: 0;
							padding: 0;
						}
						.gallery_box ul li {
							margin: 0 0 20px;
						}
						.gallery_box li img {
							margin: 0 10px 10px 0;
							float: left;
							box-sizing: content-box;
							-moz-box-sizing: content-box;
							-webkit-box-sizing: content-box;
						}
						.rtl .gallery_box li img {
							margin: 0 0 10px 10px;
							float: right;
						}
						.gallery_detail_box {
							clear: both;
							float: left;
						}
						.rtl .gallery_detail_box {
							float: right;
						}
						.gllr_clear {
							clear: both;
							height: 0;
						}
					</style>";
					$custom_content .= '<div class="gallery_box">
						<ul>';
					if ( $second_query->have_posts() ) {
						if ( 'album-thumb' !== $gllr_options['image_size_album'] ) {
							$width  = absint( get_option( $gllr_options['image_size_album'] . '_size_w' ) );
							$height = absint( get_option( $gllr_options['image_size_album'] . '_size_h' ) );
						} else {
							$width  = $gllr_options['custom_size_px']['album-thumb'][0];
							$height = $gllr_options['custom_size_px']['album-thumb'][1];
						}
						if ( 1 === absint( $gllr_options['cover_border_images'] ) ) {
							$border = 'border-width: ' . $gllr_options['cover_border_images_width'] . 'px; border-color: ' . $gllr_options['cover_border_images_color'] . ';border: ' . $gllr_options['cover_border_images_width'] . 'px solid ' . $gllr_options['cover_border_images_color'];
						} else {
							$border = '';
						}

						while ( $second_query->have_posts() ) {
							$second_query->the_post();
							$attachments = get_post_thumbnail_id( $post->ID );
							if ( empty( $attachments ) ) {
								$images_id   = get_post_meta( $post->ID, '_gallery_images', true );
								$attachments = get_posts(
									array(
										'showposts'      => 1,
										'what_to_show'   => 'posts',
										'post_status'    => 'inherit',
										'post_type'      => 'attachment',
										'orderby'        => $gllr_options['order_by'],
										'order'          => $gllr_options['order'],
										'post__in'       => explode( ',', $images_id ),
										'meta_key'       => '_gallery_order_' . $post->ID,
									)
								);
								if ( ! empty( $attachments[0] ) ) {
									$first_attachment = $attachments[0];
									$image_attributes = wp_get_attachment_image_src( $first_attachment->ID, $gllr_options['image_size_album'] );
								} else {
									$image_attributes = array( '' );
								}
							} else {
								$image_attributes = wp_get_attachment_image_src( $attachments, $gllr_options['image_size_album'] );
							}
							$excerpt = wp_strip_all_tags( get_the_content() );
							if ( strlen( $excerpt ) > 100 ) {
								$excerpt = substr( $excerpt, 0, strripos( substr( $excerpt, 0, 100 ), ' ' ) ) . '...';
							}
							$custom_content .= '<li>
								<img width="' . $width . '" height="' . $height . '" style="width:' . $width . 'px; height:' . $height . 'px;' . $border . '" src="' . $image_attributes[0] . '" />
								<div class="gallery_detail_box">
									<div class="gllr_detail_title">' . get_the_title() . '</div>
									<div class="gllr_detail_excerpt">' . $excerpt . '</div>
									<a href="' . get_permalink( $post->ID ) . '">' . $gllr_options['read_more_link_text'] . '</a>
								</div><!-- .gallery_detail_box -->
								<div class="gllr_clear"></div>
							</li>';
						}
					}
						$custom_content .= '</ul>
					</div><!-- .gallery_box -->';
				}
			} else {
				$args         = array(
					'post_type'      => $gllr_options['post_type_name'],
					'post_status'    => 'publish',
					'p'              => $id,
					'posts_per_page' => 1,
				);
				$second_query = new WP_Query( $args );

				if ( 'short' === $display ) {
					if ( $second_query->have_posts() ) {
						$second_query->the_post();
						$attachments = get_post_thumbnail_id( $post->ID );
						if ( empty( $attachments ) ) {
							$images_id   = get_post_meta( $post->ID, '_gallery_images', true );
							$attachments = get_posts(
								array(
									'showposts'      => 1,
									'what_to_show'   => 'posts',
									'post_status'    => 'inherit',
									'post_type'      => 'attachment',
									'orderby'        => $gllr_options['order_by'],
									'order'          => $gllr_options['order'],
									'post__in'       => explode( ',', $images_id ),
									'meta_key'       => '_gallery_order_' . $post->ID,
								)
							);
							if ( ! empty( $attachments[0] ) ) {
								$first_attachment          = $attachments[0];
								$image_attributes_featured = wp_get_attachment_image_src( $first_attachment->ID, $gllr_options['image_size_album'] );
							} else {
								$image_attributes_featured = array( '' );
							}
						} else {
							$image_attributes_featured = wp_get_attachment_image_src( $attachments, $gllr_options['image_size_album'] );
						}

						if ( 1 === absint( $gllr_options['cover_border_images'] ) ) {
							$border = 'border-width: ' . $gllr_options['cover_border_images_width'] . 'px; border-color: ' . $gllr_options['cover_border_images_color'] . ';border: ' . $gllr_options['cover_border_images_width'] . 'px solid ' . $gllr_options['cover_border_images_color'];
						} else {
							$border = '';
						}

						$excerpt = wp_strip_all_tags( get_the_content() );
						if ( strlen( $excerpt ) > 100 ) {
							$excerpt = substr( $excerpt, 0, strripos( substr( $excerpt, 0, 100 ), ' ' ) ) . '...';
						}
						if ( 'album-thumb' !== $gllr_options['image_size_album'] ) {
							$width  = absint( get_option( $gllr_options['image_size_album'] . '_size_w' ) );
							$height = absint( get_option( $gllr_options['image_size_album'] . '_size_h' ) );
						} else {
							$width  = $gllr_options['custom_size_px']['album-thumb'][0];
							$height = $gllr_options['custom_size_px']['album-thumb'][1];
						}
						$custom_content .= '<div class="gallery_box">';
						$custom_content .= "<img width=\"{$width}\" height=\"{$height}\" style=\"width:{$width}px; height:{$height}px; {$border}\" src=\"{$image_attributes_featured[0]}\" />";
						$custom_content .= '<div class="gallery_detail_box">
												<div class="gllr_detail_title">' . get_the_title() . '</div>
												<p class="gllr_detail_excerpt">' . $excerpt . '</p>';
						if ( 0 === absint( $gllr_options['hide_single_gallery'] ) ) {
							$custom_content .= '<a href="' . get_permalink() . '">' . $gllr_options['read_more_link_text'] . '</a>';
						}
						$custom_content .= '</div><!-- .gallery_detail_box -->
										<div class=\"gllr_clear\"></div>
									</div>';
					}
				} else {
					$custom_content .= '<div class="gallery_box_single">';
					if ( $second_query->have_posts() ) {
						if ( 'photo-thumb' !== $gllr_options['image_size_photo'] ) {
							$width  = absint( get_option( $gllr_options['image_size_photo'] . '_size_w' ) );
							$height = absint( get_option( $gllr_options['image_size_photo'] . '_size_h' ) );
						} else {
							$width  = $gllr_options['custom_size_px']['photo-thumb'][0];
							$height = $gllr_options['custom_size_px']['photo-thumb'][1];
						}
						while ( $second_query->have_posts() ) {
							$second_query->the_post();
							$custom_content .= do_shortcode( get_the_content() );

							$images_id = get_post_meta( $post->ID, '_gallery_images', true );

							$posts = get_posts(
								array(
									'what_to_show'   => 'posts',
									'post_status'    => 'inherit',
									'post_type'      => 'attachment',
									'orderby'        => $gllr_options['order_by'],
									'order'          => $gllr_options['order'],
									'post__in'       => explode( ',', $images_id ),
									'meta_key'       => '_gallery_order_' . $post->ID,
									'posts_per_page' => -1,
								)
							);

							if ( count( $posts ) > 0 ) {
								$count_image_block = 0;

								if ( 1 === absint( $gllr_options['border_images'] ) ) {
									$border_images_width = $gllr_options['border_images_width'];
									$border              = 'border-width: ' . $border_images_width . 'px; border-color: ' . $gllr_options['border_images_color'] . ';border: ' . $border_images_width . 'px solid ' . $gllr_options['border_images_color'];
									$border_images       = $border_images_width * 2;
								} else {
									$border_images_width = 0;
									$border              = '';
									$border_images       = 0;
								}

								$custom_content .= "
									<style type='text/css'>
										.gllr_table,
										.gllr_table td {
											border: none;
											vertical-align: top;
										}
										.gllr_table {
											table-layout: fixed;
											margin: 0 auto;
										}
										.gllr_table td img {
											width: {$width}px;
											height: {$height}px;
										}
									</style>\n";

								if ( 1 === absint( $gllr_options['border_images'] ) ) {
									$image_style = "border: {$gllr_options['border_images_width']}px solid {$gllr_options['border_images_color']};";
								} else {
									$image_style = 'border: none;';
								}
								$image_style .= 'margin: 0;';

								$custom_content .= '<table class="gallery gllr_table" data-gllr-columns="' . $gllr_options['custom_image_row_count'] . '" data-gllr-border-width="' . $border_images_width . '"' . ( ( 1 === absint( $gllr_options['image_text'] ) ) ? 'data-image-text-position="' . $gllr_options['image_text_position'] . '"' : '' ) . '>';

								foreach ( $posts as $attachment ) {
									$image_attributes = wp_get_attachment_image_src( $attachment->ID, $gllr_options['image_size_photo'] );
									$title            = get_post_meta( $attachment->ID, 'gllr_image_text', true );
									if ( 0 === $count_image_block % $gllr_options['custom_image_row_count'] ) {
										$custom_content .= '<tr>';
									}
									$custom_content     .= '<td class="gllr_image_block">';
									$custom_content     .= '<div>';
										$custom_content .= '<img src="' . $image_attributes[0] . '" style="' . $image_style . '" />';
									$custom_content     .= '</div>';
									if ( 1 === absint( $gllr_options['image_text'] ) && '' !== $title ) {
										$custom_content .= '<div style="width:' . ( $width + $border_images ) . 'px;" class="gllr_single_image_text gllr_single_image_text_under">' . $title . '</div>';
									}
									$custom_content .= '</td>';
									if ( $count_image_block % $gllr_options['custom_image_row_count'] === $gllr_options['custom_image_row_count'] - 1 ) {
										$custom_content .= '</tr>';
									}
									$count_image_block++;
								}
								if ( $count_image_block > 0 && 0 !== $count_image_block % $gllr_options['custom_image_row_count'] ) {
									$custom_content .= '</tr>';
								}
									$custom_content .= '</table>';
							}
						}
					}
					$custom_content .= '</div><!-- .gallery_box_single -->';
					$custom_content .= '<div class="gllr_clear"></div>';
				}
			}

			wp_reset_postdata();
			$wp_query = $old_wp_query;
		}

		return $content . $custom_content;
	}
}

if ( ! function_exists( 'gllr_change_columns' ) ) {
	/**
	 * Change the columns for the edit CPT screen
	 *
	 * @param array $cols Columns.
	 * @return array $cols
	 */
	function gllr_change_columns( $cols ) {
		$cols = array(
			'cb'                 => '<input type="checkbox" />',
			'featured-image'     => __( 'Featured Image', 'gallery-plugin' ),
			'title'              => __( 'Title', 'gallery-plugin' ),
			'images'             => __( 'Images', 'gallery-plugin' ),
			'shortcode'          => __( 'Shortcode', 'gallery-plugin' ),
			'gallery_categories' => __( 'Gallery Categories', 'gallery-plugin' ),
			'author'             => __( 'Author', 'gallery-plugin' ),
			'date'               => __( 'Date', 'gallery-plugin' ),
		);
		return $cols;
	}
}

if ( ! function_exists( 'gllr_custom_columns' ) ) {
	/**
	 * Add custom columns
	 *
	 * @param string $column Column name.
	 * @param number $post_id Posy id.
	 */
	function gllr_custom_columns( $column, $post_id ) {
		global $wpdb, $gllr_options;
		$post = get_post( $post_id );
		switch ( $column ) {
			case 'shortcode':
				bws_shortcode_output( '[print_gllr id=' . $post->ID . ']' );
				echo '<br/>';
				bws_shortcode_output( '[print_gllr id=' . $post->ID . ' display=short]' );
				break;
			case 'featured-image':
				echo get_the_post_thumbnail( $post->ID, array( 65, 65 ) );
				break;
			case 'images':
				$images_id = get_post_meta( $post->ID, '_gallery_images', true );
				if ( empty( $images_id ) ) {
					echo 0;
				} else {
					echo absint( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->posts . ' WHERE ID IN( ' . $images_id . ' )' ) );
				}
				break;
			case 'gallery_categories':
				$terms = get_the_terms( $post->ID, 'gallery_categories' );
				$out   = '';
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$out .= '<a href="edit.php?post_type=' . $gllr_options['post_type_name'] . '&amp;gallery_categories=' . $term->slug . '">' . $term->name . '</a><br />';
					}
					echo wp_kses_post( trim( $out ) );
				}
				break;
		}
	}
}

if ( ! function_exists( 'gllr_manage_pre_get_posts' ) ) {
	/**
	 * Change order by and order for gallery post type
	 *
	 * @param object $query WPDB query object.
	 */
	function gllr_manage_pre_get_posts( $query ) {
		global $gllr_options;

		if ( is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === $gllr_options['post_type_name'] && ! isset( $_GET['order'] ) ) {
			$query->set( 'orderby', $gllr_options['album_order_by'] );
			$query->set( 'order', $gllr_options['album_order'] );
		}
	}
}

if ( ! function_exists( 'gllr_the_excerpt_max_charlength' ) ) {
	/**
	 * Change excerpt length for gallery post type
	 *
	 * @param number $charlength Excerpt length.
	 */
	function gllr_the_excerpt_max_charlength( $charlength ) {
		$excerpt = wp_strip_all_tags( get_the_content() );
		$charlength ++;
		if ( strlen( $excerpt ) > $charlength ) {
			$subex   = substr( $excerpt, 0, $charlength - 5 );
			$exwords = explode( ' ', $subex );
			$excut   = - ( strlen( $exwords[ count( $exwords ) - 1 ] ) );
			if ( $excut < 0 ) {
				echo esc_html( substr( $subex, 0, $excut ) );
			} else {
				echo esc_html( $subex );
			}
			echo '...';
		} else {
			echo esc_html( $excerpt );
		}
	}
}

if ( ! function_exists( 'gllr_settings_page' ) ) {
	/**
	 * Add Settings page for gallery post type
	 */
	function gllr_settings_page() {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php';
		}
		require_once dirname( __FILE__ ) . '/includes/class-gllr-settings.php';
		$page = new Gllr_Settings_Tabs( plugin_basename( __FILE__ ) );
		if ( method_exists( $page, 'add_request_feature' ) ) {
			$page->add_request_feature();
		}
		?>
		<div class="wrap">
			<h1><?php printf( esc_html__( '%s Settings', 'gallery-plugin' ), 'Gallery' ); ?></h1>
			<?php $page->display_content(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'gllr_content_save_pre' ) ) {
	/**
	 * Remove shortcode from the content of the same gallery
	 *
	 * @param string $content Content for save.
	 */
	function gllr_content_save_pre( $content ) {
		global $post, $gllr_options;

		if ( isset( $post ) && $gllr_options['post_type_name'] === $post->post_type && ! wp_is_post_revision( $post->ID ) && ! empty( $_POST ) ) {
			/* remove shortcode */
			$content = preg_replace( '/\[print_gllr id=' . $post->ID . '( display=short){0,1}\]/', '', $content );
		}
		return $content;
	}
}

if ( ! function_exists( 'gllr_register_plugin_links' ) ) {
	/**
	 * Function to add links to the plugin description on the plugins page
	 *
	 * @param array $links All links.
	 * @param file  $file File name.
	 * @return array
	 */
	function gllr_register_plugin_links( $links, $file ) {
		global $gllr_options;
		$base = plugin_basename( __FILE__ );
		if ( $file === $base ) {
			if ( ! is_network_admin() && ! is_plugin_active( 'gallery-plugin-pro/gallery-plugin-pro.php' ) ) {
				$links[] = '<a href="edit.php?post_type=' . $gllr_options['post_type_name'] . '&page=gallery-plugin.php">' . __( 'Settings', 'gallery-plugin' ) . '</a>';
			}
			$links[] = '<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538899" target="_blank">' . __( 'FAQ', 'gallery-plugin' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'gallery-plugin' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'gllr_plugin_action_links' ) ) {
	/**
	 * Function to add action links to the plugin menu
	 *
	 * @param array $links All links.
	 * @param file  $file File name.
	 * @return array
	 */
	function gllr_plugin_action_links( $links, $file ) {
		global $gllr_options;
		if ( ! is_network_admin() && ! is_plugin_active( 'gallery-plugin-pro/gallery-plugin-pro.php' ) ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}

			if ( $file === $this_plugin ) {
				$settings_link = '<a href="edit.php?post_type=' . $gllr_options['post_type_name'] . '&page=gallery-plugin.php">' . __( 'Settings', 'gallery-plugin' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

if ( ! function_exists( 'gllr_admin_head' ) ) {
	/**
	 * Enqueue script and styles
	 */
	function gllr_admin_head() {
		global $pagenow, $gllr_options, $post, $gllr_plugin_info;
		if ( empty( $gllr_plugin_inf ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}

		wp_enqueue_style( 'gllr_stylesheet', plugins_url( 'css/style.css', __FILE__ ), array(), $gllr_plugin_info['Version'] );
		wp_enqueue_script( 'jquery' );

		if ( isset( $_GET['page'] ) && 'gallery-plugin.php' === $_GET['page'] ) {
			wp_enqueue_style( 'wp-color-picker' );
			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
			wp_enqueue_script( 'gllr_script', plugins_url( 'js/script.js', __FILE__ ), array( 'wp-color-picker' ), $gllr_plugin_info['Version'], true );
			wp_localize_script(
				'gllr_script',
				'gllr_vars',
				array(
					'gllr_nonce'         => wp_create_nonce( plugin_basename( __FILE__ ), 'gllr_ajax_nonce_field' ),
					'update_img_message' => __( 'Updating images...', 'gallery-plugin' ) . '<img class="gllr_loader" src="' . plugins_url( 'images/ajax-loader.gif', __FILE__ ) . '" alt="" />',
					'not_found_img_info' => __( 'No images found.', 'gallery-plugin' ),
					'img_success'        => __( 'All images were updated.', 'gallery-plugin' ),
					'img_error'          => __( 'Error.', 'gallery-plugin' ),
				)
			);
		} elseif (
			( 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] && get_post_type( get_the_ID() ) === $gllr_options['post_type_name'] ) ||
			( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && $_GET['post_type'] === $gllr_options['post_type_name'] ) ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			bws_enqueue_settings_scripts();
			wp_enqueue_script( 'gllr_script', plugins_url( 'js/script.js', __FILE__ ), array(), $gllr_plugin_info['Version'], true );
			wp_localize_script(
				'gllr_script',
				'gllr_vars',
				array(
					'gllr_nonce'             => wp_create_nonce( plugin_basename( __FILE__ ), 'gllr_ajax_nonce_field' ),
					'gllr_add_nonce'         => wp_create_nonce( plugin_basename( __FILE__ ), 'gllr_ajax_add_nonce' ),
					'warnBulkDelete'         => __( "You are about to remove these items from this gallery.\n 'Cancel' to stop, 'OK' to delete.", 'gallery-plugin' ),
					'warnSingleDelete'       => __( "You are about to remove this image from the gallery.\n 'Cancel' to stop, 'OK' to delete.", 'gallery-plugin' ),
					'confirm_update_gallery' => __( 'Switching to another mode, all unsaved data will be lost. Save data before switching?', 'gallery-plugin' ),
					'wp_media_title'         => __( 'Insert Media', 'gallery-plugin' ),
					'wp_media_button'        => __( 'Insert', 'gallery-plugin' ),
					'export_message'         => sprintf( '%s <a href="admin.php?page=slider.php">%s</a>', __( 'A new slider is added.', 'gallery-plugin' ), __( 'Edit slider', 'gallery-plugin' ) ),
					'post'                   => $post->ID,
					'title'                  => $post->post_title,
				)
			);
		}
	}
}


if ( ! function_exists( 'gllr_enqueue_scripts' ) ) {
	/**
	 * Enqueue script and styles
	 */
	function gllr_enqueue_scripts() {
		global $gllr_options, $post, $wp_scripts;

		$post_type          = get_post_type();
		$is_gallery_on_page = ( ( is_single() && $gllr_options['post_type_name'] === $post_type ) || ( isset( $post ) && has_shortcode( $post->post_content, 'print_gllr' ) ) );

		/* disable 3rd-party fancybox */
		if ( $gllr_options['disable_foreing_fancybox'] && $is_gallery_on_page ) {
			foreach ( $wp_scripts->queue as $script ) {
				if ( preg_match( '/\.*fancybox\.*/', $script ) ) {
					wp_dequeue_script( $script );
					wp_deregister_script( $script );
				}
			}
		}

	}
}

if ( ! function_exists( 'gllr_wp_head' ) ) {
	/**
	 * Enqueue script and styles
	 */
	function gllr_wp_head() {
		global $gllr_options, $gllr_plugin_info;
		if ( empty( $gllr_plugin_inf ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}

		wp_enqueue_style( 'gllr_stylesheet', plugins_url( 'css/frontend_style.css', __FILE__ ), array( 'dashicons' ), $gllr_plugin_info['Version'] );
		do_action( 'gllr_include_plus_style' );

		if ( $gllr_options['enable_lightbox'] ) {
			wp_enqueue_style( 'gllr_fancybox_stylesheet', plugins_url( 'fancybox/jquery.fancybox.min.css', __FILE__ ), array(), $gllr_plugin_info['Version'] );
			/* Start ios */
			$script = '
			( function( $ ){
				$( document ).ready( function() {
					$( \'#fancybox-overlay\' ).css( {
						\'width\' : $( document ).width()
					} );
				} );
			} )( jQuery );
			';
			/* End ios */
			wp_register_script( 'gllr_enable_lightbox_ios', '', array(), $gllr_plugin_info['Version'], true );
			wp_enqueue_script( 'gllr_enable_lightbox_ios' );
			wp_add_inline_script( 'gllr_enable_lightbox_ios', sprintf( $script ) );
		}
		if ( 1 === absint( $gllr_options['image_text'] ) ) {
			?>
			<style type="text/css">
				.gllr_image_row {
					clear: both;
				}
			</style>
			<?php
		}
	}
}

if ( ! function_exists( 'gllr_wp_footer' ) ) {
	/**
	 * Function for script enqueing
	 */
	function gllr_wp_footer() {
		global $gllr_options, $wp_scripts, $gllr_plugin_info;
		if ( empty( $gllr_plugin_inf ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}

		if ( ! defined( 'BWS_ENQUEUE_ALL_SCRIPTS' ) && ! wp_script_is( 'gllr_js', 'registered' ) ) {
			return;
		}

		if ( $gllr_options['disable_foreing_fancybox'] ) {
			foreach ( $wp_scripts->queue as $script ) {
				if ( preg_match( '/\.*fancybox\.*/', $script ) ) {
					wp_dequeue_script( $script );
					wp_deregister_script( $script );
				}
			}
		}

		wp_enqueue_script( 'jquery' );

		if ( ! wp_script_is( 'gllr_js', 'registered' ) ) {
			wp_enqueue_script( 'gllr_js', plugins_url( 'js/frontend_script.js', __FILE__ ), array(), $gllr_plugin_info['Version'], true );
		} else {
			wp_enqueue_script( 'gllr_js' );
		}

		if ( $gllr_options['enable_lightbox'] ) {
			if ( ! wp_script_is( 'bws_fancybox' ) ) {
				wp_enqueue_script( 'bws_fancybox', plugins_url( 'fancybox/jquery.fancybox.min.js', __FILE__ ), array( 'jquery' ), $gllr_plugin_info['Version'], true );
			}

			if ( ! defined( 'BWS_ENQUEUE_ALL_SCRIPTS' ) ) {
				wp_enqueue_script( 'gllr_inline_fancybox_script', gllr_echo_inline_script(), array( 'jquery', 'bws_fancybox' ), $gllr_plugin_info['Version'], true );
			}
		}
	}
}

if ( ! function_exists( 'gllr_pagination_callback' ) ) {
	/**
	 * Function for pagination
	 *
	 * @param string $content Content for pagination.
	 */
	function gllr_pagination_callback( $content ) {
		$content .= '$( ".gllr_grid:visible" ).trigger( "resize" ); if ( typeof gllr_fancy_init === "function" ) { gllr_fancy_init(); }';
		return $content;
	}
}

if ( ! function_exists( 'gllr_shortcode' ) ) {
	/**
	 * Function for gallery shortcode
	 *
	 * @param atring $attr Shortcode attributes.
	 */
	function gllr_shortcode( $attr ) {
		global $gllr_options, $gllr_vars_for_inline_script, $gllr_plugin_info;
		if ( empty( $gllr_plugin_inf ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$gllr_plugin_info = get_plugin_data( __FILE__ );
		}
		wp_register_script( 'gllr_js', plugins_url( 'js/frontend_script.js', __FILE__ ), array( 'jquery' ), $gllr_plugin_info['Version'], true );

		extract(
			shortcode_atts(
				array(
					'id'      => '',
					'display' => 'full',
					'cat_id'  => '',
					'sort_by' => '',
				),
				$attr
			)
		);
		ob_start();
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$album_order = get_term_meta( absint( $cat_id ), 'gllr_album_order', true );
		if ( empty( $album_order ) ) {
			$album_order = isset( $gllr_options['album_order_by_category_option'] ) ? $gllr_options['album_order_by_category_option'] : 'default';
		}

		$galleries_order =
			( ! empty( $sort_by ) && 'default' !== $sort_by )
		?
			$sort_by
		: (
				( ! empty( $album_order ) && 'default' !== $album_order )
			?
				$album_order
			:
				$gllr_options['album_order_by']
		);

		if ( ! empty( $cat_id ) ) {
			global $post, $wp_query;

			$term = get_term( $cat_id, 'gallery_categories' );
			if ( ! empty( $term ) ) {

				$old_wp_query = $wp_query;

				$args         = array(
					'post_type'          => $gllr_options['post_type_name'],
					'post_status'        => 'publish',
					'posts_per_page'     => -1,
					'gallery_categories' => $term->slug,
					'orderby'            => $galleries_order,
					'order'              => $gllr_options['album_order'],
				);
				$second_query = new WP_Query( $args );
				?>
				<div class="gallery_box">
					<ul>
						<?php
						if ( $second_query->have_posts() ) {
							if ( 1 === absint( $gllr_options['cover_border_images'] ) ) {
								$border = 'border-width: ' . $gllr_options['cover_border_images_width'] . 'px; border-color:' . $gllr_options['cover_border_images_color'] . ';border: ' . $gllr_options['cover_border_images_width'] . 'px solid ' . $gllr_options['cover_border_images_color'];
							} else {
								$border = '';
							}
							if ( 'album-thumb' !== $gllr_options['image_size_album'] ) {
								$width  = absint( get_option( $gllr_options['image_size_album'] . '_size_w' ) );
								$height = absint( get_option( $gllr_options['image_size_album'] . '_size_h' ) );
							} else {
								$width  = $gllr_options['custom_size_px']['album-thumb'][0];
								$height = $gllr_options['custom_size_px']['album-thumb'][1];
							}

							while ( $second_query->have_posts() ) {
								$second_query->the_post();
								$attachments = get_post_thumbnail_id( $post->ID );
								if ( empty( $attachments ) ) {
									$images_id   = get_post_meta( $post->ID, '_gallery_images', true );
									$attachments = get_posts(
										array(
											'showposts'    => 1,
											'what_to_show' => 'posts',
											'post_status'  => 'inherit',
											'post_type'    => 'attachment',
											'orderby'      => $gllr_options['order_by'],
											'order'        => $gllr_options['order'],
											'post__in'     => explode( ',', $images_id ),
											'meta_key'     => '_gallery_order_' . $post->ID,
										)
									);
									if ( ! empty( $attachments[0] ) ) {
										$first_attachment = $attachments[0];
										$image_attributes = wp_get_attachment_image_src( $first_attachment->ID, $gllr_options['image_size_album'] );
									} else {
										$image_attributes = array( '' );
									}
								} else {
									$image_attributes = wp_get_attachment_image_src( $attachments, $gllr_options['image_size_album'] );
								}
								?>
								<li>
									<a rel="bookmark" href="<?php echo esc_url( get_permalink() ); ?>" title="<?php the_title(); ?>">
										<?php
										$width_html   = ! empty( $width ) ? 'width="' . $width . '"' : '';
										$height_html  = ! empty( $height ) ? 'height="' . $height . '"' : '';
										$width_style  = ! empty( $width ) ? 'width:' . $width . 'px;' : '';
										$height_style = ! empty( $height ) ? 'height:' . $height . 'px;' : '';
										printf(
											'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%6$s" src="%7$s" />',
											esc_html( $width_html ),
											esc_html( $height_html ),
											esc_html( $width_style ),
											esc_html( $height_style ),
											esc_html( $border ),
											esc_html( get_the_title() ),
											esc_url( $image_attributes[0] )
										);
										?>
									</a>
									<div class="gallery_detail_box">
										<div class="gllr_detail_title"><?php the_title(); ?></div>
										<div class="gllr_detail_excerpt"><?php gllr_the_excerpt_max_charlength( 100 ); ?></div>
										<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( $gllr_options['read_more_link_text'] ); ?></a>
									</div><!-- .gallery_detail_box -->
									<div class="gllr_clear"></div>
								</li>
								<?php
							}
						}
						?>
					</ul>
				</div><!-- .gallery_box -->
				<?php
				wp_reset_postdata();
				$wp_query = $old_wp_query;
			}
		} else {
			global $post, $wp_query;
			$old_wp_query = $wp_query;

			$args         = array(
				'post_type'      => $gllr_options['post_type_name'],
				'post_status'    => 'publish',
				'p'              => $id,
				'posts_per_page' => 1,
			);
			$second_query = new WP_Query( $args );
			if ( 'short' === $display ) {
				?>
				<div class="gallery_box">
					<ul>
						<?php
						if ( $second_query->have_posts() ) {
							$second_query->the_post();
							$attachments = get_post_thumbnail_id( $post->ID );

							if ( 'album-thumb' !== $gllr_options['image_size_album'] ) {
								$width  = absint( get_option( $gllr_options['image_size_album'] . '_size_w' ) );
								$height = absint( get_option( $gllr_options['image_size_album'] . '_size_h' ) );
							} else {
								$width  = $gllr_options['custom_size_px']['album-thumb'][0];
								$height = $gllr_options['custom_size_px']['album-thumb'][1];
							}

							if ( empty( $attachments ) ) {
								$images_id   = get_post_meta( $post->ID, '_gallery_images', true );
								$attachments = get_posts(
									array(
										'showposts'      => 1,
										'what_to_show'   => 'posts',
										'post_status'    => 'inherit',
										'post_type'      => 'attachment',
										'orderby'        => $gllr_options['order_by'],
										'order'          => $gllr_options['order'],
										'post__in'       => explode( ',', $images_id ),
										'meta_key'       => '_gallery_order_' . $post->ID,
									)
								);
								if ( ! empty( $attachments[0] ) ) {
									$first_attachment = $attachments[0];
									$image_attributes = wp_get_attachment_image_src( $first_attachment->ID, $gllr_options['image_size_album'] );
								} else {
									$image_attributes = array( '' );
								}
							} else {
								$image_attributes = wp_get_attachment_image_src( $attachments, $gllr_options['image_size_album'] );
							}

							if ( 1 === absint( $gllr_options['cover_border_images'] ) ) {
								$border = 'border-width: ' . $gllr_options['cover_border_images_width'] . 'px; border-color:' . $gllr_options['cover_border_images_color'] . ';border: ' . $gllr_options['cover_border_images_width'] . 'px solid ' . $gllr_options['cover_border_images_color'];
							} else {
								$border = '';
							}
							?>
							<li>
								<a rel="bookmark" href="<?php echo esc_html( get_permalink() ); ?>" title="<?php the_title(); ?>">
									<?php
									$width_html   = ! empty( $width ) ? 'width="' . $width . '"' : '';
									$height_html  = ! empty( $height ) ? 'height="' . $height . '"' : '';
									$width_style  = ! empty( $width ) ? 'width:' . $width . 'px;' : '';
									$height_style = ! empty( $height ) ? 'height:' . $height . 'px;' : '';
									printf(
										'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%6$s" src="%7$s" />',
										esc_html( $width_html ),
										esc_html( $height_html ),
										esc_html( $width_style ),
										esc_html( $height_style ),
										esc_html( $border ),
										esc_html( get_the_title() ),
										esc_url( $image_attributes[0] )
									);
									?>
								</a>
								<div class="gallery_detail_box">
									<div class="gllr_detail_title"><?php the_title(); ?></div>
									<div class="gllr_detail_excerpt"><?php gllr_the_excerpt_max_charlength( 100 ); ?></div>
									<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( $gllr_options['read_more_link_text'] ); ?></a>
								</div><!-- .gallery_detail_box -->
								<div class="gllr_clear"></div>
							</li>
						<?php } ?>
					</ul>
				</div><!-- .gallery_box -->
				<?php
			} else {
				if ( $second_query->have_posts() ) {
					if ( 1 === absint( $gllr_options['border_images'] ) ) {
						$border        = 'border-width: ' . $gllr_options['border_images_width'] . 'px; border-color:' . $gllr_options['border_images_color'] . ';border: ' . $gllr_options['border_images_width'] . 'px solid ' . $gllr_options['border_images_color'];
						$border_images = $gllr_options['border_images_width'] * 2;
					} else {
						$border        = '';
						$border_images = 0;
					}
					if ( 'photo-thumb' !== $gllr_options['image_size_photo'] ) {
						$width  = absint( get_option( $gllr_options['image_size_photo'] . '_size_w' ) );
						$height = absint( get_option( $gllr_options['image_size_photo'] . '_size_h' ) );
					} else {
						$width  = $gllr_options['custom_size_px']['photo-thumb'][0];
						$height = $gllr_options['custom_size_px']['photo-thumb'][1];
					}

					while ( $second_query->have_posts() ) {
						$second_query->the_post();
						?>
						<div class="gallery_box_single">
							<?php
							echo do_shortcode( get_the_content() );

							$images_id = get_post_meta( $post->ID, '_gallery_images', true );

							$posts = get_posts(
								array(
									'showposts'      => -1,
									'what_to_show'   => 'posts',
									'post_status'    => 'inherit',
									'post_type'      => 'attachment',
									'orderby'        => $gllr_options['order_by'],
									'order'          => $gllr_options['order'],
									'post__in'       => explode( ',', $images_id ),
									'meta_key'       => '_gallery_order_' . $post->ID,
								)
							);

							if ( 0 < count( $posts ) ) {
								$count_image_block = 0;
								?>
								<div class="gallery gllr_grid" data-gllr-columns="<?php echo esc_attr( $gllr_options['custom_image_row_count'] ); ?>" data-gllr-border-width="<?php echo esc_attr( $gllr_options['border_images_width'] ); ?>">
									<?php
									foreach ( $posts as $attachment ) {
										$image_attributes       = wp_get_attachment_image_src( $attachment->ID, $gllr_options['image_size_photo'] );
										$image_attributes_large = wp_get_attachment_image_src( $attachment->ID, 'large' );
										$image_attributes_full  = wp_get_attachment_image_src( $attachment->ID, 'full' );
										$url_for_link           = get_post_meta( $attachment->ID, 'gllr_link_url', true );
										$image_text             = get_post_meta( $attachment->ID, 'gllr_image_text', true );
										$image_alt_tag          = get_post_meta( $attachment->ID, 'gllr_image_alt_tag', true );

										if ( 0 === $count_image_block % $gllr_options['custom_image_row_count'] ) {
											?>
											<div class="gllr_image_row">
										<?php } ?>
											<div class="gllr_image_block">
												<p style="
												<?php
												if ( $width ) {
													echo 'width:' . ( esc_attr( $width + $border_images ) ) . 'px;';
												} if ( $height ) {
													echo 'height:' . ( esc_attr( $height + $border_images ) ) . 'px;';
												}
												?>
												">
													<?php
													$width_html   = ! empty( $width ) ? 'width="' . $width . '"' : '';
													$height_html  = ! empty( $height ) ? 'height="' . $height . '"' : '';
													$width_style  = ! empty( $width ) ? 'width:' . $width . 'px;' : '';
													$height_style = ! empty( $height ) ? 'height:' . $height . 'px;' : '';
													if ( ! empty( $url_for_link ) ) {
														?>
														<a href="<?php echo esc_url( $url_for_link ); ?>" title="<?php echo esc_html( $image_text ); ?>" target="_blank">
															<?php
															printf(
																'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%7$s" src="%8$s" />',
																esc_html( $width_html ),
																esc_html( $height_html ),
																esc_html( $width_style ),
																esc_html( $height_style ),
																esc_html( $border ),
																esc_html( $image_alt_tag ),
																esc_html( $image_text ),
																esc_url( $image_attributes[0] )
															);
															?>
														</a>
														<?php
													} else {
														if ( 1 !== absint( $gllr_options['enable_image_opening'] ) ) {
															?>
															<a data-fancybox="gallery_fancybox<?php echo 0 === absint( $gllr_options['single_lightbox_for_multiple_galleries'] ) ? '_' . esc_attr( $post->ID ) : ''; ?>" href="<?php echo esc_url( $image_attributes_large[0] ); ?>" title="<?php echo esc_html( $image_text ); ?>" >
																<?php
																printf(
																	'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%7$s" src="%8$s" rel="%9$s" />',
																	esc_html( $width_html ),
																	esc_html( $height_html ),
																	esc_html( $width_style ),
																	esc_html( $height_style ),
																	esc_html( $border ),
																	esc_html( $image_alt_tag ),
																	esc_html( $image_text ),
																	esc_url( $image_attributes[0] ),
																	esc_url( $image_attributes_full[0] )
																);
																?>
															</a>
														<?php } else { ?>
															<a data-fancybox="gallery_fancybox<?php echo 0 === absint( $gllr_options['single_lightbox_for_multiple_galleries'] ) ? '_' . esc_attr( $post->ID ) : ''; ?>" href="#" style="pointer-events: none;" title="<?php echo esc_html( $image_text ); ?>" >
																<?php
																printf(
																	'<img %1$s %2$s style="%3$s %4$s %5$s" alt="%6$s" title="%7$s" src="%8$s" rel="#" />',
																	esc_html( $width_html ),
																	esc_html( $height_html ),
																	esc_html( $width_style ),
																	esc_html( $height_style ),
																	esc_html( $border ),
																	esc_html( $image_alt_tag ),
																	esc_html( $image_text ),
																	esc_url( $image_attributes[0] )
																);
																?>
															</a>
															<?php
														}
													}
													?>
												</p>
												<?php if ( 1 === absint( $gllr_options['image_text'] ) ) { ?>
													<div 
													<?php
													if ( $width ) {
														echo 'style="width:' . ( esc_attr( $width + $border_images ) ) . 'px;"';
													}
													?>
													class="gllr_single_image_text gllr_single_image_text_under"><?php echo esc_html( $image_text ); ?>&nbsp;</div>
												<?php } ?>
											</div><!-- .gllr_image_block -->
										<?php if ( $count_image_block % $gllr_options['custom_image_row_count'] === $gllr_options['custom_image_row_count'] - 1 ) { ?>
											</div><!-- .gllr_image_row -->
											<?php
										}
										$count_image_block++;
									}
									if ( 0 < $count_image_block && 0 !== $count_image_block % $gllr_options['custom_image_row_count'] ) {
										?>
											<div class="clear"></div>
										</div><!-- .gllr_image_row -->
									<?php } ?>
								</div><!-- .gallery.clearfix -->
								<?php
							}
							if ( 1 === absint( $gllr_options['return_link_shortcode'] ) ) {
								if ( empty( $gllr_options['return_link_url'] ) ) {
									if ( ! empty( $gllr_options['page_id_gallery_template'] ) ) {
										?>
										<div class="gllr_clear"></div>
										<div class="return_link gllr_return_link"><a href="<?php echo esc_url( get_permalink( $gllr_options['page_id_gallery_template'] ) ); ?>"><?php echo esc_html( $gllr_options['return_link_text'] ); ?></a></div>
										<?php
									}
								} else {
									?>
									<div class="gllr_clear"></div>
									<div class="return_link gllr_return_link"><a href="<?php echo esc_url( $gllr_options['return_link_url'] ); ?>"><?php echo esc_html( $gllr_options['return_link_text'] ); ?></a></div>
									<?php
								}
							}
							?>
						</div><!-- .gallery_box_single -->
						<div class="gllr_clear"></div>
						<?php
					}
					if ( $gllr_options['enable_lightbox'] ) {

						$gllr_vars_for_inline_script['single_script'][] = apply_filters( 'gllr_options_for_inline_script', array( 'post_id' => $post->ID ) );

						if ( defined( 'BWS_ENQUEUE_ALL_SCRIPTS' ) ) {
							gllr_echo_inline_script();
						}
					}
				} else {
					?>
					<div class="gallery_box_single">
						<p class="not_found"><?php esc_html_e( 'Sorry, nothing found.', 'gallery-plugin' ); ?></p>
					</div><!-- .gallery_box_single -->
					<?php
				}
			}
			wp_reset_postdata();
			$wp_query = $old_wp_query;
		}
		$gllr_output = ob_get_clean();
		return $gllr_output;
	}
}

if ( ! function_exists( 'gllr_echo_inline_script' ) ) {
	/**
	 * Function for inline scripts
	 */
	function gllr_echo_inline_script() {
		global $gllr_vars_for_inline_script, $gllr_options;

		if ( isset( $gllr_vars_for_inline_script['single_script'] ) ) {
			$script = '
			var gllr_onload = window.onload;
			function gllr_fancy_init() {
				var options = {
					loop	: true,
					arrows  : ' . ( 1 === absint( $gllr_options['lightbox_arrows'] ) ? 'true' : 'false' ) . ',
					infobar : true,
					caption : function( instance, current ) {
						current.full_src = jQuery( this ).find( \'img\' ).attr( \'rel\' );
						var title = jQuery( this ).attr( \'title\' ).replace(/</g, "&lt;");
						return title ? \'<div>\' + title + \'</div>\' : \'\';
					},
					buttons : [\'close\', ]
				};
			';

			if ( $gllr_options['lightbox_download_link'] ) {
				$script .= '
				if ( ! jQuery.fancybox.defaults.btnTpl.download ) {
					jQuery.fancybox.defaults.btnTpl.download = \'<a class="fancybox-button bws_gallery_download_link dashicons dashicons-download"></a>\';
				}

				options.buttons.unshift( \'download\' );
				options.beforeShow = function( instance, current ) {
					jQuery( \'.bws_gallery_download_link\' ).attr( \'href\', current.full_src ).attr( \'download\', current.full_src.substring( current.full_src.lastIndexOf(\'/\') + 1 ) );
				}
				';
			}

			if ( $gllr_options['start_slideshow'] ) {
				$script .= '
				options.buttons.unshift( \'slideShow\' );
				options.slideShow = { autoStart : true, speed : ' . $gllr_options['slideshow_interval'] . ' };
				';
			}

			/* prevent several fancybox initialization */
			if ( $gllr_options['single_lightbox_for_multiple_galleries'] ) {
				$script .= '
				jQuery( "a[data-fancybox=gallery_fancybox]" ).fancybox( options );
				';
			} else {
				foreach ( $gllr_vars_for_inline_script['single_script'] as $vars ) {
					$script .= '
					jQuery( "a[data-fancybox=gallery_fancybox_' . $vars['post_id'] . ']" ).fancybox( options );
					';
				}
			}

			$script .= '
			}
			if ( typeof gllr_onload === \'function\' ) {
				window.onload = function() {
					gllr_onload();
					gllr_fancy_init();
				}
			} else {
				window.onload = gllr_fancy_init;
			}
			';

			wp_register_script( 'gllr_fancy_init', '', array(), false, true );
			wp_enqueue_script( 'gllr_fancy_init' );
			wp_add_inline_script( 'gllr_fancy_init', sprintf( $script ) );

			unset( $gllr_vars_for_inline_script );
		}

		$additional_options = do_action( 'gllr_echo_additional_inline_script', array() );
	}
}

if ( ! function_exists( 'gllr_update_image' ) ) {
	/**
	 * Function for gallery ajax
	 */
	function gllr_update_image() {
		global $wpdb, $gllr_options;
		check_ajax_referer( plugin_basename( __FILE__ ), 'gllr_ajax_nonce_field' );

		$action = isset( $_REQUEST['action1'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action1'] ) ) : '';
		$id     = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : '';
		switch ( $action ) {
			case 'get_all_attachment':
				$array_parent_id = $wpdb->get_col( $wpdb->prepare( "SELECT `ID` FROM $wpdb->posts WHERE `post_type` = %s", $gllr_options['post_type_name'] ) );

				if ( ! empty( $array_parent_id ) ) {

					$string_parent_id = implode( ',', $array_parent_id );

					$metas = $wpdb->get_results( "SELECT `meta_value` FROM $wpdb->postmeta WHERE `meta_key` = '_gallery_images' AND `post_id` IN ( " . $string_parent_id . ' )', ARRAY_A );

					$result_attachment_id = '';
					foreach ( $metas as $value ) {
						if ( ! empty( $value['meta_value'] ) ) {
							$result_attachment_id .= $value['meta_value'] . ',';
						}
					}
					$result_attachment_id_array = explode( ',', rtrim( $result_attachment_id, ',' ) );
					echo wp_json_encode( array_unique( $result_attachment_id_array ) );
				}
				break;
			case 'update_image':
				$metadata = wp_get_attachment_metadata( $id );
				$uploads  = wp_upload_dir();
				$path     = $uploads['basedir'] . '/' . $metadata['file'];
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$metadata_new = gllr_wp_generate_attachment_metadata( $id, $path, $metadata );
				wp_update_attachment_metadata( $id, array_merge( $metadata, $metadata_new ) );
				break;
			case 'update_options':
				unset( $gllr_options['need_image_update'] );
				update_option( 'gllr_options', $gllr_options );
				break;
		}
		die();
	}
}

if ( ! function_exists( 'gllr_wp_generate_attachment_metadata' ) ) {
	/**
	 * Filter for generate attachment metadata for gallery image
	 *
	 * @param number $attachment_id Attachment ID.
	 * @param string $file          File path.
	 * @param array  $metadata      Metadata array.
	 */
	function gllr_wp_generate_attachment_metadata( $attachment_id, $file, $metadata ) {
		$attachment   = get_post( $attachment_id );
		$gllr_options = get_option( 'gllr_options' );
		$image_size   = array( 'thumbnail' );

		if ( 'album-thumb' === $gllr_options['image_size_album'] ) {
			add_image_size( 'album-thumb', $gllr_options['custom_size_px']['album-thumb'][0], $gllr_options['custom_size_px']['album-thumb'][1], true );
			$image_size[] = 'album-thumb';
		}
		if ( 'photo-thumb' === $gllr_options['image_size_photo'] ) {
			add_image_size( 'photo-thumb', $gllr_options['custom_size_px']['photo-thumb'][0], $gllr_options['custom_size_px']['photo-thumb'][1], true );
			$image_size[] = 'photo-thumb';
		}

		$metadata = array();
		if ( preg_match( '!^image/!', get_post_mime_type( $attachment ) ) && file_is_displayable_image( $file ) ) {
			$imagesize                  = getimagesize( $file );
			$metadata['width']          = $imagesize[0];
			$metadata['height']         = $imagesize[1];
			list( $uwidth, $uheight )   = wp_constrain_dimensions( $metadata['width'], $metadata['height'], 128, 96 );
			$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

			/* Make the file path relative to the upload dir */
			$metadata['file'] = _wp_relative_upload_path( $file );

			/* Make thumbnails and other intermediate sizes */
			global $_wp_additional_image_sizes;

			foreach ( $image_size as $s ) {
				$sizes[ $s ] = array(
					'width'  => '',
					'height' => '',
					'crop'   => false,
				);
				if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
					$sizes[ $s ]['width'] = absint( $_wp_additional_image_sizes[ $s ]['width'] ); /* For theme-added sizes */
				} else {
					$sizes[ $s ]['width'] = get_option( "{$s}_size_w" ); /* For default sizes set in options */
				}               if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
					$sizes[ $s ]['height'] = absint( $_wp_additional_image_sizes[ $s ]['height'] ); /* For theme-added sizes */
				} else {
					$sizes[ $s ]['height'] = get_option( "{$s}_size_h" ); /* For default sizes set in options */
				}               if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
					$sizes[ $s ]['crop'] = absint( $_wp_additional_image_sizes[ $s ]['crop'] ); /* For theme-added sizes */
				} else {
					$sizes[ $s ]['crop'] = get_option( "{$s}_crop" ); /* For default sizes set in options */
				}
			}
			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );
			foreach ( $sizes as $size => $size_data ) {
				$resized = gllr_image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $size_data['crop'] );
				if ( $resized ) {
					$metadata['sizes'][ $size ] = $resized;
				}
			}
			/* Fetch additional metadata from exif/iptc */
			$image_meta = wp_read_image_metadata( $file );
			if ( $image_meta ) {
				$metadata['image_meta'] = $image_meta;
			}
		}
		return apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
	}
}

if ( ! function_exists( 'gllr_image_make_intermediate_size' ) ) {
	/**
	 * Filter for intermediate size for gallery image
	 *
	 * @param string $file   File name.
	 * @param number $width  Width value.
	 * @param number $height Height value.
	 * @param bool   $crop   Flag for crop.
	 */
	function gllr_image_make_intermediate_size( $file, $width, $height, $crop = false ) {
		if ( $width || $height ) {
			$resized_file = gllr_image_resize( $file, $width, $height, $crop );
			$info         = getimagesize( $resized_file );
			if ( ! is_wp_error( $resized_file ) && $resized_file && ! empty( $info ) ) {
				$resized_file = apply_filters( 'image_make_intermediate_size', $resized_file );
				return array(
					'file'   => wp_basename( $resized_file ),
					'width'  => $info[0],
					'height' => $info[1],
				);
			}
		}
		return false;
	}
}

if ( ! function_exists( 'gllr_image_resize' ) ) {
	/**
	 * Filter for gallery image resize
	 *
	 * @param string $file         File name.
	 * @param number $max_w        Width value.
	 * @param number $max_h        Height value.
	 * @param bool   $crop         Flag for crop.
	 * @param string $suffix       Suffix for file name.
	 * @param string $dest_path    Path for save file.
	 * @param number $jpeg_quality Quality number for save file.
	 */
	function gllr_image_resize( $file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90 ) {
		$size = @getimagesize( $file );
		if ( ! $size ) {
			return new WP_Error( 'invalid_image', __( 'Image size not defined', 'gallery-plugin' ), $file );
		}

		$type = $size[2];

		if ( 3 === $type ) {
			$image = imagecreatefrompng( $file );
		} elseif ( 2 === $type ) {
			$image = imagecreatefromjpeg( $file );
		} elseif ( 1 === $type ) {
			$image = imagecreatefromgif( $file );
		} elseif ( 15 === $type ) {
			$image = imagecreatefromwbmp( $file );
		} elseif ( 16 === $type ) {
			$image = imagecreatefromxbm( $file );
		} else {
			return new WP_Error( 'invalid_image', __( 'Plugin updates only PNG, JPEG, GIF, WPMP or XBM filetype. For other, please reload images manually.', 'gallery-plugin' ), $file );
		}

		if ( ! is_resource( $image ) ) {
			return new WP_Error( 'error_loading_image', $image, $file );
		}

		list( $orig_w, $orig_h, $orig_type ) = $size;

		$dims = gllr_image_resize_dimensions( $orig_w, $orig_h, $max_w, $max_h, $crop );

		if ( ! $dims ) {
			return new WP_Error( 'error_getting_dimensions', __( 'Image size not defined', 'gallery-plugin' ) );
		}
		list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

		$newimage = wp_imagecreatetruecolor( $dst_w, $dst_h );

		imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );

		/* Convert from full colors to index colors, like original PNG. */
		if ( IMAGETYPE_PNG === $orig_type && function_exists( 'imageistruecolor' ) && ! imageistruecolor( $image ) ) {
			imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );
		}

		/* We don't need the original in memory anymore */
		imagedestroy( $image );

		/* $suffix will be appended to the destination filename, just before the extension */
		if ( ! $suffix ) {
			$suffix = "{$dst_w}x{$dst_h}";
		}

		$info = pathinfo( $file );
		$dir  = $info['dirname'];
		$ext  = $info['extension'];
		$name = wp_basename( $file, ".$ext" );

		$_dest_path = realpath( $dest_path );

		if ( ! is_null( $dest_path ) && ! empty( $_dest_path ) ) {
			$dir = $_dest_path;
		}
		$destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";

		if ( IMAGETYPE_GIF === $orig_type ) {
			if ( ! imagegif( $newimage, $destfilename ) ) {
				return new WP_Error( 'resize_path_invalid', __( 'Invalid path', 'gallery-plugin' ) );
			}
		} elseif ( IMAGETYPE_PNG === $orig_type ) {
			if ( ! imagepng( $newimage, $destfilename ) ) {
				return new WP_Error( 'resize_path_invalid', __( 'Invalid path', 'gallery-plugin' ) );
			}
		} else {
			/* All other formats are converted to jpg */
			$destfilename = "{$dir}/{$name}-{$suffix}.jpg";
			if ( ! imagejpeg( $newimage, $destfilename, apply_filters( 'jpeg_quality', $jpeg_quality, 'image_resize' ) ) ) {
				return new WP_Error( 'resize_path_invalid', __( 'Invalid path', 'gallery-plugin' ) );
			}
		}
		imagedestroy( $newimage );

		/* Set correct file permissions */
		$stat  = stat( dirname( $destfilename ) );
		$perms = $stat['mode'] & 0000666; /* Same permissions as parent folder, strip off the executable bits */
		@chmod( $destfilename, $perms );
		return $destfilename;
	}
}

if ( ! function_exists( 'gllr_image_resize_dimensions' ) ) {
	/**
	 * Filter for gallery image resize dimensions
	 *
	 * @param number $orig_w Width value.
	 * @param number $orig_h Height value.
	 * @param number $dest_w New width value.
	 * @param number $dest_h New height value.
	 * @param bool   $crop   Flag for crop.
	 */
	function gllr_image_resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, $crop = false ) {
		if ( 0 >= $orig_w || 0 >= $orig_h ) {
			return false;
		}
		/* At least one of dest_w or dest_h must be specific */
		if ( 0 >= $dest_w && 0 >= $dest_h ) {
			return false;
		}

		if ( $crop ) {
			/* Crop the largest possible portion of the original image that we can size to $dest_w x $dest_h */
			$aspect_ratio = $orig_w / $orig_h;
			$new_w        = min( $dest_w, $orig_w );
			$new_h        = min( $dest_h, $orig_h );

			if ( ! $new_w ) {
				$new_w = absint( $new_h * $aspect_ratio );
			}

			if ( ! $new_h ) {
				$new_h = absint( $new_w / $aspect_ratio );
			}

			$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );

			$crop_w = round( $new_w / $size_ratio );
			$crop_h = round( $new_h / $size_ratio );
			$s_x    = floor( ( $orig_w - $crop_w ) / 2 );
			$s_y    = 0;

		} else {
			/* Don't crop, just resize using $dest_w x $dest_h as a maximum bounding box */
			$crop_w = $orig_w;
			$crop_h = $orig_h;
			$s_x    = 0;
			$s_y    = 0;

			list( $new_w, $new_h ) = wp_constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
		}
		/* If the resulting image would be the same size or larger we don't want to resize it */
		if ( $new_w >= $orig_w && $new_h >= $orig_h ) {
			return false;
		}
		/**
		 * The return array matches the parameters to imagecopyresampled()
		 * Int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
		 */
		return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
	}
}

if ( ! function_exists( 'gllr_theme_body_classes' ) ) {
	/**
	 * Add a class with theme name
	 *
	 * @param array $classes Classes array.
	 */
	function gllr_theme_body_classes( $classes ) {
		if ( function_exists( 'wp_get_theme' ) ) {
			$current_theme = wp_get_theme();
			$classes[]     = 'gllr_' . basename( $current_theme->get( 'ThemeURI' ) );
		}
		return $classes;
	}
}

if ( ! function_exists( 'gllr_media_custom_box' ) ) {
	/**
	 * Create custom meta box for gallery post type
	 *
	 * @param string $obj Object for shortcode.
	 * @param string $box Box for shortcode.
	 */
	function gllr_media_custom_box( $obj = '', $box = '' ) {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php';
		}
		require_once dirname( __FILE__ ) . '/includes/class-gllr-settings.php';
		$page = new Gllr_Settings_Tabs( plugin_basename( __FILE__ ) );
		?>
		<div style="padding-top:10px;">
			<?php $page->display_tabs(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'gllr_delete_image' ) ) {
	/**
	 * Filter for gallery delete image
	 */
	function gllr_delete_image() {
		check_ajax_referer( plugin_basename( __FILE__ ), 'gllr_ajax_nonce_field' );

		$action          = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		$delete_id_array = isset( $_POST['delete_id_array'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_id_array'] ) ) : ''; /* !!!!!!!!!!!!!!!!!!!!!!! */
		$post_id         = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '';

		if ( 'gllr_delete_image' === $action && ! empty( $delete_id_array ) && ! empty( $post_id ) ) {
			if ( ! is_array( $delete_id_array ) ) {
				$delete_id = explode( ',', trim( $delete_id_array, ',' ) );
			} else {
				$delete_id[] = $delete_id_array;
			}

			$gallery_images = get_post_meta( $post_id, '_gallery_images', true );

			$gallery_images_array = explode( ',', $gallery_images );
			$gallery_images_array = array_flip( $gallery_images_array );

			foreach ( $delete_id as $delete_id ) {
				delete_post_meta( $delete_id, '_gallery_order_' . $post_id );
				unset( $gallery_images_array[ $delete_id ] );
			}

			$gallery_images_array = array_flip( $gallery_images_array );
			$gallery_images       = implode( ',', $gallery_images_array );
			/* Custom field has a value and this custom field exists in database */
			update_post_meta( $post_id, '_gallery_images', $gallery_images );
			echo 'updated';
		} else {
			echo 'error';
		}
		die();
	}
}

if ( ! function_exists( 'gllr_add_from_media' ) ) {
	/**
	 * Add image for gallery from media
	 */
	function gllr_add_from_media() {
		global $original_post, $post;
		check_ajax_referer( plugin_basename( __FILE__ ), 'gllr_ajax_add_nonce' );

		$add_id           = isset( $_POST['add_id'] ) ? absint( $_POST['add_id'] ) : '';
		$original_post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '';
		$gllr_mode        = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'grid';

		if ( ! empty( $add_id ) && ! empty( $original_post_id ) ) {
			$post = get_post( $add_id );
			if ( ! empty( $post ) ) {
				if ( preg_match( '!^image/!', $post->post_mime_type ) ) {
					setup_postdata( $post );
					$original_post          = get_post( $original_post_id );
					$GLOBALS['hook_suffix'] = 'gallery';
					$wp_gallery_media_table = new Gllr_Media_Table();
					$wp_gallery_media_table->prepare_items();
					$wp_gallery_media_table->single_row( $gllr_mode );
				}
			}
		}
		die();
	}
}

if ( ! function_exists( 'gllr_change_view_mode' ) ) {
	/**
	 * Save view mode to user option
	 */
	function gllr_change_view_mode() {
		check_ajax_referer( plugin_basename( __FILE__ ), 'gllr_ajax_nonce_field' );
		if ( ! empty( $_POST['mode'] ) ) {
			update_user_option(
				get_current_user_id(),
				'gllr_media_library_mode',
				sanitize_text_field( wp_unslash( $_POST['mode'] ) )
			);
		}
		die();
	}
}

if ( ! function_exists( 'gllr_print_media_notice' ) ) {
	/**
	 * Add place for notice in media upoader for gallery
	 *
	 * See wp_print_media_templates() in "wp-includes/media-template.php"
	 */
	function gllr_print_media_notice() {
		global $post, $gllr_options;
		if ( isset( $post ) ) {
			if ( $post->post_type === $gllr_options['post_type_name'] ) {

				$script = "( function ($) {
					$( '#tmpl-attachment-details' ).html(
						$( '#tmpl-attachment-details' ).html().replace( '<div class=\"attachment-info\"', '<# gllr_notice_wiev( data.id ); #><div id=\"gllr_media_notice\" class=\"upload-errors\"></div>$&' )
					);
				} )(jQuery);";
				wp_register_script( 'gllr_print_media_notice', '', array(), false, true );
				wp_enqueue_script( 'gllr_print_media_notice' );
				wp_add_inline_script( 'gllr_print_media_notice', sprintf( $script ) );
			}
		}
	}
}

if ( ! function_exists( 'gllr_media_check_ajax_action' ) ) {
	/**
	 * Add notises in media upoader for portfolio  and gallery
	 */
	function gllr_media_check_ajax_action() {
		check_ajax_referer( plugin_basename( __FILE__ ), 'gllr_ajax_nonce_field' );
		if ( isset( $_POST['thumbnail_id'] ) ) {
			$thumbnail_id = absint( $_POST['thumbnail_id'] );
			/*get information about the selected item */
			$atachment_detail = get_post( $thumbnail_id );
			if ( ! empty( $atachment_detail ) ) {
				if ( ! preg_match( '!^image/!', $atachment_detail->post_mime_type ) ) {
					$notice_attach = "<div class='upload-error'><strong>" . __( 'Warning', 'gallery-plugin' ) . ': </strong>' . __( 'You can add only images to the gallery', 'gallery-plugin' ) . '</div>';
					wp_send_json_success( $notice_attach );
				}
			}
		}
		die();
	}
}

if ( ! function_exists( 'gllr_shortcode_button_content' ) ) {
	/**
	 * Add shortcode content
	 *
	 * @param string $content Content for shortcode.
	 */
	function gllr_shortcode_button_content( $content ) {
		global $post, $gllr_options;
		?>
		<div id="gllr" style="display:none;">
			<fieldset>
				<?php
				$old_post = $post;
				$query    = new WP_Query( 'post_type=' . $gllr_options['post_type_name'] . '&post_status=publish&posts_per_page=-1&order=DESC&orderby=date' );
				if ( $query->have_posts() ) {
					?>
					<label>
					<input name="gllr_shortcode_radio" class="gllr_shortcode_radio_category" type="radio"/>
					<span class="title"><?php esc_html_e( 'Gallery Categories', 'gallery-plugin' ); ?></span>
					<?php
					$cat_args = array(
						'orderby'      => 'date',
						'order'        => 'DESC',
						'show_count'   => 1,
						'hierarchical' => 1,
						'taxonomy'     => 'gallery_categories',
						'name'         => 'gllr_gallery_categories',
						'id'           => 'gllr_gallery_categories',
					);
					wp_dropdown_categories( apply_filters( 'widget_categories_dropdown_args', $cat_args ) );
					?>
					</label><br/>
					<label class="gllr_shortcode_selectbox">
						<select name="album_order_by_shortcode_option" id="gllr_album_order_by_shortcode_option">
							<option value="ID" <?php selected( 'ID', $gllr_options['album_order_by_shortcode_option'] ); ?>><?php esc_html_e( 'Gallery ID', 'gallery-plugin' ); ?></option>
							<option value="title" <?php selected( 'title', $gllr_options['album_order_by_shortcode_option'] ); ?>><?php esc_html_e( 'Title', 'gallery-plugin' ); ?></option>
							<option value="date" <?php selected( 'date', $gllr_options['album_order_by_shortcode_option'] ); ?>><?php esc_html_e( 'Date', 'gallery-plugin' ); ?></option>
							<option value="modified" <?php selected( 'modified', $gllr_options['album_order_by_shortcode_option'] ); ?>><?php esc_html_e( 'Last modified date', 'gallery-plugin' ); ?></option>
							<option value="comment_count" <?php selected( 'comment_count', $gllr_options['album_order_by_shortcode_option'] ); ?>><?php esc_html_e( 'Comment count', 'gallery-plugin' ); ?></option>
							<option value="menu_order" <?php selected( 'menu_order', $gllr_options['album_order_by_shortcode_option'] ); ?>><?php esc_html_e( '"Order" field on the gallery edit page', 'gallery-plugin' ); ?></option>
							<option value="author" <?php selected( 'author', $gllr_options['album_order_by_shortcode_option'] ); ?>><?php esc_html_e( 'Author', 'gallery-plugin' ); ?></option>
							<option value="rand" <?php selected( 'rand', $gllr_options['album_order_by_shortcode_option'] ); ?> class="bws_option_affect"><?php esc_html_e( 'Random', 'gallery-plugin' ); ?></option>
							<option value="default" <?php selected( 'default', $gllr_options['album_order_by_shortcode_option'] ); ?> class="bws_option_affect"><?php esc_html_e( 'Plugin Settings', 'gallery-plugin' ); ?></option>
						</select>
						<div class="bws_info"><?php echo sprintf( esc_html__( 'Select galleries sorting order in your category. The sorting direction you can select in the %s', 'gallery-plugin' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=bws-gallery&page=gallery-plugin.php' ) ) . '">' . esc_html__( 'Cover Settings', 'gallery-plugin' ) . '</a>' ); ?></div>
					</label>
					<label>
					<input name="gllr_shortcode_radio" class="gllr_shortcode_radio_single" type="radio" checked/>
					<span class="title"><?php esc_html_e( 'Gallery', 'gallery-plugin' ); ?></span>
					<select name="gllr_list" id="gllr_shortcode_list" style="max-width: 350px;">
					<?php
					while ( $query->have_posts() ) {
						$query->the_post();
						if ( ! isset( $gllr_first ) ) {
							$gllr_first = get_the_ID();
						}
						$title = get_the_title( $post->ID );
						if ( empty( $title ) ) {
							$title = ' ( ' . __( 'no title', 'gallery-plugin' ) . ' ) ';
						}
						?>
						<option value="<?php the_ID(); ?>"><?php echo esc_html( $title ); ?> ( <?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?> )</option>
						<?php
					}
					wp_reset_postdata();
					$post = $old_post;
					?>
					</select>
					</label><br/>
					<label class="gllr_shortcode_checkbox">
						<input type="checkbox" value="1" name="gllr_display_short" id="gllr_display_short" />
						<span class="checkbox-title">
							<?php esc_html_e( 'Display an album image with the description and the link to a single gallery page', 'gallery-plugin' ); ?>
						</span>
					</label>
				<?php } else { ?>
					<span class="title"><?php esc_html_e( 'Sorry, no gallery found.', 'gallery-plugin' ); ?></span>
				<?php } ?>
			</fieldset>
			<?php if ( ! empty( $gllr_first ) ) { ?>
				<input class="bws_default_shortcode" type="hidden" name="default" value="[print_gllr id=<?php echo esc_attr( $gllr_first ); ?>]" />
				<?php
			}

			$script = '
			function gllr_shortcode_init() {
					(function($) {
						$( \'.mce-reset input[name="gllr_shortcode_radio"], .mce-reset #gllr_display_short, .mce-reset #gllr_album_order_by_shortcode_option, .mce-reset #gllr_shortcode_list, .mce-reset #gllr_gallery_categories\' ).on( \'change\', function() {
							if ( $( \'.mce-reset #gllr_shortcode_list\' ).is( ":focus" ) ) {
								$( \'.mce-reset input[class="gllr_shortcode_radio_single"]\' ).prop( \'checked\', true );
							} else if ( $( \'.mce-reset #gllr_gallery_categories\' ).is( ":focus" ) ) {
								$( \'.mce-reset input[class=gllr_shortcode_radio_category]\' ).prop( \'checked\', true );
							}
							var shortcode 	= \'[print_gllr \',
								id 			= \'\',
								sort_by 	= \'\';
							if( $( \'.mce-reset input[class=gllr_shortcode_radio_category]\' ).is( \':checked\' ) ) {
								id = \'gllr_gallery_categories\';
								shortcode += \'cat_id=\';
								$( \'.gllr_shortcode_checkbox\' ).hide();
								$( \'.gllr_shortcode_selectbox\' ).show();
							} else if ( $( \'.mce-reset input[class="gllr_shortcode_radio_single"]\' ).is( \':checked\' ) ) {
								id = \'gllr_shortcode_list\';
								shortcode += \'id=\';
								$( \'.gllr_shortcode_checkbox\' ).show();
								$( \'.gllr_shortcode_selectbox\' ).hide();
							}
							var gllr_list = $( \'.mce-reset #\' + id + \' option:selected\' ).val();
							shortcode += gllr_list;
							if ( $( \'.mce-reset #gllr_display_short\' ).is( \':checked\' ) && $( \'.mce-reset input[class="gllr_shortcode_radio_single"]\' ).is( \':checked\' ) ) {
								shortcode += \' display=short\';
							}
							if ( $( \'.mce-reset input[class="gllr_shortcode_radio_category"]\' ).is( \':checked\' ) ) {
								sort_by = \' sort_by=\' + $( \'.mce-reset #gllr_album_order_by_shortcode_option option:selected\' ).val();
							}
							shortcode += sort_by;
							shortcode += \']\';
							$( \'.mce-reset #bws_shortcode_display\' ).text( shortcode );
						} ).trigger( \'change\' );
					})(jQuery);
				}
			';

			wp_register_script( 'gllr_shortcode_script', '', array(), false, true );
			wp_enqueue_script( 'gllr_shortcode_script' );
			wp_add_inline_script( 'gllr_shortcode_script', sprintf( $script ) );
			?>
			<div class="clear"></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'gllr_add_tabs' ) ) {
	/**
	 * Add help tab
	 */
	function gllr_add_tabs() {
		global $gllr_options;
		$screen = get_current_screen();

		if ( ! empty( $screen->post_type ) && $gllr_options['post_type_name'] === $screen->post_type ) {
			$args = array(
				'id'      => 'gllr',
				'section' => '200538899',
			);
			bws_help_tab( $screen, $args );
		}
	}
}

if ( ! function_exists( 'gllr_admin_notices' ) ) {
	/**
	 * Add admin notice
	 */
	function gllr_admin_notices() {
		global $hook_suffix, $gllr_plugin_info, $gllr_bws_demo_data, $gllr_options;

		if ( 'plugins.php' === $hook_suffix || ( isset( $_GET['page'] ) && 'gallery-plugin.php' === $_GET['page'] ) ) {

			if ( ! $gllr_bws_demo_data ) {
				gllr_include_demo_data();
			}

			$gllr_bws_demo_data->bws_handle_demo_notice( $gllr_options['display_demo_notice'] );

			if ( 'plugins.php' === $hook_suffix ) {
				bws_plugin_banner_to_settings( $gllr_plugin_info, 'gllr_options', 'gallery-plugin', 'edit.php?post_type=' . $gllr_options['post_type_name'] . '&page=gallery-plugin.php', 'post-new.php?post_type=' . $gllr_options['post_type_name'] );
			} else {
				bws_plugin_suggest_feature_banner( $gllr_plugin_info, 'gllr_options', 'gallery-plugin' );
			}
		} else {
			$max_filesize        = ini_get( 'upload_max_filesize' );
			$max_input_vars      = ini_get( 'max_input_vars' );
			$cs                  = get_current_screen();
			$is_bws_gallery_page = ( 'add' === $cs->action || 'post' === $cs->base ) && $cs->post_type === $gllr_options['post_type_name'];
			if ( $is_bws_gallery_page && '' !== $max_filesize ) {
				echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Warning', 'gallery-plugin' ) . ': </strong>' . sprintf( esc_html__( 'Maximum upload file size %s. Contact your administrator or hosting provider if you need to upload a large file size.', 'gallery-plugin' ), esc_html( $max_filesize ) ) . '</p></div>';
			}
			if ( $is_bws_gallery_page && '' !== $max_input_vars ) {
				echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Warning', 'gallery-plugin' ) . ': </strong>' . sprintf( esc_html__( 'Input vars count %s i.e. the number of pictures in the gallery may be limited. Contact your administrator or hosting provider if you need to upload a large input vars count.', 'gallery-plugin' ), esc_html( $max_input_vars ) ) . '</p></div>';
			}
		}
	}
}

if ( ! function_exists( 'gllr_get_gallery_data' ) ) {
	/**
	 * Gallery data objects. Takes array of id or empty array, single id or 'all'
	 *
	 * @param number $gallery_id Gallery ID.
	 */
	function gllr_get_gallery_data( $gallery_id ) {
		global $gllr_options;

		if ( empty( $gllr_options ) ) {
			gllr_settings();
		}

		$gallery_images = array();
		$gallery_posts  = array();

		/* Get the entire list of posts or the specified array */
		if ( 'all' === $gallery_id || is_array( $gallery_id ) ) {

			/* Prepare args for get_posts */
			if ( is_array( $gallery_id ) && ! empty( $gallery_id ) ) {
				$gallery_id_list = $gallery_id;
				$args            = array(
					'post_type' => $gllr_options['post_type_name'],
					'include'   => $gallery_id_list,
				);
			} else {
				$args = array( 'post_type' => $gllr_options['post_type_name'] );
			}

			$gallery_posts = get_posts( $args );

		} elseif ( is_int( $gallery_id ) ) {

			$gallery_int_id  = absint( $gallery_id );
			$gallery_posts[] = get_post( $gallery_int_id );

		}

		/* Return false if there are no records */
		if ( ! $gallery_posts ) {
			return false;
		}

		foreach ( (array) $gallery_posts as $gallery_post ) {

			/* Get a id list of gallery pictures */
			$gallery_images_meta = get_post_meta( $gallery_post->ID, '_gallery_images', true );
			$gallery_images_id   = explode( ',', $gallery_images_meta );

			/* Add gallery data to resulting array from wp_posts */
			$gallery_images[ $gallery_post->ID ]['gallery_wp_post'] = $gallery_post;

			foreach ( $gallery_images_id as $image_id ) {

				/* Resulting array with images */
				$gallery_image_post_meta = array();

				$image_post_meta = get_post_meta( $image_id, '', true );
				foreach ( $image_post_meta as $key => $value ) {
					$image_post_meta_item            = get_post_meta( $image_id, $key, true );
					$gallery_image_post_meta[ $key ] = $image_post_meta_item;
				}
				$gallery_images[ $gallery_post->ID ]['gallery_images'][] = array(
					'wp_post_image'   => get_post( $image_id ),
					'image_post_meta' => $gallery_image_post_meta,
				);
			}
		}

		$gallery_objects = $gallery_images;

		return $gallery_objects;
	}
}

if ( ! function_exists( 'gllr_plugin_uninstall' ) ) {
	/**
	 * Perform at uninstall
	 */
	function gllr_plugin_uninstall() {
		global $wpdb, $gllr_bws_demo_data;

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'gallery-plugin-pro/gallery-plugin-pro.php', $all_plugins )
			&& ! array_key_exists( 'gallery-plus/gallery-plus.php', $all_plugins ) ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					gllr_include_demo_data();
					$gllr_bws_demo_data->bws_remove_demo_data();

					delete_option( 'gllr_options' );
					delete_option( 'widget_gallery_categories_widget' );
				}
				switch_to_blog( $old_blog );
			} else {
				gllr_include_demo_data();
				$gllr_bws_demo_data->bws_remove_demo_data();

				delete_option( 'gllr_options' );
				delete_option( 'widget_gallery_categories_widget' );
			}
			delete_metadata( 'user', null, 'wp_gllr_media_library_mode', '', true );
		}

		if ( is_multisite() ) {
			delete_site_option( 'gllr_options' );
		}

		require_once dirname( __FILE__ ) . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/* Activate plugin */
register_activation_hook( __FILE__, 'gllr_plugin_activate' );
add_action( 'after_switch_theme', 'gllr_register_galleries' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'gllr_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'gllr_register_plugin_links', 10, 2 );

add_action( 'admin_menu', 'add_gllr_admin_menu' );
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	add_action( 'network_admin_menu', 'add_gllr_admin_menu' );
}

add_action( 'init', 'gllr_init' );
add_action( 'admin_init', 'gllr_admin_init' );

add_action( 'plugins_loaded', 'gllr_plugins_loaded' );

add_filter( 'rewrite_rules_array', 'gllr_custom_permalinks' ); /* Add custom permalink for gallery */

/* this function returns custom content with images for PDF&Print plugin on Gallery page */
add_filter( 'bwsplgns_get_pdf_print_content', 'gllr_add_pdf_print_content', 100, 2 );

add_action( 'after-gallery_categories-table', 'gllr_add_notice_below_table' );
add_filter( 'manage_edit-gallery_categories_columns', 'gllr_add_column' );
add_filter( 'manage_gallery_categories_custom_column', 'gllr_fill_column', 10, 3 );
add_action( 'post_updated', 'gllr_default_term' );
add_action( 'restrict_manage_posts', 'gllr_taxonomy_filter' );
add_filter( 'gallery_categories_row_actions', 'gllr_hide_delete_link', 10, 2 );
add_action( 'admin_footer-edit-tags.php', 'gllr_hide_delete_cb' );
add_action( 'delete_term_taxonomy', 'gllr_delete_term', 10, 1 );

/* Save custom data from admin  */
add_action( 'save_post', 'gllr_save_postdata', 1, 2 );
/* check post content */
add_filter( 'content_save_pre', 'gllr_content_save_pre', 10, 1 );

add_action( 'pre_get_posts', 'gllr_manage_pre_get_posts', 1 );

add_action( 'gallery_categories_add_form_fields', 'gllr_additive_field_in_category', 10, 2 );
add_action( 'gallery_categories_edit_form_fields', 'gllr_additive_field_in_category_edit', 10, 2 );
add_action( 'edited_gallery_categories', 'gllr_save_category_additive_field', 10, 2 );
add_action( 'create_gallery_categories', 'gllr_save_category_additive_field', 10, 2 );

add_action( 'admin_enqueue_scripts', 'gllr_admin_head' );
add_action( 'wp_head', 'gllr_wp_head' );
add_action( 'wp_footer', 'gllr_wp_footer', 15 );
add_action( 'wp_enqueue_scripts', 'gllr_enqueue_scripts', 100 );

add_filter( 'pgntn_callback', 'gllr_pagination_callback' );

/* add theme name as class to body tag */
add_filter( 'body_class', 'gllr_theme_body_classes' );

add_shortcode( 'print_gllr', 'gllr_shortcode' );
add_filter( 'widget_text', 'do_shortcode' );

add_action( 'wp_ajax_gllr_update_image', 'gllr_update_image' );
add_action( 'admin_notices', 'gllr_admin_notices' );

/*	Add place for notice in media upoader for portfolio	*/
add_action( 'print_media_templates', 'gllr_print_media_notice', 11 );
/*	Add notises in media upoader for gallery	*/
add_action( 'wp_ajax_gllr_media_check', 'gllr_media_check_ajax_action' );

/* custom filter for bws button in tinyMCE */
add_filter( 'bws_shortcode_button_content', 'gllr_shortcode_button_content' );

add_action( 'wp_ajax_gllr_delete_image', 'gllr_delete_image' );
add_action( 'wp_ajax_gllr_add_from_media', 'gllr_add_from_media' );
add_action( 'wp_ajax_gllr_change_view_mode', 'gllr_change_view_mode' );

add_action( 'wp_ajax_gllr_export_slider', 'gllr_export_slider' );
