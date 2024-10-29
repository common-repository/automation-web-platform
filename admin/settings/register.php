<div class="notification-form english hint setting-card register-card d-none">
	<div class="hint-box">
		<label for="awp_notifications" class="hint-title"><?php esc_html_e( 'Signup OTP Verification', 'awp' ); ?></label>
		<p class="hint-desc"><?php esc_html_e( 'Confirms new user registration with a one-time code sent via WhatsApp.', 'awp' ); ?></p>
			<p><?php _e( 'Register shortcode', 'awp' ); ?> <code>[wawp_account_register]</code></p>

	</div>
</div>

<div class="msg-setting">
	<div class="notification-form english otp-card setting-card register-card d-none">
		<div class="heading-bar">
			<label for="register_message" class="notification-title"><?php esc_html_e( 'Signup OTP Message', 'awp' ); ?>
				<span class="tooltip-text"><?php esc_html_e( 'Sent when a customer registers with their WhatsApp number.', 'awp' ); ?></span>
			</label>
		</div>
		<hr class="line">
		<div class="notification">
			<div class="form">
				<!-- Add textareas for  English messages -->
				<textarea id="register_message" name="register[message]" cols="53" rows="5" class="otp_message" placeholder="<?php esc_html_e( 'Write your message...', 'awp' ); ?>">
																																				<?php
																																				echo esc_textarea( trim( $settings['register']['message'] ?? 'Hi, {{otp}} is your confirmation code for Signup. Do not share this code with others.' ) );
																																				?>
		</textarea>
				<p class="placeholders">
					<?php esc_html_e( 'Shortcodes: ', 'awp' ); ?>
					<code>{{otp}}</code> <?php esc_html_e( 'Generated OTP code', 'awp' ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="notification-form english otp-card setting-card register-card d-none">
		<div class="heading-bar">
			<label for="register_url_redirection" class="notification-title"><?php esc_html_e( 'URL redirection', 'awp' ); ?>
				<span class="tooltip-text"><?php esc_html_e( ' ', 'awp' ); ?></span>
			</label>
			<p class="deactive-hint"><em><?php esc_html_e( 'leave blank to deactivate', 'awp' ); ?></em></p>
		</div>
		<hr class="line">
		<div class="form">
			<input type="text" name="register[url_redirection]" id="register_url_redirection" class="url_redirection regular-text" placeholder="https://" value="<?php echo $settings['register']['url_redirection'] ?? ''; ?>">
			<p class="mb-0 text-small text-muted"><?php esc_html_e( '* Redirection only work for WooCommerce native forms.', 'awp' ); ?></p>
		</div>
	</div>
</div>

	
	