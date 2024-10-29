jQuery(document).ready(function($) {
    const notificationMappings = {
        '[login_message_active]': '.nofi-login',
        '[registration_message_active]': '.nofi-registration-english',
        '[login_message_arabic_active]': '.nofi-login-arabic',
        '[registration_message_arabic_active]': '.nofi-registration-arabic',
        '[admin_login_message_active]': '.nofi-login-admin',
        '[admin_registration_message_active]': '.nofi-registration-admin',
        '[order_onhold_active]': '.nofi-order-onhold-english',
        '[order_onhold_arabic_active]': '.nofi-order-onhold-arabic',
        '[admin_onhold_active]': '.nofi-order-onhold-admin',
        '[order_pending_active]': '.nofi-order-pending-english',
        '[order_pending_arabic_active]': '.nofi-order-pending-arabic',
        '[admin_pending_active]': '.nofi-order-pending-admin',
        '[order_processing_active]': '.nofi-order-processing-english',
        '[order_processing_arabic_active]': '.nofi-order-processing-arabic',
        '[admin_processing_active]': '.nofi-order-processing-admin',
        '[order_completed_active]': '.nofi-order-completed-english',
        '[order_completed_arabic_active]': '.nofi-order-completed-arabic',
        '[admin_completed_active]': '.nofi-order-completed-admin',
        '[order_failed_active]': '.nofi-order-failed-english',
        '[order_failed_arabic_active]': '.nofi-order-failed-arabic',
        '[admin_failed_active]': '.nofi-order-failed-admin',
        '[order_refunded_active]': '.nofi-order-refunded-english',
        '[order_refunded_arabic_active]': '.nofi-order-refunded-arabic',
        '[admin_refunded_active]': '.nofi-order-refunded-admin',
        '[order_cancelled_active]': '.nofi-order-cancelled-english',
        '[order_cancelled_arabic_active]': '.nofi-order-cancelled-arabic',
        '[admin_cancelled_active]': '.nofi-order-cancelled-admin',
        '[order_note_active]': '.nofi-order-note-english',
        '[order_note_arabic_active]': '.nofi-order-note-arabic',
        '[admin_order_note_active]': '.nofi-order-note-admin',
        '[followup_onhold_active]': '.nofi-followup-onhold',
        '[followup_onhold_2_active]': '.nofi-followup-onhold-2',
        '[followup_onhold_3_active]': '.nofi-followup-onhold-3',
        '[followup_onhold_4_active]': '.nofi-followup-onhold-4',
        '[followup_aftersales_active]': '.nofi-followup-aftersales',
        '[followup_aftersales_2_active]': '.nofi-followup-aftersales-2',
        '[followup_aftersales_3_active]': '.nofi-followup-aftersales-3',
        '[followup_aftersales_4_active]': '.nofi-followup-aftersales-4',
        '[followup_abandoned_active]': '.nofi-followup-abandoned'
    };

    // Dynamically add custom statuses to notificationMappings for Admin
    $('input[type="checkbox"][name^="awp_notifications[admin_order_"]').each(function() {
        const statusKey = $(this).attr('name').match(/admin_order_(.*?)_active/)[1];
        notificationMappings[`[admin_order_${statusKey}_active]`] = `.nofi-order-${statusKey}-admin`;
    });

    // Dynamically add custom statuses to notificationMappings for English
    $('input[type="checkbox"][name^="awp_notifications[order_"]').each(function() {
        const statusKey = $(this).attr('name').match(/order_(.*?)_active/)[1];
        if (!statusKey.includes('arabic')) {  // Skip Arabic statuses
            notificationMappings[`[order_${statusKey}_active]`] = `.nofi-order-${statusKey}-english`;
        }
    });

    // Dynamically add custom statuses to notificationMappings for Arabic
    $('input[type="checkbox"][name^="awp_notifications[order_"]').each(function() {
        const statusKey = $(this).attr('name').match(/order_(.*?)_arabic_active/);
        if (statusKey) {  // Handle only Arabic statuses
            notificationMappings[`[order_${statusKey[1]}_arabic_active]`] = `.nofi-order-${statusKey[1]}-arabic`;
        }
    });

    console.log("Notification Mappings: ", notificationMappings);

    function toggleNotificationVisibility() {
        $('.notification-form').each(function() {
            console.log("Toggling visibility for: ", $(this));
            for (const [inputName, notificationClass] of Object.entries(notificationMappings)) {
                const isChecked = $(this).find(`input[name$="${inputName}"]`).is(':checked');
                console.log("Is checked: ", isChecked, " for ", notificationClass);
                if (isChecked) {
                    $(this).find(notificationClass).show();
                } else {
                    $(this).find(notificationClass).hide();
                }
            }
        });
    }

    // Initial check on page load
    toggleNotificationVisibility();

    // Toggle visibility on checkbox change
    $('.notification-form').on('change', 'input[type="checkbox"]', function() {
        toggleNotificationVisibility();
    });
});





