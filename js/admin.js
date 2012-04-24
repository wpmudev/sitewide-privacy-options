jQuery(document).ready(
    function () {
        jQuery('input[name=registration]').click(function() {
            if (jQuery('#registration3:checked').length > 0 || jQuery('#registration4:checked').length > 0) {
                jQuery('#sitewide_privacy_signup_options_yes').parent().parent().parent().show();
            } else {
                jQuery('#sitewide_privacy_signup_options_no').parent().parent().parent().hide();
            }
        });
        jQuery('input[name=sitewide_privacy_signup_options]').click(function() {
            if (jQuery('#sitewide_privacy_signup_options_yes:checked').length > 0) {
                jQuery('#sitewide_privacy_pro_only_row').hide();
            } else {
                jQuery('#sitewide_privacy_pro_only_row').show();
            }
        });
        if (jQuery('#registration3:checked').length > 0 || jQuery('#registration4:checked').length > 0) {
            jQuery('#sitewide_privacy_signup_options_yes').parent().parent().parent().show();
        } else {
            jQuery('#sitewide_privacy_signup_options_no').parent().parent().parent().hide();
        }
        if (jQuery('#sitewide_privacy_signup_options_yes:checked').length > 0) {
            jQuery('#sitewide_privacy_pro_only_row').hide();
        } else {
            jQuery('#sitewide_privacy_pro_only_row').show();
        }
    }
);
