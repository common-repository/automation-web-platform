<div class="otp-card setting-card d-none">

	<div class="heading-bar credential">
		
		<div class="access-title">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="title-icon"><path d="M17 14H12.6586C11.8349 16.3304 9.61244 18 7 18C3.68629 18 1 15.3137 1 12C1 8.68629 3.68629 6 7 6C9.61244 6 11.8349 7.66962 12.6586 10H23V14H21V18H17V14ZM7 14C8.10457 14 9 13.1046 9 12C9 10.8954 8.10457 10 7 10C5.89543 10 5 10.8954 5 12C5 13.1046 5.89543 14 7 14Z"></path></svg>
			<span><?php esc_html_e( 'Access Keys', 'awp' ); ?></span>
		</div>
		<p><span>Get your access token and instance ID on </span>
		<a href="https://app.wawp.net/whatsapp_profile" class="" target="_blank">https://app.wawp.net</a>
		</p>
	</div>
	<div class="fields">
		<div class="field">
			<label for="instance_id" class="fw-bold"><?php esc_html_e( 'Instance ID', 'awp' ); ?></label>
			<input type="text" name="general[instance_id]" id="instance_id" class="regular-text data" placeholder="<?php esc_html_e( 'Your Instance ID', 'awp' ); ?>" value="<?php echo $settings['general']['instance_id'] ?? ''; ?>" required>
		</div>
		<div class="field">
			<label for="access_token" class="fw-bold"><?php esc_html_e( 'Access Token', 'awp' ); ?></label>
			<input type="text" name="general[access_token]" id="access_token" class="regular-text data" placeholder="<?php esc_html_e( 'Your Access Token', 'awp' ); ?>" value="<?php echo $settings['general']['access_token'] ?? ''; ?>" required>
		</div>
	   
	</div>
</div>



<div class="otp-card setting-card d-none">
	<div class="heading-bar credential">
		<div class="access-title">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="title-icon"><path d="m2.344 15.271 2 3.46a1 1 0 0 0 1.366.365l1.396-.806c.58.457 1.221.832 1.895 1.112V21a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-1.598a8.094 8.094 0 0 0 1.895-1.112l1.396.806c.477.275 1.091.11 1.366-.365l2-3.46a1.004 1.004 0 0 0-.365-1.366l-1.372-.793a7.683 7.683 0 0 0-.002-2.224l1.372-.793c.476-.275.641-.89.365-1.366l-2-3.46a1 1 0 0 0-1.366-.365l-1.396.806A8.034 8.034 0 0 0 15 4.598V3a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v1.598A8.094 8.094 0 0 0 7.105 5.71L5.71 4.904a.999.999 0 0 0-1.366.365l-2 3.46a1.004 1.004 0 0 0 .365 1.366l1.372.793a7.683 7.683 0 0 0 0 2.224l-1.372.793c-.476.275-.641.89-.365 1.366zM12 8c2.206 0 4 1.794 4 4s-1.794 4-4 4-4-1.794-4-4 1.794-4 4-4z"></path></svg>
			<span><?php esc_html_e( 'General', 'awp' ); ?></span>
		</div>
	</div>
	<div class="checks">
		<div class="field">
			<div class="form-check form-switch d-flex align-items-center">
				<input class="form-check-input" type="checkbox" role="switch" name="general[active_login]" id="login_active" <?php echo ( isset( $settings['general']['active_login'] ) && $settings['general']['active_login'] == 'on' ) ? 'checked' : ''; ?>>
				<label class="form-check-label" for="login_active"><?php esc_html_e( 'Activate login with WhatsApp', 'awp' ); ?></label>
			</div>
		</div>
		<div class="field">
			<div class="form-check form-switch d-flex align-items-center">
				<input class="form-check-input" type="checkbox" role="switch" name="general[active_register]" id="active_register" <?php echo ( isset( $settings['general']['active_register'] ) && $settings['general']['active_register'] == 'on' ) ? 'checked' : ''; ?>>
				<label class="form-check-label" for="active_register"><?php esc_html_e( 'Activate validate register with WhatsApp', 'awp' ); ?></label>
			</div>
		</div>
	</div>

</div>
