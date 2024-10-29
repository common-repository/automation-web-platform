jQuery(function ($) {
    // Use localized script data for token and instance
    let plugin_url = wwo.plugin_url;
    const checkInterval = 15 * 60 * 1000; // 15 minutes

    function checkConnectionStatus(instanceId, token, $status) {
        $.getJSON(`https://app.wawp.net/api/reconnect?instance_id=${instanceId}&access_token=${token}`)
            .done(function (data) {
                let statusText = '';
                let statusClass = '';
                if (data.status === 'success') {
                    statusText = wwo.translations.phoneOnline;
                    statusClass = 'status-success';
                } else {
                    let errorMessage = data.message === 'Access token does not exist' ? wwo.translations.accessTokenError :
                        data.message === 'Instance ID Invalidated' ? wwo.translations.instanceIdInvalidated :
                        data.message === 'Access token is required' ? wwo.translations.accessTokenRequired : data.message;
                    statusText = errorMessage;
                    statusClass = 'status-error';
                }

                // Update the status appearance
                $status.find('.status-text').text(statusText);
                $status.removeClass('status-success status-error').addClass(statusClass);
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error("Error: ", textStatus, errorThrown);
                $status.find('.status-text').text(`${wwo.translations.error}: ${textStatus}`);
                $status.removeClass('status-success').addClass('status-error');
            });
    }

    function initializeConnectionStatusCheck() {
        $('.instance-control').each(function () {
            let $this = $(this);
            let instanceId = $this.data('instance-id');
            let token = $this.data('access-token');
            let $status = $this.find('.connection-status');

            // Initial check when the page loads
            checkConnectionStatus(instanceId, token, $status);

            // Set up interval to check status every 15 minutes
            setInterval(function () {
                checkConnectionStatus(instanceId, token, $status);
            }, checkInterval);
        });
    }

    $(document).ready(function () {
        initializeConnectionStatusCheck();
    });

    $('.ins-action').click(function (e) {
        e.preventDefault();
        let $this = $(this);
        let actionData = $this.data('action');
        let controlPopup = '';

        let instance = $this.closest('.instance-control').data('instance-id');
        let token = $this.closest('.instance-control').data('access-token');

        function displayResult(status, successMessage, errorMessage) {
            let resultHTML = status === 'success' ?
                `<div class="success-container">
                    <img src="${plugin_url}/assets/img/success.gif" alt="Success">
                    <div class="success-message">${successMessage}</div>
                </div>` :
                `<div class="error-container">
                    <img src="${plugin_url}/assets/img/error.gif" alt="Error">
                    <div class="error-message">${errorMessage}</div>
                </div>`;
            $('#control-modal').find('.ins-results').html(resultHTML);
        }

        function handleApiResponse(data) {
            console.log("API response: ", data);
            if (data.status === 'success') {
                displayResult('success', wwo.translations.phoneOnline, '');
            } else {
                let errorMessage = data.message === 'Access token does not exist' ? wwo.translations.accessTokenError :
                    data.message === 'Instance ID Invalidated' ? wwo.translations.instanceIdInvalidated :
                    data.message === 'Access token is required' ? wwo.translations.accessTokenRequired : data.message;
                displayResult('error', '', errorMessage);
            }
        }

        if (actionData === 'status') {
            controlPopup += `<h6 class="modal-type">${wwo.translations.connectionStatus}</h6>`;
            controlPopup += `<div class="ins-results"><div class="loader"></div><p class="loader-message">${wwo.translations.testInProgress}</p></div>`;
            $('#control-modal').find('.modal-body').html(controlPopup);
            $('#control-modal').modal('show');
            $.getJSON(`https://app.wawp.net/api/reconnect?instance_id=${instance}&access_token=${token}`)
                .done(handleApiResponse)
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.error("Error: ", textStatus, errorThrown);
                    displayResult('error', '', `${wwo.translations.error}: ${textStatus}`);
                });
        }

        if (actionData === 'connectionButtons') {
            controlPopup += `<h6 class="modal-type">${wwo.translations.messagesendingstatus}</h6>`;
            controlPopup += `<div class="ins-results"><div class="loader"></div><p class="loader-message">${wwo.translations.testInProgress}</p></div>`;
            $('#control-modal').find('.modal-body').html(controlPopup);
            $('#control-modal').modal('show');
            $.getJSON(`https://app.wawp.net/api/send?instance_id=${instance}&access_token=${token}&number=447441429009&type=text&message=Wawp+Notification+work`)
                .done(handleApiResponse)
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.error("Error: ", textStatus, errorThrown);
                    displayResult('error', '', `${wwo.translations.error}: ${textStatus}`);
                });
        }

        if (actionData === 'generateQRCode') {
            controlPopup += `<h6 class="modal-type">${wwo.translations.generateQRCode}</h6>`;
            controlPopup += `<div class="ins-results"><div class="loader"></div><p class="loader-message">${wwo.translations.testInProgress}</p></div>`;
            $('#control-modal').find('.modal-body').html(controlPopup);
            $('#control-modal').modal('show');
            $.getJSON(`https://api.wawp.net/get_qrcode/?access_token=${token}&instance_id=${instance}`)
                .done(function (data) {
                    console.log("API response: ", data);
                    if (data.status === 'success') {
                        let resultHTML = `
                            <div class="success-container">
                                <img src="${data.qr_code_url}" alt="QR Code">
                                <div class="success-message">${wwo.translations.messageSent}</div>
                            </div>`;
                        $('#control-modal').find('.ins-results').html(resultHTML);
                    } else {
                        let errorMessage = data.message === 'Access token does not exist' ? wwo.translations.accessTokenError :
                            data.message === 'Instance ID Invalidated' ? wwo.translations.instanceIdInvalidated :
                            data.message === 'Access token is required' ? wwo.translations.accessTokenRequired : data.message;
                        displayResult('error', '', errorMessage);
                    }
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.error("Error: ", textStatus, errorThrown);
                    displayResult('error', '', `${wwo.translations.error}: ${textStatus}`);
                });
        }
    });

    $('#control-modal').on("click", '#ins-btn', function (e) {
        let $this = $(this);
        $this.parent().hide();
        $this.parents('.modal').find('.ins-results').html('<div class="loader"></div><p class="loader-message">Test in progress..</p>');
    });
});
