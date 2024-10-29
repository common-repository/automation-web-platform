<?php
/**
 * Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform.
 */

ob_start();
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WWO.
 */
class WWO {
	/**
	 * Data variable to hold the instance of the class.
	 *
	 * @var WWO
	 */
	private static $instance;

	/**
	 * Instance ID.
	 *
	 * @var string
	 */
	private $instance_id;

	/**
	 * Access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * Phone number.
	 *
	 * @var string
	 */
	private $phone;

	/**
	 * Message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Get the single instance of the class.
	 *
	 * @return WWO
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class constructor. Initializes settings and includes necessary files based on settings.
	 */
	public function __construct() {
		$settings     = get_option( 'wwo_settings' );
		$access_token = isset( $settings['general']['access_token'] ) ? $settings['general']['access_token'] : null;
		$instance_id  = isset( $settings['general']['instance_id'] ) ? $settings['general']['instance_id'] : null;
		$this->set_access_token( $access_token );
		$this->set_instance_id( $instance_id );

		if ( isset( $settings['general']['active_login'] ) && 'on' === $settings['general']['active_login'] ) {
			include_once 'class-login.php';
			new Login();
		}
		if ( isset( $settings['general']['active_register'] ) && 'on' === $settings['general']['active_register'] ) {
			include_once 'class-register.php';
			new Register();
		}

		add_action( 'admin_menu', array( $this, 'settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'woocommerce_edit_account_form', array( $this, 'add_billing_phone_to_edit_account_form' ) );
		add_action( 'woocommerce_save_account_details', array( $this, 'save_billing_phone_on_edit_account' ) );
		add_action( 'admin_bar_menu', array( $this, 'status_on_admin_bar' ), 100 );
		add_filter( 'manage_users_custom_column', array( $this, 'display_billing_phone_content' ), 10, 3 );
	}

	/**
	 * Add settings page to the admin menu.
	 */
	public function settings() {
		$hook = add_menu_page(
			esc_html__( 'Wawp OTP', 'awp' ),
			esc_html__( 'Wawp OTP', 'awp' ),
			'manage_options',
			'awp-otp',
			array( $this, 'setting_page' ),
			esc_url( WWO_URL . 'assets/img/menu.png' ),
			101
		);
		remove_menu_page( 'awp-otp' );
	}

	/**
	 * Display the settings page content.
	 */
	public function setting_page() {
		include_once 'admin/wc-setting-page.php';
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function enqueue() {
		global $pagenow;
		if ( is_admin() ) {
			add_action( 'admin_head', array( $this, 'wawp_consent_banner' ) );
			add_action( 'admin_footer', array( $this, 'wawp_add_banner_script' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'awp-otp' === $_GET['page'] ) :
			wp_enqueue_style( 'bootstrap-css', plugins_url( '/assets/css/resources/bootstrap.min.css', __FILE__ ), array(), '5.2.3' );
			wp_enqueue_style( 'bootstrap-icons-css', plugins_url( '/assets/css/resources/bootstrap-icons.css', __FILE__ ), array(), '1.8.1' );
			wp_enqueue_style( 'bootstrap-table-css', plugins_url( '/assets/css/resources/bootstrap-table.min.css', __FILE__ ), array(), '1.21.1' );
			wp_enqueue_style( 'sweetalert2-css', plugins_url( '/assets/css/resources/sweetalert2.min.css', __FILE__ ), array(), '11.4.35' );
			wp_enqueue_style( 'jquery-ui-css', plugins_url( '/assets/css/resources/jquery-ui.css', __FILE__ ), array(), '1.13.2' );
			wp_enqueue_style( 'lineicons-css', plugins_url( '/assets/css/resources/lineicons.css', __FILE__ ), array(), '3.0' );
			wp_enqueue_style( 'select2', plugins_url( '/assets/css/resources/select2.min.css', __FILE__ ), array(), '4.1.0' );
			wp_enqueue_style( 'admin', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), '4.1.0' );
			wp_enqueue_script( 'jquery-js', plugins_url( 'assets/js/resources/jquery.min.js', __FILE__ ), array(), '3.6.0', true );
			wp_enqueue_script( 'jquery-ui-js', plugins_url( 'assets/js/resources/jquery-ui.js', __FILE__ ), array(), '1.13.2', true );
			wp_enqueue_script( 'bootstrap-js', plugins_url( 'assets/js/resources/bootstrap.bundle.min.js', __FILE__ ), array(), '5.2.3', true );
			wp_enqueue_script( 'bootstrap-table-js', plugins_url( 'assets/js/resources/bootstrap-table.min.js', __FILE__ ), array(), true, true, '1.21.1' );
			wp_enqueue_script( 'sweetalert2-js', plugins_url( 'assets/js/resources/sweetalert2.min.js', __FILE__ ), array(), true, true, '11.4.35' );
			wp_enqueue_script( 'select2', plugins_url( 'assets/js/resources/select2.js', __FILE__ ), array(), true, true, '4.1.0' );

			wp_enqueue_script( 'admin-script', esc_url( WWO_URL . '/assets/js/admin.js' ), array( 'jquery' ), true, true );
			if ( is_rtl() ) {
				// Enqueue RTL versions of your CSS files here.
				wp_enqueue_style( 'otp-rtl-css', plugins_url( '/assets/css/otp-rtl.css', __FILE__ ), array(), '5.2.3' );
				// You can enqueue more RTL CSS files as needed.
			}
			// Localize the script with new data.
			$script_data_array = array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'admin_nonce' => wp_create_nonce( 'wwo_nonce' ),
			);
			wp_localize_script( 'ajax-script', 'wwo', $script_data_array );
			// Enqueued script with localized data.
			wp_enqueue_script( 'ajax-script' );
		endif;
	}

	/**
	 * Set instance ID method.
	 *
	 * @param string $data Instance ID.
	 */
	public function set_instance_id( $data ) {
		$this->instance_id = $data;
	}

	/**
	 * Set access token method.
	 *
	 * @param string $data Access token.
	 */
	public function set_access_token( $data ) {
		$this->access_token = $data;
	}

	/**
	 * Display the consent banner on specific admin pages.
	 */
    public function wawp_consent_banner() {
	$current_screen = get_current_screen();
	$allowed_pages  = array(
		'toplevel_page_awp-settings',
		'toplevel_page_awp-countrycode',
		'toplevel_page_awp',
		'toplevel_page_awp-otp',
		'toplevel_page_awp-checkout-otp',
		'awp_page_awp-message-log',
		'toplevel_page_awp-system-status-info',
	);

	if ( ! in_array( $current_screen->id, $allowed_pages, true ) && ! ( isset( $_GET['page'] ) && 'awp-message-log' === $_GET['page'] ) ) {
		return;
	}

	// Output HTML and JavaScript
	echo '<div id="wawp-consent-banner">' .
		'<div class="wawp-header">' .
			'<div class="wawp-topbar">' .
				'<div class="wawp-logo">' .
					'<a href="https://wawp.net" title="Wawp" target="_blank"><img style="height: 54px;" src="' . esc_url( plugins_url( 'assets/img/wawp-logo.png', __FILE__ ) ) . '" alt="Wawp logo"></a>' .
					'<h1 class="title-text">' . esc_html( get_admin_page_title() ) . '</h1>' .
				'</div>' .
				'<div class="wawp-links">' .
					'<div class="links-box">' .
						'<a href="https://wawp.net/whatsapp-text-formatter/" target="_blank" class="hint-btn">' . esc_html__( 'Text formatting', 'awp' ) . '</a>' .
						'<a href="https://www.facebook.com/groups/894016848870156" target="_blank" class="hint-btn">' . esc_html__( 'Support', 'awp' ) . '</a>' .
						'<a href="https://wawp.net/pricing/" target="_blank" class="hint-btn pro"><img style="margin-right: 6px;" src="' . esc_url( plugins_url( 'assets/img/star.png', __FILE__ ) ) . '" alt="Star icon">' . esc_html__( ' Wawp Pro ', 'awp' ) . '</a>' .
					'</div>' .
				'</div>' .
			'</div>' .
		'</div>' .
	'</div>';

	// JavaScript to handle .wawp-banner visibility and cookies
	echo '<script>
	document.addEventListener("DOMContentLoaded", function() {
	    const banner = document.querySelector(".wawp-banner");
	    const closeBannerButton = document.getElementById("close-banner");
	    const closedTime = getCookie("wawp_banner_closed");
	    const showBannerTime = getCookie("wawp_banner_show_time");

	    const now = new Date().getTime();

	    if (!closedTime && (!showBannerTime || now >= showBannerTime)) {
	        banner.style.display = "block";
	    }

	    closeBannerButton.addEventListener("click", function() {
	        banner.style.display = "none";

	        const expireDate = new Date();
	        expireDate.setDate(expireDate.getDate() + 7);
	        document.cookie = `wawp_banner_closed=true; expires=${expireDate.toUTCString()}; path=/`;

	        const nextShowTime = new Date();
	        nextShowTime.setHours(nextShowTime.getHours() + 1);
	        document.cookie = `wawp_banner_show_time=${nextShowTime.getTime()}; path=/`;
	    });

	    function getCookie(name) {
	        const value = `; ${document.cookie}`;
	        const parts = value.split(`; ${name}=`);
	        if (parts.length === 2) return parts.pop().split(";").shift();
	    }
	});
	</script>';
}

	/**
	 * Add script to move the consent banner to the top of the admin content.
	 */
	public function wawp_add_banner_script() {
		?>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				var banner = document.getElementById('wawp-consent-banner');
				if (banner) {
					var wpcontent = document.getElementById('wpcontent');
					if (wpcontent) {
						wpcontent.insertBefore(banner, wpcontent.firstChild);
					}
				}
			});
		</script>
		<?php
	}
	
	// Add custom status to the admin bar
	public function status_on_admin_bar( $wp_admin_bar ) {
		$args = array(
			'id'    => 'awp-admin-link',
			'title' => 'Wawp',
			'href'  => admin_url() . 'admin.php?page=awp',
			'meta'  => array(
				'class' => 'awp-admin-link',
			),
		);
		$wp_admin_bar->add_node( $args );
		$args = array(
			'id'     => 'awp-sub-link-2',
			'title'  => 'Wawp Notification',
			'href'   => admin_url() . 'admin.php?page=awp',
			'parent' => 'awp-admin-link',
			'meta'   => array(
				'class' => 'awp-admin-link',
			),
		);
		$wp_admin_bar->add_node( $args );
		$args = array(
			'id'     => 'awp-sub-link-3',
			'title'  => 'Wawp Otp',
			'href'   => admin_url() . 'admin.php?page=awp-otp',
			'parent' => 'awp-admin-link',
			'meta'   => array(
				'class' => 'awp-admin-link',
			),
		);
		$wp_admin_bar->add_node( $args );
		$args = array(
			'id'     => 'awp-sub-link-4',
			'title'  => 'Visit Wawp Dashboard',
			'href'   => 'https://app.wawp.net/',
			'parent' => 'awp-admin-link',
			'meta'   => array(
				'class'  => 'awp-sub-link',
				'title'  => 'Go to Wawp.net',
				'target' => '_blank',
			),
		);
		$wp_admin_bar->add_node( $args );
	}
	
	// Display billing phone number in the custom column
	public function display_billing_phone_content( $content, $column_name, $user_id ) {
		if ( 'billing_phone' === $column_name ) {
			$customer      = new WC_Customer( $user_id );
			$billing_phone = $customer->get_billing_phone();

			if ( $billing_phone ) {
				$content = esc_html( $billing_phone );
			} else {
				$content = '-';
			}
		}
		return $content;
	}
	
	// Add billing phone field to edit account form
	public function add_billing_phone_to_edit_account_form() {
		$user_id       = get_current_user_id();
		$billing_phone = get_user_meta( $user_id, 'billing_phone', true );
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="billing_phone"><?php esc_html_e( 'Phone', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="billing_phone" value="<?php echo esc_attr( $billing_phone ); ?>" />
		</p>
		<?php
	}
	
	// Save billing phone number on edit account
	public function save_billing_phone_on_edit_account( $user_id ) {
		if ( isset( $_POST['billing_phone'] ) ) {
			update_user_meta( $user_id, 'billing_phone', sanitize_text_field( $_POST['billing_phone'] ) );
		}
	}
}
?>
