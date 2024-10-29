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
 * Class Login
 * Handles WhatsApp OTP login functionality.
 */
class Login extends WWO {

	/**
	 * Indicates whether the user is logged in.
	 *
	 * @var bool
	 */
	private $is_login = false;

	/**
	 * Singleton instance of the class.
	 *
	 * @var Login
	 */
	private static $instance;

	/**
	 * Returns an instance of this class.
	 *
	 * @return Login
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 * Initializes the plugin by setting up the actions and filters.
	 */
	public function __construct() {
		add_action( 'woocommerce_login_form', array( $this, 'login_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		add_action( 'wp_ajax_awp_send_login_otp', array( $this, 'login_otp' ) );
		add_action( 'wp_ajax_nopriv_awp_send_login_otp', array( $this, 'login_otp' ) );

		add_shortcode( 'wawp_account_login', array( $this, 'wawp_account_login_shortcode' ) );

		add_action( 'wp_ajax_awp_login', array( $this, 'login' ) );
		add_action( 'wp_ajax_nopriv_awp_login', array( $this, 'login' ) );
	}

	/**
	 * Redirects the user to the register page if not logged in.
	 */
	public function redirect_myaccount() {
		if ( false === $this->is_login ) {
			wp_safe_redirect( site_url( 'register' ) );
			exit;
		}
	}

	/**
	 * Displays the custom login form for WhatsApp OTP.
	 */
	public function login_form() {
		$settings = get_option( 'wwo_settings' );
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide awp" style="display:none;">
			<label for="tel" class="awp-label"><?php esc_html_e( 'Your Whatsapp number', 'awp' ); ?></label>
			<?php if ( isset( $settings['general'] ) && 'on' === $settings['general'] ) : ?>
				<!-- Additional content can be placed here -->
			<?php endif; ?>
			<input id="login_your_whatsapp" class="woocommerce-Input woocommerce-Input--text input-text" type="tel" name="login_your_whatsapp" />
			<button type="button" class="send_login_otp sendotpcss woocommerce-button button woocommerce-form-login__awp <?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="Send OTP">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="rgba(255,255,255,1)" style="margin:0 6px;">
					<path d="M1.94619 9.31543C1.42365 9.14125 1.41953 8.86022 1.95694 8.68108L21.0431 2.31901C21.5716 2.14285 21.8747 2.43866 21.7266 2.95694L16.2734 22.0432C16.1224 22.5716 15.8178 22.59 15.5945 22.0876L12 14L18 6.00005L10 12L1.94619 9.31543Z"></path>
				</svg>
				<?php esc_html_e( 'Send code via Whatsapp', 'awp' ); ?>
			</button>
		</p>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide awp-input" style="display:none;">
			<label for="tel" class="awp-label"><?php esc_html_e( 'Enter the code you received', 'awp' ); ?></label>
			<input class="woocommerce-Input woocommerce-Input--text input-text" type="tel" name="login_otp" id="login_otp" />
		</p>
		<p class="form-row awp-login-otp-submit">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="awp_rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'awp' ); ?></span>
			</label>
			<?php wp_nonce_field( 'awp-login', 'awp-login-nonce' ); ?>
			<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Login', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Login', 'awp' ); ?>
			</button>
			<button data-button="login_w_wa" type="button" class="awp_login_btn woocommerce-button button whatsappcss woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Login via Whatsapp', 'woocommerce' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);transform: ;msFilter:;margin:0 6px;"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.403 5.633A8.919 8.919 0 0 0 12.053 3c-4.948 0-8.976 4.027-8.978 8.977 0 1.582.413 3.126 1.198 4.488L3 21.116l4.759-1.249a8.981 8.981 0 0 0 4.29 1.093h.004c4.947 0 8.975-4.027 8.977-8.977a8.926 8.926 0 0 0-2.627-6.35m-6.35 13.812h-.003a7.446 7.446 0 0 1-3.798-1.041l-.272-.162-2.824.741.753-2.753-.177-.282a7.448 7.448 0 0 1-1.141-3.971c.002-4.114 3.349-7.461 7.465-7.461a7.413 7.413 0 0 1 5.275 2.188 7.42 7.42 0 0 1 2.183 5.279c-.002 4.114-3.349 7.462-7.461 7.462m4.093-5.589c-.225-.113-1.327-.655-1.533-.73-.205-.075-.354-.112-.504.112s-.58.729-.711.879-.262.168-.486.056-.947-.349-1.804-1.113c-.667-.595-1.117-1.329-1.248-1.554s-.014-.346.099-.458c.101-.1.224-.262.336-.393.112-.131.149-.224.224-.374s.038-.281-.019-.393c-.056-.113-.505-1.217-.692-1.666-.181-.435-.366-.377-.504-.383a9.65 9.65 0 0 0-.429-.008.826.826 0 0 0-.599.28c-.206.225-.785.767-.785 1.871s.804 2.171.916 2.321c.112.15 1.582 2.415 3.832 3.387.536.231.954.369 1.279.473.537.171 1.026.146 1.413.089.431-.064 1.327-.542 1.514-1.066.187-.524.187-.973.131-1.067-.056-.094-.207-.151-.43-.263"></path></svg> <?php esc_html_e( 'Login via Whatsapp', 'awp' ); ?>
			</button>
			<button data-button="login_w_email" type="button" class="awp_login_btn woocommerce-button emailcss button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Login via Email & Password', 'woocommerce' ); ?>" style="display:none;">
				<?php esc_html_e( 'Login via Email', 'awp' ); ?>
			</button>
		</p>
		<?php
	}

	/**
	 * Handles the OTP login process.
	 */
	public function login_otp() {
		if ( ! isset( $_REQUEST['phone'] ) ) {
			wp_send_json_error(
				array(
					'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Valid WhatsApp number is required.', 'awp' ) . '</li>',
				)
			);
			exit;
		}

		$settings = get_option( 'wwo_settings' );
		$phone    = sanitize_text_field( wp_unslash( $_REQUEST['phone'] ) );
		$otp      = wp_rand( 123456, 999999 );

		// Remove any '+' from the phone number.
		$phone = str_replace( '+', '', $phone );

		$args       = array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'billing_phone',
					'value'   => $phone,
					'compare' => 'LIKE',
				),
			),
			'order'      => 'DESC',
		);
		$user_query = new WP_User_Query( $args );
		$user_exist = $user_query->get_results();

		if ( count( $user_exist ) > 0 && strlen( $phone ) > 10 ) {
			$user      = reset( $user_exist );
			$user_id   = $user->ID;
			$login     = $user->user_login;
			$your_name = $user->display_name;

			setcookie( 'wc_log_awp', base64_encode( base64_encode( base64_encode( $otp ) ) ), time() + 300, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

			$instance_id      = $settings['general']['instance_id'] ?? '';
			$access_token     = $settings['general']['access_token'] ?? '';
			$message_template = $settings['login']['message'] ?? 'Hi {{name}}, {{otp}} is your Login Generated OTP code. Do not share this code with others.';
			$message          = str_replace( array( '{{name}}', '{{otp}}' ), array( $your_name, $otp ), $message_template );

			if ( empty( $instance_id ) || empty( $access_token ) ) {
				wp_send_json_error(
					array(
						'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Instance ID or Access Token is missing. Please check your settings.', 'awp' ) . '</li>',
					)
				);
				exit;
			}

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
						'user_id' => $user_id,
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'This number is not registered on this site. Please try again with a valid number or register.', 'awp' ) . '</li>',
				)
			);
		}

		exit;
	}

	/**
	 * Handles the login process after OTP verification.
	 */
public function login() {
    if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'awp-login' ) ) {
        wp_send_json_error(
            array(
                'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Security check failed.', 'awp' ) . '</li>',
            )
        );
        exit;
    }

    $settings     = get_option( 'wwo_settings' );
    $code         = sanitize_text_field( wp_unslash( $_REQUEST['code'] ) );
    $confirm_code = base64_decode( base64_decode( base64_decode( sanitize_text_field( wp_unslash( $_COOKIE['wc_log_awp'] ) ) ) ) );
    $user_id      = sanitize_text_field( wp_unslash( $_REQUEST['user'] ) );

    if ( $code === $confirm_code ) {
        wp_clear_auth_cookie();
        wp_set_current_user( $user_id );
        $log_this_user = wp_set_auth_cookie( $user_id, true );

        if ( ! is_wp_error( $log_this_user ) ) {
            // Trigger the wp_login action with the user's login name.
            $user = get_userdata( $user_id );
            do_action( 'wp_login', $user->user_login, $user );

            $redirect = ( ! empty( $settings['login']['url_redirection'] ) && $_REQUEST['referer'] !== '/checkout/' ) ? $settings['login']['url_redirection'] : 'reload';
            wp_send_json_success(
                array(
                    'message' => '<li class="awp-notice success"><i class="bi bi-check-circle-fill"></i>' . esc_html__( 'Success!', 'awp' ) . '</li>',
                    'action'  => $redirect,
                )
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Something wrong with your login', 'awp' ) . '</li>',
                )
            );
        }
    } else {
        wp_send_json_error(
            array(
                'message' => '<li class="awp-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>' . esc_html__( 'Mismatch passkey. Try again.', 'awp' ) . '</li>',
            )
        );
    }

    exit;
}


	/**
	 * Shortcode to display the custom login form or a welcome message if logged in.
	 */
	public function wawp_account_login_shortcode() {
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
			// Load the custom login form template.
			$template_path = plugin_dir_path( __FILE__ ) . 'templates/form-login-only.php';
			if ( file_exists( $template_path ) ) {
				include_once $template_path;
			} else {
				echo esc_html__( 'Template not found.', 'awp' );
			}
		}

		return ob_get_clean();
	}

	/**
	 * Enqueues the necessary scripts and styles.
	 */
	public function enqueue() {
		// Enqueue RTL stylesheet if the current locale is RTL.
		if ( is_rtl() ) {
			wp_enqueue_style( 'custom-my-account-rtl', WWO_URL . 'assets/css/my-account-rtl.css', array(), '1.0.0' );
		}

		wp_enqueue_style( 'custom-my-account', WWO_URL . 'assets/css/my-account.css', array(), '1.0.0' );
		wp_enqueue_script( 'custom-my-account', WWO_URL . 'assets/js/my-account.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'awp-login', WWO_URL . 'assets/js/my-account-login.js', array( 'jquery' ), '1.0.0', true );

		// Localize the script with new data.
		$script_data_array = array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'admin_nonce' => wp_create_nonce( 'wwo_nonce' ),
		);
		wp_localize_script( 'custom-my-account', 'wwo', $script_data_array );
	}
}
?>
