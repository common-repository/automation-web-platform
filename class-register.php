<?php
/**
 * Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Register
 *
 * Handles user registration and OTP verification.
 */
class Register extends WWO {

	/**
	 * Indicates whether the user is logged in.
	 *
	 * @var bool
	 */
	private $is_login = false;

	/**
	 * The single instance of the class.
	 *
	 * @var Register
	 */
	private static $instance;

	/**
	 * Returns an instance of this class.
	 *
	 * @return Register
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Sets up actions and hooks for the registration process.
	 */
	public function __construct() {
		add_action( 'woocommerce_register_form', array( $this, 'register_form' ) );
		add_action( 'woocommerce_created_customer', array( $this, 'register_action' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_awp_send_register_otp', array( $this, 'register_otp' ) );
		add_action( 'wp_ajax_nopriv_awp_send_register_otp', array( $this, 'register_otp' ) );
		add_action( 'wp_ajax_awp_register', array( $this, 'register' ) );
		add_action( 'wp_ajax_nopriv_awp_register', array( $this, 'register' ) );
		add_shortcode( 'wawp_account_register', array( $this, 'wawp_register_form_shortcode' ) );
	}

	/**
	 * Shortcode to display the registration form.
	 *
	 * @return string
	 */
	public function wawp_register_form_shortcode() {
		// Enqueue WooCommerce styles and scripts.
		if ( function_exists( 'is_woocommerce' ) ) {
			wp_enqueue_style( 'woocommerce-general' );
			wp_enqueue_style( 'woocommerce-layout' );
			wp_enqueue_style( 'woocommerce-smallscreen' );
			wp_enqueue_script( 'wc-cart-fragments' );
			wp_enqueue_script( 'woocommerce' );
			wp_enqueue_script( 'wc-address-i18n' );
			wp_enqueue_script( 'jquery-blockui' );
			wp_enqueue_script( 'jquery-payment' );
		}

		ob_start();

		// Check if the user is logged in.
		if ( is_user_logged_in() ) {
			$current_user   = wp_get_current_user();
			$home_url       = home_url( '/' );
			$my_account_url = wc_get_page_permalink( 'myaccount' );

			echo '<div style="text-align: center; margin-top: 50px;">';
			echo '<h2>Hello ' . esc_html( $current_user->display_name ) . ', you are now logged in.</h2>';
			echo '<a href="' . esc_url( $home_url ) . '" style="display: inline-block; padding: 10px 20px; margin: 10px; background-color: #007cba; color: #fff; text-decoration: none; font-family: inherit; font-size: 16px; border-radius: 4px;">Go to Home Page</a>';
			echo '<a href="' . esc_url( $my_account_url ) . '" style="display: inline-block; padding: 10px 20px; margin: 10px; background-color: #007cba; color: #fff; text-decoration: none; font-family: inherit; font-size: 16px; border-radius: 4px;">Go to My Account</a>';
			echo '</div>';
		} else {
			// Load the custom registration form template.
			$template_path = plugin_dir_path( __FILE__ ) . 'templates/form-register-only.php';
			if ( file_exists( $template_path ) ) {
				include_once $template_path;
			} else {
				echo esc_html__( 'Template not found.', 'awp' );
			}
		}

		return ob_get_clean();
	}

	/**
	 * Adds custom fields to the WooCommerce registration form.
	 */
	public function register_form() {
		$settings = get_option( 'wwo_settings' );
		wp_nonce_field( 'register_action', 'register_nonce' ); // Adding nonce field for security.
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide awp">
			<label for="register_your_whatsapp" class="awp-label"><?php esc_html_e( 'Your WhatsApp Number', 'awp' ); ?></label>
			<input class="woocommerce-Input woocommerce-Input--text input-text" type="tel" name="register_your_whatsapp" id="register_your_whatsapp" />
			<button type="button" class="send_register_otp sendotpcss woocommerce-button button woocommerce-form-register__awp <?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="Send OTP">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="rgba(255,255,255,1)" style="margin:0 6px;">
			<path d="M1.94619 9.31543C1.42365 9.14125 1.41953 8.86022 1.95694 8.68108L21.0431 2.31901C21.5716 2.14285 21.8747 2.43866 21.7266 2.95694L16.2734 22.0432C16.1224 22.5716 15.8178 22.59 15.5945 22.0876L12 14L18 6.00005L10 12L1.94619 9.31543Z"></path>
		</svg>
		<?php esc_html_e( 'Send code via Whatsapp', 'awp' ); ?></button>
		</p>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide awp-input" style="display:none;">
			<label for="register_otp" class="awp-label"><?php esc_html_e( 'OTP', 'awp' ); ?>&nbsp;<span class="required">*</span></label>
			<input class="woocommerce-Input woocommerce-Input--text input-text" type="tel" name="register_otp" id="register_otp" />
		</p>
		<?php
	}

	/**
	 * Handles the WooCommerce customer creation action.
	 *
	 * @param int $customer_id The ID of the newly created customer.
	 */
	public function register_action( $customer_id ) {
		if ( isset( $_POST['register_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['register_nonce'] ) ), 'register_action' ) ) {
			if ( isset( $_POST['register_your_whatsapp'] ) ) {
				$whatsapp_number = sanitize_text_field( wp_unslash( $_POST['register_your_whatsapp'] ) );
				update_user_meta( $customer_id, 'billing_phone', $whatsapp_number );
			}
		}
	}

	/**
	 * Sends the OTP via WhatsApp.
	 */
	public function register_otp() {
		// Ensure the phone number is set.
		if ( ! isset( $_REQUEST['phone'] ) ) {
			wp_send_json_error(
				array(
					'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Valid WhatsApp number is required.', 'awp' ) . '</li>',
				)
			);
			exit();
		}

		$settings = get_option( 'wwo_settings' );
		$phone    = sanitize_text_field( wp_unslash( $_REQUEST['phone'] ) );
		$otp      = wp_rand( 123456, 999999 );

		// Remove any '+' from the phone number.
		$phone = str_replace( '+', '', $phone );

		// Retrieve instance ID and access token from settings.
		$instance_id  = isset( $settings['general']['instance_id'] ) ? sanitize_text_field( $settings['general']['instance_id'] ) : '';
		$access_token = isset( $settings['general']['access_token'] ) ? sanitize_text_field( $settings['general']['access_token'] ) : '';

		// Retrieve the message template from settings.
		$message_template = isset( $settings['register']['message'] ) ? sanitize_text_field( $settings['register']['message'] ) : 'Hi, {{otp}} is your confirmation code for Signup. Do not share this code with others.';
		$message          = str_replace( '{{otp}}', $otp, $message_template );

		if ( empty( $instance_id ) || empty( $access_token ) ) {
			wp_send_json_error(
				array(
					'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Instance ID or Access Token is missing. Please check your settings.', 'awp' ) . '</li>',
				)
			);
			exit();
		}

		if ( ! empty( $phone ) && strlen( $phone ) >= 9 ) {
			// Base64 encoding is used here for simple obfuscation of OTP to be stored in a cookie. This is not intended for security purposes.
			setcookie( 'wc_reg_awp', base64_encode( base64_encode( base64_encode( $otp ) ) ), time() + 300 );

			$api_url  = 'https://app.wawp.net/api/send';
			$api_data = array(
				'number'       => $phone,
				'type'         => 'text',
				'message'      => $message,
				'instance_id'  => $instance_id,
				'access_token' => $access_token,
			);

			$response = wp_remote_post(
				$api_url,
				array(
					'body'    => wp_json_encode( $api_data ),
					'headers' => array(
						'Content-Type' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error(
					array(
						'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Failed to send passkey. Please try again or contact administrator.', 'awp' ) . '</li>',
					)
				);
			} else {
				wp_send_json_success(
					array(
						'message' => '<li class="awp-notice success"><i class="bi bi-check-circle-fill"></i>' . esc_html__( 'Request sent! Check your WhatsApp.', 'awp' ) . '</li>',
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Your WhatsApp number may not be valid. Please recheck.', 'awp' ) . '</li>',
				)
			);
		}
		exit();
	}

	/**
	 * Handles the registration process.
	 */
	public function register() {
		$settings      = get_option( 'wwo_settings' );
		$error_message = '';

		// Validate required fields.
		if (
			isset( $_REQUEST['email'], $_REQUEST['phone'], $_REQUEST['code'], $_REQUEST['nonce'] )
			&& ! empty( $_REQUEST['email'] )
			&& ! empty( $_REQUEST['phone'] )
			&& ! empty( $_REQUEST['code'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'woocommerce-register' )
		) {
			$email    = sanitize_email( wp_unslash( $_REQUEST['email'] ) );
			$username = isset( $_REQUEST['username'] ) ? sanitize_user( wp_unslash( $_REQUEST['username'] ) ) : $email;
			$password = isset( $_REQUEST['pass'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pass'] ) ) : wp_generate_password();
			$phone    = sanitize_text_field( wp_unslash( $_REQUEST['phone'] ) );
			$code     = sanitize_text_field( wp_unslash( $_REQUEST['code'] ) );

			if ( isset( $_COOKIE['wc_reg_awp'] ) ) {
				$confirm_code = base64_decode( base64_decode( base64_decode( sanitize_text_field( wp_unslash( $_COOKIE['wc_reg_awp'] ) ) ) ) );
			} else {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'OTP is missing or invalid.', 'awp' ) . '</li>';
			}

			// Check if the phone number, username, or email is already registered.
			$check_whatsapp = new WP_User_Query(
				array(
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => 'billing_phone',
							'value'   => $phone,
							'compare' => 'LIKE',
						),
					),
				)
			);

			$check_username = new WP_User_Query(
				array(
					'search'         => $username,
					'search_columns' => array( 'user_login' ),
				)
			);

			$check_email = new WP_User_Query(
				array(
					'search'         => $email,
					'search_columns' => array( 'user_login', 'user_email' ),
				)
			);

			// Generate error messages based on validation.
			if ( ! empty( $check_email->get_results() ) ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'This email is already registered.', 'awp' ) . '</li>';
			}

			if ( ! empty( $check_whatsapp->get_results() ) ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'This WhatsApp number is already registered.', 'awp' ) . '</li>';
			}

			if ( ! empty( $check_username->get_results() ) ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'This username is already taken.', 'awp' ) . '</li>';
			}

			if ( strlen( $phone ) < 10 ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Your WhatsApp number may not be valid. Please recheck.', 'awp' ) . '</li>';
			}

			if ( $code !== $confirm_code ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'The OTP code is incorrect. Please try again.', 'awp' ) . '</li>';
			}

			// If no errors, proceed with user registration.
			if ( empty( $error_message ) && $code === $confirm_code && empty( $check_whatsapp->get_results() ) && empty( $check_email->get_results() ) ) {
				$userdata = array(
					'user_login'    => $username,
					'user_pass'     => $password,
					'user_nicename' => sanitize_text_field( $username ),
					'user_email'    => $email,
					'user_name'     => $username,
					'display_name'  => $username,
					'meta_input'    => array(
						'nickname'   => sanitize_text_field( $username ),
						'first_name' => $username,
					),
				);

				$user_id = wp_insert_user( $userdata );

				update_user_meta( $user_id, 'billing_phone', $phone );
				update_user_meta( $user_id, 'phone_verified', true ); // Set phone_verified to true and related.

				$new_customer_data = apply_filters(
					'woocommerce_new_customer_data',
					array(
						'user_login' => $phone,
						'user_pass'  => $password,
						'user_email' => $email,
						'role'       => 'customer',
					)
				);

				do_action( 'woocommerce_created_customer', $user_id, $new_customer_data, false );

				// Redirect URL.
				wp_clear_auth_cookie();
				wp_set_current_user( $user_id ); // Set the current wp user.
				wp_set_auth_cookie( $user_id, true );

				$redirect = ( ! empty( $settings['register']['url_redirection'] ) && isset( $_REQUEST['referer'] ) && '/checkout/' !== sanitize_text_field( wp_unslash( $_REQUEST['referer'] ) ) ) ? $settings['register']['url_redirection'] : 'reload';

				wp_send_json_success(
					array(
						'message' => '<li class="awp-notice success"><i class="bi bi-check-circle-fill"></i>' . esc_html__( 'Success!', 'awp' ) . '</li>',
						'action'  => $redirect,
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => $error_message,
					)
				);
			}
		} else {
			if ( empty( $_REQUEST['email'] ) ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Email is required.', 'awp' ) . '</li>';
			}
			if ( empty( $_REQUEST['phone'] ) ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Phone number is required.', 'awp' ) . '</li>';
			}
			if ( empty( $_REQUEST['code'] ) ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'OTP code is required.', 'awp' ) . '</li>';
			}
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'woocommerce-register' ) ) {
				$error_message .= '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Your session has expired. Please reload the page and try again.', 'awp' ) . '</li>';
			}
			wp_send_json_error(
				array(
					'message' => $error_message,
				)
			);
		}

		exit();
	}

	/**
	 * Enqueues necessary scripts.
	 */
	public function enqueue() {
		if ( ! is_user_logged_in() ) {
			wp_enqueue_script( 'awp-register', WWO_URL . 'assets/js/my-account-register.js', array( 'jquery' ), '1.0.0', true );
		}
	}
}
?>
