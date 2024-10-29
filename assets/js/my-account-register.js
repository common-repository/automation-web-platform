jQuery(document).ready(function($){

    $('.woocommerce-form-register__submit').prop('disabled', true);

    $('#register_otp').on('input', function(){
        if($(this).val().length > 0){
            $('button.awp-ajax-register').prop('disabled', false);
        }else{
            $('button.awp-ajax-register').prop('disabled', true);
        }
    });
    
    $('#register_your_whatsapp').on('input', function(){
        if($(this).val().length == 0){
            $('.woocommerce-form.register .awp-input').slideUp();
        }
    });

    $(document).on('click', '.send_register_otp', function(e){
        e.preventDefault();

        $('.register-response').remove();


        var phone = $('#register_your_whatsapp').val();
        var $thisbutton = $(this);

        $thisbutton.prop('disabled', true);

        $.ajax({
            type: 'post',
            url: wwo.ajaxurl,
            data: {
                action: 'awp_send_register_otp',
                phone: phone
            },
            success: function (res) {
                // console.log(res);
                $thisbutton.prop('disabled', false);
                if(res.success){
                    $('.awp-ajax-register').attr('data-user', res.data.user_id);
                    $('.woocommerce-form.register .awp-input').slideDown();
                    $('.woocommerce-form-register__submit').addClass('awp-ajax-register');
                    $('button.awp-ajax-register').prop('disabled', false);
                }
                $thisbutton.parents('form').append('<ul class="register-response">'+res.data.message+'</ul>');
                setTimeout(()=>{
                    $('.register-response').remove();
                }, 5000);
            }
        });
    });

    $(document).on('click', '.awp-ajax-register', function(e){
        e.preventDefault();

        $('.register-response').remove();

        var code = $('#register_otp').val();
        var $thisbutton = $(this);

        $thisbutton.prop('disabled', true);

        $.ajax({
            type: 'post',
            url: wwo.ajaxurl,
            data: {
                action: 'awp_register',
                username: $('#reg_username').val(),
                email: $('#reg_email').val(),
                pass: $('#reg_password').val(),
                phone: $('#register_your_whatsapp').val(),
                code: code,
                nonce: $('#woocommerce-register-nonce').val(),
                referer: $('[name=_wp_http_referer').val()
            },
            success: function (res) {
                // console.log(res);
                $thisbutton.prop('disabled', false);
                $thisbutton.parents('form').append('<ul class="register-response">'+res.data.message+'</ul>');
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
                        $('.register-response').remove();
                    }, 5000);
                }
            }
        });
    });

});