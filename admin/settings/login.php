<div class="notification-form english hint setting-card login-card">
	<div class="hint-box">
		<label for="awp_notifications" class="hint-title"><?php esc_html_e( 'Login with OTP', 'awp' ); ?></label>
		<p class="hint-desc"><?php esc_html_e( 'Ensures secure login with a quick one-time password sent via WhatsApp.', 'awp' ); ?></p>
		<p><?php _e( 'Login shortcode', 'awp' ); ?> <code>[wawp_account_login]</code></p>
	</div>
</div>



<div class="msg-setting">
	<div class="notification-form english otp-card setting-card login-card general-card">
		<div class="heading-bar">
			<label for="login_message" class="notification-title"><?php esc_html_e( 'Login OTP Message', 'awp' ); ?>
				<span class="tooltip-text"><?php esc_html_e( 'Sent when a customer logs in with their WhatsApp number.', 'awp' ); ?></span>
			</label>
		</div>
		<hr class="line">
		<div class="notification">
			<div class="form">
				<!-- Add textareas for English messages -->
				<textarea id="login_message" name="login[message]" cols="53" rows="5" class="otp_message" placeholder="<?php esc_html_e( 'Write your message...', 'awp' ); ?>"><?php
										echo esc_textarea( trim( $settings['login']['message'] ?? 'Hi {{name}},{{otp}} is your Login Generated OTP code. Do not share this code with others.' ) );?>
				</textarea>
				<p class="placeholders">
					<?php esc_html_e( 'Shortcodes: ', 'awp' ); ?>
					<code>{{name}}</code> <?php esc_html_e( 'Member name', 'awp' ); ?> — 
					<code>{{otp}}</code> <?php esc_html_e( 'Generated OTP code', 'awp' ); ?>
				</p>
			</div>
		</div>
	</div>


	
	
	<div class="notification-form english otp-card setting-card login-card general-card">
		<div class="heading-bar">
			<label for="login_message" class="notification-title"><?php esc_html_e( 'URL redirection', 'awp' ); ?>
				<span class="tooltip-text"><?php esc_html_e( ' ', 'awp' ); ?></span>
			</label>
			<p class="deactive-hint"><em><?php esc_html_e( 'leave blank to deactivate', 'awp' ); ?></em></p>
		</div>
		<hr class="line">
		<div class="form">
			<input type="text" name="login[url_redirection]" id="login_url_redirection" class="url_redirection regular-text" placeholder="https://" value="<?php echo $settings['login']['url_redirection'] ?? ''; ?>">
			<p class="mb-0 text-small text-muted"><?php esc_html_e( '* Redirection only work for WooCommerce native forms.', 'awp' ); ?></p>
		</div>
	</div>
</div>