jQuery(document).ready(function($) {

    $(document).on('click', '.awp_login_btn[data-button=login_w_wa]', function(){
        $('.woocommerce-form.login .form-row:not(.awp):not(.awp-login-otp-submit)').slideUp();
        $('.woocommerce-form.login .form-row.awp').slideDown();
        $(this).hide();
        $('.awp_login_btn[data-button=login_w_email]').show();
        $('.woocommerce-form-login__submit[type=submit]').addClass('awp-ajax-login');
        $('button.awp-ajax-login').prop('disabled', true);
        if($('#login_otp').val() > 0){
            $('button.awp-ajax-login').prop('disabled', false);
        }
        if($('#login_your_whatsapp').val().length > 0){
            $('.woocommerce-form.login .awp-input').slideDown();
        }
    });

    $(document).on('click', '.awp_login_btn[data-button=login_w_email]', function(){
        $('.woocommerce-form.login .form-row:not(.awp-login-otp-submit)').slideDown();
        $('.woocommerce-form.login .form-row.awp').slideUp();
        $('.woocommerce-form.login .awp-input').hide();
        $(this).hide();
        $('.awp_login_btn[data-button=login_w_wa]').show();
        $('button.awp-ajax-login').prop('disabled', false);
        $('.woocommerce-form-login__submit[type=submit]').removeClass('awp-ajax-login');
    });

});
