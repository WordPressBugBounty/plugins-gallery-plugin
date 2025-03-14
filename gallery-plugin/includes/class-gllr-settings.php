<?php
/**
 * Displays the content on the plugin settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Gllr_Settings_Tabs' ) ) {
	/**
	 * Class for diplsay Settings tab
	 */
	class Gllr_Settings_Tabs extends Bws_Settings_Tabs {
		/**
		 * Image sizes.
		 *
		 * @var array
		 */
		public $wp_image_sizes     = array();
		/**
		 * Flag for global settings
		 *
		 * @var boor
		 */
		public $is_global_settings = true;
		/**
		 * Options for custom search
		 *
		 * @var array
		 */
		public $cstmsrch_options;
		/**
		 * Image sizes.
		 *
		 * @var array
		 */
		private $wp_sizes;

		/**
		 * Constructor
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename Plugin basename.
		 */
		public function __construct( $plugin_basename ) {
			global $_wp_additional_image_sizes, $gllr_options, $gllr_plugin_info, $gllr_bws_demo_data;

			$this->is_global_settings = ( isset( $_GET['page'] ) && 'gallery-plugin.php' === $_GET['page'] );

			if ( $this->is_global_settings ) {
				$tabs = array(
					'settings'      => array( 'label' => __( 'Settings', 'gallery-plugin' ) ),
					'cover'         => array( 'label' => __( 'Cover', 'gallery-plugin' ) ),
					'lightbox'      => array( 'label' => __( 'Lightbox', 'gallery-plugin' ) ),
					'social'        => array(
						'label'  => __( 'Social', 'gallery-plugin' ),
						'is_pro' => 1,
					),
					'misc'          => array( 'label' => __( 'Misc', 'gallery-plugin' ) ),
					'custom_code'   => array( 'label' => __( 'Custom Code', 'gallery-plugin' ) ),
					'import-export' => array( 'label' => __( 'Import / Export', 'gallery-plugin' ) ),
					/*pls */
					'license'       => array( 'label' => __( 'License Key', 'gallery-plugin' ) ),
					/* pls*/
				);
			} else {
				$tabs = array(
					'images'   => array( 'label' => __( 'Images', 'gallery-plugin' ) ),
					'settings' => array( 'label' => __( 'Settings', 'gallery-plugin' ) ),
				);
			}

			parent::__construct(
				array(
					'plugin_basename'    => $plugin_basename,
					'plugins_info'       => $gllr_plugin_info,
					'prefix'             => 'gllr',
					'default_options'    => gllr_get_options_default(),
					'options'            => $gllr_options,
					'is_network_options' => is_network_admin(),
					'tabs'               => $tabs,
					'doc_link'           => 'https://bestwebsoft.com/documentation/gallery/gallery-user-guide/',
					'demo_data'          => $gllr_bws_demo_data,
					/*pls */
					'wp_slug'            => 'gallery-plugin',
					'link_key'           => '63a36f6bf5de0726ad6a43a165f38fe5',
					'link_pn'            => '79',
					/* pls*/
					'trial_days'         => 7,
				)
			);

			$this->wp_sizes = get_intermediate_image_sizes();

			foreach ( (array) $this->wp_sizes as $size ) {
				if ( ! array_key_exists( $size, $gllr_options['custom_size_px'] ) ) {
					if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
						$width  = absint( $_wp_additional_image_sizes[ $size ]['width'] );
						$height = absint( $_wp_additional_image_sizes[ $size ]['height'] );
					} else {
						$width  = absint( get_option( $size . '_size_w' ) );
						$height = absint( get_option( $size . '_size_h' ) );
					}

					if ( ! $width && ! $height ) {
						$this->wp_image_sizes[] = array(
							'value' => $size,
							'name'  => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ),
						);
					} else {
						$this->wp_image_sizes[] = array(
							'value'  => $size,
							'name'   => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ) . ' ( ' . $width . ' &#215; ' . $height . ' ) ',
							'width'  => $width,
							'height' => $height,
						);
					}
				}
			}

			$this->cstmsrch_options = get_option( 'cstmsrch_options' );

			add_action( get_parent_class( $this ) . '_display_custom_messages', array( $this, 'display_custom_messages' ) );
			add_action( get_parent_class( $this ) . '_additional_misc_options_affected', array( $this, 'additional_misc_options_affected' ) );
			add_action( get_parent_class( $this ) . '_additional_import_export_options', array( $this, 'additional_import_export_options' ) );
			add_filter( get_parent_class( $this ) . '_additional_restore_options', array( $this, 'additional_restore_options' ) );
		}

		/**
		 * Save plugin options to the database
		 *
		 * @access public
		 * @return array The action results
		 */
		public function save_options() {
			global $gllr_upload_errors;
			$message = '';
			$notice  = '';
			$error   = '';

			if ( isset( $_POST['gllr_import_submit'] ) ) {
				if ( ! empty( $gllr_upload_errors ) ) {
					$error = $gllr_upload_errors;
				} else {
					$message .= __( 'Import completed successfully', 'gallery-plugin' );
				}
			} elseif ( isset( $_POST['gllr_settings_nonce_field'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gllr_settings_nonce_field'] ) ), 'gllr_settings_action' )
			) {

				$image_names      = array( 'photo', 'album' );
				$this->wp_sizes[] = 'full';
				foreach ( $image_names as $name ) {
					$new_image_size      = isset( $_POST[ 'gllr_image_size_' . $name ] ) && in_array( sanitize_text_field( wp_unslash( $_POST[ 'gllr_image_size_' . $name ] ) ), $this->wp_sizes, true ) ? sanitize_text_field( wp_unslash( $_POST[ 'gllr_image_size_' . $name ] ) ) : $this->options[ 'image_size_' . $name ];
					$custom_image_size_w = isset( $_POST[ 'gllr_custom_image_size_w_' . $name ] ) && is_numeric( $_POST[ 'gllr_custom_image_size_w_' . $name ] ) ? absint( $_POST[ 'gllr_custom_image_size_w_' . $name ] ) : $this->options['custom_size_px'][ $name . '-thumb' ][0];
					$custom_image_size_h = isset( $_POST[ 'gllr_custom_image_size_h_' . $name ] ) && is_numeric( $_POST[ 'gllr_custom_image_size_h_' . $name ] ) ? absint( $_POST[ 'gllr_custom_image_size_h_' . $name ] ) : $this->options['custom_size_px'][ $name . '-thumb' ][1];
					$custom_size_px      = array( $custom_image_size_w, $custom_image_size_h );

					if ( $name . '-thumb' === $new_image_size ) {
						if ( $new_image_size !== $this->options[ 'image_size_' . $name ] ) {
							$need_image_update = true;
						} else {
							foreach ( $custom_size_px as $key => $value ) {
								if ( $value !== $this->options['custom_size_px'][ $name . '-thumb' ][ $key ] ) {
									$need_image_update = true;
									break;
								}
							}
						}
					}
					$this->options[ 'image_size_' . $name ]              = $new_image_size;
					$this->options['custom_size_px'][ $name . '-thumb' ] = ( $name . '-thumb' === $this->options[ 'image_size_' . $name ] ) ? $custom_size_px : $this->options['custom_size_px'][ $name . '-thumb' ];
				}

				/* Settings Tab */
				$this->options['custom_image_row_count']   = isset( $_POST['gllr_custom_image_row_count'] ) && is_numeric( $_POST['gllr_custom_image_row_count'] ) ? absint( $_POST['gllr_custom_image_row_count'] ) : $this->options['custom_image_row_count'];
				$this->options['image_text']               = isset( $_POST['gllr_image_text'] ) ? 1 : 0;
				$this->options['border_images']            = isset( $_POST['gllr_border_images'] ) ? 1 : 0;
				$this->options['border_images_width']      = isset( $_POST['gllr_border_images_width'] ) && is_numeric( $_POST['gllr_border_images_width'] ) ? absint( $_POST['gllr_border_images_width'] ) : $this->options['border_images_width'];
				$this->options['border_images_color']      = isset( $_POST['gllr_border_images_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['gllr_border_images_color'] ) ) : $this->options['border_images_color'];
				$this->options['order_by']                 = isset( $_POST['gllr_order_by'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['gllr_order_by'] ) ), array( 'meta_value_num', 'ID', 'title', 'date', 'rand' ), true ) ? sanitize_text_field( wp_unslash( $_POST['gllr_order_by'] ) ) : $this->options['order_by'];
				$this->options['order']                    = isset( $_POST['gllr_order'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['gllr_order'] ) ), array( 'ASC', 'DESC' ), true ) ? sanitize_text_field( wp_unslash( $_POST['gllr_order'] ) ) : $this->options['order'];
				$this->options['return_link']              = isset( $_POST['gllr_return_link'] ) ? 1 : 0;
				$this->options['return_link_url']          = isset( $_POST['gllr_return_link_url'] ) ? esc_url( wp_unslash( $_POST['gllr_return_link_url'] ) ) : $this->options['return_link_url'];
				$this->options['return_link_text']         = isset( $_POST['gllr_return_link_text'] ) ? sanitize_text_field( wp_unslash( $_POST['gllr_return_link_text'] ) ) : $this->options['return_link_text'];
				$this->options['return_link_shortcode']    = isset( $_POST['gllr_return_link_shortcode'] ) && isset( $_POST['gllr_return_link'] ) ? 1 : 0;
				$this->options['disable_foreing_fancybox'] = isset( $_POST['gllr_disable_foreing_fancybox'] ) ? 1 : 0;

				/* Cover Tab for rewrite */
				if ( isset( $_POST['gllr_page_id_gallery_template'] ) && $this->options['page_id_gallery_template'] !== $_POST['gllr_page_id_gallery_template'] ) {
					$this->options['flush_rewrite_rules'] = 1;
				}
				$this->options['page_id_gallery_template'] = isset( $_POST['gllr_page_id_gallery_template'] ) && is_numeric( $_POST['gllr_page_id_gallery_template'] ) ? absint( $_POST['gllr_page_id_gallery_template'] ) : '';

				$this->options['galleries_layout']           = isset( $_POST['gllr_layout'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['gllr_layout'] ) ), array( 'column', 'rows' ), true ) ? sanitize_text_field( wp_unslash( $_POST['gllr_layout'] ) ) : $this->options['galleries_layout'];
				$this->options['galleries_column_alignment'] = isset( $_POST['gllr_column_align'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['gllr_column_align'] ) ), array( 'left', 'right', 'center' ), true ) ? sanitize_text_field( wp_unslash( $_POST['gllr_column_align'] ) ) : $this->options['galleries_column_alignment'];
				$this->options['cover_border_images']        = isset( $_POST['gllr_cover_border_images'] ) ? 1 : 0;
				$this->options['cover_border_images_width']  = isset( $_POST['gllr_cover_border_images_width'] ) && is_numeric( $_POST['gllr_cover_border_images_width'] ) ? absint( $_POST['gllr_cover_border_images_width'] ) : $this->options['cover_border_images_width'];
				$this->options['cover_border_images_color']  = isset( $_POST['gllr_cover_border_images_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['gllr_cover_border_images_color'] ) ) : $this->options['cover_border_images_color'];
				$this->options['album_order_by']             = isset( $_POST['gllr_album_order_by'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['gllr_album_order_by'] ) ), array( 'ID', 'title', 'date', 'modified', 'comment_count', 'menu_order', 'author', 'rand' ), true ) ? sanitize_text_field( wp_unslash( $_POST['gllr_album_order_by'] ) ) : $this->options['album_order_by'];
				$this->options['album_order']                = isset( $_POST['gllr_album_order'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['gllr_album_order'] ) ), array( 'ASC', 'DESC' ), true ) ? sanitize_text_field( wp_unslash( $_POST['gllr_album_order'] ) ) : $this->options['album_order'];
				$this->options['read_more_link_text']        = isset( $_POST['gllr_read_more_link_text'] ) ? sanitize_text_field( wp_unslash( $_POST['gllr_read_more_link_text'] ) ) : $this->options['read_more_link_text'];

				/* Lightbox Tab */
				$this->options['enable_image_opening']                   = isset( $_POST['gllr_enable_image_opening'] ) ? 1 : 0;
				$this->options['enable_lightbox']                        = isset( $_POST['gllr_enable_lightbox'] ) ? 1 : 0;
				$this->options['start_slideshow']                        = isset( $_POST['gllr_start_slideshow'] ) ? 1 : 0;
				$this->options['slideshow_interval']                     = isset( $_POST['gllr_slideshow_interval'] ) && is_numeric( $_POST['gllr_slideshow_interval'] ) ? absint( $_POST['gllr_slideshow_interval'] ) : $this->options['slideshow_interval'];
				$this->options['lightbox_download_link']                 = isset( $_POST['gllr_lightbox_download_link'] ) ? 1 : 0;
				$this->options['lightbox_arrows']                        = isset( $_POST['gllr_lightbox_arrows'] ) ? 1 : 0;
				$this->options['single_lightbox_for_multiple_galleries'] = isset( $_POST['gllr_single_lightbox_for_multiple_galleries'] ) ? 1 : 0;

				$this->options = apply_filters( 'gllr_save_additional_options', $this->options );

				/**
				 * Rewriting post types name with unique one from default options
				 *
				 * @since 4.4.4
				 */
				if ( ! empty( $_POST['gllr_rename_post_type'] ) ) {
					global $wpdb;
					$wpdb->update(
						$wpdb->prefix . 'posts',
						array(
							'post_type' => $this->default_options['post_type_name'],
						),
						array(
							'post_type' => 'gallery',
						)
					);
					$this->options['post_type_name'] = $this->default_options['post_type_name'];
				}

				if ( isset( $need_image_update ) ) {
					$this->options['need_image_update'] = 1;
				}

				if ( ! empty( $this->cstmsrch_options ) ) {
					if ( isset( $this->cstmsrch_options['output_order'] ) ) {
						$is_enabled      = isset( $_POST['gllr_add_to_search'] ) ? 1 : 0;
						$post_type_exist = false;
						foreach ( $this->cstmsrch_options['output_order'] as $key => $item ) {
							if ( isset( $item['name'] ) && $item['name'] === $this->options['post_type_name'] && 'post_type' === $item['type'] ) {
								$post_type_exist = true;
								if ( $item['enabled'] !== $is_enabled ) {
									$this->cstmsrch_options['output_order'][ $key ]['enabled'] = $is_enabled;
									$cstmsrch_options_update                                   = true;
								}
								break;
							}
						}
						if ( ! $post_type_exist ) {
							$this->cstmsrch_options['output_order'][] = array(
								'name'    => $this->options['post_type_name'],
								'type'    => 'post_type',
								'enabled' => $is_enabled,
							);
							$cstmsrch_options_update                  = true;
						}
					} elseif ( isset( $this->cstmsrch_options['post_types'] ) ) {
						if ( isset( $_POST['gllr_add_to_search'] ) && ! in_array( $this->options['post_type_name'], $this->cstmsrch_options['post_types'] ) ) {
							array_push( $this->cstmsrch_options['post_types'], $this->options['post_type_name'] );
							$cstmsrch_options_update = true;
						} elseif ( ! isset( $_POST['gllr_add_to_search'] ) && in_array( $this->options['post_type_name'], $this->cstmsrch_options['post_types'] ) ) {
							unset( $this->cstmsrch_options['post_types'][ array_search( $this->options['post_type_name'], $this->cstmsrch_options['post_types'] ) ] );
							$cstmsrch_options_update = true;
						}
					}
					if ( isset( $cstmsrch_options_update ) ) {
						update_option( 'cstmsrch_options', $this->cstmsrch_options );
					}
				}

				update_option( 'gllr_options', $this->options );
				$message .= __( 'Settings saved', 'gallery-plugin' );
			}

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 * Display custom error\message\notice
		 *
		 * @access public
		 * @param array $save_results - array with error\message\notice.
		 * @return void
		 */
		public function display_custom_messages( $save_results ) { ?>
			<noscript><div class="error below-h2"><p><strong><?php esc_html_e( 'Please, enable JavaScript in Your browser.', 'gallery-plugin' ); ?></strong></p></div></noscript>
			<?php if ( isset( $this->options['need_image_update'] ) ) { ?>
				<div class="updated bws-notice inline gllr_image_update_message">
					<p>
						<?php esc_html_e( 'Custom image size was changed. You need to update gallery images.', 'gallery-plugin' ); ?>
						<input type="button" value="<?php esc_html_e( 'Update Images', 'gallery-plugin' ); ?>" id="gllr_ajax_update_images" name="ajax_update_images" class="button" />
					</p>
				</div>
				<?php
			}
		}

		/**
		 * Display tab image
		 */
		public function tab_images() {
			global $post, $gllr_mode, $original_post;
			$original_post = $post;

			$wp_gallery_media_table = new Gllr_Media_Table();
			$wp_gallery_media_table->prepare_items();
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Gallery Images', 'gallery-plugin' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<div>
				<h4><?php esc_html_e( 'The number of images you can add to the gallery depends on your server settings. If you have problems saving the gallery, try reducing the number of images in it.', 'gallery-plugin' ); ?></h4>
				<div class="error hide-if-js">
					<p><?php esc_html_e( 'Images adding requires JavaScript.', 'gallery-plugin' ); ?></p>
				</div>
				<div class="wp-media-buttons">
					<a href="#" id="gllr-media-insert" class="button insert-media add_media hide-if-no-js"><span class="wp-media-buttons-icon"></span> <?php esc_html_e( 'Add Media', 'gallery-plugin' ); ?></a>
				</div>
				<?php $wp_gallery_media_table->views(); ?>
			</div>
			<div class="clear"></div>
			<?php
			if ( 'list' === $gllr_mode ) {
				$wp_gallery_media_table->display();
			} else {
				?>
				<div class="error hide-if-js">
					<p><?php esc_html_e( 'The grid view for the Gallery images requires JavaScript.', 'gallery-plugin' ); ?> <a href="<?php echo esc_url( add_query_arg( 'mode', 'list', filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ); ?>"><?php esc_html_e( 'Switch to the list view', 'gallery-plugin' ); ?></a></p>
				</div>
				<ul tabindex="-1" class="attachments ui-sortable ui-sortable-disabled hide-if-no-js" id="__attachments-view-39">
					<?php $wp_gallery_media_table->display_grid_rows(); ?>
				</ul>
			<?php } ?>
			<div class="clear"></div>
			<div id="hidden"></div>
			<?php wp_nonce_field( 'gllr_action', 'gllr_nonce_field' ); ?>
			<?php
		}

		/**
		 * Display tab settings
		 */
		public function tab_settings() {

			$wp_gallery_media_table = new Gllr_Media_Table();
			$wp_gallery_media_table->prepare_items();

			$all_plugins   = get_plugins();
			$attrs         = '';
			$plugin_notice = '';
			?>

			<h3 class="bws_tab_label"><?php esc_html_e( 'Gallery Settings', 'gallery-plugin' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<?php if ( ! $this->is_global_settings ) { ?>
				<table class="form-table ">
					<tr>
						<th scope="row"><?php esc_html_e( 'Create Slider', 'gallery-plugin' ); ?> </th>
						<td>
							<?php
							$plugin_info = gallery_plugin_status(
								'slider-bws/slider-bws.php',
								$all_plugins,
								$this->is_network_options
							);
							if ( '0' === $wp_gallery_media_table->_pagination_args['total_items'] ) {
								$attrs = 'disabled="disabled"';
							}
							if ( 'deactivated' === $plugin_info['status'] ) {
								$attrs         = 'disabled="disabled"';
								$plugin_notice = ' <a href="' . self_admin_url( 'plugins.php' ) . '">' . __( 'Activate', 'gallery-plugin' ) . '</a>';
							} elseif ( 'not_installed' === $plugin_info['status'] ) {
								$attrs         = 'disabled="disabled"';
								$plugin_notice = ' <a href="https://bestwebsoft.com/products/wordpress/plugins/slider/ " target="_blank">' . __( 'Install Now', 'gallery-plugin' ) . '</a>';
							}
							$export = __( 'Create New Slider', 'gallery-plugin' )
							?>
							<input type="button"  class="button" <?php echo esc_html( $attrs ); ?> id="gllr-export-slider" name="gllr-export-slider" value="<?php echo esc_attr( $export ); ?>">
							<span id="gllr_export_loader" class="gllr_loader"><img src="<?php echo esc_url( plugins_url( '../images/ajax-loader.gif', __FILE__ ) ); ?>" alt="loader" /></span><br />
							<span class="bws_info">
							<?php
							esc_html_e( 'Click to create a new slider using gallery images. Slider plugin is required.', 'gallery-plugin' );
							echo wp_kses_post( $plugin_notice );
							?>
							</span>
						</td>
					</tr>
				</table>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th scope="row"><?php esc_html_e( 'Single Gallery Settings', 'gallery-plugin' ); ?> </th>
									<td>
										<input disabled="disabled" type="checkbox" /> <span class="bws_info"><?php printf( esc_html__( 'Enable to configure single gallery settings and disable %s.', 'gallery-plugin' ), '<a style="z-index: 2;position: relative;" href="edit.php?post_type=' . esc_attr( $this->options['post_type_name'] ) . '&page=gallery-plugin.php" target="_blank">' . esc_html__( 'Global Settings', 'gallery-plugin' ) . '</a>' ); ?></span>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<?php } ?>
			<?php } else { ?>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th scope="row"><?php esc_html_e( 'Gallery Layout', 'gallery-plugin' ); ?> </th>
									<td>
										<fieldset>
											<label>
												<input disabled="disabled" type="radio" checked="checked" />
												<?php esc_html_e( 'Grid', 'gallery-plugin' ); ?>
												<?php echo wp_kses_post( bws_add_help_box( '<img src="' . plugins_url( 'images/view_grid.jpg', dirname( __FILE__ ) ) . '" />', 'bws-hide-for-mobile bws-auto-width' ) ); ?>
											</label>
											<br />
											<label>
												<input disabled="disabled" type="radio" />
												<?php esc_html_e( 'Masonry', 'gallery-plugin' ); ?>
												<?php echo wp_kses_post( bws_add_help_box( '<img src="' . plugins_url( 'images/view_masonry.jpg', dirname( __FILE__ ) ) . '" />', 'bws-hide-for-mobile bws-auto-width' ) ); ?>
											</label>
											<br />
											<label>
												<input disabled="disabled" type="radio" />
												<?php esc_html_e( 'Carousel', 'gallery-plugin' ); ?>
												<?php echo wp_kses_post( bws_add_help_box( '<img src="' . plugins_url( 'images/view_carousel.jpg', dirname( __FILE__ ) ) . '" />', 'bws-hide-for-mobile bws-auto-width' ) ); ?>
											</label>
											<br />
											<label>
												<input disabled="disabled" type="radio" />
												<?php esc_html_e( 'Tilled', 'gallery-plugin' ); ?>
												<?php echo wp_kses_post( bws_add_help_box( '<img src="' . plugins_url( 'images/view_tilled.jpg', dirname( __FILE__ ) ) . '" />', 'bws-hide-for-mobile bws-auto-width' ) ); ?>
											</label>
										</fieldset>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<?php } ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Number of Columns', 'gallery-plugin' ); ?> </th>
						<td>
							<input type="number" name="gllr_custom_image_row_count" min="1" max="10000" value="<?php echo esc_attr( $this->options['custom_image_row_count'] ); ?>" />
							<div class="bws_info"><?php printf( esc_html__( 'Number of gallery columns (default is %s).', 'gallery-plugin' ), '3' ); ?></div>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Image Size', 'gallery-plugin' ); ?> </th>
						<td>
							<select name="gllr_image_size_photo">
								<?php foreach ( $this->wp_image_sizes as $data ) { ?>
									<option value="<?php echo esc_attr( $data['value'] ); ?>" <?php selected( $data['value'], $this->options['image_size_photo'] ); ?>><?php echo esc_html( $data['name'] ); ?></option>
								<?php } ?>
								<option value="photo-thumb" <?php selected( 'photo-thumb', $this->options['image_size_photo'] ); ?> class="bws_option_affect" data-affect-show=".gllr_for_custom_image_size"><?php esc_html_e( 'Custom', 'gallery-plugin' ); ?></option>
							</select>
							<div class="bws_info"><?php esc_html_e( 'Maximum gallery image size. "Custom" uses the Image Dimensions values.', 'gallery-plugin' ); ?></div>
						</td>
					</tr>
					<tr class="gllr_for_custom_image_size">
						<th scope="row"><?php esc_html_e( 'Custom Image Size', 'gallery-plugin' ); ?> </th>
						<td>
							<input type="number" name="gllr_custom_image_size_w_photo" min="1" max="10000" value="<?php echo esc_attr( $this->options['custom_size_px']['photo-thumb'][0] ); ?>" /> x <input type="number" name="gllr_custom_image_size_h_photo" min="1" max="10000" value="<?php echo esc_attr( $this->options['custom_size_px']['photo-thumb'][1] ); ?>" /> <?php esc_html_e( 'px', 'gallery-plugin' ); ?>
							<div class="bws_info"><?php esc_html_e( "Adjust these values based on the number of columns in your gallery. This won't effect the full size of your images in the lightbox.", 'gallery-plugin' ); ?></div>
						</td>
					</tr>
				</table>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					<div class="bws_pro_version_bloc gllr_for_custom_image_size">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th scope="row"><?php esc_html_e( 'Crop Images', 'gallery-plugin' ); ?></th>
									<td>
										<input disabled checked type="checkbox" /> <span class="bws_info"><?php esc_html_e( 'Enable to crop images using the sizes defined for Custom Image Size. Disable to resize images automatically using their aspect ratio.', 'gallery-plugin' ); ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Crop Position', 'gallery-plugin' ); ?></th>
									<td>
										<div>
											<input disabled type="radio" />
											<input disabled type="radio" />
											<input disabled type="radio" />
											<br>
											<input disabled type="radio" />
											<input disabled checked type="radio" />
											<input disabled type="radio" />
											<br>
											<input disabled type="radio" />
											<input disabled type="radio" />
											<input disabled type="radio" />
										</div>
										<div class="bws_info"><?php esc_html_e( 'Select crop position base (by default: center).', 'gallery-plugin' ); ?></div>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<?php } ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Image Title', 'gallery-plugin' ); ?></th>
						<td>
							<input type="checkbox" name="gllr_image_text" value="1" <?php checked( 1, $this->options['image_text'] ); ?> class="bws_option_affect" data-affect-show=".gllr_for_image_text" /> <span class="bws_info"><?php esc_html_e( 'Enable to display image title along with the gallery image.', 'gallery-plugin' ); ?></span>
						</td>
					</tr>
				</table>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					<div class="bws_pro_version_bloc gllr_for_image_text">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th scope="row"><?php esc_html_e( 'Image Title Position', 'gallery-plugin' ); ?></th>
									<td>
										<fieldset>
											<label>
												<input disabled type="radio" value="under" checked="checked">
												<?php esc_html_e( 'Below images', 'gallery-plugin' ); ?>
												<?php echo wp_kses_post( bws_add_help_box( '<img src="' . plugins_url( 'images/display_text_under_image.jpg', dirname( __FILE__ ) ) . '" />', 'bws-hide-for-mobile bws-auto-width' ) ); ?>
											</label>
											<br/>
											<label>
												<input disabled type="radio" value="hover">
												<?php esc_html_e( 'On mouse hover', 'gallery-plugin' ); ?>
												<?php echo wp_kses_post( bws_add_help_box( '<img src="' . plugins_url( 'images/display_text_by_mouse_hover.jpg', dirname( __FILE__ ) ) . '" />', 'bws-hide-for-mobile bws-auto-width' ) ); ?>
											</label>
										</fieldset>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<?php } ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Image Border', 'gallery-plugin' ); ?></th>
						<td>
							<input type="checkbox" name="gllr_border_images" value="1" 
							<?php
							if ( 1 === absint( $this->options['border_images'] ) ) {
								echo 'checked="checked"';}
							?>
							class="bws_option_affect" data-affect-show=".gllr_for_border_images" /> <span class="bws_info"><?php esc_html_e( 'Enable images border using the styles defined for Image Border Size and Color options.', 'gallery-plugin' ); ?></span>
						</td>
					</tr>
					<tr class="gllr_for_border_images">
						<th scope="row"><?php esc_html_e( 'Image Border Size', 'gallery-plugin' ); ?></th>
						<td>
							<input type="number" min="0" max="10000" value="<?php echo esc_attr( $this->options['border_images_width'] ); ?>" name="gllr_border_images_width" /> <?php esc_html_e( 'px', 'gallery-plugin' ); ?>
							<div class="bws_info"><?php printf( esc_html__( 'Gallery image border width (default is %s).', 'gallery-plugin' ), '10px' ); ?></div>
						</td>
					</tr>
					<tr class="gllr_for_border_images">
						<th scope="row"><?php esc_html_e( 'Image Border Color', 'gallery-plugin' ); ?></th>
						<td>
							<input type="text" value="<?php echo esc_attr( $this->options['border_images_color'] ); ?>" name="gllr_border_images_color" class="gllr_color_field" data-default-color="#F1F1F1" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Unclickable Thumbnail Images', 'gallery-plugin' ); ?> </th>
						<td>
							<input type="checkbox" name="gllr_enable_image_opening" 
							<?php
							if ( 1 === absint( $this->options['enable_image_opening'] ) ) {
								echo 'checked="checked"';}
							?>
							/>
							<span class="bws_info"><?php esc_html_e( 'Enable to make the images in a single gallery unclickable and hide their URLs. This option also disables Lightbox.', 'gallery-plugin' ); ?></span>
						</td>
					</tr>
				</table>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th scope="row"><?php esc_html_e( 'Pagination', 'gallery-plugin' ); ?></th>
									<td>
										<input disabled type="checkbox" value="1" />
										<span class="bws_info"><?php esc_html_e( 'Enable pagination for images to limit number of images displayed on a single gallery page.', 'gallery-plugin' ); ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Number of Images', 'gallery-plugin' ); ?></th>
									<td>
										<input disabled type="number" value="10" />
										<div class="bws_info"><?php printf( esc_html__( 'Number of images displayed per page (default is %d).', 'gallery-plugin' ), '10' ); ?></div>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<?php } ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Sort Images by', 'gallery-plugin' ); ?></th>
						<td>
							<select name="gllr_order_by">
								<option value="meta_value_num" <?php selected( 'meta_value_num', $this->options['order_by'] ); ?>><?php esc_html_e( 'Manually (default)', 'gallery-plugin' ); ?></option>
								<option value="ID" <?php selected( 'ID', $this->options['order_by'] ); ?>><?php esc_html_e( 'Image ID', 'gallery-plugin' ); ?></option>
								<option value="title" <?php selected( 'title', $this->options['order_by'] ); ?>><?php esc_html_e( 'Name', 'gallery-plugin' ); ?></option>
								<option value="date" <?php selected( 'date', $this->options['order_by'] ); ?>><?php esc_html_e( 'Date', 'gallery-plugin' ); ?></option>
								<option value="rand" <?php selected( 'rand', $this->options['order_by'] ); ?> class="bws_option_affect" data-affect-hide=".gllr_image_order"><?php esc_html_e( 'Random', 'gallery-plugin' ); ?></option>
							</select>
							<div class="bws_info"><?php esc_html_e( 'Select images sorting order in your gallery. By default, you can sort images manually in the images tab.', 'gallery-plugin' ); ?></div>
						</td>
					</tr>
					<tr class="gllr_image_order">
						<th scope="row"><?php esc_html_e( 'Arrange Images by', 'gallery-plugin' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="gllr_order" value="ASC" 
								<?php
								if ( 'ASC' === $this->options['order'] ) {
									echo 'checked="checked"';}
								?>
								/> <?php esc_html_e( 'Ascending (e.g. 1, 2, 3; a, b, c)', 'gallery-plugin' ); ?></label><br />
								<label><input type="radio" name="gllr_order" value="DESC" 
								<?php
								if ( 'DESC' === $this->options['order'] ) {
									echo 'checked="checked"';}
								?>
								/> <?php esc_html_e( 'Descending (e.g. 3, 2, 1; c, b, a)', 'gallery-plugin' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Back Link', 'gallery-plugin' ); ?></th>
						<td>
							<input type="checkbox" name="gllr_return_link" value="1" 
							<?php
							if ( 1 === absint( $this->options['return_link'] ) ) {
								echo 'checked="checked"';}
							?>
							class="bws_option_affect" data-affect-show=".gllr_for_return_link" /> <span class="bws_info"><?php esc_html_e( 'Enable to show a back link in a single gallery page which navigate to a previous page.', 'gallery-plugin' ); ?></span>
						</td>
					</tr>
					<tr class="gllr_for_return_link">
						<th scope="row"><?php esc_html_e( 'Back Link URL', 'gallery-plugin' ); ?></th>
						<td>
							<input type="text" value="<?php echo esc_url( $this->options['return_link_url'] ); ?>" name="gllr_return_link_url" maxlength="250" />
							<div class="bws_info"><?php esc_html_e( 'Leave blank to use the Gallery page template or enter a custom page URL.', 'gallery-plugin' ); ?></div>
						</td>
					</tr>
					<tr class="gllr_for_return_link">
						<th scope="row"><?php esc_html_e( 'Back Link Label', 'gallery-plugin' ); ?> </th>
						<td>
							<input type="text" name="gllr_return_link_text" maxlength="250" value="<?php echo esc_html( $this->options['return_link_text'] ); ?>" />
						</td>
					</tr>
					<tr class="gllr_for_return_link">
						<th scope="row"><?php esc_html_e( 'Back Link with Shortcode', 'gallery-plugin' ); ?> </th>
						<td>
							<input type="checkbox" name="gllr_return_link_shortcode" value="1" 
							<?php
							if ( 1 === absint( $this->options['return_link_shortcode'] ) ) {
								echo 'checked="checked"';}
							?>
							/>
							<span class="bws_info"><?php esc_html_e( 'Enable to display a back link on a page where shortcode is used.', 'gallery-plugin' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Fancybox', 'gallery-plugin' ); ?></th>
						<td>
							<input type="checkbox" name="gllr_disable_foreing_fancybox" value="1" 
							<?php
							if ( 1 === absint( $this->options['disable_foreing_fancybox'] ) ) {
								echo 'checked="checked"';}
							?>
							/> <span class="bws_info"><?php esc_html_e( 'Enable to avoid possible conflicts with a 3rd party Fancybox.', 'gallery-plugin' ); ?></span>
						</td>
					</tr>
				</table>
				<?php wp_nonce_field( 'gllr_settings_action', 'gllr_settings_nonce_field' ); ?>
				<?php
			}
		}

		/**
		 * Display tab Cover
		 */
		public function tab_cover() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Cover Settings', 'gallery-plugin' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Galleries Page', 'gallery-plugin' ); ?></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'depth'            => 0,
								'selected'         => ( isset( $this->options['page_id_gallery_template'] ) ? esc_attr( $this->options['page_id_gallery_template'] ) : false ),
								'name'             => 'gllr_page_id_gallery_template',
								'show_option_none' => '...',
							)
						);
						?>
						<div class="bws_info"><?php esc_html_e( 'Base page where all existing galleries will be displayed.', 'gallery-plugin' ); ?></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Albums Displaying', 'gallery-plugin' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="gllr_layout" value="column" id="gllr_column" <?php checked( 'column' === $this->options['galleries_layout'] ); ?> class="bws_option_affect" data-affect-show=".gllr_column_alignment" /> <?php esc_html_e( 'Column', 'gallery-plugin' ); ?></label><br/>
							<label><input type="radio" name="gllr_layout" value="rows" id="gllr_rows" <?php checked( 'rows' === $this->options['galleries_layout'] ); ?> class="bws_option_affect" data-affect-hide=".gllr_column_alignment" /> <?php esc_html_e( 'Rows', 'gallery-plugin' ); ?></label>
						</fieldset>
						<div class="bws_info"><?php esc_html_e( 'Select the way galleries will be displayed on the Galleries Page.', 'gallery-plugin' ); ?></div>
					</td>
				</tr>
				<tr class="gllr_column_alignment">
					<th scope="row"><?php esc_html_e( 'Column Alignment', 'gallery-plugin' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="gllr_column_align" value="left" <?php checked( 'left' === $this->options['galleries_column_alignment'] ); ?> /> <?php esc_html_e( 'Left', 'gallery-plugin' ); ?></label><br/>
							<label><input type="radio" name="gllr_column_align" value="right" <?php checked( 'right' === $this->options['galleries_column_alignment'] ); ?> /> <?php esc_html_e( 'Right', 'gallery-plugin' ); ?></label><br/>
							<label><input type="radio" name="gllr_column_align" value="center" <?php checked( 'center' === $this->options['galleries_column_alignment'] ); ?> /> <?php esc_html_e( 'Center', 'gallery-plugin' ); ?></label>
						</fieldset>
						<div class="bws_info"><?php esc_html_e( 'Select the column alignment.', 'gallery-plugin' ); ?></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cover Image Size', 'gallery-plugin' ); ?> </th>
					<td>
						<select name="gllr_image_size_album">
							<?php foreach ( $this->wp_image_sizes as $data ) { ?>
								<option value="<?php echo esc_attr( $data['value'] ); ?>" <?php selected( $data['value'], $this->options['image_size_album'] ); ?>><?php echo esc_html( $data['name'] ); ?></option>
							<?php } ?>
							<option value="album-thumb" <?php selected( 'album-thumb', $this->options['image_size_album'] ); ?> class="bws_option_affect" data-affect-show=".gllr_for_custom_image_size_album"><?php esc_html_e( 'Custom', 'gallery-plugin' ); ?></option>
						</select>
						<div class="bws_info"><?php esc_html_e( 'Maximum cover image size. Custom uses the Image Dimensions values.', 'gallery-plugin' ); ?></div>
					</td>
				</tr>
				<tr class="gllr_for_custom_image_size_album">
					<th scope="row"><?php esc_html_e( 'Custom Cover Image Size', 'gallery-plugin' ); ?> </th>
					<td>
						<input type="number" name="gllr_custom_image_size_w_album" min="1" max="10000" value="<?php echo esc_attr( $this->options['custom_size_px']['album-thumb'][0] ); ?>" /> x <input type="number" name="gllr_custom_image_size_h_album" min="1" max="10000" value="<?php echo esc_attr( $this->options['custom_size_px']['album-thumb'][1] ); ?>" /> <?php esc_html_e( 'px', 'gallery-plugin' ); ?>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc gllr_for_custom_image_size_album">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th scope="row"><?php esc_html_e( 'Crop Cover Images', 'gallery-plugin' ); ?></th>
								<td>
									<input disabled checked type="checkbox" name="" /> <span class="bws_info"><?php esc_html_e( 'Enable to crop images using the sizes defined for Custom Cover Image Size. Disable to resize images automatically using their aspect ratio.', 'gallery-plugin' ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Crop Position', 'gallery-plugin' ); ?></th>
								<td>
									<div>
										<input disabled type="radio" name="" />
										<input disabled type="radio" name="" />
										<input disabled type="radio" name="" />
										<br>
										<input disabled type="radio" name="" />
										<input disabled checked type="radio" name="" />
										<input disabled type="radio" name="" />
										<br>
										<input disabled type="radio" name="" />
										<input disabled type="radio" name="" />
										<input disabled type="radio" name="" />
									</div>
									<div class="bws_info"><?php esc_html_e( 'Select crop position base (by default: center).', 'gallery-plugin' ); ?></div>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Cover Image Border', 'gallery-plugin' ); ?></th>
					<td>
						<input type="checkbox" name="gllr_cover_border_images" value="1" 
						<?php
						if ( 1 === absint( $this->options['cover_border_images'] ) ) {
							echo 'checked="checked"';}
						?>
						class="bws_option_affect" data-affect-show=".gllr_for_cover_border_images" /> <span class="bws_info"><?php esc_html_e( 'Enable cover images border using the styles defined for Image Border Size and Color.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
				<tr class="gllr_for_cover_border_images">
					<th scope="row"><?php esc_html_e( 'Cover Image Border Size', 'gallery-plugin' ); ?></th>
					<td>
						<input type="number" min="0" max="10000" value="<?php echo esc_attr( $this->options['cover_border_images_width'] ); ?>" name="gllr_cover_border_images_width" /> <?php esc_html_e( 'px', 'gallery-plugin' ); ?>
						<div class="bws_info"><?php printf( esc_html__( 'Cover image border width (default is %s).', 'gallery-plugin' ), '10px' ); ?></div>
					</td>
				</tr>
				<tr class="gllr_for_cover_border_images">
					<th scope="row"><?php esc_html_e( 'Cover Image Border Color', 'gallery-plugin' ); ?></th>
					<td>
						<input type="text" value="<?php echo esc_attr( $this->options['cover_border_images_color'] ); ?>" name="gllr_cover_border_images_color" class="gllr_color_field" data-default-color="#F1F1F1" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sort Albums by', 'gallery-plugin' ); ?></th>
					<td>
						<select name="gllr_album_order_by">
							<option value="ID" <?php selected( 'ID', $this->options['album_order_by'] ); ?>><?php esc_html_e( 'Gallery ID', 'gallery-plugin' ); ?></option>
							<option value="title" <?php selected( 'title', $this->options['album_order_by'] ); ?>><?php esc_html_e( 'Title', 'gallery-plugin' ); ?></option>
							<option value="date" <?php selected( 'date', $this->options['album_order_by'] ); ?>><?php esc_html_e( 'Date', 'gallery-plugin' ); ?></option>
							<option value="modified" <?php selected( 'modified', $this->options['album_order_by'] ); ?>><?php esc_html_e( 'Last modified date', 'gallery-plugin' ); ?></option>
							<option value="comment_count" <?php selected( 'comment_count', $this->options['album_order_by'] ); ?>><?php esc_html_e( 'Comment count', 'gallery-plugin' ); ?></option>
							<option value="menu_order" <?php selected( 'menu_order', $this->options['album_order_by'] ); ?>><?php esc_html_e( '"Order" field on the gallery edit page', 'gallery-plugin' ); ?></option>
							<option value="author" <?php selected( 'author', $this->options['album_order_by'] ); ?>><?php esc_html_e( 'Author', 'gallery-plugin' ); ?></option>
							<option value="rand" <?php selected( 'rand', $this->options['album_order_by'] ); ?> class="bws_option_affect" data-affect-hide=".gllr_album_order"><?php esc_html_e( 'Random', 'gallery-plugin' ); ?></option>
						</select>
						<div class="bws_info"><?php esc_html_e( 'Select galleries sorting order in your galleries page.', 'gallery-plugin' ); ?></div>
					</td>
				</tr>
				<tr class="gllr_album_order">
					<th scope="row"><?php esc_html_e( 'Arrange Albums by', 'gallery-plugin' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="gllr_album_order" value="ASC" 
							<?php
							if ( 'ASC' === $this->options['album_order'] ) {
								echo 'checked="checked"';}
							?>
							/> <?php esc_html_e( 'Ascending (e.g. 1, 2, 3; a, b, c)', 'gallery-plugin' ); ?></label><br />
							<label><input type="radio" name="gllr_album_order" value="DESC" 
							<?php
							if ( 'DESC' === $this->options['album_order'] ) {
								echo 'checked="checked"';}
							?>
							/> <?php esc_html_e( 'Descending (e.g. 3, 2, 1; c, b, a)', 'gallery-plugin' ); ?></label>
						</fieldset>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc gllr_for_enable_lightbox">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th scope="row"><?php esc_html_e( 'Instant Lightbox', 'gallery-plugin' ); ?> </th>
								<td>
									<input type="checkbox" value="1" disabled />
									<span class="bws_info"><?php esc_html_e( 'Enable to display all images in the lightbox after clicking cover image or URL instead of going to a single gallery page.', 'gallery-plugin' ); ?></span>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Read More Link Label', 'gallery-plugin' ); ?></th>
					<td>
						<input type="text" name="gllr_read_more_link_text" maxlength="250" value="<?php echo esc_html( $this->options['read_more_link_text'] ); ?>" />
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Display tab lightbox
		 */
		public function tab_lightbox() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Lightbox Settings', 'gallery-plugin' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr class="gllr_for_enable_opening_images">
					<th scope="row"><?php esc_html_e( 'Enable Lightbox', 'gallery-plugin' ); ?> </th>
					<td>
						<input type="checkbox" name="gllr_enable_lightbox" 
						<?php
						if ( 1 === absint( $this->options['enable_lightbox'] ) ) {
							echo 'checked="checked"';}
						?>
						class="bws_option_affect" data-affect-show=".gllr_for_enable_lightbox" />
						<span class="bws_info"><?php esc_html_e( 'Enable to show the lightbox when clicking on gallery images.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc gllr_for_enable_lightbox">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th scope="row"><?php esc_html_e( 'Image Size', 'gallery-plugin' ); ?> </th>
								<td>
									<select disabled name="gllr_image_size_full">
										<?php foreach ( $this->wp_image_sizes as $data ) { ?>
											<option value="<?php echo esc_attr( $data['value'] ); ?>" <?php selected( $data['value'], 'large' ); ?>><?php echo esc_html( $data['name'] ); ?></option>
										<?php } ?>
									</select>
									<div class="bws_info"><?php esc_html_e( 'Select the maximum gallery image size for the lightbox view. "Default" will display the original, full size image.', 'gallery-plugin' ); ?></div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Overlay Color', 'gallery-plugin' ); ?> </th>
								<td>
									<input disabled="disabled" type="text" value="#777777" size="7" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Overlay Opacity', 'gallery-plugin' ); ?> </th>
								<td>
									<input disabled type="text" size="8" value="0.7" />
									<div class="bws_info"><?php printf( esc_html__( 'Lightbox overlay opacity. Leave blank to disable opacity (default is %1$s, max is %2$s).', 'gallery-plugin' ), '0.7', '1' ); ?></div>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<table class="form-table gllr_for_enable_lightbox">
			<?php do_action( 'gllr_display_settings_overlay', $this->options ); ?>				
				<tr class="gllr_for_enable_lightbox">
					<th scope="row"><?php esc_html_e( 'Slideshow', 'gallery-plugin' ); ?> </th>
					<td>
						<input type="checkbox" name="gllr_start_slideshow" value="1" 
						<?php
						if ( 1 === absint( $this->options['start_slideshow'] ) ) {
							echo 'checked="checked"';}
						?>
						 class="bws_option_affect" data-affect-show=".gllr_for_start_slideshow" /> <span class="bws_info"><?php esc_html_e( 'Enable to start the slideshow automatically when the lightbox is used.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
				<tr class="gllr_for_start_slideshow">
					<th scope="row"><?php esc_html_e( 'Slideshow Duration', 'gallery-plugin' ); ?></th>
					<td>
						<input type="number" name="gllr_slideshow_interval" min="1" max="1000000" value="<?php echo esc_attr( $this->options['slideshow_interval'] ); ?>" /> <?php esc_html_e( 'ms', 'gallery-plugin' ); ?>
						<div class="bws_info"><?php esc_html_e( 'Slideshow interval duration between two images.', 'gallery-plugin' ); ?></div>
					</td>
				</tr>			
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc gllr_for_enable_lightbox">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th scope="row"><?php esc_html_e( 'Lightbox Helpers', 'gallery-plugin' ); ?></th>
								<td>
									<input disabled type="checkbox" name="" /> <span class="bws_info"><?php esc_html_e( 'Enable to display the lightbox toolbar and arrows.', 'gallery-plugin' ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Lightbox Thumbnails', 'gallery-plugin' ); ?></th>
								<td>
									<input disabled type="checkbox" name="" /> <span class="bws_info"><?php esc_html_e( 'Enable to use a lightbox helper navigation between images.', 'gallery-plugin' ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Lightbox Thumbnails Position', 'gallery-plugin' ); ?></th>
								<td>
									<select disabled name="">
										<option><?php esc_html_e( 'Top', 'gallery-plugin' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Lightbox Button Label', 'gallery-plugin' ); ?></th>
								<td>
									<input type="text" disabled value="<?php esc_html_e( 'Read More', 'gallery-plugin' ); ?>" />
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>		
			<table class="form-table gllr_for_enable_lightbox">
				<?php do_action( 'gllr_display_settings_lightbox', $this->options ); ?>
				<tr class="gllr_for_enable_lightbox">
					<th scope="row"><?php esc_html_e( 'Download Button', 'gallery-plugin' ); ?></th>
					<td>
						<input type="checkbox" name="gllr_lightbox_download_link" value="1" <?php checked( 1 === absint( $this->options['lightbox_download_link'] ), true ); ?> class="bws_option_affect" data-affect-show=".gllr_for_lightbox_download_link" /> <span class="bws_info"><?php esc_html_e( 'Enable to display download button.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
				<tr class="gllr_for_enable_lightbox">
					<th scope="row"><?php esc_html_e( 'Display Arrows', 'gallery-plugin' ); ?></th>
					<td>
						<input type="checkbox" name="gllr_lightbox_arrows" value="1" <?php checked( 1 === absint( $this->options['lightbox_arrows'] ), true ); ?> class="bws_option_affect" data-affect-show=".gllr_for_lightbox_download_link" /> <span class="bws_info"><?php esc_html_e( 'Enable to display arrows.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
				<tr class="gllr_for_enable_lightbox">
					<th scope="row"><?php esc_html_e( 'Single Lightbox', 'gallery-plugin' ); ?></th>
					<td>
						<input type="checkbox" name="gllr_single_lightbox_for_multiple_galleries" value="1" <?php checked( 1 === absint( $this->options['single_lightbox_for_multiple_galleries'] ), true ); ?> /> <span class="bws_info"><?php esc_html_e( 'Enable to use a single lightbox for multiple galleries located on a single page.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Display tab social
		 */
		public function tab_social() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Social Sharing Buttons Settings', 'gallery-plugin' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<div class="bws_pro_version_bloc">
				<div class="bws_pro_version_table_bloc">
					<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
					<div class="bws_table_bg"></div>
					<table class="form-table bws_pro_version">
						<tr>
							<th scope="row"><?php esc_html_e( 'Social Buttons', 'gallery-plugin' ); ?></th>
							<td>
								<input type="checkbox" disabled="disabled" /> <span class="bws_info"><?php esc_html_e( 'Enable social sharing buttons in the lightbox.', 'gallery-plugin' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Social Networks', 'gallery-plugin' ); ?></th>
							<td>
								<fieldset>
									<label><input disabled="disabled" type="checkbox" /> Facebook</label><br>
									<label><input disabled="disabled" type="checkbox" /> Twitter</label><br>
									<label><input disabled="disabled" type="checkbox" /> Pinterest</label><br>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Counter', 'gallery-plugin' ); ?></th>
							<td>
								<input disabled type="checkbox" value="1" />
								<span class="bws_info"><?php esc_html_e( 'Enable to show likes counter for each social button.', 'gallery-plugin' ); ?></span>
							</td>
						</tr>
					</table>
				</div>
				<?php $this->bws_pro_block_links(); ?>
			</div>
			<?php
			do_action( 'gllr_display_settings_social', $this->options );
		}

		/**
		 * Additional options for export/import
		 */
		public function additional_import_export_options() {
			?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Demo Data', 'gallery-plugin' ); ?></th>
					<td>
						<?php $this->demo_data->bws_show_demo_button( __( 'Install demo data to create galleries with images, post with shortcodes and page with a list of all galleries.', 'gallery-plugin' ) ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Export to CSV', 'gallery-plugin' ); ?></th>
					<td>
						<?php wp_nonce_field( 'gllr_export', 'gllr_export_nonce' ); ?>
						<input type="submit" name="gllr_export_submit" class="button-secondary" value="<?php esc_html_e( 'Export Now', 'gallery-plugin' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Import from CSV', 'gallery-plugin' ); ?></th>
					<td>
						<?php wp_nonce_field( 'gllr_import', 'gllr_import_nonce' ); ?>
						<input type="file" name="gllr_csv_file">
						<input type="submit" name="gllr_import_submit" class="button-secondary" value="<?php esc_html_e( 'Import Now', 'gallery-plugin' ); ?>" /> <br />
						<span class="bws_info"><?php esc_html_e( 'Note: Starting from Gallery version 4.7.4, the data conversion method for export and import has been changed.', 'gallery-plugin' ); ?></span><br />
						<span class="bws_info"><?php esc_html_e( 'You can still import a file created with an earlier version, but the gallery image data will not be fully loaded.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Display custom options on the 'misc' tab
		 *
		 * @access public
		 */
		public function additional_misc_options_affected() {
			global $wp_version, $wpdb;
			do_action( 'gllr_settings_page_misc_action', $this->options );
			/* Rename post_type via $_GET if checkbox doesn't shows */
			if ( isset( $_GET['bws_rename_post_type_gallery'] ) ) {
				global $wpdb;
				$wpdb->update(
					$wpdb->prefix . 'posts',
					array(
						'post_type' => 'bws-gallery',
					),
					array(
						'post_type' => 'gallery',
					)
				);
				gllr_plugin_upgrade( false );
			}

			if ( ! $this->all_plugins ) {
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$this->all_plugins = get_plugins();
			}

			$old_post_type_gallery = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'gallery'" );

			if ( ! empty( $old_post_type_gallery ) ) {
				?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Gallery Post Type', 'gallery-plugin' ); ?></th>
					<td>
						<input type="checkbox" name="gllr_rename_post_type" value="1" /> <span class="bws_info"><?php esc_html_e( 'Enable to avoid conflicts with other gallery plugins installed. All galleries created earlier will stay unchanged. However, after enabling we recommend to check settings of other plugins where "gallery" post type is used.', 'gallery-plugin' ); ?></span>
					</td>
				</tr>
			<?php } ?>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>				
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'gallery-plugin' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th scope="row"><?php esc_html_e( 'Gallery Slug', 'gallery-plugin' ); ?></th>
								<td>
									<input type="text" value="gallery" disabled />
									<br>
									<span class="bws_info"><?php esc_html_e( 'Enter the unique gallery slug.', 'gallery-plugin' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'ShortPixel Image Optimizer', 'gallery-plugin' ); ?></th>
								<td>
									<label>
										<input<?php echo wp_kses_post( $this->change_permission_attr ); ?> type="checkbox" name="gllr_short_pixel" value="1" disabled /> <span class="bws_info"><?php esc_html_e( 'Enable to apply ShortPixel optimizer to gallery images.', 'gallery-plugin' ); ?></span>
									</label>
								</td>
							</tr>
							<tr id="gllr_for_short_pixel">
								<th scope="row"><?php esc_html_e( 'API Key', 'gallery-plugin' ); ?></th>
								<td>
									<input type="text" name="gllr_short_pixel_api_key"  value="<?php echo isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : ''; ?>" disabled  />
									<div class="bws_info">
										<?php printf( esc_html__( 'Input API Key. If you don\'t have an API Key, please  %s ', 'gallery-plugin' ), '<a href="https://shortpixel.com/wp-apikey" target="_blank">' . esc_html__( ' sign up to get your API key', 'gallery-plugin' ) . '</a>' ); ?>&nbsp;
									</div>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>		
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Search Galleries', 'gallery-plugin' ); ?></th>
					<td>
						<?php
						$disabled = '';
						$checked  = '';
						$link     = '';
						if ( array_key_exists( 'custom-search-plugin/custom-search-plugin.php', $this->all_plugins ) || array_key_exists( 'custom-search-pro/custom-search-pro.php', $this->all_plugins ) ) {
							if ( ! is_plugin_active( 'custom-search-plugin/custom-search-plugin.php' ) && ! is_plugin_active( 'custom-search-pro/custom-search-pro.php' ) ) {
								$disabled = ' disabled="disabled"';
								$link     = '<a href="' . admin_url( 'plugins.php' ) . '">' . __( 'Activate Now', 'gallery-plugin' ) . '</a>';
							}
							if ( isset( $this->cstmsrch_options['output_order'] ) ) {
								foreach ( $this->cstmsrch_options['output_order'] as $key => $item ) {
									if ( isset( $item['name'] ) && $item['name'] === $this->options['post_type_name'] && 'post_type' === $item['type'] ) {
										if ( $item['enabled'] ) {
											$checked = ' checked="checked"';
										}
										break;
									}
								}
							} elseif ( ! empty( $this->cstmsrch_options['post_types'] ) && in_array( $this->options['post_type_name'], $this->cstmsrch_options['post_types'] ) ) {
								$checked = ' checked="checked"';
							}
						} else {
							$disabled = ' disabled="disabled"';
							$link     = '<a href="https://bestwebsoft.com/products/wordpress/plugins/custom-search/?k=62eae81381e03dd9e843fc277c6e64c1&amp;pn=' . esc_attr( $this->link_pn ) . '&amp;v=' . esc_attr( $this->plugins_info['Version'] ) . '&amp;wp_v=' . esc_attr( $wp_version ) . '" target="_blank">' . esc_html__( 'Install Now', 'gallery-plugin' ) . '</a>';
						}
						?>
						<input type="checkbox" name="gllr_add_to_search" value="1"<?php echo wp_kses_post( $disabled . $checked ); ?> />
						<span class="bws_info"><?php esc_html_e( 'Enable to include galleries to your website search.', 'gallery-plugin' ); ?> <?php printf( esc_html__( '%s is required.', 'gallery-plugin' ), 'Custom Search plugin' ); ?> <?php echo esc_url( $link ); ?></span>
					</td>
				</tr>
			<?php
		}

		/**
		 * Custom functions for "Restore plugin options to defaults"
		 *
		 * @access public
		 * @param array $default_options Array with deafult options.
		 */
		public function additional_restore_options( $default_options ) {
			$default_options['post_type_name'] = $this->options['post_type_name'];

			return $default_options;
		}
	}
}
