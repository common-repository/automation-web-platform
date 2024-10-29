<?php
/**
 * Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class awp_checkout_otp {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_save_blocked_numbers', array( $this, 'save_blocked_numbers' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'init', array( $this, 'load_otp_verification' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_filter( 'manage_users_columns', array( $this, 'add_user_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'show_user_column_data' ), 10, 3 );
		add_filter( 'manage_users_sortable_columns', array( $this, 'make_columns_sortable' ) );
		add_action( 'pre_get_users', array( $this, 'sort_users_by_column' ) );
		add_action( 'wp_ajax_update_user_phone_number', array( $this, 'update_user_phone_number' ) );
		add_action( 'wp_ajax_nopriv_update_user_phone_number', array( $this, 'update_user_phone_number' ) );
		add_action( 'wp_ajax_nopriv_verify_otp', array( $this, 'verify_otp_ajax_handler' ) );
		add_action( 'wp_ajax_nopriv_check_if_phone_number_blocked', array( $this, 'check_if_phone_number_blocked' ) );
	}

	public function update_user_phone_number() {
		check_ajax_referer( 'otp-ajax-nonce', 'security' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( esc_html__( 'User is not logged in.', 'awp' ) );
			wp_die();
		}

		$phone_number = sanitize_text_field( $_POST['phone_number'] );
		if ( empty( $phone_number ) ) {
			wp_send_json_error( esc_html__( 'Phone number is missing.', 'awp' ) );
			wp_die();
		}

		$current_user = wp_get_current_user();
		update_user_meta( $current_user->ID, 'billing_phone', $phone_number );
		wp_send_json_success( esc_html__( 'Phone number updated successfully.', 'awp' ) );
	}

	// Enqueue scripts and styles
	public function enqueue_scripts() {
		wp_enqueue_script( 'awp-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/checkout.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'awp-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/checkout.css', array(), '1.0', 'all' );

		$translation_array = array(
			'otp_sent_success'     => esc_html__( 'OTP sent successfully via WhatsApp.', 'awp' ),
			'otp_sent_failure'     => esc_html__( 'Failed to send OTP. Please try again.', 'awp' ),
			'otp_verified_success' => esc_html__( 'OTP verified successfully.', 'awp' ),
			'otp_incorrect'        => esc_html__( 'Incorrect OTP. Please try again.', 'awp' ),
			'phone_registered'     => esc_html__( 'This phone number is already registered. Please login or use a different number.', 'awp' ),
		);
		wp_localize_script( 'awp-checkout-js', 'awp_translations', $translation_array );

		wp_localize_script(
			'awp-checkout-js',
			'otpAjax',
			array(
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'nonce'                 => wp_create_nonce( 'otp-ajax-nonce' ),
				'isLoggedIn'            => is_user_logged_in() ? 'true' : 'false',
				'enableForVisitorsOnly' => get_option( 'awp_enable_otp_for_visitors', 'no' ),
			)
		);
	}

	// Register settings, section, and fields
	public function register_settings() {
		add_option( 'awp_enable_otp', 'no' );
		add_option( 'awp_enable_otp_for_visitors', 'no' );
		add_option( 'awp_otp_message_template', 'Hi {{name}}, {{otp}} is your checkout Generated OTP code. Do not share this code with others.' );
		add_option( 'awp_blocked_numbers', '' );

		register_setting( 'awp_options_group', 'awp_enable_otp' );
		register_setting( 'awp_options_group', 'awp_enable_otp_for_visitors' );
		register_setting( 'awp_options_group', 'awp_otp_message_template' );
		register_setting( 'awp_options_group', 'awp_blocked_numbers' );

		add_settings_section( 'awp_settings_section', esc_html__( '', 'awp' ), null, 'awp-settings' );
		add_settings_field( 'awp_enable_otp_field', esc_html__( 'Enable checkout verification', 'awp' ), array( $this, 'enable_otp_field_callback' ), 'awp-settings', 'awp_settings_section' );
		add_settings_field( 'awp_enable_otp_for_visitors_field', esc_html__( 'Disable for logged in users', 'awp' ), array( $this, 'enable_otp_for_visitors_field_callback' ), 'awp-settings', 'awp_settings_section' );
		add_settings_field( 'awp_otp_message_template_field', esc_html__( 'Message template', 'awp' ), array( $this, 'otp_message_template_field_callback' ), 'awp-settings', 'awp_settings_section' );
		add_settings_field( 'awp_blocked_numbers_field', esc_html__( 'Blocked phone numbers', 'awp' ), array( $this, 'blocked_numbers_field_callback' ), 'awp-settings', 'awp_settings_section' );
	}

	public function blocked_numbers_field_callback() {
		$value = get_option( 'awp_blocked_numbers', '' );
		echo '<input id="awp_blocked_numbers" name="awp_blocked_numbers" value="' . esc_attr( $value ) . '" />';
		echo '<p>' . esc_html__( 'Enter blocked phone numbers separated by commas (,).', 'awp' ) . '</p>';
	}

	public function enable_otp_field_callback() {
		$value   = get_option( 'awp_enable_otp', 'no' );
		$checked = checked( $value, 'yes', false );
		echo '<label class="switch">';
		echo '<input type="checkbox" id="awp_enable_otp" name="awp_enable_otp" value="yes"' . $checked . ' />';
		echo '<span class="slider"></span>';
		echo '</label>';
	}

	public function enable_otp_for_visitors_field_callback() {
		$value   = get_option( 'awp_enable_otp_for_visitors', 'no' );
		$checked = checked( $value, 'yes', false );
		echo '<label class="switch">';
		echo '<input type="checkbox" id="awp_enable_otp_for_visitors" name="awp_enable_otp_for_visitors" value="yes"' . $checked . ' />';
		echo '<span class="slider"></span>';
		echo '</label>';
	}

	public function otp_message_template_field_callback() {
		$value = get_option( 'awp_otp_message_template', 'Hi {{name}}, {{otp}} is your Checkout Generated OTP code. Do not share this code with others.' );
		?>
		<div class="notification-form english otp-card">
			<div class="heading-bar">
				<label for="login_message" class="notification-title"><?php esc_html_e( 'Checkout OTP message', 'awp' ); ?>
					<span class="tooltip-text"><?php esc_html_e( "Sent during checkout to verify the customer's number.", 'awp' ); ?></span>
				</label>
			</div>
			<hr class="line">
			<div class="notification">
				<div class="form">
					<textarea id="awp_otp_message_template" name="awp_otp_message_template" rows="5" cols="85" class="otp_message"><?php echo esc_textarea( $value ); ?></textarea>
					<p class="placeholders">
						<?php esc_html_e( 'Shortcodes: ', 'awp' ); ?>
						<code>{{name}}</code> <?php esc_html_e( 'Member name', 'awp' ); ?> —
						<code>{{otp}}</code> <?php esc_html_e( 'Generated OTP code', 'awp' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	public function add_settings_page() {
		$hook = add_menu_page(
			__( 'Checkout OTP Verification', 'awp' ),
			__( 'Checkout OTP', 'awp' ),
			'manage_options',
			'awp-checkout-otp',
			array( $this, 'settings_page_html' )
		);
		remove_menu_page( 'awp-checkout-otp' );
	}

	public function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include_once plugin_dir_path( __FILE__ ) . 'admin/awp-checkout-admin.php';
	}

	public function admin_enqueue_scripts( $hook ) {
		global $pagenow;
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'awp-checkout-otp' === $_GET['page'] ) {
			wp_enqueue_style( 'awp-checkoutadmin-css', plugin_dir_url( __FILE__ ) . 'assets/css/awp-admin-style.css', array(), '1.0', 'all' );
			if ( is_rtl() ) {
				wp_enqueue_style( 'awp-admin-rtl-css', plugins_url( 'assets/css/awp-admin-rtl-style.css', __FILE__ ), array(), '1.1.4' );
			}
			wp_enqueue_style( 'tagify-css', plugin_dir_url( __FILE__ ) . 'assets/css/resources/tagify.css', array(), '1.0', 'all' );
			wp_enqueue_script( 'tagify-js', plugin_dir_url( __FILE__ ) . 'assets/js/resources/tagify.js', array( 'jquery' ), '4.9.1', true );
			wp_enqueue_script( 'awp-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/checkout-admin.js', array( 'jquery' ), '1.0', true );
			wp_localize_script(
				'awp-admin-js',
				'ajax_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'awp_ajax_nonce' ),
				)
			);
		}
	}

	public function save_blocked_numbers() {
		check_ajax_referer( 'awp_ajax_nonce', 'security' );
		$blocked_numbers = sanitize_text_field( $_POST['blocked_numbers'] );
		update_option( 'awp_blocked_numbers', $blocked_numbers );
		wp_send_json_success( esc_html__( 'Blocked numbers saved successfully.', 'awp' ) );
	}

	public function load_otp_verification() {
		$enable_otp              = get_option( 'awp_enable_otp', 'no' );
		$enable_otp_for_visitors = get_option( 'awp_enable_otp_for_visitors', 'no' );

		if ( 'yes' === $enable_otp || ( 'yes' === $enable_otp_for_visitors && ! is_user_logged_in() ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'woocommerce_after_order_notes', array( $this, 'add_otp_verification_popup' ) );
			add_action( 'wp_ajax_send_otp', array( $this, 'send_otp_ajax_handler' ) );
			add_action( 'wp_ajax_nopriv_send_otp', array( $this, 'send_otp_ajax_handler' ) );
			add_action( 'wp_ajax_verify_otp', array( $this, 'verify_otp_ajax_handler' ) );
			add_action( 'wp_ajax_nopriv_verify_otp', array( $this, 'verify_otp_ajax_handler' ) );
			add_action( 'woocommerce_checkout_process', array( $this, 'check_if_otp_verified' ) );
			add_action( 'wp_ajax_verify_phone_number', array( $this, 'verify_phone_number_ajax_handler' ) );
			add_action( 'wp_ajax_nopriv_verify_phone_number', array( $this, 'verify_phone_number_ajax_handler' ) );
			add_action( 'woocommerce_checkout_process', array( $this, 'check_if_phone_number_blocked' ) );
		}
	}

	public function verify_phone_number_ajax_handler() {
		check_ajax_referer( 'otp-ajax-nonce', 'security' );
		$phone_number = sanitize_text_field( $_POST['phone_number'] );
		if ( empty( $phone_number ) ) {
			wp_send_json_error(
				array(
					'status'  => 'error',
					'message' => esc_html__( 'Phone number is missing.', 'awp' ),
				)
			);
			wp_die();
		}

		$blocked_numbers = explode( ',', get_option( 'awp_blocked_numbers', '' ) );
		$blocked_numbers = array_map( 'trim', $blocked_numbers );

		if ( in_array( $phone_number, $blocked_numbers ) ) {
			wp_send_json_error(
				array(
					'status'  => 'blocked',
					'message' => esc_html__( 'This phone number is blocked.', 'awp' ),
				)
			);
			wp_die();
		}

		if ( is_user_logged_in() ) {
			$current_user      = wp_get_current_user();
			$user_phone_number = get_user_meta( $current_user->ID, 'billing_phone', true );
			$phone_verified    = get_user_meta( $current_user->ID, 'phone_verified', true );

			if ( $phone_number === $user_phone_number ) {
				if ( $phone_verified ) {
					wp_send_json_success(
						array(
							'status'  => 'verified',
							'message' => esc_html__( 'Phone number matches user account and is verified.', 'awp' ),
						)
					);
				} else {
					wp_send_json_error(
						array(
							'status'  => 'not_verified',
							'message' => esc_html__( 'Phone number matches user account but is not verified.', 'awp' ),
						)
					);
				}
				wp_die();
			}
		}

		$user_query = new WP_User_Query(
			array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $phone_number,
				'number'     => 1,
				'exclude'    => is_user_logged_in() ? array( get_current_user_id() ) : array(),
			)
		);

		if ( ! empty( $user_query->get_results() ) ) {
			wp_send_json_error(
				array(
					'status'  => 'registered',
					'message' => esc_html__( 'This phone number is already registered. Please use a different number.', 'awp' ),
				)
			);
			wp_die();
		}

		wp_send_json_success(
			array(
				'status'  => 'not_registered',
				'message' => esc_html__( 'Phone number is not registered.', 'awp' ),
			)
		);
	}


	public function check_if_phone_number_blocked() {
		if ( ! is_user_logged_in() ) {
			$phone_number = WC()->checkout->get_value( 'billing_phone' );
		} else {
			$current_user = wp_get_current_user();
			$phone_number = get_user_meta( $current_user->ID, 'billing_phone', true );
		}

		$blocked_numbers = explode( ',', get_option( 'awp_blocked_numbers', '' ) );
		$blocked_numbers = array_map( 'trim', $blocked_numbers );

		if ( in_array( $phone_number, $blocked_numbers ) ) {
			wc_add_notice( __( 'This phone number is blocked. Please use a different number.', 'awp' ), 'error' );
		}
	}

	public function add_otp_verification_popup() {
		?>
		<div id="awp_otp_popup" class="awp-otp-popup" style="display:none;">
			<div class="awp-otp-popup-content">
				<div class="awp-otp-box">
					<h3 class="awp-title">
						<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" style="fill: #00a884;transform: ;msFilter:;">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M18.403 5.633A8.919 8.919 0 0 0 12.053 3c-4.948 0-8.976 4.027-8.978 8.977 0 1.582.413 3.126 1.198 4.488L3 21.116l4.759-1.249a8.981 8.981 0 0 0 4.29 1.093h.004c4.947 0 8.975-4.027 8.977-8.977a8.926 8.926 0 0 0-2.627-6.35m-6.35 13.812h-.003a7.446 7.446 0 0 1-3.798-1.041l-.272-.162-2.824.741.753-2.753-.177-.282a7.448 7.448 0 0 1-1.141-3.971c.002-4.114 3.349-7.461 7.465-7.461a7.413 7.413 0 0 1 5.275 2.188 7.42 7.42 0 0 1 2.183 5.279c-.002 4.114-3.349 7.462-7.461 7.462m4.093-5.589c-.225-.113-1.327-.655-1.533-.73-.205-.075-.354-.112-.504.112s-.58.729-.711.879-.262.168-.486.056-.947-.349-1.804-1.113c-.667-.595-1.117-1.329-1.248-1.554s-.014-.346.099-.458c.101-.1.224-.262.336-.393.112-.131.149-.224.224-.374s.038-.281-.019-.393c-.056-.113-.505-1.217-.692-1.666-.181-.435-.366-.377-.504-.383a9.65 9.65 0 0 0-.429-.008.826.826 0 0 0-.599.28c-.206.225-.785.767-.785 1.871s.804 2.171.916 2.321c.112.15 1.582 2.415 3.832 3.387.536.231.954.369 1.279.473.537.171 1.026.146 1.413.089.431-.064 1.327-.542 1.514-1.066.187-.524.187-.973.131-1.067-.056-.094-.207-.151-.43-.263"></path>
						</svg>
						<?php esc_html_e( 'Confirm your order', 'awp' ); ?>
					</h3>
					<span class="awp-otp-popup-close">&times;</span>
				</div>
				<div class="awp-otp-content">
					<p class="awp-desc"><?php esc_html_e( 'To complete your order, enter the 6-digit code sent via WhatsApp to', 'awp' ); ?>
						<span id="user_phone_number"></span>
					</p>
					<div class="awp-popup-message"></div>
					<div class="awp-otp-error" style="display:none;"></div>
					<div class="otp-inputs">
						<input type="tel" class="otp-input" maxlength="1">
						<input type="tel" class="otp-input" maxlength="1">
						<input type="tel" class="otp-input" maxlength="1">
						<input type="tel" class="otp-input" maxlength="1">
						<input type="tel" class="otp-input" maxlength="1">
						<input type="tel" class="otp-input" maxlength="1">
					</div>
					<div class="awp-btn-group">
						<button type="button" class="button alt" id="awp_verify_otp_btn"><?php esc_html_e( 'Confirm order', 'awp' ); ?></button>
						<button type="button" class="button alt" id="awp_resend_otp_btn" disabled><?php esc_html_e( 'Resend code', 'awp' ); ?><span id="awp_resend_timer"></span></button>
					</div>
				</div>
			</div>
			<div class="awp-powered-by">
				<?php esc_html_e( 'Powered by', 'awp' ); ?> <a href="https://wawp.net" target="_blank">
					<svg width="58" height="16" viewBox="0 0 58 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M16.9977 2.99969V8.99875C16.9977 10.0986 16.0979 10.9984 14.9981 10.9984H11.5886L11.2886 11.2884L8.99899 13.588L6.70934 11.2884L6.4094 10.9984H2.99993C1.9001 10.9984 1.00024 10.0986 1.00024 8.99875V2.99969C1.00024 2.60975 1.11022 2.23981 1.31019 1.92985H1.3202C1.67015 1.36994 2.30004 1 2.99993 1H14.9981C15.6979 1 16.3278 1.36994 16.6778 1.91986H16.6878C16.8878 2.23981 16.9977 2.60975 16.9977 2.99969Z" fill="#00EE88" />
						<path d="M14.9977 0H2.99953C1.33979 0 0 1.33979 0 2.99953V10.4084C0 12.0681 1.33979 13.4079 2.99953 13.4079H5.99906L8.28871 15.7075C8.67865 16.0975 9.31853 16.0975 9.70847 15.7075L11.9981 13.4079H14.9977C16.6574 13.4079 17.9972 12.0681 17.9972 10.4084V2.99953C17.9972 1.33979 16.6574 0 14.9977 0ZM16.9973 8.99859C16.9973 10.0984 16.0975 10.9983 14.9977 10.9983H11.5882L11.2882 11.2882L8.99859 13.5879L6.70894 11.2882L6.409 10.9983H2.99953C1.8997 10.9983 0.999844 10.0984 0.999844 8.99859V2.99953C0.999844 2.60959 1.10982 2.23965 1.30979 1.9297H1.3198C1.66975 1.36979 2.29964 0.999844 2.99953 0.999844H14.9977C15.6975 0.999844 16.3274 1.36979 16.6774 1.9197C16.8774 2.23965 16.9873 2.60959 16.9873 2.99953V8.99859H16.9973Z" fill="#004444" />
						<path d="M6.99895 4.99911V6.9988C6.99895 7.54871 6.54903 7.99864 5.99911 7.99864C5.4492 7.99864 4.99927 7.54871 4.99927 6.9988V4.99911C4.99927 4.4492 5.4492 3.99927 5.99911 3.99927C6.54903 3.99927 6.99895 4.4492 6.99895 4.99911Z" fill="#004444" />
						<path d="M12.9982 4.99911V6.9988C12.9982 7.54871 12.5483 7.99864 11.9984 7.99864C11.4485 7.99864 10.9985 7.54871 10.9985 6.9988V4.99911C10.9985 4.4492 11.4485 3.99927 11.9984 3.99927C12.5483 3.99927 12.9982 4.4492 12.9982 4.99911Z" fill="#004444" />
						<path d="M23.9071 10.8804L22.2417 3.13647H24.2772L25.2833 9.28468H25.4106L26.6712 3.13647H29.0189L30.3026 9.28468H30.4299L31.436 3.13647H33.3558L31.6789 10.8804H29.0883L27.8739 4.74393H27.7583L26.5555 10.8804H23.9071ZM35.4105 11.0447C35.0713 11.0447 34.7706 10.9782 34.5084 10.8452C34.2463 10.7044 34.0419 10.5049 33.8955 10.2468C33.749 9.98086 33.6757 9.66015 33.6757 9.28468C33.6757 8.93269 33.7413 8.64327 33.8723 8.41643C34.0111 8.18176 34.2077 7.99794 34.4622 7.86496C34.7243 7.72417 35.0366 7.61074 35.3989 7.5247C35.7613 7.43084 36.1738 7.3487 36.6364 7.2783C36.86 7.23919 37.045 7.20399 37.1915 7.1727C37.3457 7.13359 37.4614 7.07493 37.5385 6.99671C37.6156 6.91848 37.6541 6.80506 37.6541 6.65644C37.6541 6.46089 37.5848 6.29271 37.446 6.15191C37.3072 6.01112 37.0836 5.94072 36.7752 5.94072C36.5593 5.94072 36.3627 5.97983 36.1854 6.05805C36.0157 6.13627 35.8692 6.24969 35.7459 6.39831C35.6225 6.54693 35.53 6.72684 35.4683 6.93804L33.8261 6.44524C33.9263 6.11671 34.0651 5.83121 34.2424 5.58872C34.4275 5.34623 34.6472 5.14677 34.9016 4.99032C35.1561 4.82606 35.4452 4.70873 35.769 4.63833C36.1006 4.56011 36.4514 4.521 36.8214 4.521C37.4383 4.521 37.9394 4.62268 38.3249 4.82606C38.7181 5.02161 39.0073 5.33059 39.1923 5.75298C39.3851 6.16756 39.4814 6.70338 39.4814 7.36044V8.31083C39.4814 8.59242 39.4853 8.87793 39.493 9.16735C39.5084 9.44895 39.5238 9.73446 39.5393 10.0239C39.5624 10.3055 39.5894 10.591 39.6202 10.8804H37.978C37.9471 10.7005 37.9163 10.4854 37.8854 10.2351C37.8546 9.97695 37.8315 9.7149 37.8161 9.44895H37.5963C37.4884 9.74619 37.3303 10.02 37.1221 10.2703C36.9217 10.5128 36.675 10.7044 36.382 10.8452C36.0967 10.9782 35.7729 11.0447 35.4105 11.0447ZM36.2548 9.70708C36.3935 9.70708 36.5323 9.68361 36.6711 9.63668C36.8176 9.58193 36.9564 9.51153 37.0874 9.42548C37.2185 9.33162 37.338 9.2182 37.446 9.08522C37.5539 8.95224 37.6349 8.80753 37.6888 8.65109L37.6657 7.72417L37.9201 7.78283C37.7736 7.8767 37.6117 7.95101 37.4344 8.00576C37.2571 8.06052 37.0759 8.10354 36.8908 8.13483C36.7135 8.16612 36.5362 8.20132 36.3588 8.24043C36.1892 8.27172 36.035 8.31474 35.8962 8.36949C35.7652 8.42425 35.6611 8.49856 35.584 8.59242C35.5069 8.68629 35.4683 8.81536 35.4683 8.97962C35.4683 9.19864 35.5416 9.37464 35.6881 9.50762C35.8346 9.64059 36.0235 9.70708 36.2548 9.70708ZM41.6776 10.8804L40.1972 4.68526H42.1402L42.8919 9.40202H43.1232L44.06 4.68526H46.269L47.2404 9.40202H47.4833L48.2003 4.68526H50.1086L48.6283 10.8804H46.0608L45.2165 6.21058H44.9968L44.2103 10.8804H41.6776ZM50.9628 12.8633V7.85323V4.68526H52.4894L52.5125 6.44524L52.7207 6.46871C52.7901 6.03067 52.9134 5.66694 53.0908 5.37752C53.2681 5.0881 53.4956 4.87299 53.7731 4.73219C54.0584 4.59139 54.3822 4.521 54.7446 4.521C55.2843 4.521 55.7469 4.65397 56.1324 4.91993C56.5256 5.18588 56.8263 5.56525 57.0345 6.05805C57.2427 6.55084 57.3468 7.14142 57.3468 7.82976C57.3468 8.43989 57.2542 8.98744 57.0692 9.47242C56.8919 9.95739 56.6182 10.3407 56.2481 10.6223C55.8857 10.9039 55.4231 11.0447 54.8602 11.0447C54.4825 11.0447 54.1625 10.9782 53.9003 10.8452C53.6382 10.7044 53.4146 10.501 53.2296 10.2351C53.0522 9.96912 52.9019 9.63668 52.7785 9.23775H52.5588C52.605 9.44895 52.6474 9.66406 52.686 9.88308C52.7245 10.1021 52.7554 10.3133 52.7785 10.5167C52.8094 10.72 52.8248 10.9156 52.8248 11.1033V12.8633H50.9628ZM54.1779 9.55455C54.4323 9.55455 54.6482 9.48415 54.8256 9.34335C55.0106 9.20255 55.1532 9.00309 55.2535 8.74496C55.3537 8.48683 55.4038 8.18567 55.4038 7.8415C55.4038 7.47386 55.3498 7.16097 55.2419 6.90284C55.1417 6.63689 54.9952 6.43351 54.8024 6.29271C54.6097 6.14409 54.3861 6.06978 54.1316 6.06978C53.8926 6.06978 53.6883 6.12454 53.5187 6.23405C53.3568 6.33573 53.2218 6.47262 53.1139 6.64471C53.0137 6.80897 52.9404 6.98497 52.8942 7.1727C52.8479 7.36044 52.8248 7.53643 52.8248 7.7007V7.95883C52.8248 8.10745 52.8441 8.25607 52.8826 8.40469C52.9212 8.55331 52.979 8.69802 53.0561 8.83882C53.1332 8.9718 53.2257 9.09304 53.3336 9.20255C53.4416 9.31206 53.5649 9.39811 53.7037 9.46068C53.8502 9.52326 54.0083 9.55455 54.1779 9.55455Z" fill="white" />
					</svg></a>
			</div>
		</div>
		<?php
	}

	public function send_otp_ajax_handler() {
		check_ajax_referer( 'otp-ajax-nonce', 'security' );
		$phone_number = sanitize_text_field( $_POST['phone_number'] );
		$first_name   = sanitize_text_field( $_POST['first_name'] );

		if ( empty( $phone_number ) ) {
			wp_send_json_error( esc_html__( 'Phone number is missing.', 'awp' ) );
			wp_die();
		}

		$blocked_numbers = explode( ',', get_option( 'awp_blocked_numbers', '' ) );
		$blocked_numbers = array_map( 'trim', $blocked_numbers );

		if ( in_array( $phone_number, $blocked_numbers ) ) {
			wp_send_json_error( esc_html__( 'This phone number is blocked.', 'awp' ) );
			wp_die();
		}

		$otp = rand( 100000, 999999 );
		WC()->session->set( 'otp', $otp );
		WC()->session->set( 'otp_verified', false );

		$settings     = get_option( 'wwo_settings' );
		$instance_id  = isset( $settings['general']['instance_id'] ) ? $settings['general']['instance_id'] : '';
		$access_token = isset( $settings['general']['access_token'] ) ? $settings['general']['access_token'] : '';

		if ( empty( $instance_id ) || empty( $access_token ) ) {
			error_log( esc_html__( 'Instance ID or Access Token is missing.', 'awp' ) );
			wp_send_json_error( esc_html__( 'Instance ID or Access Token is missing.', 'awp' ) );
			wp_die();
		}

		$message_template = get_option( 'awp_otp_message_template', 'Hi {{name}}, {{otp}} is your Generated OTP code to make order. Do not share this code with others.' );
		$message          = str_replace( array( '{{name}}', '{{otp}}' ), array( $first_name, $otp ), $message_template );

		$data = array(
			'number'       => $phone_number,
			'type'         => 'text',
			'message'      => $message,
			'instance_id'  => $instance_id,
			'access_token' => $access_token,
		);

		$args = array(
			'body'        => json_encode( $data ),
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'method'      => 'POST',
			'data_format' => 'body',
		);

		error_log( 'Sending OTP via WhatsApp with data: ' . print_r( $data, true ) );
		$response = wp_remote_post( 'https://app.wawp.net/api/send', $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( esc_html__( 'Failed to send OTP via WhatsApp. Error: ', 'awp' ) . $error_message );
			wp_send_json_error( esc_html__( 'Failed to send OTP via WhatsApp. Error: ', 'awp' ) . $error_message );
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );
			error_log( 'OTP API Response Code: ' . $response_code );
			error_log( 'OTP API Response Body: ' . $response_body );

			if ( $response_code != 200 ) {
				wp_send_json_error( esc_html__( 'Failed to send OTP via WhatsApp. Response: ', 'awp' ) . $response_body );
			} else {
				wp_send_json_success( esc_html__( 'OTP sent successfully via WhatsApp.', 'awp' ) );
			}
		}
	}

	public function verify_otp_ajax_handler() {
		check_ajax_referer( 'otp-ajax-nonce', 'security' );
		$user_otp    = sanitize_text_field( $_POST['otp'] );
		$correct_otp = WC()->session->get( 'otp' );

		if ( $user_otp == $correct_otp ) {
			WC()->session->set( 'otp_verified', true );

			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				update_user_meta( $current_user->ID, 'phone_verified', true );
			}

			wp_send_json_success( esc_html__( 'OTP verified successfully.', 'awp' ) );
		} else {
			wp_send_json_error( esc_html__( 'Incorrect OTP. Please try again.', 'awp' ) );
		}
	}

	public function check_if_otp_verified() {
		$enable_otp              = get_option( 'awp_enable_otp', 'no' );
		$enable_otp_for_visitors = get_option( 'awp_enable_otp_for_visitors', 'no' );

		if ( 'yes' === $enable_otp || ( 'yes' === $enable_otp_for_visitors && ! is_user_logged_in() ) ) {
			if ( is_user_logged_in() ) {
				$current_user   = wp_get_current_user();
				$phone_verified = get_user_meta( $current_user->ID, 'phone_verified', true );

				if ( $phone_verified ) {
					return;
				}
			} elseif ( WC()->session->get( 'otp_verified' ) ) {
					return;
			}

			if ( ! WC()->session->get( 'otp_verified' ) ) {
				wc_add_notice( esc_html__( 'Please verify the OTP before placing your order.', 'awp' ), 'error' );
			}
		}

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$phone_number = get_user_meta( $current_user->ID, 'billing_phone', true );
		} else {
			$phone_number = WC()->checkout->get_value( 'billing_phone' );
		}

		$blocked_numbers = explode( ',', get_option( 'awp_blocked_numbers', '' ) );
		$blocked_numbers = array_map( 'trim', $blocked_numbers );

		if ( in_array( $phone_number, $blocked_numbers ) ) {
			wc_add_notice( __( 'This phone number is blocked. Please use a different number.', 'awp' ), 'error' );
		}
	}

	public function add_user_columns( $columns ) {
		$columns['whatsapp_number']       = esc_html__( 'Whatsapp Number', 'awp' );
		$columns['whatsapp_verification'] = esc_html__( 'WhatsApp Verified', 'awp' );
		return $columns;
	}

	public function show_user_column_data( $value, $column_name, $user_id ) {
		if ( 'whatsapp_number' === $column_name ) {
			$phone_number = get_user_meta( $user_id, 'billing_phone', true );
			$value        = ! empty( $phone_number ) ? esc_html( $phone_number ) : esc_html__( 'N/A', 'awp' );
		}
		if ( 'whatsapp_verification' === $column_name ) {
			$phone_verified = get_user_meta( $user_id, 'phone_verified', true );
			if ( $phone_verified ) {
				$value = '<span style="color: green;">&#x1F4F1; Verified</span>';
			} else {
				$value = '<span style="color: red;">&#x274C; Not verified</span>';
			}
		}
		return $value;
	}

	public function make_columns_sortable( $columns ) {
		$columns['whatsapp_number']       = 'whatsapp_number';
		$columns['whatsapp_verification'] = 'whatsapp_verification';
		return $columns;
	}


	public function sort_users_by_column( $query ) {
		global $pagenow;

		if ( $pagenow !== 'users.php' ) {
			return;
		}

		if ( isset( $query->query_vars['orderby'] ) ) {
			if ( 'whatsapp_number' === $query->query_vars['orderby'] ) {
				$query->query_vars['meta_key'] = 'billing_phone';
				$query->query_vars['orderby']  = 'meta_value';
			}
			if ( 'whatsapp_verification' === $query->query_vars['orderby'] ) {
				$query->query_vars['meta_key'] = 'phone_verified';
				$query->query_vars['orderby']  = 'meta_value';
			}
		}
	}
}

new awp_checkout_otp();
?>
