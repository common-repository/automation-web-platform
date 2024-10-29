jQuery(document).ready(function($) {
    var input = document.querySelector('#awp_blocked_numbers');
    var tagify = new Tagify(input);

    tagify.on('change', function() {
        var blockedNumbers = tagify.value.map(tag => tag.value).join(',');

        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'save_blocked_numbers',
                security: ajax_object.nonce,
                blocked_numbers: blockedNumbers
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Failed to save blocked numbers.');
                }
            }
        });
    });
});
