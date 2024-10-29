<?php
/**
 * Custom login form template for WooCommerce.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login-only.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package AutomationWebPlatform
 * @version 4.1.0
 */

defined( 'ABSPATH' ) || exit;

// Display any notices if present.
wc_print_notices();

do_action( 'woocommerce_before_customer_login_form' ); ?>

<div class="u-columns col2-set" id="wawp_login">

	<div class="awp-content">

		<h2 class="login-title"><?php esc_html_e( 'Login', 'woocommerce' ); ?></h2>

		<form method="post" class="wawp woocommerce-form woocommerce-form-login login">

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username" class="awp-label"><?php esc_html_e( 'Email or username', 'woocommerce' ); ?></label>
				<input type="text" class="wawp woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['username'] ) ) ) : ''; ?>" />
			</div>
			<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password" class="awp-label"><?php esc_html_e( 'Password', 'woocommerce' ); ?></label>
				<input class="wawp woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
			</div>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<p class="form-row">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Login', 'woocommerce' ); ?>"><?php esc_html_e( 'Login', 'woocommerce' ); ?></button>
				<label class="awp woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
				</label>
			</p>
			<div class="awp-label woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
			</div>

			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>

	</div>

</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>

<?php
// Form processing logic.
if ( isset( $_POST['login'] ) ) {

	// Verify the nonce field before processing the form.
	$nonce = isset( $_POST['woocommerce-login-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-login-nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'woocommerce-login' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'woocommerce' ) );
	}

	// Process the form data if nonce is verified.
	$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
	$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

	// Additional form processing logic goes here.
}
?>
