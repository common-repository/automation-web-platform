<?php
/**
 * Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform.
 */

// Define a constant for the function name
define( 'awp_FUNCTION', 'awp_connection' );

class awp_Main {

	protected static $instance = null;

	// Singleton pattern to ensure only one instance
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public $ui;
	public $log;

	// Constructor to initialize the plugin
	public function __construct() {
		$this->ui  = new awp_UI();
		$this->log = new awp_logger();

		// Adding various hooks and actions
		add_action( 'admin_init', array( $this, 'awp_register_settings' ) );
		add_action( 'admin_menu', array( $this, 'awp_admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'awp_admin_notices' ) );
		add_action( 'woocommerce_created_customer', array( $this, 'awp_send_registration_notification' ), 10, 1 );
		add_action( 'wp_login', [ $this, 'awp_send_login_notification' ], 10, 2 );
		add_action( 'woocommerce_order_status_pending', array( $this, 'awp_wa_process_states_pending' ), 10 );
		add_action( 'woocommerce_order_status_failed', array( $this, 'awp_wa_process_states_failed' ), 10 );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'awp_wa_process_states_onhold' ), 10 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'awp_wa_process_states_completed' ), 10 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'awp_wa_process_states_processing' ), 10 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'awp_wa_process_states_refunded' ), 10 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'awp_wa_process_states_cancelled' ), 10 );
		add_action( 'woocommerce_new_customer_note', array( $this, 'awp_wa_process_note' ), 10 );
		add_action( 'admin_init', array( $this, 'awp_custom_order_status' ) );
	    add_action('wp_ajax_log_resend_message', array($this, 'awp_log_resend_message'));
		add_action( 'followup_cron_hook', array( $this, 'followup_order' ) );
		add_action( 'followup_cron_hook_2', array( $this, 'followup_order_2' ) );
		add_action( 'followup_cron_hook_3', array( $this, 'followup_order_3' ) );
		add_action( 'followup_cron_hook_4', array( $this, 'followup_order_4' ) );
		add_action( 'aftersales_cron_hook', array( $this, 'aftersales_order' ) );
		add_action( 'aftersales_cron_hook_2', array( $this, 'aftersales_order_2' ) );
		add_action( 'aftersales_cron_hook_3', array( $this, 'aftersales_order_3' ) );
		add_action( 'aftersales_cron_hook_4', array( $this, 'aftersales_order_4' ) );
		add_action( 'abandoned_cron_hook', array( $this, 'abandoned_order' ) );
		add_filter( 'cron_schedules', array( $this, 'followup_cron_schedule' ) );
		
		
		// Schedule cron events if not already scheduled
		$this->schedule_cron_events();
	}

	// Check if a plugin is active
	public function is_plugin_active( $plugin ) {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
	}

	// Register settings
	public function awp_register_settings() {
		register_setting( 'awp_storage_notifications', 'awp_notifications' );
		register_setting( 'awp_storage_instances', 'awp_instances' );
	}
	
	// Add menu and submenu pages
	public function awp_admin_menu() {
		$config = get_option( 'awp_notifications' );
		add_submenu_page(
			'awp',
			__( 'Sender Settings', 'awp' ),
			__( 'Sender Settings', 'awp' ),
			'manage_options',
			'awp-settings'
		);
			add_submenu_page(
				'awp',
				__( 'Country code settings', 'awp' ),
				__( 'Country Code', 'awp' ),
				'manage_options',
				'awp-countrycode'
			);

		$my_page_1 = add_menu_page(
			__( 'Notifications', 'awp' ),
			__( 'Wawp', 'awp' ),
			'manage_options',
			'awp',
			array(
				$this->ui,
				'admin_page',
			),
			plugin_dir_url( __FILE__ ) . 'assets/img/menu.png'
		);
		add_action( 'load-' . $my_page_1, array( $this, 'awp_load_admin_js' ) );

		add_submenu_page(
			'awp',
			__( 'WhatsApp Notifications', 'awp' ),
			__( 'Wa Notifications', 'awp' ),
			'manage_options',
			'awp',
			array( $this->ui, 'admin_page' )
		);

		add_submenu_page(
			'awp',
			__( 'Login & Signup OTP', 'awp' ),
			__( 'OTP Logins', 'awp' ),
			'administrator',
			'awp-otp'
		);

		add_submenu_page(
			'awp',
			__( 'Checkout OTP Verification', 'awp' ),
			__( 'Checkout OTP', 'awp' ),
			'manage_options',
			'awp-checkout-otp'
		);

		$my_page_2 = add_submenu_page(
			'awp',
			__( 'Notification Logs', 'awp' ),
			__( 'Notification Logs', 'awp' ),
			'manage_options',
			'awp-message-log',
			array( $this->ui, 'logs_page' )
		);

		add_action( 'load-' . $my_page_2, array( $this, 'awp_load_admin_js' ) );

		add_submenu_page(
			'awp',
			__( 'System Status', 'awp' ),
			__( 'System Status', 'awp' ),
			'manage_options',
			'awp-system-status-info'
		);

		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'shop_order' ) {
			if ( isset( $_GET['id'] ) ) {
				// $post_id = sanitize_text_field($_GET['id']);
				$post_id = isset( $_GET['id'] ) ? absint( sanitize_text_field( $_GET['id'] ) ) : 0;
				$result  = $this->awp_wa_process_states( $post_id );
				?>
					<div class="notice notice-success is-dismissible">
	<p><?php printf( __( 'Resend Message %s', 'awp-send' ), esc_html( $result ) ); ?></p>

</div>

				<?php
			}
		}
	}

	// Load admin JS
	public function awp_load_admin_js() {
		add_action( 'admin_enqueue_scripts', array( $this, 'awp_admin_assets' ) );
	}
	
	// Enqueue admin assets
	public function awp_admin_assets() {
		global $pagenow;
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && ( 'awp' === $_GET['page'] || 'awp-message-log' === $_GET['page'] ) ) {
			wp_enqueue_style( 'awp-admin-style', plugins_url( 'assets/css/awp-admin-style.css', __FILE__ ), array(), '1.1.4' );
			wp_enqueue_style( 'awp-admin-emojicss', plugins_url( 'assets/css/resources/emojionearea.min.css', __FILE__ ) );
			wp_enqueue_style( 'awp-admin-telcss', plugins_url( 'assets/css/intlTelInput.css', __FILE__ ) );
			wp_enqueue_script( 'awp-admin-teljs', plugins_url( 'assets/js/resources/intlTelInput.js', __FILE__ ), array( 'jquery' ), '23.0.10', true );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script( 'awp-jquery-modal', plugins_url( 'assets/js/resources/jquery.modal.min.js', __FILE__ ) );
			wp_enqueue_script( 'awp-admin-utils', plugins_url( 'assets/js/resources/utils.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
			wp_enqueue_script( 'awp-plugin-textcomplete', plugins_url( 'assets/js/resources/jquery.textcomplete.js', __FILE__ ), array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'awp-admin-js', plugins_url( 'assets/js/awp-admin-js.js', __FILE__ ), array(), true, true, '1.1.4' );
			wp_enqueue_script( 'awp-cards-js', plugins_url( 'assets/js/awp-cards.js', __FILE__ ), array(), true, true, '1.1.4' );
			wp_enqueue_script( 'awp-admin-emojijs', plugins_url( 'assets/js/resources/emojionearea.min.js', __FILE__ ), array( 'jquery' ), '3.4.0', true );

			// Check if WordPress is in RTL mode and load RTL CSS if necessary
			if ( is_rtl() ) {
				wp_enqueue_style( 'awp-admin-rtl-style', plugins_url( 'assets/css/awp-admin-rtl-style.css', __FILE__ ), array(), '1.1.4' );
			}
			wp_enqueue_media();
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
		}
	}
	
	// Display admin notices
	public function awp_admin_notices() {
		$screen = get_current_screen();
		if ( isset( $_GET['settings-updated'] ) && $screen->id == 'toplevel_page_awp' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'All changes have been saved!', 'awp-send' ); ?></p>
			</div>
			<?php
		}
		if ( $screen->id == 'awp-send-new_page_awp-message-log' ) {
			if ( isset( $_GET['clear'] ) ) {
				$this->log->clear( 'awp-send', 'awp_logger' );
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Message logs have been cleared!', 'awp-send' ); ?></p>
				</div>
				<?php
			}
			if ( isset( $_POST['awp_resend_wa'] ) ) {
				$resend_phone   = isset( $_POST['awp_resend_phone'] ) ? sanitize_text_field( $_POST['awp_resend_phone'] ) : '';
				$resend_message = isset( $_POST['awp_resend_message'] ) ? sanitize_textarea_field( $_POST['awp_resend_message'] ) : '';
				$resend_image   = isset( $_POST['awp_resend_image'] ) ? esc_url_raw( $_POST['awp_resend_image'] ) : '';
				if ( ! $resend_phone || ! $resend_message ) {
					// handle the error here
				} else {
					$result = $this->awp_wa_send_msg( '', $resend_phone, $resend_message, $resend_image, '' );
				}
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( sprintf( __( 'Resend Message %s', 'awp-send' ), $result ) ); ?></p>
				</div>
				<?php
			}
		}
		if ( isset( $_POST['awp_send_test'] ) ) {
			if ( ! empty( $_POST['awp_test_number'] ) ) {
				$test_number  = isset( $_POST['awp_test_number'] ) ? sanitize_text_field( $_POST['awp_test_number'] ) : '';
				$test_message = isset( $_POST['awp_test_message'] ) ? sanitize_textarea_field( $_POST['awp_test_message'] ) : '';
				$test_image   = isset( $_POST['awp_test_image'] ) ? esc_url_raw( $_POST['awp_test_image'] ) : '';
				if ( ! $test_number || ! $test_message ) {
					// handle the error here
				} else {
					$result = $this->awp_wa_send_msg( '', $test_number, $test_message, $test_image, '' );
				}
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( sprintf( __( 'Send Message %s', 'awp-send' ), $result ) ); ?></p>
				</div>
				<?php
			}
		}
	}

    public function awp_send_registration_notification( $user_id ) {
    // Start debugging
    error_log( 'AWP: Registration function triggered for user ID ' . $user_id );

    // Retrieve plugin settings
    $config = get_option( 'awp_notifications' );

    // Initialize result variable
    $result = null;

    // Get the user object
    $user = get_userdata( $user_id );

    // Attempt to get the user's phone number from WooCommerce billing data
    $phone = get_user_meta( $user_id, 'billing_phone', true );
    error_log( 'AWP: Retrieved phone number: ' . $phone );

    if ( empty( $phone ) ) {
        error_log( 'AWP Registration Notification: No phone number found for user ' . $user->user_login );
        return;
    }

    // Get the user's locale
    $user_locale = get_user_locale($user_id);

    // Determine if the user prefers Arabic
    $is_arabic = ($user_locale == 'ar');

    // Check and send the appropriate registration notification based on the user's locale
    if ($is_arabic) {
        // Check and send Arabic registration notification
        if (!empty($config['registration_message_arabic_active']) && !empty($config['registration_message_arabic'])) {
            $msg_arabic = $this->awp_wa_process_variables( $config['registration_message_arabic'], null, [], '', $user );
            $img_arabic = !empty( $config['registration_message_img_arabic'] ) ? $config['registration_message_img_arabic'] : '';

            error_log( 'AWP: Registration message (Arabic): ' . $msg_arabic );

            if ( !empty($msg_arabic) ) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - registration', '' );
                error_log( 'AWP: Message sent result (Arabic): ' . print_r($result, true) );
            }
        }
    } else {
        // Check and send English registration notification
        if (!empty($config['registration_message_active']) && !empty($config['registration_message'])) {
            $msg = $this->awp_wa_process_variables( $config['registration_message'], null, [], '', $user );
            $img = !empty( $config['registration_message_img'] ) ? $config['registration_message_img'] : '';

            error_log( 'AWP: Registration message (English): ' . $msg );

            if ( !empty($msg) ) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En) - registration', '' );
                error_log( 'AWP: Message sent result (English): ' . print_r($result, true) );
            }
        }
    }

    // Check and send Admin registration notification
    if (!empty($config['admin_registration_message_active']) && !empty($config['admin_registration_message'])) {
        $msg_admin = $this->awp_wa_process_variables($config['admin_registration_message'], null, [], '', $user);
        $img_admin = $config['admin_registration_message_img'];
        $phone_admin = preg_replace( '/[^0-9]/', '', $config['admin_number'] );

        error_log( 'AWP: Registration message (Admin): ' . $msg_admin );

        if ( !empty($msg_admin) ) {
            $this->awp_wa_send_msg( $config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - registration', '' );
            error_log( 'AWP: Message sent result (Admin): ' . print_r($result, true) );
        }
    }

    // Return the result of the last sent message (if any)
    return isset($result) ? $result : null;
}

	public function awp_send_login_notification($user_login, $user) {
    // Retrieve plugin settings
    $config = get_option('awp_notifications');

    // Initialize result variable
    $result = null;

    // Get the user's phone number
    $phone = get_user_meta($user->ID, 'billing_phone', true);

    if (empty($phone)) {
        error_log('AWP Login Notification: No phone number found for user ' . $user_login);
        return;
    }

    // Get the user's locale
    $user_locale = get_user_locale($user->ID);

    // Determine if the user prefers Arabic
    $is_arabic = ($user_locale == 'ar');

    // Check and send the appropriate login notification
    if ($is_arabic) {
        // Check and send Arabic login notification
        if (!empty($config['login_message_arabic_active']) && !empty($config['login_message_arabic'])) {
            $msg_arabic = $this->awp_wa_process_variables($config['login_message_arabic'], null, [], '', $user);
            $img_arabic = !empty($config['login_message_img_arabic']) ? $config['login_message_img_arabic'] : '';

            if (!empty($msg_arabic)) {
                $result = $this->awp_wa_send_msg($config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - login', '' );
            }
        }
    } else {
        // Check and send English login notification
        if (!empty($config['login_message_active']) && !empty($config['login_message'])) {
            $msg = $this->awp_wa_process_variables($config['login_message'], null, [], '', $user);
            $img = !empty($config['login_message_img']) ? $config['login_message_img'] : '';

            if (!empty($msg)) {
                $result = $this->awp_wa_send_msg($config, $phone, $msg, $img, '', 'Customer (En) - login', '' );
            }
        }
    }

    // Check and send Admin login notification
    if (!empty($config['admin_login_message_active']) && !empty($config['admin_login_message'])) {
        $msg_admin = $this->awp_wa_process_variables($config['admin_login_message'], null, [], '', $user);
        $img_admin = !empty($config['admin_login_message_img']) ? $config['admin_login_message_img'] : '';
        $phone_admin = preg_replace('/[^0-9]/', '', $config['admin_number']);

        if (!empty($msg_admin)) {
            $this->awp_wa_send_msg($config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - login', '' );
        }
    }

    // Return the result of the last sent message (if any)
    return isset($result) ? $result : null;
}

	public function awp_wa_process_states_onhold( $order ) {
    global $woocommerce, $custom_status_list_temp;
    $order       = new WC_Order( $order );
    $status      = $order->get_status();
    $order_id    = $order->get_id(); // Get the Order ID
    $status_list = array(
        'on-hold' => __( 'Receive', 'awp-send' ),
    );

    foreach ( $status_list as $status_lists => $translations ) {
        if ( $status == $status_lists ) {
            $status = $translations;
        }
    }

    $config = get_option( 'awp_notifications' );
    $phone  = $order->get_billing_phone();
    // Get the user's locale
    $user_locale = get_user_locale( get_current_user_id() );

    $result = null;

    // Use different messages based on the user's locale
    if ( $status == __( 'Receive', 'awp-send' ) ) {
        if ( $user_locale === 'ar' && !empty($config['order_onhold_arabic_active']) && !empty($config['order_onhold_arabic']) ) {
            $msg_arabic = $this->awp_wa_process_variables( $config['order_onhold_arabic'], $order, '' );
            $img_arabic = $config['order_onhold_img_arabic'];
            if (!empty($msg_arabic)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order "On-Hold"', $order_id );
            }
        } elseif (!empty($config['order_onhold_active']) && !empty($config['order_onhold']) && $user_locale !== 'ar' ) {
            $msg = $this->awp_wa_process_variables( $config['order_onhold'], $order, '' );
            $img = $config['order_onhold_img'];
            if (!empty($msg)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En) - order "On-Hold"', $order_id );
            }
        }
    }

    /* Admin Receive Notification */
    if ( !empty($config['admin_onhold_active']) && $status == 'Receive' ) {
        $msg_admin   = $this->awp_wa_process_variables( $config['admin_onhold'], $order, '' );
        $img_admin   = $config['admin_onhold_img'];
        $phone_admin = preg_replace( '/[^0-9]/', '', $config['admin_number'] );
        if ( ! empty( $msg_admin ) ) {
            $this->awp_wa_send_msg( $config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "On-Hold"', $order_id );
        }
    }

    return isset($result) ? $result : null;
}

	public function awp_wa_process_states_pending( $order ) {
    global $woocommerce, $custom_status_list_temp;
    $order       = new WC_Order( $order );
    $status      = $order->get_status();
    $order_id    = $order->get_id(); // Get the Order ID
    $status_list = array(
        'pending' => __( 'Pending', 'awp-send' ),
    );

    foreach ( $status_list as $status_lists => $translations ) {
        if ( $status == $status_lists ) {
            $status = $translations;
        }
    }

    $config = get_option( 'awp_notifications' );
    $phone  = $order->get_billing_phone();
    // Get the user's locale
    $user_locale = get_user_locale( get_current_user_id() );

    $result = null;

    // Use different messages based on the user's locale
    if ( $status == __( 'Pending', 'awp-send' ) ) {
        if ( $user_locale === 'ar' && !empty($config['order_pending_arabic_active']) && !empty($config['order_pending_arabic']) ) {
            $msg_arabic = $this->awp_wa_process_variables( $config['order_pending_arabic'], $order, '' );
            $img_arabic = $config['order_pending_img_arabic'];
            if (!empty($msg_arabic)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order "Pending"', $order_id );
            }
        } elseif (!empty($config['order_pending_active']) && !empty($config['order_pending']) && $user_locale !== 'ar' ) {
            $msg = $this->awp_wa_process_variables( $config['order_pending'], $order, '' );
            $img = $config['order_pending_img'];
            if (!empty($msg)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En) - order "Pending"', $order_id );
            }
        }
    }

    /* Admin Pending Notification */
    if ( !empty($config['admin_pending_active']) && $status == 'Pending' ) {
        $msg_admin   = $this->awp_wa_process_variables( $config['admin_pending'], $order, '' );
        $img_admin   = $config['admin_pending_img'];
        $phone_admin = preg_replace( '/[^0-9]/', '', $config['admin_number'] );
        if ( ! empty( $msg_admin ) ) {
            $this->awp_wa_send_msg( $config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "Pending"', $order_id );
        }
    }

    return isset($result) ? $result : null;
}

	public function awp_wa_process_states_processing($order) {
    global $woocommerce, $custom_status_list_temp;
    $order  = new WC_Order($order);
    $status = $order->get_status();
    $order_id = $order->get_id(); // Get the Order ID
    $status_list = array(
        'processing' => __('Processing', 'awp-send'),
    );

    foreach ($status_list as $status_lists => $translations) {
        if ($status == $status_lists) {
            $status = $translations;
        }
    }

    $config = get_option('awp_notifications');
    $phone  = $order->get_billing_phone();
    $user_locale = get_user_locale(get_current_user_id());
    $result = null;

    // Send message for English locale
    if ($status == __('Processing', 'awp-send')) {
        if ($user_locale == 'ar') {
            if (!empty($config['order_processing_arabic_active']) && !empty($config['order_processing_arabic'])) {
                $msg_arabic = $this->awp_wa_process_variables($config['order_processing_arabic'], $order, '');
                $img_arabic = $config['order_processing_img_arabic'];
                if (!empty($msg_arabic)) {
                    $result = $this->awp_wa_send_msg($config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order "Processing"', $order_id);
                }
            }
        } else {
            if (!empty($config['order_processing_active']) && !empty($config['order_processing'])) {
                $msg = $this->awp_wa_process_variables($config['order_processing'], $order, '');
                $img = $config['order_processing_img'];
                if (!empty($msg)) {
                    $result = $this->awp_wa_send_msg($config, $phone, $msg, $img, '', 'Customer (En) - order "Processing"', $order_id);
                }
            }
        }
    }

    // Admin Processing Notification
    if (!empty($config['admin_processing_active']) && $status == 'Processing') {
        $msg_admin = $this->awp_wa_process_variables($config['admin_processing'], $order, '');
        $img_admin = $config['admin_processing_img'];
        $phone_admin = preg_replace('/[^0-9]/', '', $config['admin_number']);
        if (!empty($msg_admin)) {
            $this->awp_wa_send_msg($config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "Processing"', $order_id);
        }
    }

    return isset($result) ? $result : null;
}

	public function awp_wa_process_states_completed($order) {
    global $woocommerce, $custom_status_list_temp;
    $order  = new WC_Order($order);
    $status = $order->get_status();
    $order_id = $order->get_id(); // Get the Order ID
    $status_list = array(
        'completed' => __('Completed', 'awp-send'),
    );

    foreach ($status_list as $status_lists => $translations) {
        if ($status == $status_lists) {
            $status = $translations;
        }
    }

    $config = get_option('awp_notifications');
    $phone  = $order->get_billing_phone();
    $user_locale = get_user_locale(get_current_user_id());
    $result = null;

    // Send message for English locale
    if ($status == __('Completed', 'awp-send')) {
        if ($user_locale == 'ar') {
            if (!empty($config['order_completed_arabic_active']) && !empty($config['order_completed_arabic'])) {
                $msg_arabic = $this->awp_wa_process_variables($config['order_completed_arabic'], $order, '');
                $img_arabic = $config['order_completed_img_arabic'];
                if (!empty($msg_arabic)) {
                    $result = $this->awp_wa_send_msg($config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order "Completed"', $order_id);
                }
            }
        } else {
            if (!empty($config['order_completed_active']) && !empty($config['order_completed'])) {
                $msg = $this->awp_wa_process_variables($config['order_completed'], $order, '');
                $img = $config['order_completed_img'];
                if (!empty($msg)) {
                    $result = $this->awp_wa_send_msg($config, $phone, $msg, $img, '', 'Customer (En) - order "Completed"', $order_id);
                }
            }
        }
    }

    // Admin Completed Notification
    if (!empty($config['admin_completed_active']) && $status == 'Completed') {
        $msg_admin = $this->awp_wa_process_variables($config['admin_completed'], $order, '');
        $img_admin = $config['admin_completed_img'];
        $phone_admin = preg_replace('/[^0-9]/', '', $config['admin_number']);
        if (!empty($msg_admin)) {
            $this->awp_wa_send_msg($config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "Completed"', $order_id);
        }
    }

    return isset($result) ? $result : null;
}

	public function awp_wa_process_states_failed( $order ) {
    global $woocommerce, $custom_status_list_temp;
    $order       = new WC_Order( $order );
    $status      = $order->get_status();
    $order_id    = $order->get_id(); // Get the Order ID
    $status_list = array(
        'failed' => __( 'Failed', 'awp-send' ),
    );

    foreach ( $status_list as $status_lists => $translations ) {
        if ( $status == $status_lists ) {
            $status = $translations;
        }
    }

    $config = get_option( 'awp_notifications' );
    $phone  = $order->get_billing_phone();
    // Get the user's locale
    $user_locale = get_user_locale( get_current_user_id() );

    $result = null;

    // Use different messages based on the user's locale
    if ( $status == __( 'Failed', 'awp-send' ) ) {
        if ( $user_locale === 'ar' && !empty($config['order_failed_arabic_active']) && !empty($config['order_failed_arabic']) ) {
            $msg_arabic = $this->awp_wa_process_variables( $config['order_failed_arabic'], $order, '' );
            $img_arabic = $config['order_failed_img_arabic'];
            if (!empty($msg_arabic)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order "Failed"', $order_id );
            }
        } elseif (!empty($config['order_failed_active']) && !empty($config['order_failed']) && $user_locale !== 'ar' ) {
            $msg = $this->awp_wa_process_variables( $config['order_failed'], $order, '' );
            $img = $config['order_failed_img'];
            if (!empty($msg)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En) - order "Failed"', $order_id );
            }
        }
    }

    /* Admin Failed Notification */
    if ( !empty($config['admin_failed_active']) && $status == 'Failed' ) {
        $msg_admin   = $this->awp_wa_process_variables( $config['admin_failed'], $order, '' );
        $img_admin   = $config['admin_failed_img'];
        $phone_admin = preg_replace( '/[^0-9]/', '', $config['admin_number'] );
        if ( ! empty( $msg_admin ) ) {
            $this->awp_wa_send_msg( $config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "Failed"', $order_id );
        }
    }

    return isset($result) ? $result : null;
}

	public function awp_wa_process_states_refunded( $order ) {
    global $woocommerce, $custom_status_list_temp;
    $order       = new WC_Order( $order );
    $status      = $order->get_status();
    $order_id    = $order->get_id(); // Get the Order ID
    $status_list = array(
        'refunded' => __( 'Refunded', 'awp-send' ),
    );

    foreach ( $status_list as $status_lists => $translations ) {
        if ( $status == $status_lists ) {
            $status = $translations;
        }
    }

    $config = get_option( 'awp_notifications' );
    $phone  = $order->get_billing_phone();
    // Get the user's locale
    $user_locale = get_user_locale( get_current_user_id() );

    $result = null;

    // Use different messages based on the user's locale
    if ( $status == __( 'Refunded', 'awp-send' ) ) {
        if ( $user_locale === 'ar' && !empty($config['order_refunded_arabic_active']) && !empty($config['order_refunded_arabic']) ) {
            $msg_arabic = $this->awp_wa_process_variables( $config['order_refunded_arabic'], $order, '' );
            $img_arabic = $config['order_refunded_img_arabic'];
            if (!empty($msg_arabic)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order "Refunded"', $order_id );
            }
        } elseif (!empty($config['order_refunded_active']) && !empty($config['order_refunded']) && $user_locale !== 'ar' ) {
            $msg = $this->awp_wa_process_variables( $config['order_refunded'], $order, '' );
            $img = $config['order_refunded_img'];
            if (!empty($msg)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En) - order "Refunded"', $order_id );
            }
        }
    }

    /* Admin Refunded Notification */
    if ( !empty($config['admin_refunded_active']) && $status == 'Refunded' ) {
        $msg_admin   = $this->awp_wa_process_variables( $config['admin_refunded'], $order, '' );
        $img_admin   = $config['admin_refunded_img'];
        $phone_admin = preg_replace( '/[^0-9]/', '', $config['admin_number'] );
        if ( ! empty( $msg_admin ) ) {
            $this->awp_wa_send_msg( $config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "Refunded"', $order_id );
        }
    }

    return isset($result) ? $result : null;
}

	public function awp_wa_process_states_cancelled( $order ) {
    global $woocommerce, $custom_status_list_temp;
    $order       = new WC_Order( $order );
    $status      = $order->get_status();
    $order_id    = $order->get_id(); // Get the Order ID
    $status_list = array(
        'cancelled' => __( 'Cancelled', 'awp-send' ),
    );

    foreach ( $status_list as $status_lists => $translations ) {
        if ( $status == $status_lists ) {
            $status = $translations;
        }
    }

    $config = get_option( 'awp_notifications' );
    $phone  = $order->get_billing_phone();
    // Get the user's locale
    $user_locale = get_user_locale( get_current_user_id() );

    $result = null;

    // Use different messages based on the user's locale
    if ( $status == __( 'Cancelled', 'awp-send' ) ) {
        if ( $user_locale === 'ar' && !empty($config['order_cancelled_arabic_active']) && !empty($config['order_cancelled_arabic']) ) {
            $msg_arabic = $this->awp_wa_process_variables( $config['order_cancelled_arabic'], $order, '' );
            $img_arabic = $config['order_cancelled_img_arabic'];
            if (!empty($msg_arabic)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order "Cancelled"', $order_id );
            }
        } elseif (!empty($config['order_cancelled_active']) && !empty($config['order_cancelled']) && $user_locale !== 'ar' ) {
            $msg = $this->awp_wa_process_variables( $config['order_cancelled'], $order, '' );
            $img = $config['order_cancelled_img'];
            if (!empty($msg)) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En) - order "Cancelled"', $order_id );
            }
        }
    }

    /* Admin Cancelled Notification */
    if ( !empty($config['admin_cancelled_active']) && $status == 'Cancelled' ) {
        $msg_admin   = $this->awp_wa_process_variables( $config['admin_cancelled'], $order, '' );
        $img_admin   = $config['admin_cancelled_img'];
        $phone_admin = preg_replace( '/[^0-9]/', '', $config['admin_number'] );
        if ( ! empty( $msg_admin ) ) {
            $this->awp_wa_send_msg( $config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "Cancelled"', $order_id );
        }
    }

    return isset($result) ? $result : null;
}

    public function awp_wa_process_note( $data ) {
    global $woocommerce;
    $order     = new WC_Order( $data['order_id'] );
    $order_id  = $order->get_id(); // Get the Order ID
    $config    = get_option( 'awp_notifications' );
    $phone     = $order->get_billing_phone();

    // Get the user's locale
    $user_locale = get_user_locale( get_current_user_id() );

    $result = null;

    // Use different messages based on the user's locale
    if ( $user_locale === 'ar' ) {
        if ( !empty($config['order_note_arabic_active']) && !empty($config['order_note_arabic']) ) {
            $msg_arabic = $this->awp_wa_process_variables( $config['order_note_arabic'], $order, '', wptexturize( $data['customer_note'] ) );
            $img_arabic = $config['order_note_img_arabic'];
            if ( !empty($msg_arabic) ) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg_arabic, $img_arabic, '', 'Customer (Ar) - order note', $order_id );
            }
        }
    } else {
        if ( !empty($config['order_note_active']) && !empty($config['order_note']) ) {
            $msg = $this->awp_wa_process_variables( $config['order_note'], $order, '', wptexturize( $data['customer_note'] ) );
            $img = $config['order_note_img'];
            if ( !empty($msg) ) {
                $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En) - order note', $order_id );
            }
        }
    }

    return isset($result) ? $result : null;
}

    public function awp_custom_order_status() {
    if ( $this->is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        global $custom_status_list_temp;
        $custom_status_list = wc_get_order_statuses();
        $custom_status_list_temp = array();
        $original_status = array(
            'pending',
            'failed',
            'on-hold',
            'processing',
            'completed',
            'refunded',
            'cancelled',
        );

        foreach ( $custom_status_list as $key => $status ) {
            $status_name = str_replace( 'wc-', '', $key );

            // Exclude "Draft" status from being processed
            if ( $status_name === 'draft' ) {
                continue;
            }

            if ( ! in_array( $status_name, $original_status ) ) {
                $custom_status_list_temp[ $status ] = $status_name;
                add_action( 'woocommerce_order_status_' . $status_name, array( $this, 'awp_wa_process_states' ), 10 );
            }
        }
    }
}

    public function awp_wa_process_states( $order ) {
    global $woocommerce, $custom_status_list_temp;
    $order       = new WC_Order( $order );
    $status      = $order->get_status();
    $order_id    = $order->get_id(); // Get the Order ID
    $config      = get_option( 'awp_notifications' );
    $phone       = $order->get_billing_phone();
    $custom_status_list = $custom_status_list_temp;
    $msg         = '';
    $img         = '';
    $result      = null;

    if ( ! empty( $custom_status_list ) ) {
        foreach ( $custom_status_list as $status_name => $custom_status ) {
            if ( strtolower( $status ) == $custom_status ) {
                // Use different messages based on the user's locale
                $user_locale = get_user_locale( get_current_user_id() );

                if ( $user_locale === 'ar' ) {
                    if ( !empty($config['order_' . $custom_status . '_arabic_active']) && !empty($config['order_' . $custom_status . '_arabic']) ) {
                        $msg = $this->awp_wa_process_variables( $config[ 'order_' . $custom_status . '_arabic' ], $order, '' );
                        $img = $config[ 'order_' . $custom_status . '_img_arabic' ];
                    }
                } else {
                    if ( !empty($config['order_' . $custom_status . '_active']) && !empty($config['order_' . $custom_status]) ) {
                        $msg = $this->awp_wa_process_variables( $config[ 'order_' . $custom_status ], $order, '' );
                        $img = $config[ 'order_' . $custom_status . '_img' ];
                    }
                }

                if ( !empty($msg) ) {
                    $result = $this->awp_wa_send_msg( $config, $phone, $msg, $img, '', 'Customer (En/Ar) - order status "' . ucfirst($custom_status) . '"', $order_id );
                }

                // Admin Notification
                if ( !empty($config['admin_order_' . $custom_status . '_active']) && !empty($config['admin_order_' . $custom_status]) ) {
                    $msg_admin = $this->awp_wa_process_variables( $config['admin_order_' . $custom_status], $order, '' );
                    $img_admin = $config['admin_order_' . $custom_status . '_img'];
                    $phone_admin = preg_replace( '/[^0-9]/', '', $config['admin_number'] );
                    if (!empty($msg_admin)) {
                        $this->awp_wa_send_msg( $config, $phone_admin, $msg_admin, $img_admin, '', 'Admin - notifications "' . ucfirst($custom_status) . '"', $order_id );
                    }
                }
            }
        }
    }
    return isset($result) ? $result : null;
}

    public function awp_wa_send_msg($config, $phone, $msg, $img, $resend, $notification_type = '', $order_id = null) {
    global $result;
    $config = get_option('awp_notifications');
    $phone  = preg_replace('/[^0-9]/', '', $phone);
    $instances = get_option('awp_instances');

    if (isset($instances['dashboard_prefix']) && isset($instances['access_token']) && isset($instances['instance_id'])) {
        $dashboard_prefix = $instances['dashboard_prefix'];
        $access_token     = $instances['access_token'];
        $instance_id      = $instances['instance_id'];
        $msg              = $this->spintax($msg);
    } else {
        // handle error or provide default values
    }

    $msg              = $this->spintax($msg);
    $instances        = get_option('awp_instances');
    $dashboard_prefix = isset($instances['dashboard_prefix']) ? $instances['dashboard_prefix'] : '';
    $access_token     = $instances['access_token'];
    $instance_id      = $instances['instance_id'];
    $timeout          = 3; // Timeout set to 3 seconds

    // Initialize billing first and last name
    $billing_first_name = '';
    $billing_last_name = '';
    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $billing_first_name = $order->get_billing_first_name();
            $billing_last_name = $order->get_billing_last_name();
        }
    }

    // Fall back to WordPress user first and last name if billing info is empty
    if (empty($billing_first_name) && empty($billing_last_name)) {
        $user = get_userdata(get_current_user_id());
        if ($user) {
            $billing_first_name = $user->first_name;
            $billing_last_name = $user->last_name;
        }
    }

    $full_name = trim($billing_first_name . ' ' . $billing_last_name);

    if (empty($img)) {
        $url           = 'https://app.wawp.net/api/send?number=' . $phone . '&type=text&message=' . urlencode($msg) . '&instance_id=' . $instance_id . '&access_token=' . $access_token;
        $rest_response = wp_remote_retrieve_body(
            wp_remote_get(
                $url,
                array(
                    'sslverify' => true,
                    'timeout'   => $timeout,
                )
            )
        );
    } else {
        $url = 'https://app.wawp.net/api/send?number=' . $phone . '&type=media&message=' . urlencode($msg) . '&media_url=' . $img . '&instance_id=' . $instance_id . '&access_token=' . $access_token;
        $rest_response = wp_remote_retrieve_body(
            wp_remote_get(
                $url,
                array(
                    'sslverify' => true,
                    'timeout'   => $timeout,
                )
            )
        );
    }

    // Get current date and time according to WordPress settings
$current_datetime = current_time('mysql');

// Format the date and time according to WordPress settings
$formatted_datetime = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($current_datetime));
    $result           = json_decode($rest_response, true);

    // Replace null message with a custom error message
    if ($result['status'] === 'error' && empty($result['message'])) {
        $result['message'] = 'WhatsApp number not correct';
    }

    // Determine the plugin status (success or error)
    $plugin_status = !empty($result['status']) && strtolower($result['status']) === 'success' ? 'Success' : 'Error';
    $plugin_status_class = strtolower($plugin_status);

    // Get current user ID and order link
    $user_id = get_current_user_id();
    $user_link = !empty($user_id) ? get_edit_user_link($user_id) : '#';

    // Generate the order link
    $order_link = '';
    if ($order_id && is_numeric($order_id)) {
        $order_link = admin_url('post.php?post=' . $order_id . '&action=edit');
    } else {
        $order_id = 'N/A';
        $order_link = '#';
    }

    // Read the log file and count the number of existing entries to determine the next record number
    $logger = new awp_logger();
    $log_content = $logger->get_log_file('awpsend');
    $record_number = substr_count(trim($log_content), '<tr>') + 1;

    // Adjust the notification_type to 'Log Resend' if the message is being resent
    if ($resend) {
        $notification_type = 'Log Resend';
    }

    // Log the result with additional data, including the record number and adjusted notification_type
    $this->log->add(
        'awpsend',
        '<tr><td>' . esc_html($record_number) . '</td>' . // Record number
        '<td><a href="' . esc_url($user_link) . '">' . esc_html($user_id) . '</a></td>' .
        '<td><a href="' . esc_url($order_link) . '">' . esc_html($order_id) . '</a></td>' .
        '<td>' . esc_html($full_name) . '</td>' . // Add full name (first + last) here
        '<td>' . esc_html($current_datetime) . '</td>' .
        '<td class="log-phone">' . esc_html($phone) . '</td>' .
        '<td class="log-msg"><div>' . esc_html($msg) . '</div></td>' .
        '<td class="log-img">' . esc_html($img) . '</td>' .
        '<td>' . esc_html($notification_type) . '</td>' .
        '<td><a href="#" class="plugin-status-link status-' . $plugin_status_class . '" data-popup="' . esc_attr(json_encode($result)) . '">' . esc_html($plugin_status) . '</a></td>' .
        '<td><button type="button" class="button log-resend" data-instance-id="' . esc_html($instance_id) . '" data-access-token="' . esc_html($access_token) . '" data-phone="' . esc_html($phone) . '" data-message="' . esc_html($msg) . '" data-img="' . esc_html($img) . '" data-notification-type="' . esc_html($notification_type) . '">Resend WhatsApp</button></td>' .
        '</tr>'
    );

    // Attempt to reconnect if the status is empty or an error
    if (empty($result['status'])) {
        $url           = 'https://app.wawp.net/api/reconnect?instance_id=' . $instance_id . '&access_token=' . $access_token;
        $rest_response = wp_remote_retrieve_body(
            wp_remote_get(
                $url,
                array(
                    'sslverify' => true,
                    'timeout'   => $timeout,
                )
            )
        );
    }

    return $result['status'];
}
	// Encode message
	
	public function awp_wa_encoding( $msg ) {
		return htmlentities( $msg, ENT_QUOTES, 'UTF-8' );
	}
	
    /**
 * Process message variables and replace placeholders with actual data, including custom fields.
 *
 * @param string      $msg       The message containing placeholders.
 * @param WC_Order    $order     The WooCommerce order object (optional).
 * @param array       $variables Additional variables to process (optional).
 * @param string      $note      Note to include in the message (optional).
 * @param WP_User     $user      The WordPress user object (optional).
 *
 * @return string The processed message.
 */
    // Function to process message variables
    public function awp_wa_process_variables($msg, $order = null, $variables = [], $note = '', $user = null) {
    global $wpdb, $woocommerce;

    // List of known variables to replace
    $awp_wa = array(
        'id', 'order_key', 'billing_first_name', 'billing_last_name', 'billing_company',
        'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode',
        'billing_country', 'billing_state', 'billing_email', 'billing_phone',
        'shipping_first_name', 'shipping_last_name', 'shipping_company',
        'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_postcode',
        'shipping_country', 'shipping_state', 'shipping_method', 'shipping_method_title',
        'bacs_account', 'payment_method', 'payment_method_title', 'order_subtotal',
        'order_discount', 'cart_discount', 'order_tax', 'order_shipping',
        'order_shipping_tax', 'order_total', 'status', 'shop_name', 'currency',
        'cust_note', 'note', 'product', 'product_name', 'dpd', 'unique_transfer_code',
        'order_date', 'order_link', 'transaction_id', 'current_date_time',
        'user_name', 'user_first_last_name', 'wc_billing_first_name', 'wc_billing_last_name', 'wc_billing_phone',
        'site_link'
    );

    // Convert variables to an array, ensure proper handling of newlines
    if (is_array($variables)) {
        $variables = implode("\n", $variables);
    }
    $variables = str_replace(array("\r\n", "\r"), "\n", $variables);
    $variables = explode("\n", $variables);

    // Find all placeholders in the message
    preg_match_all('/{{(.*?)}}/', $msg, $search);
    $currency = get_woocommerce_currency_symbol();
    $locale = get_locale();
    $is_arabic = strpos($locale, 'ar') === 0;

    foreach ($search[1] as $variable) {
        $variable = strtolower($variable);

        // Handle login-specific placeholders if $order is null and $user is not null
        if (is_null($order) && !is_null($user)) {
            switch ($variable) {
                case 'user_name':
                    $msg = str_replace('{{' . $variable . '}}', $user->user_login, $msg);
                    break;

                case 'user_first_last_name':
                    $first_name = get_user_meta($user->ID, 'first_name', true);
                    $last_name = get_user_meta($user->ID, 'last_name', true);
                    $full_name = $first_name . ' ' . $last_name;
                    $msg = str_replace('{{' . $variable . '}}', $full_name, $msg);
                    break;

                case 'wc_billing_first_name':
                    $billing_first_name = get_user_meta($user->ID, 'billing_first_name', true);
                    $msg = str_replace('{{' . $variable . '}}', $billing_first_name, $msg);
                    break;

                case 'wc_billing_last_name':
                    $billing_last_name = get_user_meta($user->ID, 'billing_last_name', true);
                    $msg = str_replace('{{' . $variable . '}}', $billing_last_name, $msg);
                    break;

                case 'wc_billing_phone':
                    $billing_phone = get_user_meta($user->ID, 'billing_phone', true);
                    $msg = str_replace('{{' . $variable . '}}', $billing_phone, $msg);
                    break;

                case 'shop_name':
                    $msg = str_replace('{{' . $variable . '}}', get_bloginfo('name'), $msg);
                    break;

                case 'current_date_time':
                    $formatted_date = $is_arabic ? date_i18n('Y-m-d H:i:s', current_time('timestamp'), true) : date('Y-m-d H:i:s');
                    $msg = str_replace('{{' . $variable . '}}', $formatted_date, $msg);
                    break;

                case 'site_link':
                    $msg = str_replace('{{' . $variable . '}}', site_url(), $msg);
                    break;
            }
        } elseif ($order instanceof WC_Order) {
            // Ensure the user is correctly retrieved based on order information
            if (!$user) {
                $user_id = $order->get_user_id();
                if ($user_id) {
                    $user = get_userdata($user_id);
                }
            }

            // Handle order-specific placeholders
            switch ($variable) {
                case 'id':
                    $msg = str_replace('{{' . $variable . '}}', $order->get_id(), $msg);
                    break;

                case 'order_date':
                    $date_format = get_option('date_format');
                    $time_format = get_option('time_format');
                    $order_date = $order->get_date_created();
                    $formatted_date = $order_date->date("$date_format $time_format");
                    if ($is_arabic) {
                        $formatted_date = date_i18n("$date_format $time_format", strtotime($formatted_date));
                    }
                    $msg = str_replace('{{' . $variable . '}}', $formatted_date, $msg);
                    break;

                case 'order_link':
                    $order_received_url = wc_get_endpoint_url('order-received', $order->get_id(), wc_get_checkout_url());
                    $order_received_url = add_query_arg('key', $order->get_order_key(), $order_received_url);
                    $msg = str_replace('{{' . $variable . '}}', $order_received_url, $msg);
                    break;

                case 'product':
                    $product_items = '';
                    $i = 0;
                    foreach ($order->get_items() as $item_id => $item_data) {
                        ++$i;
                        $new_line = ($i > 1) ? "\n" : '';
                        $product = $item_data->get_product();
                        $product_name = $product->get_name();
                        $item_quantity = $item_data->get_quantity();
                        $item_total = $item_data->get_total();
                        $product_items .= $new_line . $i . '. ' . $product_name . ' x ' . $item_quantity . ' = ' . $currency . ' ' . number_format($item_total, wc_get_price_decimals());
                    }
                    $msg = str_replace('{{' . $variable . '}}', html_entity_decode($product_items), $msg);
                    break;

                case 'order_discount':
                    $msg = str_replace('{{' . $variable . '}}', number_format($order->get_total_discount(), wc_get_price_decimals()), $msg);
                    break;
                    
                    case 'product_name':
    $product_names = array();
    foreach ($order->get_items() as $item_id => $item_data) {
        $product = $item_data->get_product();
        if ($product) {
            $product_names[] = $product->get_name();
        }
    }
    $msg = str_replace('{{' . $variable . '}}', implode(', ', $product_names), $msg);
    break;


                case 'cart_discount':
                    $cart_discount = $order->get_discount_total();
                    $msg = str_replace('{{' . $variable . '}}', number_format($cart_discount, wc_get_price_decimals()), $msg);
                    break;

                case 'order_subtotal':
                    $msg = str_replace('{{' . $variable . '}}', number_format($order->get_subtotal(), wc_get_price_decimals()), $msg);
                    break;

                case 'order_tax':
                    $order_tax = $order->get_total_tax();
                    $msg = str_replace('{{' . $variable . '}}', number_format($order_tax, wc_get_price_decimals()), $msg);
                    break;

                case 'currency':
                    $msg = str_replace('{{' . $variable . '}}', html_entity_decode($currency), $msg);
                    break;

                case 'shop_name':
                    $msg = str_replace('{{' . $variable . '}}', get_bloginfo('name'), $msg);
                    break;

                case 'cust_note':
                    $msg = str_replace('{{' . $variable . '}}', $order->get_customer_note(), $msg);
                    break;

                case 'shipping_method':
                    $msg = str_replace('{{' . $variable . '}}', $order->get_shipping_method(), $msg);
                    break;

                case 'order_shipping':
                    $msg = str_replace('{{' . $variable . '}}', number_format($order->get_shipping_total(), wc_get_price_decimals()), $msg);
                    break;

                case 'order_shipping_tax':
                    $msg = str_replace('{{' . $variable . '}}', number_format($order->get_shipping_tax(), wc_get_price_decimals()), $msg);
                    break;

                case 'transaction_id':
                    $transaction_id = $order->get_transaction_id();
                    $msg = str_replace('{{' . $variable . '}}', $transaction_id ? $transaction_id : 'N/A', $msg);
                    break;

                case 'bacs_account':
                    $gateway = new WC_Gateway_BACS();
                    $bacs_info = get_option('woocommerce_bacs_accounts');
                    $bacs_items = '';
                    if ($bacs_info) {
                        foreach ($bacs_info as $account) {
                            $bacs_items .= "🏦 " . esc_attr(wp_unslash($account['bank_name'])) . "\n";
                            $bacs_items .= "👤 " . esc_attr(wp_unslash($account['account_name'])) . "\n";
                            $bacs_items .= "🔢 " . esc_attr($account['account_number']) . "\n";
                        }
                    }
                    $msg = str_replace('{{' . $variable . '}}', $bacs_items, $msg);
                    break;

                case 'note':
                    $msg = str_replace('{{' . $variable . '}}', $note, $msg);
                    break;

                case 'unique_transfer_code':
                    $unique_transfer_code = $order->get_meta('_unique_transfer_code', true);
                    $msg = str_replace('{{' . $variable . '}}', $unique_transfer_code ? $unique_transfer_code : 'N/A', $msg);
                    break;

                case 'site_link':
                    $msg = str_replace('{{' . $variable . '}}', site_url(), $msg);
                    break;

                default:
                    if (in_array($variable, $awp_wa)) {
                        $meta_value = get_post_meta($order->get_id(), '_' . $variable, true);
                        if (empty($meta_value)) {
                            $meta_value = get_post_meta($order->get_id(), $variable, true);
                        }
                        $msg = str_replace('{{' . $variable . '}}', $meta_value, $msg);
                    }
                    break;
            }
        }
    }

    return $msg;
}

	// Spintax for randomizing message content
	public function spintax( $str ) {
		return preg_replace_callback(
			'/{(.*?)}/',
			function ( $match ) {
				$words = explode( '|', $match[1] );
				return $words[ array_rand( $words ) ];
			},
			$str
		);
	}

	// Followup order function
	public function followup_order() {
    global $woocommerce;
    $config = get_option( 'awp_notifications' );

    // Check if the follow-up is active
    if ( empty( $config['followup_onhold_active'] ) || $config['followup_onhold_active'] !== '1' ) {
        return; // Exit if not active
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'on-hold',
        )
    );
    if ( isset( $customer_orders ) ) {
        $followup_send = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $purchase_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;
            $followup_day  = $config['followup_onhold_day'];

            if ( empty( $followup_day ) ) {
                $followup_day = 24;
            }
            if ( $day_range >= $followup_day ) {
                $sent = get_post_meta( $single_order->ID, 'followup', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'followup', '0' );
                }
                if ( $sent == '0' ) {
                    $followup_send[] = $single_order->ID;
                }
            }
        }
        if ( count( $followup_send ) != 0 ) {
            foreach ( $followup_send as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_onhold'], $order, '' );
                $img   = $config['followup_onhold_img'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'followup', '1' );
            }
        }
    }
}

	// Followup order 2 function
	public function followup_order_2() {
    global $woocommerce;
    $config = get_option( 'awp_notifications' );

    // Check if the follow-up is active
    if ( !isset($config['followup_onhold_2_active']) || $config['followup_onhold_2_active'] !== '1' ) {
        return; // Exit if follow-up is not active
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'on-hold',
        )
    );
    if ( isset( $customer_orders ) ) {
        $followup_send_2 = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $purchase_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;
            $followup_day  = $config['followup_onhold_day_2'];

            if ( empty( $followup_day ) ) {
                $followup_day = 48;
            }
            if ( $day_range >= $followup_day ) {
                $sent = get_post_meta( $single_order->ID, 'followup_2', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'followup_2', '0' );
                }
                if ( $sent == '0' ) {
                    echo esc_attr( $single_order->ID ) . ' = ' . esc_attr( $sent ) . '<br>';
                    $followup_send_2[] = $single_order->ID;
                }
            }
        }
        if ( count( $followup_send_2 ) != 0 ) {
            foreach ( $followup_send_2 as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_onhold_2'], $order, '' );
                $img   = $config['followup_onhold_img_2'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'followup_2', '1' );
            }
        }
    }
}

	// Followup order 3 function
	public function followup_order_3() {
    global $woocommerce;
    $config          = get_option( 'awp_notifications' );

    // Check if the follow-up notification is active
    if ( empty( $config['followup_onhold_3_active'] ) || $config['followup_onhold_3_active'] !== '1' ) {
        return;
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'on-hold',
        )
    );
    if ( isset( $customer_orders ) ) {
        $followup_send_3 = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $purchase_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;
            $followup_day  = $config['followup_onhold_day_3'];

            if ( empty( $followup_day ) ) {
                $followup_day = 72;
            }
            if ( $day_range >= $followup_day ) {
                $sent = get_post_meta( $single_order->ID, 'followup_3', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'followup_3', '0' );
                }
                if ( $sent == '0' ) {
                    $followup_send_3[] = $single_order->ID;
                }
            }
        }
        if ( count( $followup_send_3 ) != 0 ) {
            foreach ( $followup_send_3 as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_onhold_3'], $order, '' );
                $img   = $config['followup_onhold_img_3'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'followup_3', '1' );
            }
        }
    }
}

	// Followup order 4 function
	public function followup_order_4() {
    global $woocommerce;
    $config = get_option( 'awp_notifications' );

    // Check if the follow-up notification is active
    if ( empty( $config['followup_onhold_4_active'] ) || $config['followup_onhold_4_active'] !== '1' ) {
        return;
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'on-hold',
        )
    );
    if ( isset( $customer_orders ) ) {
        $followup_send_4 = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $purchase_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;
            $followup_day  = $config['followup_onhold_day_4'];

            if ( empty( $followup_day ) ) {
                $followup_day = 96;
            }
            if ( $day_range >= $followup_day ) {
                $sent = get_post_meta( $single_order->ID, 'followup_4', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'followup_4', '0' );
                }
                if ( $sent == '0' ) {
                    $followup_send_4[] = $single_order->ID;
                }
            }
        }
        if ( count( $followup_send_4 ) != 0 ) {
            foreach ( $followup_send_4 as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_onhold_4'], $order, '' );
                $img   = $config['followup_onhold_img_4'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'followup_4', '1' );
            }
        }
    }
}

	// Aftersales order function
	public function aftersales_order() {
    global $woocommerce;
    $config = get_option( 'awp_notifications' );

    // Check if the aftersales notification is active
    if ( empty( $config['followup_aftersales_active'] ) || $config['followup_aftersales_active'] !== '1' ) {
        return;
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'completed',
        )
    );
    if ( isset( $customer_orders ) ) {
        $aftersales_send = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $paid_date_raw = date_format( date_create( get_post_meta( $single_order->ID, '_completed_date', true ) ), 'Y-m-d H:i:s' );
            $paid_date_obj = new DateTime();
            $paid_date     = $paid_date_obj->createFromFormat( 'Y-m-d H:i:s', $paid_date_raw );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $paid_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;

            $aftersales_day = $config['followup_aftersales_day'];
            if ( empty( $aftersales_day ) ) {
                $aftersales_day = 48;
            }
            if ( $day_range >= $aftersales_day ) {
                $sent = get_post_meta( $single_order->ID, 'aftersales', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'aftersales', '0' );
                }
                if ( $sent == '0' ) {
                    $aftersales_send[] = $single_order->ID;
                }
            }
        }
        if ( count( $aftersales_send ) != 0 ) {
            foreach ( $aftersales_send as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_aftersales'], $order, '' );
                $img   = $config['followup_aftersales_img'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'aftersales', '1' );
            }
        }
    }
}

	// Aftersales order 2 function
	public function aftersales_order_2() {
    global $woocommerce;
    $config = get_option( 'awp_notifications' );

    // Check if the aftersales notification is active
    if ( empty( $config['followup_aftersales_2_active'] ) || $config['followup_aftersales_2_active'] !== '1' ) {
        return;
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'completed',
        )
    );
    if ( isset( $customer_orders ) ) {
        $aftersales_send_2 = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $paid_date_raw = date_format( date_create( get_post_meta( $single_order->ID, '_completed_date', true ) ), 'Y-m-d H:i:s' );
            $paid_date_obj = new DateTime();
            $paid_date     = $paid_date_obj->createFromFormat( 'Y-m-d H:i:s', $paid_date_raw );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $paid_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;

            $aftersales_day_2 = $config['followup_aftersales_day_2'];
            if ( empty( $aftersales_day_2 ) ) {
                $aftersales_day_2 = 72;
            }
            if ( $day_range >= $aftersales_day_2 ) {
                $sent = get_post_meta( $single_order->ID, 'aftersales_2', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'aftersales_2', '0' );
                }
                if ( $sent == '0' ) {
                    $aftersales_send_2[] = $single_order->ID;
                }
            }
        }
        if ( count( $aftersales_send_2 ) != 0 ) {
            foreach ( $aftersales_send_2 as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_aftersales_2'], $order, '' );
                $img   = $config['followup_aftersales_img_2'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'aftersales_2', '1' );
            }
        }
    }
}

	// Aftersales order 3 function
	public function aftersales_order_3() {
    global $woocommerce;
    $config = get_option( 'awp_notifications' );

    // Check if the aftersales notification is active
    if ( empty( $config['followup_aftersales_3_active'] ) || $config['followup_aftersales_3_active'] !== '1' ) {
        return;
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'completed',
        )
    );
    if ( isset( $customer_orders ) ) {
        $aftersales_send_3 = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $paid_date_raw = date_format( date_create( get_post_meta( $single_order->ID, '_completed_date', true ) ), 'Y-m-d H:i:s' );
            $paid_date_obj = new DateTime();
            $paid_date     = $paid_date_obj->createFromFormat( 'Y-m-d H:i:s', $paid_date_raw );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $paid_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;

            $aftersales_day_3 = $config['followup_aftersales_day_3'];
            if ( empty( $aftersales_day_3 ) ) {
                $aftersales_day_3 = 96;
            }
            if ( $day_range >= $aftersales_day_3 ) {
                $sent = get_post_meta( $single_order->ID, 'aftersales_3', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'aftersales_3', '0' );
                }
                if ( $sent == '0' ) {
                    $aftersales_send_3[] = $single_order->ID;
                }
            }
        }
        if ( count( $aftersales_send_3 ) != 0 ) {
            foreach ( $aftersales_send_3 as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_aftersales_3'], $order, '' );
                $img   = $config['followup_aftersales_img_3'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'aftersales_3', '1' );
            }
        }
    }
}

	// Aftersales order 4 function
	public function aftersales_order_4() {
    global $woocommerce;
    $config = get_option( 'awp_notifications' );

    // Check if the aftersales notification is active
    if ( empty( $config['followup_aftersales_4_active'] ) || $config['followup_aftersales_4_active'] !== '1' ) {
        return;
    }

    $customer_orders = wc_get_orders(
        array(
            'limit'      => -1,
            'date_after' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'status'     => 'completed',
        )
    );
    if ( isset( $customer_orders ) ) {
        $aftersales_send_4 = array();
        foreach ( $customer_orders as $order => $single_order ) {
            $today         = date_create( date( 'Y-m-d H:i:s' ) );
            $purchase_date = date_create( $single_order->date_created->date( 'Y-m-d H:i:s' ) );
            $paid_date_raw = date_format( date_create( get_post_meta( $single_order->ID, '_completed_date', true ) ), 'Y-m-d H:i:s' );
            $paid_date_obj = new DateTime();
            $paid_date     = $paid_date_obj->createFromFormat( 'Y-m-d H:i:s', $paid_date_raw );
            $ts1           = strtotime( $today->format( 'Y-m-d H:i:s' ) );
            $ts2           = strtotime( $paid_date->format( 'Y-m-d H:i:s' ) );
            $day_range     = abs( $ts1 - $ts2 ) / 3600;

            $aftersales_day_4 = $config['followup_aftersales_day_4'];
            if ( empty( $aftersales_day_4 ) ) {
                $aftersales_day_4 = 120;
            }
            if ( $day_range >= $aftersales_day_4 ) {
                $sent = get_post_meta( $single_order->ID, 'aftersales_4', true );
                if ( empty( $sent ) || $sent == null ) {
                    update_post_meta( $single_order->ID, 'aftersales_4', '0' );
                }
                if ( $sent == '0' ) {
                    $aftersales_send_4[] = $single_order->ID;
                }
            }
        }
        if ( count( $aftersales_send_4 ) != 0 ) {
            foreach ( $aftersales_send_4 as $flw => $foll_id ) {
                $order = new WC_Order( $foll_id );
                $msg   = $this->awp_wa_process_variables( $config['followup_aftersales_4'], $order, '' );
                $img   = $config['followup_aftersales_img_4'];
                $phone = $order->get_billing_phone();
                if ( ! empty( $msg ) ) {
                    $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                }
                update_post_meta( $foll_id, 'aftersales_4', '1' );
            }
        }
    }
}

	// Abandoned order function
	public function abandoned_order() {
    if ( $this->is_plugin_active( 'woo-save-abandoned-carts/cartbounty-abandoned-carts.php' ) ) {
        global $wpdb;
        $config = get_option( 'awp_notifications' );

        // Check if the abandoned cart notification is active
        if ( empty( $config['followup_abandoned_active'] ) || $config['followup_abandoned_active'] !== '1' ) {
            return;
        }

        $table_name = $wpdb->prefix . 'cartbounty';
        $ab_carts   = $wpdb->get_results( "SELECT * FROM $table_name WHERE other_fields != '1'" );
        if ( isset( $ab_carts ) ) {
            foreach ( $ab_carts as $ab_cart => $cart ) {
                $id      = $cart->id;
                $name    = $cart->name;
                $surname = $cart->surname;
                $email   = $cart->email;
                // Check for an existing WooCommerce order by email
                $orders = wc_get_orders( array( 'billing_email' => $email ) );
                if ( count( $orders ) > 0 ) {
                    // An order exists for this email, skip sending message
                    continue;
                }
                $phone              = $cart->phone;
                $total              = $cart->cart_total;
                $currency           = $cart->currency;
                $today              = date_create( date( 'Y-m-d H:i:s' ) );
                $abandoned_date_raw = date_format( date_create( $cart->time ), 'Y-m-d H:i:s' );
                $abandoned_date_obj = new DateTime();
                $abandoned_date     = $abandoned_date_obj->createFromFormat( 'Y-m-d H:i:s', $abandoned_date_raw );
                $ts1                = strtotime( $today->format( 'Y-m-d H:i:s' ) );
                $ts2                = strtotime( $abandoned_date->format( 'Y-m-d H:i:s' ) );
                $day_range          = round( abs( $ts1 - $ts2 ) / 3600 );
                $abandoned_day      = $config['followup_abandoned_day'];
                $product_array      = @unserialize( $cart->cart_contents );
                if ( $product_array ) {
                    $product_items = '';
                    $i             = 0;
                    foreach ( $product_array as $product ) {
                        ++$i;
                        $new_line       = ( $i > 1 ) ? '\n' : '';
                        $product_name   = $product['product_title'];
                        $item_quantity  = $product['quantity'];
                        $item_total     = $product['product_variation_price'];
                        $product_items .= $new_line . $i . '. ' . $product_name . ' x ' . $item_quantity . ' = ' . $currency . ' ' . $item_total;
                    }
                }
                if ( empty( $abandoned_day ) ) {
                    $abandoned_day = 24;
                }
                if ( $day_range >= $abandoned_day ) {
                    $replace_in_message   = array( '{{billing_first_name}}', '{{billing_last_name}}', '{{billing_email}}', '{{billing_phone}}', '{{product}}', '{{order_total}}', '{{currency}}' );
                    $replace_with_message = array( $name, $surname, $email, $phone, $product_items, $total, $currency );
                    $msg                  = str_replace( $replace_in_message, $replace_with_message, $config['followup_abandoned'] );
                    $img                  = $config['followup_abandoned_img'];
                    // Follow Up Abandoned Cart when status not shopping
                    $type         = $cart->type;
                    $time         = $cart->time;
                    $status       = $cart->status;
                    $cart_time    = strtotime( $time );
                    $date         = date_create( current_time( 'mysql', false ) );
                    $current_time = strtotime( date_format( $date, 'Y-m-d H:i:s' ) );
                    if ( $cart_time > $current_time - 60 * 60 && $item['type'] != 1 ) {
                        // Status is shopping
                        // Do nothing
                        // Source: woo-save-abandoned-carts/admin/class-cartbounty-admin-table.php:320
                    } else {
                        if ( ! empty( $phone ) ) {
                            $this->awp_wa_send_msg( $config, $phone, $msg, $img, '' );
                        }
                        $wpdb->update( $table_name, array( 'other_fields' => '1' ), array( 'id' => $id ) );
                    }
                }
            }
        }
    }
}

	// Add custom cron schedules
	public function followup_cron_schedule( $schedules ) {
		$schedules['every_six_hours']  = array(
			'interval' => 21600,
			'display'  => __( 'Every 6 hours' ),
		);
		$schedules['every_half_hours'] = array(
			'interval' => 1800,
			'display'  => __( 'Every 30 minutes' ),
		);
		return $schedules;
	}
	
	// Schedule cron events if not already scheduled
	private function schedule_cron_events() {
		if ( ! wp_next_scheduled( 'followup_cron_hook' ) ) {
			wp_schedule_event( time(), 'every_half_hours', 'followup_cron_hook' );
		}
		if ( ! wp_next_scheduled( 'followup_cron_hook_2' ) ) {
			wp_schedule_event( time(), 'daily', 'followup_cron_hook_2' );
		}
		if ( ! wp_next_scheduled( 'followup_cron_hook_3' ) ) {
			wp_schedule_event( time(), '+2 days', 'followup_cron_hook_3' );
		}
		if ( ! wp_next_scheduled( 'followup_cron_hook_4' ) ) {
			wp_schedule_event( time(), '+3 days', 'followup_cron_hook_4' );
		}
		if ( ! wp_next_scheduled( 'aftersales_cron_hook' ) ) {
			wp_schedule_event( time(), 'every_half_hours', 'aftersales_cron_hook' );
		}
		if ( ! wp_next_scheduled( 'aftersales_cron_hook_2' ) ) {
			wp_schedule_event( time(), 'daily', 'aftersales_cron_hook_2' );
		}
		if ( ! wp_next_scheduled( 'aftersales_cron_hook_3' ) ) {
			wp_schedule_event( time(), '+2 days', 'aftersales_cron_hook_4' );
		}
		if ( ! wp_next_scheduled( 'aftersales_cron_hook_3' ) ) {
			wp_schedule_event( time(), '+3 days', 'aftersales_cron_hook_4' );
		}
		if ( ! wp_next_scheduled( 'abandoned_cron_hook' ) ) {
			wp_schedule_event( time(), 'every_half_hours', 'abandoned_cron_hook' );
		}
	}
	
	public function awp_log_resend_message() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_text_field($_POST['message']);
    $img = sanitize_text_field($_POST['img']);
    $message_type = sanitize_text_field($_POST['message_type']);

    // Get the current user ID
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $full_name = $user->first_name . ' ' . $user->last_name;

    // Get the current datetime
    $current_datetime = date(get_option('date_format') . ' ' . get_option('time_format'));

    // Generate a new record number
    $logger = new awp_logger();
    $log_content = $logger->get_log_file('awpsend');
    $record_number = substr_count(trim($log_content), '<tr>') + 1;

    // Create a new log entry
    $logger->add(
        'awpsend',
        '<tr><td>' . esc_html($record_number) . '</td>' . // Record number
        '<td><a href="' . esc_url(get_edit_user_link($user_id)) . '">' . esc_html($user_id) . '</a></td>' .
        '<td>N/A</td>' . // No Order ID for a resend
        '<td>' . esc_html($full_name) . '</td>' .
        '<td>' . esc_html($current_datetime) . '</td>' .
        '<td class="log-phone">' . esc_html($phone) . '</td>' .
        '<td class="log-msg"><div>' . esc_html($message) . '</div></td>' .
        '<td class="log-img">' . esc_html($img) . '</td>' .
        '<td>' . esc_html($message_type) . '</td>' .
        '<td><a href="#" class="plugin-status-link status-success" data-popup="{}">Success</a></td>' .
        '<td></td>' .
        '</tr>'
    );

    wp_send_json_success();
}

}

// Register the AJAX handler for clearing selected logs
add_action('wp_ajax_clear_selected_logs', function() {
    // Ensure the user has the required capability to manage options
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    // Check if any logs were selected
    if (!isset($_POST['logs']) || empty($_POST['logs'])) {
        wp_send_json_error('No logs selected.');
    }

    // Sanitize and process the selected log IDs
    $log_ids = array_map('intval', $_POST['logs']);
    $logger = new awp_logger();

    // Call the method to clear the selected logs
    $logger->clear_selected_logs($log_ids);

    // Return a success response
    wp_send_json_success();
});

?>
