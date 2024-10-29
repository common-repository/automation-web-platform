<?php
/**
 * Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform.
 */

if ( $_POST ) {
	$_settings                         = get_option( 'wwo_settings' );
	$_POST['general']['awp_validated'] = $_settings['general']['awp_validated'];
	update_option( 'wwo_settings', $_POST );
}

$settings     = get_option( 'wwo_settings' );
$general_tab  = '';
$general_show = '';
$general_tab  = 'active';
$general_show = '';
?>

<div id="awp-wrap" class="wrap">
			<div class="form-wrapper">
			<div class="awp-tab-wrapper">
					<ul class="nav-tab-wrapper woo-nav-tab-wrapper">
						<li class="nav-tab list-group-item m-0 pointer <?php echo $general_tab; ?> " data-card="login"><?php esc_html_e( 'Login', 'awp' ); ?></li>
						
						<li class="nav-tab list-group-item m-0 pointer" data-card="register"><?php esc_html_e( 'Register', 'awp' ); ?></li>
						
						
					</ul>
			<div class="wp-tab-panels">
				<form action="" method="POST" id="wwo_settings" class="<?php echo $general_show; ?>">
					<?php
					require_once 'settings/general.php';
					require_once 'settings/login.php';
					require_once 'settings/register.php';
					?>
					<button type="submit" class="button-primarywa submit"><?php esc_html_e( 'Save Settings', 'awp' ); ?></button>
				</form>
			</div>

			</div>
		</div>
</div>

		<?php



