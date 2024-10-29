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

/**
 * Main settings class for the Automation Web Platform Plugin.
 */
class AWP_Mainset {

	/**
	 * Constructor.
	 * Initializes the plugin by setting up hooks.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Schedule the phone status check cron job
		add_action( 'awp_check_phone_status_cron', array( $this, 'check_phone_status' ) );
		add_action( 'wp', array( $this, 'schedule_phone_status_check' ) );
		register_deactivation_hook( __FILE__, array( $this, 'unschedule_phone_status_check' ) );
	    add_filter( 'cron_schedules', array( $this, 'add_fifteen_minute_cron_interval' ) );
	}

	/**
	 * Enqueue admin-specific assets.
	 */
	public function enqueue_admin_assets() {
		global $pagenow;
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'awp-settings' === $_GET['page'] ) {
			wp_enqueue_style( 'bootstrap-css', plugins_url( 'assets/css/resources/bootstrap.min.css', __FILE__ ), array(), '5.2.3' );
			wp_enqueue_script( 'bootstrap-js', plugins_url( 'assets/js/resources/bootstrap.min.js', __FILE__ ), array( 'jquery' ), '5.2.3', true );

			if ( is_rtl() ) {
				wp_enqueue_style( 'awp-admin-rtl-css', plugins_url( 'assets/css/awp-admin-rtl-style.css', __FILE__ ), array(), '1.1.4' );
			}
			wp_enqueue_style( 'admin', plugins_url( 'assets/css/awp-admin-style.css', __FILE__ ), array(), '1.0.0' );
			wp_enqueue_script( 'awp-admin-js', plugins_url( 'assets/js/awp-admin-set.js', __FILE__ ), array( 'jquery', 'bootstrap-js' ), '1.1.4', true );

			$script_data_array = array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'admin_nonce'  => wp_create_nonce( 'wwo_nonce' ),
				'plugin_url'   => plugins_url( '/', __FILE__ ),
				'translations' => array(
					'connectionStatus'      => __( 'Connection Status', 'awp' ),
					'messagesendingstatus'  => __( 'Message delivery statuses', 'awp' ),
					'testInProgress'        => __( 'Test in progress..', 'awp' ),
					'phoneOnline'           => __( 'Phone Status: Online', 'awp' ),
					'phoneOffline'          => __( 'Phone Status: Offline', 'awp' ),
					'messageSent'           => __( "You've sent a test message to (Wawp), check your WhatsApp.", 'awp' ),
					'accessTokenError'      => __( 'Access token does not exist', 'awp' ),
					'instanceIdInvalidated' => __( 'Instance ID Invalidated', 'awp' ),
					'generateQRCode'        => __( 'Generate QR Code', 'awp' ),
					'error'                 => __( 'Error', 'awp' ),
				),
			);
			wp_localize_script( 'awp-admin-js', 'wwo', $script_data_array );
		}
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		$hook = add_menu_page(
			'AWP Settings',
			'AWP Settings',
			'manage_options',
			'awp-settings',
			array( $this, 'render_settings_page' )
		);
		remove_menu_page( 'awp-settings' ); // Remove this line if you don't want to remove the menu item.
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings  = get_option( 'wwo_settings' );
		$instances = get_option( 'awp_instances' );
		$status    = get_option( 'awp_phone_status', 'offline' ); // Get the stored phone status.

		?>
		<div class="wrap" id="awp-wrap">
			<div class="form-wrapper">
				<div class="awp-setting-info">
					<h3 class="card-title"><?php esc_html_e( 'How to connect your WhatsApp:', 'awp' ); ?></h3>
					<ol class="steps-list">
						<li><span><?php esc_html_e( 'Create a free account at', 'awp' ); ?> <a href="https://wawp.net" target="_blank">Wawp.net.</a></span></li>
						<li><?php esc_html_e( 'Link your WhatsApp using a QR code.', 'awp' ); ?></li>
						<li><?php esc_html_e( 'Copy and paste instance id and access token.', 'awp' ); ?></li>
						<li><?php esc_html_e( 'Click "Save", then perform a connection test.', 'awp' ); ?></li>
					</ol>
					<a href="https://wawp.net/signup" class="button button-secondarywa" target="_blank"><?php esc_html_e( 'Create free account', 'awp' ); ?></a>
					<p><strong><?php esc_html_e( 'Note:', 'awp' ); ?></strong><br> <?php esc_html_e( 'You can link the same number for notifications and OTP.', 'awp' ); ?></p>
				</div>
				<div class="awp-settings-card">
					<div class="box">
						<h2 class="card-title"><?php esc_html_e( 'Notifications number', 'awp' ); ?></h2>
						<p class="hint-desc"><?php esc_html_e( 'Connect your WhatsApp number to send order updates, follow-up messages, and abandoned cart notifications.', 'awp' ); ?></p>
						<hr class="divi">
					</div>
					<form method="post" action="options.php" class="setting-fields">
						<?php
						settings_fields( 'awp_storage_instances' );
						?>
						<div class="setting-labels">
							<div class="awp-field">
								<label for="instance_id_notifications" class="awp-label"><?php esc_html_e( 'Instance ID', 'awp' ); ?></label>
								<input type="text" name="awp_instances[instance_id]" id="instance_id_notifications" class="awp-input" placeholder="<?php esc_html_e( 'Your Instance ID', 'awp' ); ?>" value="<?php echo isset( $instances['instance_id'] ) ? esc_attr( $instances['instance_id'] ) : ''; ?>" required>
							</div>
							<div class="awp-field">
								<label for="access_token_notifications" class="awp-label"><?php esc_html_e( 'Access Token', 'awp' ); ?></label>
								<input type="text" name="awp_instances[access_token]" id="access_token_notifications" class="awp-input" placeholder="<?php esc_html_e( 'Your Access Token', 'awp' ); ?>" value="<?php echo isset( $instances['access_token'] ) ? esc_attr( $instances['access_token'] ) : ''; ?>" required>
							</div>
						</div>
						<?php submit_button( esc_html__( 'Save', 'awp' ) ); ?>
					</form>
					<div class="instance-control" data-instance-id="<?php echo esc_attr( $instances['instance_id'] ); ?>" data-access-token="<?php echo esc_attr( $instances['access_token'] ); ?>">
						<hr class="divi">
						<div class="btn-box">
							<div class="connection-status">
							<img src="<?php echo plugins_url( 'assets/img/phone-icon.png', __FILE__ ); ?>" alt="Phone Icon">
							<span class="status-text"><?php echo esc_html( $status === 'online' ? __( 'Phone Status: Online', 'awp' ) : __( 'Phone Status: Offline', 'awp' ) ); ?></span>
						</div>
							<a href="#" class="button ins-action" data-action="connectionButtons"><?php esc_html_e( 'Connection test', 'awp' ); ?></a>
						</div>
						
					</div>
				</div>
				<div class="awp-settings-card">
					<div class="box">
						<h2 class="card-title"><?php esc_html_e( 'OTP number', 'awp' ); ?></h2>
						<p class="hint-desc"><?php esc_html_e( 'Connect your WhatsApp number to send a One-Time Password (OTP) for login, register and checkout verification.', 'awp' ); ?></p>
						<hr class="divi">
					</div>
					<form method="post" action="options.php">
						<?php
						settings_fields( 'awp_settings_group' );
						?>
						<div class="awp-field">
							<label for="instance_id" class="awp-label"><?php esc_html_e( 'Instance ID', 'awp' ); ?></label>
							<input type="text" name="wwo_settings[general][instance_id]" id="instance_id" class="awp-input" placeholder="<?php esc_html_e( 'Your Instance ID', 'awp' ); ?>" value="<?php echo isset( $settings['general']['instance_id'] ) ? esc_attr( $settings['general']['instance_id'] ) : ''; ?>" required>
						</div>
						<div class="awp-field">
							<label for="access_token" class="awp-label"><?php esc_html_e( 'Access Token', 'awp' ); ?></label>
							<input type="text" name="wwo_settings[general][access_token]" id="access_token" class="awp-input" placeholder="<?php esc_html_e( 'Your Access Token', 'awp' ); ?>" value="<?php echo isset( $settings['general']['access_token'] ) ? esc_attr( $settings['general']['access_token'] ) : ''; ?>" required>
						</div>
						<div class="toggles">
							<div class="awp-field">
								<div class="form-check form-switch d-flex align-items-center">
									<input class="form-check-input awp-toggle-switch" type="checkbox" role="switch" name="wwo_settings[general][active_login]" id="login_active" <?php echo ( isset( $settings['general']['active_login'] ) && 'on' === $settings['general']['active_login'] ) ? 'checked' : ''; ?>>
									<label class="form-check-label" for="login_active"><?php esc_html_e( 'Enable OTP login', 'awp' ); ?></label>
								</div>
							</div>
							<div class="awp-field">
								<div class="form-check form-switch d-flex align-items-center">
									<input class="form-check-input awp-toggle-switch" type="checkbox" role="switch" name="wwo_settings[general][active_register]" id="active_register" <?php echo ( isset( $settings['general']['active_register'] ) && 'on' === $settings['general']['active_register'] ) ? 'checked' : ''; ?>>
									<label class="form-check-label" for="active_register"><?php esc_html_e( 'Enable signup verification', 'awp' ); ?></label>
								</div>
							</div>
						</div>
						<?php submit_button( esc_html__( 'Save', 'awp' ) ); ?>
					</form>
					<div class="instance-control" data-instance-id="<?php echo esc_attr( $settings['general']['instance_id'] ); ?>" data-access-token="<?php echo esc_attr( $settings['general']['access_token'] ); ?>">
						<hr class="divi">
						<div class="btn-box">
							<div class="connection-status">
							<img src="<?php echo plugins_url( 'assets/img/phone-icon.png', __FILE__ ); ?>" alt="Phone Icon">
							<span class="status-text"><?php echo esc_html( $status === 'online' ? __( 'Phone Status: Online', 'awp' ) : __( 'Phone Status: Offline', 'awp' ) ); ?></span>
						</div>
							<a href="#" class="button ins-action" data-action="connectionButtons"><?php esc_html_e( 'Connection test', 'awp' ); ?></a>
						</div>
						
					</div>
				</div>
			</div>
			<div id="control-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="exampleModalLabel"><?php esc_html_e( 'Wawp testing', 'awp' ); ?></h5>
							<img src="<?php echo esc_url( plugins_url( '/assets/img/wawp-Logox2.png', __FILE__ ) ); ?>" alt="Wawp Logo" class="wawp-logo" style="height: 24px; margin: 0 8px;">
						</div>
						<div class="modal-body">
							<!-- Dynamic content will be injected here -->
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e( 'Done', 'awp' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'awp_settings_group', 'wwo_settings' );
		register_setting( 'awp_storage_instances', 'awp_instances' );

		add_settings_section(
			'awp_access_settings_section',
			'',
			null,
			'awp-settings-access'
		);

		add_settings_section(
			'awp_notifications_settings_section',
			'',
			null,
			'awp-settings-notifications'
		);

		add_settings_field(
			'awp_instance_id',
			'Instance ID',
			array( $this, 'instance_id_callback' ),
			'awp-settings-access',
			'awp_access_settings_section'
		);

		add_settings_field(
			'awp_access_token',
			'Access Token',
			array( $this, 'access_token_callback' ),
			'awp-settings-access',
			'awp_access_settings_section'
		);

		add_settings_field(
			'awp_activate_login',
			'Activate login with WhatsApp',
			array( $this, 'activate_login_callback' ),
			'awp-settings-access',
			'awp_access_settings_section'
		);

		add_settings_field(
			'awp_activate_register',
			'Activate validate register with WhatsApp',
			array( $this, 'activate_register_callback' ),
			'awp-settings-access',
			'awp_access_settings_section'
		);

		add_settings_field(
			'awp_instance_id_notifications',
			'Instance ID',
			array( $this, 'instance_id_notifications_callback' ),
			'awp-settings-notifications',
			'awp_notifications_settings_section'
		);

		add_settings_field(
			'awp_access_token_notifications',
			'Access Token',
			array( $this, 'access_token_notifications_callback' ),
			'awp-settings-notifications',
			'awp_notifications_settings_section'
		);
	}

	/**
	 * Callback for the instance ID field.
	 */
	public function instance_id_callback() {
		$settings = get_option( 'wwo_settings' );
		?>
		<div class="awp-field">
			<label for="instance_id" class="awp-label"><?php esc_html_e( 'Instance ID', 'awp' ); ?></label>
			<input type="text" name="wwo_settings[general][instance_id]" id="instance_id" class="awp-input" placeholder="<?php esc_html_e( 'Your Instance ID', 'awp' ); ?>" value="<?php echo isset( $settings['general']['instance_id'] ) ? esc_attr( $settings['general']['instance_id'] ) : ''; ?>" required>
		</div>
		<?php
	}

	/**
	 * Callback for the access token field.
	 */
	public function access_token_callback() {
		$settings = get_option( 'wwo_settings' );
		?>
		<div class="awp-field">
			<label for="access_token" class="awp-label"><?php esc_html_e( 'Access Token', 'awp' ); ?></label>
			<input type="text" name="wwo_settings[general][access_token]" id="access_token" class="awp-input" placeholder="<?php esc_html_e( 'Your Access Token', 'awp' ); ?>" value="<?php echo isset( $settings['general']['access_token'] ) ? esc_attr( $settings['general']['access_token'] ) : ''; ?>" required>
		</div>
		<?php
	}

	/**
	 * Callback for the activate login switch.
	 */
	public function activate_login_callback() {
		$settings = get_option( 'wwo_settings' );
		?>
		<div class="awp-field">
			<div class="form-check form-switch d-flex align-items-center">
				<input class="form-check-input awp-toggle-switch" type="checkbox" role="switch" name="wwo_settings[general][active_login]" id="login_active" <?php echo ( isset( $settings['general']['active_login'] ) && 'on' === $settings['general']['active_login'] ) ? 'checked' : ''; ?>>
				<label class="form-check-label" for="login_active"><?php esc_html_e( 'Activate login with WhatsApp', 'awp' ); ?></label>
			</div>
		</div>
		<?php
	}

	/**
	 * Callback for the activate register switch.
	 */
	public function activate_register_callback() {
		$settings = get_option( 'wwo_settings' );
			?>
		<div class="awp-field">
			<div class="form-check form-switch d-flex align-items-center">
				<input class="form-check-input awp-toggle-switch" type="checkbox" role="switch" name="wwo_settings[general][active_register]" id="active_register" <?php echo ( isset( $settings['general']['active_register'] ) && 'on' === $settings['general']['active_register'] ) ? 'checked' : ''; ?>>
				<label class="form-check-label" for="active_register"><?php esc_html_e( 'Activate validate register with WhatsApp', 'awp' ); ?></label>
			</div>
		</div>
		<?php
	}

	/**
	 * Callback for the notifications instance ID field.
	 */
	public function instance_id_notifications_callback() {
		$instances = get_option( 'awp_instances' );
		?>
		<div class="awp-field">
			<label for="instance_id_notifications" class="awp-label"><?php esc_html_e( 'Instance ID', 'awp' ); ?></label>
			<input type="text" name="awp_instances[instance_id]" id="instance_id_notifications" class="awp-input" placeholder="<?php esc_html_e( 'Your Instance ID', 'awp' ); ?>" value="<?php echo isset( $instances['instance_id'] ) ? esc_attr( $instances['instance_id'] ) : ''; ?>" required>
		</div>
		<?php
	}

	/**
	 * Callback for the notifications access token field.
	 */
	public function access_token_notifications_callback() {
		$instances = get_option( 'awp_instances' );
		?>
		<div class="awp-field">
			<label for="access_token_notifications" class="awp-label"><?php esc_html_e( 'Access Token', 'awp' ); ?></label>
			<input type="text" name="awp_instances[access_token]" id="access_token_notifications" class="awp-input" placeholder="<?php esc_html_e( 'Your Access Token', 'awp' ); ?>" value="<?php echo isset( $instances['access_token'] ) ? esc_attr( $instances['access_token'] ) : ''; ?>" required>
		</div>
		<?php
	}

	/**
	 * Check the phone status via cron job.
	 */
	public function check_phone_status() {
		$instances = get_option( 'awp_instances' );

		if ( isset( $instances['instance_id'], $instances['access_token'] ) ) {
			$instance_id = esc_attr( $instances['instance_id'] );
			$access_token = esc_attr( $instances['access_token'] );

			$response = wp_remote_get( "https://app.wawp.net/api/reconnect?instance_id=$instance_id&access_token=$access_token" );

			if ( is_wp_error( $response ) ) {
				// Handle error here.
				error_log( 'Phone status check failed: ' . $response->get_error_message() );
			} else {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $data['status'] ) && $data['status'] === 'success' ) {
					// Update the status option in the database, or handle as needed.
					update_option( 'awp_phone_status', 'online' );
				} else {
					// Update the status option in the database, or handle as needed.
					update_option( 'awp_phone_status', 'offline' );
				}
			}
		}
	}

	/**
	 * Schedule the cron job for phone status check.
	 */
	public function schedule_phone_status_check() {
		if ( ! wp_next_scheduled( 'awp_check_phone_status_cron' ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', 'awp_check_phone_status_cron' );
		}
	}

	/**
	 * Add custom cron interval for 15 minutes.
	 */
	public function add_fifteen_minute_cron_interval( $schedules ) {
		$schedules['fifteen_minutes'] = array(
			'interval' => 15 * 60, // 15 minutes in seconds.
			'display'  => __( 'Every 15 Minutes' ),
		);
		return $schedules;
	}

	/**
	 * Unschedule the event upon plugin deactivation.
	 */
	public function unschedule_phone_status_check() {
		$timestamp = wp_next_scheduled( 'awp_check_phone_status_cron' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'awp_check_phone_status_cron' );
		}
	}

}

new AWP_Mainset();
