
function validate_awp() {
    var token = $('#access_token');
    var instance = $('#instance_id');
    var $thisbutton = $('button.validate');

    $('.validation-wrapper').remove();
    $('.validate-message').remove();

    $thisbutton.prop('disabled', true);
    $.ajax({
        type: 'post',
        url: wwo.ajaxurl,
        data: {
            action: 'awp_validate',
            token: token.val(),
            instance: instance.val()
        },
        success: function (response) {
            // console.log(response);
            if(response.data.show_qr){
                $thisbutton.before(`
                <div class="validation-wrapper d-flex flex-column justify-content-center align-items-center">
                    <img class="" src="`+response.data.image+`" width="250">
                    <p class="text-danger">Your device has not connected. Scan QR Code to connect.</p>
                    <p class="text-dark fw-bold">Make sure your camera pointing to QR Code for at least 8 seconds to connected properly.</p>
                    <p id="countdown">20</p>
                </div>
                `);
                countdown_refresh();
            }else{
                $thisbutton.prop('disabled', false);
                $thisbutton.before('<p class="validate-message text-success">Your device has been connected</p>');
                $thisbutton.after(`
                    <p class="text-dark">Click reboot button below if your WhatsApp still not connected properly</p>
                    <button type="button" class="btn btn-sm btn-dark reboot">Reboot Connection</button>
                `);
                setTimeout(() => {
                    $('.validation-wrapper').remove();
                    $('.validate-message').remove();
                }, 3000);
                $thisbutton.remove();
            }
        }
    });
}

function reboot_connection_awp() {
    var token = $('#access_token');
    var instance = $('#instance_id');
    var $thisbutton = $('button.reboot');

    $('.validation-wrapper').remove();
    $thisbutton.prop('disabled', true);
    $.ajax({
        type: 'post',
        url: wwo.ajaxurl,
        data: {
            action: 'awp_reboot',
            token: token.val(),
            instance: instance.val()
        },
        success: function (response) {
            // console.log(response);
            if(response.data.reboot){
                $thisbutton.before('<p class="validate-message text-success">Reboot connection success</p>');
            }else{
                $thisbutton.before('<p class="validate-message text-danger">Reboot connection failed. '+response.data.message+'</p>');
            }
            $thisbutton.after(`
                <button type="button" class="btn btn-sm btn-dark validate">Validate</button>
            `);
            $thisbutton.remove();
            setTimeout(() => {
                $('.validation-wrapper').remove();
                $('.validate-message').remove();
            }, 3000);
        }
    });
}


$(document).on('click', 'button.validate', function(){
    var token = $('#access_token');
    var instance = $('#instance_id');
    var validate = true;
    var $thisbutton = $(this);

    $('.validate-message').remove();
    
    if(!token.val()){
        validate = false;
        token.parent().append('<p class="validate-message mb-0 text-danger">Provide your Access Token before validating.</p>');
    }
    if(!token.val()){
        validate = false;
        instance.parent().append('<p class="validate-message mb-0 text-danger">Provide your Instance ID before validating.</p>');
    }
    if(validate){
        validate_awp();
    }
});

$(document).on('click', 'button.reboot', function(){
    var token = $('#access_token');
    var instance = $('#instance_id');
    var validate = true;
    var $thisbutton = $(this);

    $('.validate-message').remove();
    
    if(!token.val()){
        validate = false;
        token.parent().append('<p class="validate-message mb-0">Provide your Access Token before rebooting connection.</p>');
    }
    if(!token.val()){
        validate = false;
        token.parent().append('<p class="validate-message mb-0">Provide your Instance ID before rebooting connection.</p>');
    }
    if(validate){
        reboot_connection_awp();
    }
})
