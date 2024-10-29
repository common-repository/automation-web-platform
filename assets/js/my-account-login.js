jQuery(document).ready(function($){
    
    $('#login_otp').on('input', function(){
        if($(this).val().length > 0){
            $('button.awp-ajax-login').prop('disabled', false);
        }else{
            $('button.awp-ajax-login').prop('disabled', true);
        }
    });

    $('#login_your_whatsapp').on('input', function(){
        if($(this).val().length == 0){
            $('.woocommerce-form.login .awp-input').slideUp();
        }
    });
    
    $(document).on('click', '.send_login_otp', function(e){
        e.preventDefault();

        $('.login-response').remove();

        var code = $('#login_country_code').val();
        var phone = $('#login_your_whatsapp').val();
        var $thisbutton = $(this);

        $thisbutton.prop('disabled', true);

        $.ajax({
            type: 'post',
            url: wwo.ajaxurl,
            data: {
                action: 'awp_send_login_otp',
                code: code,
                phone: phone
            },
            success: function (res) {
                // console.log(res);
                $thisbutton.prop('disabled', false);
                if(res.success){
                    $('.awp-ajax-login').attr('data-user', res.data.user_id);
                    $('.woocommerce-form.login .awp-input').slideDown();
                }
                $thisbutton.parents('form').append('<ul class="login-response">'+res.data.message+'</ul>');
                setTimeout(()=>{
                    $('.login-response').remove();
                }, 5000);
            }
        });
    });

    $(document).on('click', '.awp-ajax-login', function(e){
        e.preventDefault();

        $('.login-response').remove();

        var code = $('#login_otp').val();
        var $thisbutton = $(this);

        $thisbutton.prop('disabled', true);

        $.ajax({
            type: 'post',
            url: wwo.ajaxurl,
            data: {
                action: 'awp_login',
                phone: $('#register_your_whatsapp').val(),
                code: code,
                user: $thisbutton.data('user'),
                remember: $('#awp_rememberme:checked').val(),
                nonce: $('#awp-login-nonce').val(),
                referer: $('[name=_wp_http_referer').val()
            },
            success: function (res) {
                // console.log(res);
                $thisbutton.prop('disabled', false);
                $thisbutton.parents('form').append('<ul class="login-response">'+res.data.message+'</ul>');
                if(res.success){
                    setTimeout(()=>{
                        if(res.data.action == 'reload'){
                            window.location.reload();
                        }else{
                            window.location.href = res.data.action;
                        }
                    }, 2500);
                }else{
                    setTimeout(()=>{
                        $('.login-response').remove();
                    }, 5000);
                }
            }
        });
    });

});
