jQuery(document).ready(
    function () {
        jQuery('input[name=registration]').click(function() {
            if (jQuery('#registration3:checked').length > 0 || jQuery('#registration4:checked').length > 0) {
                jQuery('#sitewide_privacy_signup_options_yes').parent().parent().parent().show();
            } else {
                jQuery('#sitewide_privacy_signup_options_no').parent().parent().parent().hide();
            }
        });
        if (jQuery('#registration3:checked').length > 0 || jQuery('#registration4:checked').length > 0) {
            jQuery('#sitewide_privacy_signup_options_yes').parent().parent().parent().show();
        } else {
            jQuery('#sitewide_privacy_signup_options_no').parent().parent().parent().hide();
        }
    }
);
