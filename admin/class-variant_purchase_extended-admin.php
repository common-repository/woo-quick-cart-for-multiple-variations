<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.multidots.com/
 * @since      1.0.0
 *
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/admin
 * @author     Multidots <wordpress@multidots.com>
 */
class Variant_purchase_extended_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$settings_btn = filter_input( INPUT_POST, 'wqcmv_submit_restock_notification_settings', FILTER_SANITIZE_STRING );
		if ( isset( $settings_btn ) ) {
			$this->wqcmv_update_settings_for_restock_notifications();
		}
	}

	/**
	 * Function enqueue Stylesheets & Javascript for admin dashboard.
	 *
	 * @param string $hook To check the page condition.
	 *
	 * @since 1.0.0
	 */
	function enqueue_styles_scripts( $hook ) {
		global $typenow;
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		if ( isset( $hook ) && ! empty( $hook ) && 'dotstore-plugins_page_woocommerce-quick-cart-for-multiple-variations' === $hook || ( 'product' === $typenow ) ) {

			//enqueue stylesheet
			wp_enqueue_style( $this->plugin_name . 'font-awesome', plugin_dir_url( __FILE__ ) . 'css/font-awesome.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'woocommerce-quick-cart-for-multiple-variations-main-style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), $this->version );
			wp_enqueue_style( 'woocommerce-quick-cart-for-multiple-variations-media', plugin_dir_url( __FILE__ ) . 'css/media.css', array(), $this->version );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wc-variable-products-purchase-extended-admin.css' );

			//enqueue Javascript
			wp_enqueue_script( 'wp-pointer' ); //enqueue script for notice pointer
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_script( 'fancy-box', plugin_dir_url( __FILE__ ) . 'js/jquery.fancybox.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'fancybox', plugin_dir_url( __FILE__ ) . 'js/jquery.fancybox.pack.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'fancybox-buttons', plugin_dir_url( __FILE__ ) . 'js/jquery.fancybox-buttons.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'fancybox-media', plugin_dir_url( __FILE__ ) . 'js/jquery.fancybox-media.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'fancybox-thumbs', plugin_dir_url( __FILE__ ) . 'js/jquery.fancybox-thumbs.js', array( 'jquery' ), $this->version );
			wp_register_script( $this->plugin_name . 'admin-js', plugin_dir_url( __FILE__ ) . 'js/wc-variable-products-purchase-extended-admin'.$suffix.'.js', array(
				'jquery',
				'jquery-ui-dialog',
				'jquery-ui-core'
			), $this->version, true );
			wp_localize_script(
				$this->plugin_name . 'admin-js', 'WQCMVAdminJSObj',
				array(
					'ajaxurl'                             => admin_url( 'admin-ajax.php' ),
					'loader_url'                          => includes_url( 'images/spinner-2x.gif' ),
					'fetch_notification_log_wait_message' => esc_html__( 'Please wait while we fetch requests log...', 'woocommerce-quick-cart-for-multiple-variations' ),
				)
			);
			wp_enqueue_script( $this->plugin_name . 'admin-js' );
		}
	}

	/**
	 * Function for welcome screen.
	 *
	 * @since 1.0.0
	 */
	function welcome_variable_purchase_extended_screen_do_activation_redirect() {

		if ( ! get_transient( '_welcome_screen_WC_Variable_Products_Purchase_Extended_activation_redirect_data' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( '_welcome_screen_WC_Variable_Products_Purchase_Extended_activation_redirect_data' );

		// if activating from network, or bulk
		$activate_multi = filter_var( INPUT_GET, 'activate-multi', FILTER_SANITIZE_STRING );
		if ( is_network_admin() || isset( $activate_multi ) && ! empty( $activate_multi ) ) {
			return;
		}

		// Redirect to extra cost welcome  page
		wp_safe_redirect( html_entity_decode( add_query_arg( array( 'page' => 'woocommerce-quick-cart-for-multiple-variations' ), admin_url( 'admin.php' ) ) ) );
		exit();
	}

	/**
	 * Creating Dotstore menu.
	 *
	 * @since 1.0.0
	 */
	public function dot_store_menu_traking_fbg() {
		global $GLOBALS;
		if ( empty( $GLOBALS['admin_page_hooks']['dots_store'] ) ) {
			add_menu_page(
				esc_html__( 'DotStore Plugins', 'woocommerce-quick-cart-for-multiple-variations' ), esc_html__( 'DotStore Plugins', 'woocommerce-quick-cart-for-multiple-variations' ), 'NULL', 'dots_store', array(
				$this,
				'dot_store_menu_customer_io'
			), plugin_dir_url( __FILE__ ) . 'images/menu-icon.png', 25
			);
		}
	}

	/**
	 * Creating the submenu for quick cart for multiple variations.
	 *
	 * @since 1.0.0
	 */
	public function add_new_menu_items_traking_fbg() {
		add_submenu_page(
			'dots_store',
			esc_html__( 'WooCommerce Quick Cart for Multiple Variations', 'woocommerce-quick-cart-for-multiple-variations' ),
			esc_html__( 'Quick Cart for Multiple Variations', 'woocommerce-quick-cart-for-multiple-variations' ),
			'manage_options',
			'woocommerce-quick-cart-for-multiple-variations',
			array( $this, 'custom_variant_extended' )
		);
	}

	/**
	 * Function for displaying the data in tabs.
	 *
	 * @since 1.0.0
	 */
	function custom_variant_extended() {

		$url = admin_url( 'admin.php?page=woocommerce-quick-cart-for-multiple-variations&tab=wqcmv_variant_purchase_extended' );
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		include_once( plugin_dir_path( __FILE__ ) . 'partials/header/plugin-header.php' );
		if ( ! empty( $tab ) ) {
			if ( 'wqcmv_variant_purchase_extended' === $tab ) {
				$this->wqcmv_variable_product_purchase_extended_setting();
			}
			if ( 'wqcmv_variant_restock_notification' === $tab ) {
				$this->wqcmv_variant_restock_notification_settings();
			}
			if ( 'wqcmv_variant_purchase_extended_get_started_method' === $tab ) {
				$this->get_started_dots_plugin_settings();
			}
			if ( 'introduction_variant_extended' === $tab ) {
				$this->introduction_variant_extended();
			}
		} else {
			wp_redirect( $url );
			exit;
		}
		include_once( plugin_dir_path( __FILE__ ) . 'partials/header/plugin-sidebar.php' );
	}

	/**
	 * Review section in page footer.
	 *
	 * @since 1.0.0
	 */
	function wqcmv_admin_footer_review() {
		echo sprintf( esc_html__( 'If you like %1$s plugin, please leave us ★★★★★ ratings on %2$s.', '' ), '<strong>' . esc_html__( 'Variable Product Purchase Extended Settings', 'woocommerce-quick-cart-for-multiple-variations' ) . '</strong>', '<a href="javascript:void(0);" target="_blank">' . esc_html__( 'WordPress', 'woocommerce-quick-cart-for-multiple-variations' ) . '</a>' );
	}

	/**
	 * Add custom Field in Product general tab meta Section.
	 *
	 */
	function wqcmv_add_product_visiblity_field() {

		global $post;
		$product = wc_get_product( $post->ID );
		if ( $product->is_type( 'variable' ) ) { ?>
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox( array(
					'id'          => 'product_visiblity_option',
					'value'       => get_post_meta( $post->ID, 'product_visiblity_option', true ),
					'label'       => esc_html__( 'Product Visiblity', 'woocommerce-quick-cart-for-multiple-variations' ),
					'desc_tip'    => true,
					'description' => esc_html__( 'Product Visiblity', 'woocommerce-quick-cart-for-multiple-variations' ),
				) );
				?>
			</div>
			<?php
		}

	}

	/**
	 * Save Custom Field in Product general tab meta section.
	 *
	 * @param int $id product id.
	 *
	 */
	function wqcmv_save_product_visiblity_field( $id ) {

		$product_visiblity_option = filter_input( INPUT_POST, 'product_visiblity_option', FILTER_SANITIZE_STRING );
		update_post_meta( $id, 'product_visiblity_option', $product_visiblity_option );

	}

	/**
	 * Notify user with an email when notified products are back in stock.
	 *
	 * @param int $variation_id
	 *
	 */
	function wqcmv_notify_user_when_prooduct_back_instock( $variation_id ) {
		$headers = array();

		$restock_notification_settings = get_option( 'wqcmv_restock_notification' );
		if ( isset( $restock_notification_settings['email_subject'] ) && ! empty( $restock_notification_settings['email_subject'] ) ) {
			$email_subject = $restock_notification_settings['email_subject'];
		} else {
			$email_subject = wqcmv_default_email_subject();
		}

		if ( isset( $restock_notification_settings['email_content'] ) && ! empty( $restock_notification_settings['email_content'] ) ) {
			$email_content = $restock_notification_settings['email_content'];
		} else {
			$email_content = wqcmv_default_email_content();
		}

		$permalink     = get_permalink( $variation_id );
		$title         = get_the_title( $variation_id );
		$cart_link     = wc_get_cart_url() . '?pid=' . $variation_id;
		$email_content = str_replace( '{variation_title}', $title, $email_content );
		$email_content = str_replace( '{variation_link}', $permalink, $email_content );
		$email_content = str_replace( '{cart_link}', $cart_link, $email_content );

		$user_emails = get_post_meta( $variation_id, 'notification_email' );

		if ( ! empty( $user_emails ) && is_array( $user_emails ) ) {
			foreach ( $user_emails as $key => $email ) {
				if( 0 !== $key ) {
					$headers[] = 'Bcc: ' . $email;
				}
			}
		}

		if ( function_exists( 'wqcmv_send_mail' ) ) {
			$user_email = isset( $user_emails[0] ) ? $user_emails[0] : $user_emails;
			wqcmv_send_mail( $user_email, $email_subject, $email_content, $headers );
		}
		delete_post_meta( $variation_id, 'notification_email' );

		/**
		 * Extend the code in the way you want to send notifications in another ways.
		 */
		do_action( 'wqcmv_restock_notifications_sent', $variation_id );

	}

	/**
	 * Display variation request counter.
	 *
	 * @param object $variation product variations.
	 *
	 */
	function wqcmv_display_variation_customer_requests( $variation ) {

		$user_emails = get_post_meta( $variation->ID, 'notification_email' );
		$requests    = apply_filters( 'wqcmv_customers_requests_count', count( $user_emails ), $variation );
		if ( 0 < $requests ) {
			ob_start();
			?>
			<a href="javascript:void(0);" id="wqcmv-view-customer-requests-log"
			   data-variation-id="<?php echo esc_attr( $variation->ID ); ?>"
			   title="<?php esc_html_e( 'Click on this link to see the complete log!', 'woocommerce-quick-cart-for-multiple-variations' ) ?>"
			><?php echo sprintf( esc_html__( 'Customers Requests: %1$d', 'woocommerce-quick-cart-for-multiple-variations' ), esc_html( $requests ) ); ?></a>
			<?php

			echo wp_kses_post( ob_get_clean() );
		}

	}

	/**
	 * Add a column to the products table to let know the product type.
	 *
	 * @param $defaults
	 *
	 * @return mixed
	 * @since    1.1.2
	 * @author   Multidots <wordpress@multidots.com>
	 */
	function wqcmv_product_new_column_heading( $defaults ) {

		$defaults['wqcmv_product_type'] = esc_html__( 'Product Type', 'woocommerce-quick-cart-for-multiple-variations' );

		return $defaults;

	}

	/**
	 * Add content to the new added columns.
	 *
	 * @param $column_name
	 * @param $postid
	 *
	 * @since    1.1.2
	 * @author   Multidots <wordpress@multidots.com>
	 */
	function wqcmv_product_new_column_content( $column_name, $postid ) {

		if ( 'wqcmv_product_type' === $column_name ) {
			$prod          = wc_get_product( $postid );
			$prod_type     = $prod->get_type();
			$default_types = wc_get_product_types();
			echo ( isset( $default_types[ $prod_type ] ) && ! empty( $default_types[ $prod_type ] ) ) ? esc_html( $default_types[ $prod_type ] ) : '';
		}

	}

	/**
	 * Add custom scripts in admin footer.
	 *
	 * @author   Multidots <wordpress@multidots.com>
	 */
	function wqcmv_admin_footer_custom_assets() {

		ob_start();
		?>
		<div id="wqcmv-notificaiton-request-logs" class="modal">
			<div class="modal-content">
				<div class="modal-header">
					<span class="close">×</span>
					<h2 class="wqcmv-modal-title"><?php esc_html_e( 'Please wait...', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h2>
				</div>
				<div class="modal-body wqcmv-notificaiton-request-logs-modal-content"></div>
			</div>
		</div>
		<?php
		echo wp_kses_post( ob_get_clean() );

	}

	/**
	 * AJAX served to fetch the notification requests log based on variation ID.
	 *
	 * @author   Multidots <wordpress@multidots.com>
	 */
	function wqcmv_fetch_notifications_log() {

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		if ( isset( $action ) && 'wqcmv_fetch_notifications_log' === $action ) {
			$variation_id   = absint( filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT ) );
			$modal_title    = get_the_title( $variation_id );
			$modal_title    = sprintf( esc_html__( 'Notification Requests: %1$s', 'woocommerce-quick-cart-for-multiple-variations' ), $modal_title );
			$email_requests = get_post_meta( $variation_id, 'notification_email' );
			$logs           = apply_filters( 'wqcmv_notification_requsts_log', array( 'Emails' => $email_requests ), $variation_id );
			ob_start();
			if ( ! empty( $logs ) ) {
				foreach ( $logs as $type => $log ) {
					?>
					<div class="wqcmv-notification-log">
						<button class="wqcmv-accordion"><?php echo esc_html( $type ); ?></button>
						<div class="wqcmv-panel">
							<?php foreach ( $log as $l ) { ?>
								<p><?php echo esc_html( $l ); ?></p>
							<?php } ?>
						</div>
					</div>
					<?php
				}
			}
			$html = ob_get_clean();

			$response = array(
				'message'     => 'notifications-log-fetched',
				'html'        => $html,
				'modal_title' => $modal_title,
			);
			wp_send_json_success( $response );
			wp_die();
		}

	}

	/**
	 * Sanitizes a HTML from user input or from the database.
	 *
	 * - Checks for invalid UTF-8,
	 * - Converts single `<` characters to entities
	 * - Strips all tags
	 * - Removes line breaks, tabs, and extra whitespace
	 * - Strips octets
	 *
	 * @param HTML $html HTML to sanitize.
	 *
	 * @return HTML Sanitized string.
	 * @see wp_check_invalid_utf8()
	 *
	 * @since 2.9.0
	 *
	 * @see sanitize_textarea_field()
	 */
	function sanitize_html_field( $html ) {
		$filtered = $this->_sanitize_html_fields( $html, false );

		/**
		 * Filters a sanitized text field string.
		 *
		 * @param string $filtered The sanitized string.
		 * @param string $str The string prior to being sanitized.
		 *
		 * @since 2.9.0
		 *
		 */
		return apply_filters( 'sanitize_html_field', $filtered, $html );
	}

	/**
	 * Internal helper function to sanitize a html from user input or from the db
	 *
	 * @param string $html String to sanitize.
	 * @param bool $keep_newlines optional Whether to keep newlines. Default: false.
	 *
	 * @return string Sanitized string.
	 * @since 4.7.0
	 * @access private
	 *
	 */
	function _sanitize_html_fields( $html, $keep_newlines = false ) {
		if ( is_object( $html ) || is_array( $html ) ) {
			return '';
		}

		$html = (string) $html;

		$filtered = wp_check_invalid_utf8( $html );

		if ( strpos( $filtered, '<' ) !== false ) {
			$filtered = wp_pre_kses_less_than( $filtered );

			// Use html entities in a special case to make sure no later
			// newline stripping stage could lead to a functional tag
			$filtered = str_replace( "<\n", "&lt;\n", $filtered );
		}

		if ( ! $keep_newlines ) {
			$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		}
		$filtered = trim( $filtered );

		$found = false;
		while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
			$filtered = str_replace( $match[0], '', $filtered );
			$found    = true;
		}

		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}

		return $filtered;
	}

	/**
	 * Function to sanitize the html from wp_editor.
	 *
	 * @param $html
	 *
	 * @return HTML
	 */
	function wqcmv_content_sanitize( $html ) {

		return $this->sanitize_html_field( $html );

	}

	/**
	 * Save settings for restock notifications.
	 *
	 * @author   Multidots <wordpress@multidots.com>
	 */
	function wqcmv_update_settings_for_restock_notifications() {

		$email_subject = filter_input( INPUT_POST, 'wqcmv_restock_email_subject', FILTER_SANITIZE_STRING );
		if ( empty( $email_subject ) ) {
			$email_subject = wqcmv_default_email_subject();
		}
		$email_content = filter_input( INPUT_POST, 'wqcmv_restock_email_content', FILTER_CALLBACK, array(
			'options' => array(
				$this,
				'wqcmv_content_sanitize'
			)
		) );
		$settings      = array(
			'email_subject' => $email_subject,
			'email_content' => $email_content,
		);
		update_option( 'wqcmv_restock_notification', $settings, 'no' );
		ob_start();
		?>
		<div class='notice updated' id='message'>
			<p><?php esc_html_e( 'Settings Saved', 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
		</div>
		<?php
		echo wp_kses_post( ob_get_clean() );

	}

	function wqcmv_variant_restock_notification_settings() {

		$restock_notification_settings = get_option( 'wqcmv_restock_notification' );
		if ( isset( $restock_notification_settings['email_subject'] ) && ! empty( $restock_notification_settings['email_subject'] ) ) {
			$email_subject = $restock_notification_settings['email_subject'];
		} else {
			$email_subject = wqcmv_default_email_subject();
		}

		if ( isset( $restock_notification_settings['email_content'] ) && ! empty( $restock_notification_settings['email_content'] ) ) {
			$email_content = $restock_notification_settings['email_content'];
		} else {
			$email_content = wqcmv_default_email_content();
		}
		?>
		<div class="vpe-table">
			<form id="wqcmv-restock-notification-form" method="POST" action="">
				<?php wp_nonce_field( basename( __FILE__ ), 'variant-extended' ); ?>
				<div class="under-table third-tab">
					<div class="set">
						<h2><?php esc_html_e( 'Restock Notifications Settings', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h2>
					</div>
					<table class="table-outer form-table">
						<tbody>
						<tr>
							<td class="ur-1">
								<label for="wqcmv_restock_email_subject">
									<?php esc_html_e( 'Email Subject', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<td class="ur-2">
								<input type="text" class="regular-text" id="wqcmv_restock_email_subject"
									   name="wqcmv_restock_email_subject"
									   value="<?php echo esc_attr( $email_subject ); ?>"
									   placeholder="<?php esc_html_e( 'Email subject goes here..', 'woocommerce-quick-cart-for-multiple-variations' ); ?>">
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php echo sprintf( esc_html__( 'Email subject to be sent when variant restocking emails would be sent. Default: %1$s.', 'woocommerce-quick-cart-for-multiple-variations' ), esc_html( $email_subject ) ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<td class="ur-1">
								<label for="wqcmv_restock_email_content">
									<?php esc_html_e( 'Email Content', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<td class="ur-2">
								<?php wp_editor( $email_content, 'wqcmv_restock_email_content' ); ?>
								<span class="enable_wqcmv_discription_tab"><i class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'Email subject to be sent when variant restocking emails would be sent.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="submit"
							   value="<?php esc_html_e( 'Save Changes', 'woocommerce-quick-cart-for-multiple-variations' ); ?>"
							   class="button button-primary" name="wqcmv_submit_restock_notification_settings">
					</p>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Function for add custom pointer
	 *
	 */
	function wqcmv_variable_product_purchase_extended_setting() {
		$error_msg           = array();
		$wqcmv_submit_plugin = filter_input( INPUT_POST, 'wqcmv_submit_plugin', FILTER_SANITIZE_STRING );
		if ( isset( $wqcmv_submit_plugin ) ) {
			$variant_extended                                     = filter_input( INPUT_POST, 'variant-extended', FILTER_SANITIZE_STRING );
			$vpe_allow_unavailable_variants                       = filter_input( INPUT_POST, 'vpe_allow_unavailable_variants', FILTER_SANITIZE_STRING );
			$vpe_enable_stock_visibility                          = filter_input( INPUT_POST, 'vpe_enable_stock_visibility', FILTER_SANITIZE_STRING );
			$vpe_allow_users_to_contact_admin                     = filter_input( INPUT_POST, 'vpe_allow_users_to_contact_admin', FILTER_SANITIZE_STRING );
			$vpe_enable_price_visibility_for_nonloggedin_customer = filter_input( INPUT_POST, 'vpe_enable_price_visibility_for_nonloggedin_customer', FILTER_SANITIZE_STRING );
			$vpe_variation_per_page_post                          = filter_input( INPUT_POST, 'vpe_variation_per_page', FILTER_SANITIZE_NUMBER_INT );
			$vpe_add_to_cart_button_text_post                     = filter_input( INPUT_POST, 'vpe_add_to_cart_button_text', FILTER_SANITIZE_STRING );
			$additional_css                                       = filter_input( INPUT_POST, 'wqcmv_additional_css', FILTER_SANITIZE_STRING );
			$wqcmv_enable_thumbnail_visibility                    = filter_input( INPUT_POST, 'wqcmv_enable_thumbnail_visibility', FILTER_SANITIZE_STRING );
			// verify nonce
			if ( ! isset( $variant_extended ) || ! wp_verify_nonce( $variant_extended, basename( __FILE__ ) ) ) {
				die( 'Failed security check' );
			}
			$allow_unavailable_variants        = isset( $vpe_allow_unavailable_variants ) ? sanitize_text_field( wp_unslash( $vpe_allow_unavailable_variants ) ) : "";
			$enable_stock_visibility           = isset( $vpe_enable_stock_visibility ) ? sanitize_text_field( wp_unslash( $vpe_enable_stock_visibility ) ) : "";
			$enable_price_visibility           = isset( $vpe_enable_price_visibility_for_nonloggedin_customer ) ? sanitize_text_field( wp_unslash( $vpe_enable_price_visibility_for_nonloggedin_customer ) ) : "";
			$vpe_variation_per_page            = isset( $vpe_variation_per_page_post ) ? $vpe_variation_per_page_post : "";
			$vpe_add_to_cart_button_text       = isset( $vpe_add_to_cart_button_text_post ) ? sanitize_text_field( wp_unslash( $vpe_add_to_cart_button_text_post ) ) : "";
			$additional_css                    = isset( $additional_css ) ? sanitize_text_field( wp_unslash( $additional_css ) ) : "";
			$wqcmv_enable_thumbnail_visibility = isset( $wqcmv_enable_thumbnail_visibility ) ? sanitize_text_field( wp_unslash( $wqcmv_enable_thumbnail_visibility ) ) : "";
			/**
			 * Filter the variants per page.
			 */
			if ( 0 > $vpe_variation_per_page ) {
				$error_msg[] = esc_html__( 'Variants per page cannot be negative.', 'woocommerce-quick-cart-for-multiple-variations' );
			} elseif ( 0 === $vpe_variation_per_page ) {
				$error_msg[] = esc_html__( 'Variants per page cannot be 0.', 'woocommerce-quick-cart-for-multiple-variations' );
			} elseif ( ! empty( $vpe_variation_per_page ) && ! ctype_digit( $vpe_variation_per_page ) ) {
				$error_msg[] = esc_html__( 'Variants per page cannot be a decimal value.', 'woocommerce-quick-cart-for-multiple-variations' );
			}
			/**
			 * Filter the add to cart button text
			 */
			if ( is_numeric( $vpe_add_to_cart_button_text ) ) {
				$error_msg[] = esc_html__( 'Button text cannot be negative.', 'woocommerce-quick-cart-for-multiple-variations' );
			}
			if ( ! empty( $error_msg ) ) {
				$error_html = '<ul>';
				foreach ( $error_msg as $msg ) {
					$error_html .= "<li>{$msg}</li>";
				}
				$error_html .= '<ul>';
				/**
				 * Now display the error message
				 */
				?>
				<div id="message" class="updated error notice is-dismissible">
					<p><?php echo wp_kses_post( $error_html ); ?></p></div>
				<?php
			} else {
				/**
				 * Everything is fine, proceed to save changes.
				 */
				$allow_unavailable_variants        = ! empty( $allow_unavailable_variants ) ? 'yes' : 'no';
				$enable_stock_visibility           = ! empty( $enable_stock_visibility ) ? 'yes' : 'no';
				$enable_price_visibility           = ! empty( $enable_price_visibility ) ? 'yes' : 'no';
				$vpe_allow_users_to_contact_admin  = ! empty( $vpe_allow_users_to_contact_admin ) ? 'yes' : 'no';
				$wqcmv_enable_thumbnail_visibility = ! empty( $wqcmv_enable_thumbnail_visibility ) ? 'yes' : 'no';
				if ( ! empty( $allow_unavailable_variants ) ) {
					update_option( 'vpe_allow_unavailable_variants', $allow_unavailable_variants );
				}
				if ( ! empty( $enable_stock_visibility ) ) {
					update_option( 'vpe_enable_stock_visibility', $enable_stock_visibility );
				}
				if ( ! empty( $vpe_allow_users_to_contact_admin ) ) {
					update_option( 'vpe_allow_users_to_contact_admin', $vpe_allow_users_to_contact_admin );
				}
				if ( ! empty( $enable_price_visibility ) ) {
					update_option( 'vpe_enable_price_visibility_for_nonloggedin_customer', $enable_price_visibility );
				}
				if ( ! empty( $vpe_variation_per_page ) ) {
					update_option( 'vpe_variation_per_page', $vpe_variation_per_page );
				}
				if ( isset( $vpe_add_to_cart_button_text ) ) {
					update_option( 'vpe_add_to_cart_button_text', $vpe_add_to_cart_button_text );
				}
				if ( ! empty( $wqcmv_enable_thumbnail_visibility ) ) {
					update_option( 'wqcmv_enable_thumbnail_visibility', $wqcmv_enable_thumbnail_visibility );
				}
				if ( isset( $additional_css ) ) {
					update_option( 'wqcmv_additional_css', $additional_css );
				}
				?>
				<div id="message" class="updated inline"><p>
						<strong><?php esc_html_e( 'Your settings have been saved.', 'woocommerce-quick-cart-for-multiple-variations' ); ?></strong>
					</p>
				</div>
				<?php
			}
		}
		?>
		<div class="vpe-table">
			<form id="cw_plugin_form_id" method="post" action="" enctype="multipart/form-data" novalidate="novalidate">
				<?php wp_nonce_field( basename( __FILE__ ), 'variant-extended' ); ?>
				<div class="under-table third-tab">
					<div class="set">
						<h2><?php esc_html_e( 'Variable Product Purchase Extended Settings', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h2>
					</div>
					<table class="table-outer form-table">
						<tbody>
						<tr>
							<td class="ur-1">
								<label for="vpe_allow_unavailable_variants">
									<?php esc_html_e( "Allow out of stock product", 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<?php
							$allow_unavailable_variants = get_option( 'vpe_allow_unavailable_variants' );
							?>
							<td class="ur-2">
								<input name="vpe_allow_unavailable_variants"
									   id="vpe_allow_unavailable_variants" type="checkbox"
									   class="" value="1" <?php
								if ( 'yes' === $allow_unavailable_variants ) {
									echo 'checked';
								}
								?>>
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'Allow products that are not in stock to be visible in the front.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<td class="ur-1">
								<label for="vpe_enable_stock_visibility">
									<?php esc_html_e( "Enable stock visibility", 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<?php
							$enable_stock_visibility = get_option( 'vpe_enable_stock_visibility' );
							?>
							<td class="ur-2">
								<input name="vpe_enable_stock_visibility"
									   id="vpe_enable_stock_visibility" type="checkbox" class=""
									   value="1" <?php
								if ( 'yes' === $enable_stock_visibility ) {
									echo 'checked';
								}
								?>>
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'Allow showing the stock number to the customers per variant.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<td class="ur-1">
								<label for="vpe_allow_users_to_contact_admin">
									<?php esc_html_e( "Allow users to contact admin for outofstock products.", 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<?php
							$vpe_can_user_contact_to_admin = get_option( 'vpe_allow_users_to_contact_admin' );
							?>
							<td class="ur-2">
								<input name="vpe_allow_users_to_contact_admin"
									   id="vpe_allow_users_to_contact_admin" type="checkbox" class=""
									   value="1" <?php
								if ( 'yes' === $vpe_can_user_contact_to_admin ) {
									echo 'checked';
								}
								?>>
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'Allow users to contact admin for a outofstock products. Setting ineffective when Allow out of stock product is disabled.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<?php
							$vpe_variation_per_page = get_option( 'vpe_variation_per_page' );
							?>
							<td class="ur-1">
								<label for="vpe_variation_per_page">
									<?php esc_html_e( 'Variants per page', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<td class="ur-2">
								<input
									value="<?php echo ! empty( $vpe_variation_per_page ) ? esc_attr( $vpe_variation_per_page ) : 10; ?>"
									name="vpe_variation_per_page"
									id="vpe_variation_per_page" required type="number" placeholder="eg. 1,2,..."
									min="">
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'This shows the number of variants that would be visible per page. Default: 10. Which means 10 variants will be shown', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<?php
							$vpe_add_to_cart_button_text = get_option( 'vpe_add_to_cart_button_text' );
							?>
							<td class="ur-1">
								<label for="vpe_add_to_cart_button_text">
									<?php esc_html_e( 'Add to cart button text', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
							</td>
							<td class="ur-2">
								<input
									value="<?php echo isset( $vpe_add_to_cart_button_text ) ? esc_attr( $vpe_add_to_cart_button_text ) : ""; ?>"
									name="vpe_add_to_cart_button_text" id="vpe_add_to_cart_button_text" type="text">
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'This shows the add to cart button text. Default:Add to cart.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<td class="ur-1">
								<label for="wqcmv_enable_thumbnail_visibility">
									<?php esc_html_e( "Enable Variation Thumbnail visibility", 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<?php
							$wqcmv_enable_thumbnail_visibility = get_option( 'wqcmv_enable_thumbnail_visibility' );
							?>
							<td class="ur-2">
								<input name="wqcmv_enable_thumbnail_visibility"
									   id="wqcmv_enable_thumbnail_visibility" type="checkbox" class=""
									   value="1" <?php
								if ( 'yes' === $wqcmv_enable_thumbnail_visibility ) {
									echo 'checked';
								}
								?>>
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'Allow thumbnail visibility of variation.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<?php
							$wqcmv_additional_css = get_option( 'wqcmv_additional_css' );
							?>
							<td class="ur-1">
								<label for="wqcmv_additional_css">
									<?php esc_html_e( 'Additional CSS', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</label>
							</td>
							<td class="ur-2">
                            <textarea id="wqcmv_additional_css" name="wqcmv_additional_css" cols="90" rows="20"
									  value=""><?php if ( isset( $wqcmv_additional_css ) && ! empty( $wqcmv_additional_css ) ) {
									echo wp_kses_post( $wqcmv_additional_css );
								} ?></textarea>
								<span class="enable_wqcmv_discription_tab"><i
										class="fa fa-question-circle "></i></span>
								<p class="description">
									<?php esc_html_e( 'You can place additional css here.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
								</p>
							</td>
						</tr>
						</tbody>
					</table>
					<p class="submit save-for-later" id="save-for-later">
						<input type="submit"
							   value="<?php esc_html_e( 'Save Changes', 'woocommerce-quick-cart-for-multiple-variations' ); ?>"
							   class="button button-primary" id="wqcmv_submit_plugin" name="wqcmv_submit_plugin">
					</p>
				</div>
			</form>
		</div>
		<?php

	}

	/**
	 * Get Started page of the plugin.
	 */
	function get_started_dots_plugin_settings() {

		?>
		<div class="vpe-table res-cl">
			<h2><?php esc_html_e( 'Thanks For Installing', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h2>
			<table class="form-table table-outer">
				<tbody>
				<tr>
					<td class="fr-2">
						<p class="block gettingstarted">
							<strong><?php esc_html_e( 'Getting Started', 'woocommerce-quick-cart-for-multiple-variations' ); ?> </strong>
						</p>
						<p class="block textgetting">
							<?php esc_html_e( 'This plugin brings an intuitive approach to the variable product\'s purchase. Like you see the general format where the customers are restricted to add only one variant at a time to the cart. This plugin breaks all such restrictions and provides a smooth platform where the customers can select multiple variants at a time.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
						</p>
						<p class="block textgetting">
							<?php esc_html_e( 'We have also created a shortcode that would be an added feature to the variants. Shortcode sample usage: [vpe-woo-variable-product id=”<enter-here-product-id>”].' ); ?>
						</p>
						<h3><?php esc_html_e( 'Admin Settings', 'woocommerce-quick-cart-for-multiple-variations' ); ?> </h3>
						<p class="block textgetting">
                            <span class="gettingstarted">
                                <img
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/variant_extended_settings.png' ); ?>"
									alt="<?php esc_html_e( 'Variant Extended Settings', 'woocommerce-quick-cart-for-multiple-variations' ); ?>">
                            </span>
						</p>
						<h3><?php esc_html_e( 'Front View', 'woocommerce-quick-cart-for-multiple-variations' ); ?> </h3>
						<p class="block textgetting">
                            <span class="frontview">
                                <img
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/front_view.png' ); ?>"
									alt="<?php esc_html_e( 'Front View', 'woocommerce-quick-cart-for-multiple-variations' ); ?> ">
                            </span>
						</p>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<?php

	}

	/**
	 * Introduction to plugin.
	 */
	function introduction_variant_extended() {

		$plugin_name    = 'Quick Bulk Variations Checkout for WooCommerce';
		$plugin_version = '1.2.0';
		?>
		<div class="vpe-table">
			<form id="cw_plugin_form_id_second">
				<div class="under-table third-tab">
					<div class="set">
						<h2><?php esc_html_e( 'Quick info', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h2>
					</div>
					<table class="form-table table-outer">
						<tbody>
						<tr>
							<td class="fr-1"><?php esc_html_e( 'Product Type', 'woocommerce-quick-cart-for-multiple-variations' ); ?></td>
							<td class="fr-2"><?php esc_html_e( 'WooCommerce Plugin', 'woocommerce-quick-cart-for-multiple-variations' ) ?></td>
						</tr>
						<tr>
							<td class="fr-1"><?php esc_html_e( 'Product Name', 'woocommerce-quick-cart-for-multiple-variations' ); ?></td>
							<td class="fr-2"><?php echo esc_attr( $plugin_name ); ?></td>
						</tr>
						<tr>
							<td class="fr-1"><?php esc_html_e( 'Installed Version', 'woocommerce-quick-cart-for-multiple-variations' ); ?></td>
							<td class="fr-2"><?php echo esc_attr( $plugin_version ); ?></td>
						</tr>
						<tr>
							<td class="fr-1"><?php esc_html_e( 'License & Terms of use', 'woocommerce-quick-cart-for-multiple-variations' ); ?></td>
							<td class="fr-2">
								<?php $click_here = '<a href="https://www.thedotstore.com/terms-and-conditions/" target="_blank">' . esc_html__( 'Click here', 'woocommerce-quick-cart-for-multiple-variations' ) . '</a>';
								echo sprintf( esc_html__( '%1$s to view license and terms of use.', 'woocommerce-quick-cart-for-multiple-variations' ), wp_kses_post( $click_here ) ); ?>
							</td>
						</tr>
						<tr>
							<td class="fr-1"><?php esc_html_e( 'Help & Support', 'woocommerce-quick-cart-for-multiple-variations' ); ?></td>
							<td class="fr-2">
								<ul class="listing">
									<li>
										<a href="#"><?php esc_html_e( 'Quick Start Guide', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
									</li>
									<li>
										<a href="#"><?php esc_html_e( 'Documentation', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
									</li>
									<li>
										<a href="<?php echo esc_url( "https://www.thedotstore.com/support/" ); ?>"><?php esc_html_e( 'Support Fourm', 'woocommerce-quick-cart-for-multiple-variations' ) ?></a>
									</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td class="fr-1"><?php esc_html_e( 'Localization', 'woocommerce-quick-cart-for-multiple-variations' ); ?></td>
							<td class="fr-2"><?php esc_html_e( 'English, Spanish', 'woocommerce-quick-cart-for-multiple-variations' ); ?></td>
						</tr>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<?php

	}

	/**
	 * This function update the product meta for show the variation table in product page.
	 * @param int $batch_number
	 */
	public function wqcmv_bulk_update_visibility_options_handler( $batch_number ) {
		if ( empty( $batch_number ) ) {
			return;
		}
		$products = wqcmv_get_variable_products( 10, $batch_number, false );
		if ( is_array( $products ) && ! empty( $products ) ) {
			foreach ( $products as $product_id ) {
				update_post_meta( $product_id, 'product_visiblity_option', 'yes' );
			}
		}
	}

}