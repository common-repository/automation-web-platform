<?php
/**
 * Admin settings page HTML for Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap" id="awp-wrap">
	<div class="checkoutotp wp-tab-panels">
		<div class="notification-form english hint otp">
			<div class="hint-box">
				<label for="awp_notifications" class="hint-title">
					<?php esc_html_e( 'Checkout OTP Verification', 'awp' ); ?>
				</label>
				<p class="hint-desc">
					<?php esc_html_e( 'Verifies transactions with a one-time checkout code sent via WhatsApp.', 'awp' ); ?>
					<p><?php _e( 'Checkout shortcode', 'awp' ); ?> <code>[woocommerce_checkout]</code></p>
				</p>
			</div>
		</div>
		<div class="otp-card">
			<form action="options.php" method="post">
				<?php
				settings_fields( 'awp_options_group' );
				do_settings_sections( 'awp-settings' );
				submit_button( esc_html__( 'Save Settings', 'awp' ) );
				?>
			</form>
		</div>
	</div>
</div>

		<?php

