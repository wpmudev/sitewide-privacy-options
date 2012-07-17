<?php
/*
Plugin Name: Multisite Privacy
Plugin URI: http://premium.wpmudev.org/project/sitewide-privacy-options-for-wordpress-mu
Description: Adds more levels of privacy and allows you to control them across all sites - or allow users to override them.
Author: Ivan Shaovchev, Andrew Billits, Andrey Shipilov (Incsub), S H Mohanjith (Incsub)
Author URI: http://premium.wpmudev.org
Version: 1.1.6.7
Network: true
WDP ID: 52
License: GNU General Public License (Version 2 - GPLv2)
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Hooks-----------------------------------------------------------------//
//------------------------------------------------------------------------//

add_action('wpmu_options', 'additional_privacy_site_admin_options');
add_action('update_wpmu_options', 'additional_privacy_site_admin_options_process');
add_action('blog_privacy_selector', 'additional_privacy_blog_options');
add_action('admin_menu', 'additional_privacy_modify_menu_items', 99);
add_action('wpmu_new_blog', 'additional_privacy_set_default', 100, 2);
add_action('admin_enqueue_scripts', 'additional_privacy_admin_enqueue_scripts');
add_action('admin_init', 'additional_privacy_admin_init');

if ( spo_is_mobile_app() ) {
    add_action('template_redirect', 'additional_privacy');
} else {
    add_action('init', 'additional_privacy');
}

add_action('init', 'additional_privacy_init');

// Signup changes
add_action( 'signup_header', 'remove_default_privacy_signup');
add_action( 'signup_blogform', 'new_privacy_options_on_signup' );

//for single password
add_action( 'pre_update_option_blog_public', 'save_single_password' );
add_action( 'login_head', 'single_password_template' );
add_action( 'login_head', 'additional_privacy_login_message' );

//checking buddypress activity stream
add_action( 'bp_activity_before_save', 'hide_activity' );

add_filter( 'site_option_blog_public', 'additional_privacy_blog_public');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function additional_privacy_init() {
    load_plugin_textdomain( 'sitewide-privacy-options', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function additional_privacy_blog_public($value) {
    return "".intval($value)."";
}

function additional_privacy_admin_init() {
    wp_register_script('additional_privacy_admin_js', plugins_url('js/admin.js', __FILE__), array('jquery'));
    
    if ( isset($_COOKIE['privacy_update_all_blogs']) && $_COOKIE['privacy_update_all_blogs'] == 1 )  {
        global $wpdb, $site_id;
        $blog_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) as count FROM $wpdb->blogs WHERE blog_id != '1' AND site_id = '{$site_id}' AND deleted = 0 AND spam = 0;"));
        if ($blog_count > 0) {
            $blogs_completed = isset($_REQUEST['offset'])?intval($_REQUEST['offset']):0;
            $blog_limit = 100;
            $blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE blog_id != '1' AND site_id = '{$site_id}' AND deleted = 0 AND spam = 0 ORDER BY blog_id LIMIT %d, %d;", $blogs_completed, $blog_limit), ARRAY_A );
            if ( count( $blogs ) > 0 ) {
                ?>
                <h2><?php _e('Applying to sites, please wait...', 'sitewide-privacy-options'); ?></h2>
                <?php
                echo sprintf(__('%d of %d sites updated', 'sitewide-privacy-options'), $blogs_completed, $blog_count);
                $privacy_default = get_site_option('privacy_default');
                if (empty($privacy_default) || $privacy_default == "00") {
                    $privacy_default = "0";
                }
                foreach ( $blogs as $blog ) {
                    $blogs_completed++;
                    update_blog_option($blog['blog_id'], "blog_public", $privacy_default);
                }
                ?>
                <script type="text/javascript">
                        window.location ='<?php
                        if ($blog_count > $blogs_completed) {
                            echo network_admin_url('settings.php?privacy_update_all_blogs=step&offset='.($blogs_completed+1));
                        } else {
                            echo network_admin_url('settings.php?privacy_update_all_blogs=complete&message=blog_settings_updated&offset='.$blogs_completed);
                            setcookie('privacy_update_all_blogs', "0");
                        }
                    ?>';
                </script>
                <?php
                exit();
            }
        } else {
            setcookie('privacy_update_all_blogs', "0");
        }
    }  
}

function additional_privacy_admin_enqueue_scripts($hook) {
    if (is_multisite() && $hook == 'settings.php') {
        wp_enqueue_script('additional_privacy_admin_js');
    }
}

/**
 * check that it is mobile app
 */
function spo_is_mobile_app() {

    //WordPress for iOS
    if ( stripos( $_SERVER['HTTP_USER_AGENT'], 'wp-iphone' ) !== false ) {
        return true;
    }
    //WordPress for Android
    elseif ( stripos( $_SERVER['HTTP_USER_AGENT'], 'wp-android' ) !== false ) {
        return true;
    }
    //WordPress for Windows Phone 7
    elseif ( stripos( $_SERVER['HTTP_USER_AGENT'], 'wp-windowsphone' ) !== false ) {
        return true;
    }
    //WordPress for Nokia
    elseif ( stripos( $_SERVER['HTTP_USER_AGENT'], 'wp-nokia' ) !== false ) {
        return true;
    }
    //WordPress for Blackberry
    elseif ( stripos( $_SERVER['HTTP_USER_AGENT'], 'wp-blackberry' ) !== false ) {
        return true;
    }

    //not mobile app
    return false;
}


function new_privacy_options_on_signup() {
    global $blog_id;
    $blog_public = get_blog_option($blog_id,'blog_public');
    if (!$blog_public) {
        $blog_public = get_option('blog_public');
    }
    $text_network_name      = get_site_option( 'site_name' );
    if (!$text_network_name) {
        $text_network_name = 'site';
    }
    $text_all_user_link     = '<a href="'. admin_url(). 'users.php">'.__('Users > All Users', 'sitewide-privacy-options').'</a>';

    $default_available = array(
        'private'       => '1',
        'network'       => '1',
        'admin'         => '1',
        'single_pass'   => '1'
    );
    $privacy_available      = get_site_option( 'privacy_available');
    if (!$privacy_available) {
        $privacy_available = $default_available;
    }
    if (get_site_option('sitewide_privacy_signup_options') != 'disabled') {
    ?>
    <div id="new-privacy">
        <p class="privacy-intro">
            <label for="blog_public"><?php _e('Privacy:') ?></label>
            <label class="checkbox" for="public_on">
                <input type="radio" id="public_on" name="new_blog_public" value="1" <?php if ( !isset( $_POST['blog_public'] ) || $_POST['blog_public'] == '1' ) { ?>checked="checked"<?php } ?> />
                <?php _e( 'Public' ); ?>
            </label>
            <br />
            <label class="checkbox" for="public_off">
                <input type="radio" id="public_off" name="new_blog_public" value="0" <?php if ( isset( $_POST['blog_public'] ) && $_POST['blog_public'] == '0' ) { ?>checked="checked"<?php } ?> />
                <?php _e( 'Search Engine Blocked','sitewide-privacy-options' ); ?>
            </label>
            <br />
            <?php if ( isset( $privacy_available['network'] ) && '1' == $privacy_available['network'] ): ?>
            <label class="checkbox" for="blog_private_1">
                <input id="blog_private_1" type="radio" name="new_blog_public" value="-1" <?php if ( isset( $_POST['blog_public'] ) && $_POST['blog_public'] == '-1' ) { echo 'checked="checked"'; } ?> />
                <?php printf( __( 'Visitors must have a login - anyone that is a registered user of %s can gain access.', 'sitewide-privacy-options' ), $text_network_name ) ?>
            </label>
            <br />
            <?php endif ?>
            <?php if ( isset( $privacy_available['private'] ) &&  '1' == $privacy_available['private'] ): ?>
            <label class="checkbox" for="blog_private_2">
                <input id="blog_private_2" type="radio" name="new_blog_public" value="-2" <?php if ( isset( $_POST['blog_public'] ) && $_POST['blog_public'] == '-2' ) { echo 'checked="checked"'; } ?> />
                <?php printf( __( 'Only registered users of this sites can have access - anyone found under %s can have access.', 'sitewide-privacy-options'), $text_all_user_link ); ?>
            </label>
            <br />
            <?php endif ?>
            <?php if ( isset( $privacy_available['admin'] ) &&  '1' == $privacy_available['admin'] ): ?>
            <label class="checkbox" for="blog_private_3">
                <input id="blog_private_3" type="radio" name="new_blog_public" value="-3" <?php if ( isset( $_POST['blog_public'] ) && $_POST['blog_public'] == '-3' ) { echo 'checked="checked"'; } ?> />
                <?php _e( 'Only administrators can visit - good for testing purposes before making it live.', 'sitewide-privacy-options' ); ?>
            </label>
            <br />
            <?php endif ?>
            <?php if ( isset( $privacy_available['single_pass'] ) &&  '1' == $privacy_available['single_pass'] ): ?>
            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    jQuery( "input[name='new_blog_public']" ).change( function() {
                        if ( '-4' == jQuery( this ).val() )
                            jQuery( "#blog_pass" ).attr( "readonly", false );
                        else
                            jQuery( "#blog_pass" ).attr( "readonly", true );
                    });
                });
            </script>
            <br />
            <label class="checkbox" for="blog_private_4">
                <input id="blog_private_4" type="radio" name="new_blog_public" value="-4" <?php if ( isset( $_POST['blog_public'] ) && $_POST['blog_public'] == '-4' ) { echo 'checked="checked"'; } ?> />
                <?php _e( 'Anyone that visits must first provide this password:', 'sitewide-privacy-options' ); ?>
            </label>
            <br />
            <input id="blog_pass" type="text" name="blog_pass" value="<?php if ( isset( $_POST['blog_pass'] ) ) { echo $_POST['blog_pass']; } ?>" <?php if ( '-4'  != $blog_public ) { echo 'readonly'; } ?> />
            <br />
            <span class="description"><?php _e( "Note: Anyone that is a registered user of this site won't need this password.", 'sitewide-privacy-options' ); ?></span>
            <?php endif; ?>
        </p>
    </div>
    <br />
<?php
    }
}

/**
 * Remove default privacy options from create new blog page (signup)
 */
function remove_default_privacy_signup() {
    if (isset($_POST['new_blog_public'])) {
        $_POST['blog_public'] = $_POST['new_blog_public'];
    } else {
        $_POST['blog_public'] = '';
    }
    ?>
    <style type="text/css">
        #privacy  { display: none !important; }
        .mu_register label.checkbox { display:inline; font-weight: normal; }
        .description { color: #666; }
    </style>
    <?php
}

global $current_blog;

if ( $current_blog->public == '-4' && isset( $_GET['privacy'] ) && '4' == $_GET['privacy'] && !function_exists('wp_authenticate')) {
    
    function wp_authenticate($username, $password) {
        $username = sanitize_user($username);
        $password = trim($password);
        
        if ( isset( $_REQUEST['redirect_to'] ) )
            $redirect_to = $_REQUEST['redirect_to'];
        else
            $redirect_to = home_url();
        
        if ( isset( $_POST['pwd'] ) ) {
            $spo_settings = get_option( 'spo_settings' );
            if ( $_POST['pwd'] == $spo_settings['blog_pass'] ) {
                $value = wp_hash( get_current_blog_id() . $spo_settings['blog_pass'] . 'blogaccess yes' );
                setcookie( 'spo_blog_access', $value, time() + 1800, $current_blog->path );
                wp_safe_redirect( $redirect_to );
            } else {
                $errors = new WP_Error();
                $errors->add('incorrect_password', __('<strong>ERROR</strong>: Incorrect Password', 'sitewide-privacy-options'));
                return $errors;
            }
        }
        $user = null;
        if ( $user == null ) {
            // TODO what should the error message be? (Or would these even happen?)
            // Only needed if all authentication handlers fail to return anything.
            $user = new WP_Error('authorization_required', __('<strong>Authorization Required</strong>: This blog requires a password to view it.', 'sitewide-privacy-options'));
        }
        $ignore_codes = array('empty_username', 'empty_password');
        if (is_wp_error($user) && !in_array($user->get_error_code(), $ignore_codes) ) {
            do_action('wp_login_failed', $username);
        }
        return $user;
    }
}

function additional_privacy_login_message() {
    global $errors, $blog_id, $current_blog;
    if ( $current_blog->public == '-1' && isset( $_GET['privacy'] ) && $_GET['privacy'] == '1' ) {
        $errors->add('authorization_required', __('<strong>Authorization Required</strong>: This blog may only be viewed by users who are logged in.', 'sitewide-privacy-options'));
    } else if ( $current_blog->public  == '-2' && isset( $_GET['privacy'] ) && $_GET['privacy'] == '2' ) {
	$errors->add('authorization_required', __('<strong>Authorization Required</strong>: This blog may only be viewed by users who are subscribed to this blog.', 'sitewide-privacy-options'));
    } else if ( $current_blog->public == '-3' && isset( $_GET['privacy'] ) &&  $_GET['privacy'] == '3' ) {
        $errors->add('authorization_required', __('<strong>Authorization Required</strong>: This blog may only be viewed by administrators.', 'sitewide-privacy-options'));
    }
}

/**
 * templates for single password form
 */
function single_password_template( $page ) {
    global $errors, $reauth, $blog_id, $current_blog;
    
    if ( $current_blog->public == '-4' && isset( $_GET['privacy'] ) && '4' == $_GET['privacy'] ) {
        if ( isset( $_REQUEST['redirect_to'] ) )
            $redirect_to = 'redirect_to='.$_REQUEST['redirect_to'];
        else
            $redirect_to = ''
        ?>
        <script type="text/javascript">
            jQuery( document ).ready( function() {
                jQuery( '#loginform' ).attr( 'action', '<?php echo site_url('wp-login.php?privacy=4&'.$redirect_to); ?>' );
                jQuery( '#loginform' ).attr( 'id', 'loginform4' );
                jQuery( '#loginform' ).attr( 'name', 'loginform4' );
                jQuery( '#user_pass' ).attr( 'id', 'blog_pass');
                jQuery( '#user_login' ).parent( 'label' ).parent( 'p' ).remove();
                jQuery( '#rememberme' ).parent( 'label' ).parent( 'p' ).remove();
                jQuery( '#backtoblog' ).remove();
                jQuery( '#nav' ).remove();
                jQuery( '#loginform4' ).append('<br /><br /><br /><p><a href="<?php echo site_url('wp-login.php?'.$redirect_to); ?>"><?php _e('Or login here if you have a username', 'sitewide-privacy-options'); ?></a></p>');
                jQuery( '#loginform4' ).submit( function() {
                    if ( '' == jQuery( '#blog_pass' ).val() ) {
                        jQuery( '#blog_pass' ).css( 'border-color', 'red' );
                        return false;
                    }

                    return true;
                });

                jQuery( '#loginform4' ).change( function() {
                    jQuery( '#blog_pass' ).css( 'border-color', '' );
                });

            });
        </script>

        <?php
    }
}

/**
 * save the single pasword when change privacy option
 */
function save_single_password( $option ) {
    if ( isset($_POST['blog_public']) && '-4' == $_POST['blog_public'] ) {
        $spo_settings = array(
            'blog_pass' => $_POST['blog_pass']
        );
        update_option( 'spo_settings', $spo_settings );
    }
    return $option;
}

/**
 * hide the posts from private sites in buddypress activity stream
 */
function hide_activity( $activity ) {

    if ( function_exists( 'bp_get_root_blog_id' ) )
        $bp_root_blog_id = bp_get_root_blog_id();

    if ( isset( $bp_root_blog_id ) && get_current_blog_id() != $bp_root_blog_id ) {
        //ID of BP blog
        $privacy_bp = get_blog_option( $bp_root_blog_id, 'blog_public' );
        
        if (!$privacy_bp) {
            $privacy_bp = get_option( 'blog_public' );
        }

        //cheack that BP site Visibility
        if ( '-1' != $privacy_bp && '-2' != $privacy_bp &&'-3' != $privacy_bp )
            if ( 1 != get_option( 'blog_public' ) )
                $activity->hide_sitewide = true;

    }

    return $activity;
}

function additional_privacy_can_access_blog($blog_id) {
    $privacy = get_blog_option($blog_id, 'blog_public');
    switch( $privacy ) {
            case '-1': 
                if ( ! is_user_logged_in() ) {
                    return false;
                }
                break;
            case '-2':
                if ( ! is_user_logged_in() ) {
                    return false;
                } else {
                    if ( ! current_user_can( 'read' ) ) {
                        return false;
                    }
                }
                break;
            case '-3':
                if ( ! is_user_logged_in() ) {
                    return false;
                } else {
                    if ( ! current_user_can( 'manage_options' ) ) {
                        return false;
                    }
                }
                break;
            //single password
            case '-4':
                $spo_settings = get_option( 'spo_settings' );
                $value        = wp_hash( get_current_blog_id() . $spo_settings['blog_pass'] . 'blogaccess yes' );
                if ( !is_user_logged_in() ) {
                    if ( !isset( $_COOKIE['spo_blog_access'] ) || $value != $_COOKIE['spo_blog_access'] ) {
                        return false;
                    }
                }
                break;
    }
    return true;
}

function additional_privacy() {
    global $blog_id, $user_id, $current_blog;
    
    if ( $current_blog->public == '-4' && !is_user_logged_in() && isset( $_GET['privacy'] ) && '4' == $_GET['privacy'] ) {
        wp_enqueue_script( 'jquery' );
    }
    
    $privacy = $current_blog->public;
    
    $register_url = apply_filters( 'wp_signup_location', site_url( 'wp-signup.php' ) );
    $register_part = str_replace(site_url('/'), PATH_CURRENT_SITE, $register_url);
    
    if ( is_numeric($privacy) && $privacy < 0 && !stristr($_SERVER['REQUEST_URI'], 'wp-activate') && !stristr($_SERVER['REQUEST_URI'], $register_part) && !stristr($_SERVER['REQUEST_URI'], 'wp-login') && !stristr($_SERVER['REQUEST_URI'], 'wp-admin') ) {
    
    switch( $privacy ) {
            case '-1': {
                if ( ! is_user_logged_in() ) {
                    wp_redirect( site_url("wp-login.php?privacy=1&redirect_to=" . urlencode( $_SERVER['REQUEST_URI'] )) );
                    exit();
                }
                break;
            }
            case '-2': {
                if ( ! is_user_logged_in() ) {
                    wp_redirect( site_url("wp-login.php?privacy=2&redirect_to=" . urlencode( $_SERVER['REQUEST_URI'] )) );
                    exit();
                } else {
                    if ( ! current_user_can( 'read' ) ) {
                        additional_privacy_deny_message( '2' );
                    }
                }
                break;
            }
            case '-3': {
                if ( ! is_user_logged_in() ) {
                    wp_redirect( site_url("wp-login.php?privacy=3&redirect_to=" . urlencode( $_SERVER['REQUEST_URI'] )) );
                    exit();
                } else {
                    if ( ! current_user_can( 'manage_options' ) ) {
                        additional_privacy_deny_message( '3' );
                    }
                }
                break;
            }
            //single password
            case '-4': {
                $spo_settings           = get_option( 'spo_settings' );
                $value                  = wp_hash( get_current_blog_id() . $spo_settings['blog_pass'] . 'blogaccess yes' );

                if ( !is_user_logged_in() ) {
                    if ( !isset( $_COOKIE['spo_blog_access'] ) || $value != $_COOKIE['spo_blog_access'] ) {
                        wp_redirect( site_url("wp-login.php?privacy=4&redirect_to=" . urlencode( $_SERVER['REQUEST_URI'] )) );
                        exit();
                    }
                }
                break;
            }
        }
    }
    $file_value = hash_hmac('md5', "{$blog_id} file access yes", LOGGED_IN_SALT);
    setcookie( "spo_{$blog_id}_fa", $file_value, time() + 1800, $current_blog->path);
}

function additional_privacy_set_default($blog_id, $user_id) {
	global $wpdb;
	$privacy_default = get_site_option('privacy_default');
        if (!$privacy_default) {
            $privacy_default = 1;
        }
	update_blog_option($blog_id, "blog_public", $privacy_default);
	$wpdb->query("UPDATE $wpdb->blogs SET public = '". $privacy_default ."' WHERE blog_id = '". $blog_id ."' LIMIT 1");
}

function additional_privacy_site_admin_options_process() {
    global $wpdb;
    
    if (isset($_POST['sitewide_privacy_pro_only'])) {
        update_site_option( 'sitewide_privacy_pro_only', $_POST['sitewide_privacy_pro_only'] );
    }
    update_site_option( 'sitewide_privacy_signup_options', $_POST['sitewide_privacy_signup_options'] );
    if (empty($_POST['privacy_default'])) {
        update_site_option( 'privacy_default' , "00" );
    } else {
        update_site_option( 'privacy_default' , $_POST['privacy_default'] );
    }
    update_site_option( 'privacy_override' , $_POST['privacy_override'] );
    update_site_option( 'privacy_available' , $_POST['privacy_available'] );
    
    if ( isset( $_POST['privacy_update_all_blogs'] ) &&  $_POST['privacy_update_all_blogs'] == 'update' )  {
	$wpdb->query("UPDATE $wpdb->blogs SET public = '". $_POST['privacy_default'] ."' WHERE blog_id != '1' AND active = 1 AND deleted = 0 AND spam = 0 ");
        setcookie('privacy_update_all_blogs', "1");
    }
}

function additional_privacy_modify_menu_items() {
	global $submenu, $menu, $wpdb;
        // echo '<!-- ' . get_site_option('privacy_override') . ' -->';
	if ( get_site_option('privacy_override') != 'yes' && !is_super_admin() && $wpdb->blogid != 1 ) {
	    unset( $submenu['options-general.php'][35] );
	}
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//

function additional_privacy_deny_message( $privacy ) {
    do_action( 'additional_privacy_deny_message', $privacy );

	header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header('Pragma: no-cache');
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php _e('Site Access Denied', 'sitewide-privacy-options'); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<style media="screen" type="text/css">
		html { background: #f1f1f1; }

		body {
			background: #fff;
			color: #333;
			font-family: "Lucida Grande", "Lucida Sans Unicode", Tahoma, Verdana, sans-serif;
			margin: 2em auto 0 auto;
			width: 700px;
			padding: 1em 2em;
			-moz-border-radius: 12px;
			-khtml-border-radius: 12px;
			-webkit-border-radius: 12px;
			border-radius: 12px;
		}

		a { color: #2583ad; text-decoration: none; }

		a:hover { color: #d54e21; }


		h1 {
			font-size: 18px;
			margin-bottom: 0;
		}

		h2 { font-size: 16px; }

		p, li {
			padding-bottom: 2px;
			font-size: 13px;
			line-height: 18px;
		}
		</style>
	</head>
	<body>
	<h2><?php _e('Site Access Denied', 'sitewide-privacy-options'); ?></h2>
    <?php
    if ( $privacy == '2' ) {
        $msg = __( 'This site may only be viewed by users who are subscribed to this site.', 'sitewide-privacy-options' );
    } elseif ( $privacy == '3' ) {
        $msg = __( 'This site may only be viewed by administrators.', 'sitewide-privacy-options' );
    }
    ?>
    <p>
        <?php echo apply_filters( 'additional_privacy_deny_message', $msg )?>
    </p>
    </body>
    </html>
	<?php
	exit();
}

function additional_privacy_is_pro() {
    if (get_site_option('sitewide_privacy_pro_only') == 'yes' && function_exists('is_pro_site') && (!(is_pro_site() || !psts_show_ads()))) {
        return false;
    }
    return true;
}

function additional_privacy_blog_options() {
    if (!additional_privacy_is_pro()) {
        global $psts;
        $feature_message = str_replace( 'LEVEL', $psts->get_level_setting($level, 'name', $psts->get_setting('rebrand')), __("To use the extra privacy options, please upgrade to LEVEL &#187;", 'sitewide-privacy-options') );
        echo '<div id="message" class="error"><p><a href="' . $psts->checkout_url($blog_id) . '">' . $feature_message . '</a></p></div>';
    }
    
    $blog_public            = get_option( 'blog_public' );
    $spo_settings           = get_option( 'spo_settings' );
    $text_network_name      = get_site_option( 'site_name' );
    if (!$text_network_name) {
        $text_network_name = 'site';
    }
    $text_all_user_link     = '<a href="'. admin_url(). 'users.php">'.__('Users > All Users', 'sitewide-privacy-options').'</a>';

    $default_available      = array(
        'private'       => '1',
        'network'       => '1',
        'admin'         => '1',
        'single_pass'   => '1'
    );
    $privacy_available      = get_site_option( 'privacy_available' );
    if (!$privacy_available) {
        $privacy_available = $default_available;
    }
    ?>
    <br />
    <?php if ( isset( $privacy_available['network'] ) && '1' == $privacy_available['network'] ): ?>

    <input id="blog-privacy-reguser" type="radio" name="blog_public" value="-1" <?php if ( $blog_public == '-1' ) { echo 'checked="checked"'; } ?> <?php echo (additional_privacy_is_pro())?'':'disabled="disabled"'; ?> />
    <label><?php printf( __( 'Visitors must have a login - anyone that is a registered user of %s can gain access.', 'sitewide-privacy-options' ), $text_network_name ) ?></label>
    <br />
    <?php endif ?>
    <?php if ( isset( $privacy_available['private'] ) &&  '1' == $privacy_available['private'] ): ?>

    <input id="blog-privacy-bloguser" type="radio" name="blog_public" value="-2" <?php if ( $blog_public == '-2' ) { echo 'checked="checked"'; } ?> <?php echo (additional_privacy_is_pro())?'':'disabled="disabled"'; ?> />
    <label><?php printf( __( 'Only registered users of this blogs can have access - anyone found under %s can have access.', 'sitewide-privacy-options'), $text_all_user_link ); ?></label>
    <br />
    <?php endif ?>
    <?php if ( isset( $privacy_available['admin'] ) &&  '1' == $privacy_available['admin'] ): ?>

    <input id="blog-privacy-admin" type="radio" name="blog_public" value="-3" <?php if ( $blog_public == '-3' ) { echo 'checked="checked"'; } ?> <?php echo (additional_privacy_is_pro())?'':'disabled="disabled"'; ?> />
    <label><?php _e( 'Only administrators can visit - good for testing purposes before making it live.', 'sitewide-privacy-options' ); ?></label>
    <br />
    <?php endif ?>

    <?php if ( isset( $privacy_available['single_pass'] ) &&  '1' == $privacy_available['single_pass'] ): ?>

    <script type="text/javascript">
        jQuery( document ).ready( function() {
            jQuery( "input[name='blog_public']" ).change( function() {
                if ( '-4' == jQuery( this ).val() )
                    jQuery( "#blog_pass" ).attr( "readonly", false );
                else
                    jQuery( "#blog_pass" ).attr( "readonly", true );
            });
        });
    </script>

    <br />
    <input id="blog-privacy-pass" type="radio" name="blog_public" value="-4" <?php if ( $blog_public == '-4' ) { echo 'checked="checked"'; } ?> <?php echo (additional_privacy_is_pro())?'':'disabled="disabled"'; ?> />
    <label><?php _e( 'Anyone that visits must first provide this password:', 'sitewide-privacy-options' ); ?></label>
    <br />
    <input id="blog_pass" type="text" name="blog_pass" value="<?php if ( isset( $spo_settings['blog_pass'] ) ) { echo $spo_settings['blog_pass']; } ?>" <?php if ( '-4'  != $blog_public ) { echo 'readonly'; } ?> <?php echo (additional_privacy_is_pro())?'':'disabled="disabled"'; ?> />
    <br />
    <span class="description"><?php _e( "Note: Anyone that is a registered user of this blog won't need this password.", 'sitewide-privacy-options' ); ?></span>
    <?php endif; ?>

    <?php
}

function additional_privacy_site_admin_options() {
    $privacy_default        = get_site_option('privacy_default');
    if (!$privacy_default) {
        $privacy_default = 1;
    }
    $privacy_override       = get_site_option('privacy_override');
    if (!$privacy_override) {
        $privacy_override = 'no';
    }

    $default_available      = array(
        'private'       => '1',
        'network'       => '1',
        'admin'         => '1',
        'single_pass'   => '1'
    );
    $privacy_available      = get_site_option( 'privacy_available' );
    if (!$privacy_available) {
        $privacy_available = $default_available;
    }
    $sitewide_privacy_signup_options = get_site_option( 'sitewide_privacy_signup_options');
    if (!$sitewide_privacy_signup_options) {
        $sitewide_privacy_signup_options = 'enabled';
    }
    $sitewide_privacy_pro_only = get_site_option( 'sitewide_privacy_pro_only');
    if (!$sitewide_privacy_pro_only) {
        $sitewide_privacy_pro_only = 'no';
    }
    ?>
    <h3><?php _e('Site Privacy Settings', 'sitewide-privacy-options') ?></h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e( 'Show Privacy Options at Sign Up', 'sitewide-privacy-options' ) ?></th>
            <td>
                <label><input name="sitewide_privacy_signup_options" id="sitewide_privacy_signup_options_yes" type="radio" value="enabled" <?php echo ( 'enabled' == $sitewide_privacy_signup_options ) ? 'checked' : ''; ?> />
                <?php _e( 'Yes', 'sitewide-privacy-options' ); ?></label>
                <label><input name="sitewide_privacy_signup_options" id="sitewide_privacy_signup_options_no" type="radio" value="disabled" <?php echo ( 'disabled' == $sitewide_privacy_signup_options ) ? 'checked' : ''; ?> />
                <?php _e( 'No', 'sitewide-privacy-options' ); ?></label>
            </td>
        </tr>
        <?php if (function_exists('is_pro_site')) { ?>
        <tr valign="top" id="sitewide_privacy_pro_only_row">
            <th scope="row"><?php _e( 'Make this functionality only available to Supporters', 'sitewide-privacy-options' ) ?></th>
            <td>
                <label><input name="sitewide_privacy_pro_only" id="sitewide_privacy_pro_only_yes" type="radio" value="yes" <?php echo ( 'yes' == $sitewide_privacy_pro_only ) ? 'checked' : ''; ?> />
                <?php _e( 'Yes', 'sitewide-privacy-options' ); ?></label>
                <label><input name="sitewide_privacy_pro_only" id="sitewide_privacy_pro_only_no" type="radio" value="no" <?php echo ( 'no' == $sitewide_privacy_pro_only ) ? 'checked' : ''; ?> />
                <?php _e( 'No', 'sitewide-privacy-options' ); ?></label>
            </td>
        </tr>
        <?php } ?>
        <tr valign="top">
            <th scope="row"><?php _e( 'Available Options', 'sitewide-privacy-options' ) ?></th>
            <td>
                <input name="privacy_available[network]" type="checkbox" value="1" <?php echo ( isset( $privacy_available['network'] ) && '1' == $privacy_available['network'] ) ? 'checked' : ''; ?> />
                <?php _e( 'Only allow logged in users to see all sites.', 'sitewide-privacy-options' ); ?>
                <br />
                <input name="privacy_available[private]" type="checkbox" value="1" <?php echo ( isset( $privacy_available['private'] ) && '1' == $privacy_available['private'] ) ? 'checked' : ''; ?> />
                <?php _e( 'Only allow a registered user to see a site for which they are registered to.', 'sitewide-privacy-options' ); ?>
                <br />
                <input name="privacy_available[admin]" type="checkbox" value="1" <?php echo ( isset( $privacy_available['admin'] ) && '1' == $privacy_available['admin'] ) ? 'checked' : ''; ?> />
                <?php _e( 'Only allow administrators of a site to view the site for which they are an admin.', 'sitewide-privacy-options' ); ?>
                <br />
                <input name="privacy_available[single_pass]" type="checkbox" value="1" <?php echo ( isset( $privacy_available['single_pass'] ) && '1' == $privacy_available['single_pass'] ) ? 'checked' : ''; ?> />
                <?php _e( 'Allow Network Administrators to set a single password that any visitors must use to see the site.', 'sitewide-privacy-options' ); ?>
                <br />
            </td>
        </tr>
	<tr valign="top">
	    <th scope="row"><?php _e('Default Setting', 'sitewide-privacy-options') ?></th>
	    <td>
                <label><input name="privacy_default" id="privacy_default" value="1" <?php if ( $privacy_default == '1' ) { echo 'checked="checked"'; } ?> type="radio"> <?php _e('Allow all visitors to all sites.', 'sitewide-privacy-options'); ?>
                <br />
                <small><?php _e('This makes all sites visible to everyone, including search engines (like Google, Sphere, Technorati), archivers and all public listings around your site.', 'sitewide-privacy-options'); ?></small></label>
                <br />
                <label><input name="privacy_default" id="privacy_default" value="0" <?php if ( $privacy_default == '0' ) { echo 'checked="checked"'; } ?> type="radio"> <?php _e('Block search engines from all sites, but allow normal visitors to see all sites.', 'sitewide-privacy-options'); ?></label>
                <br />
	        <label><input name="privacy_default" id="privacy_default" value="-1" <?php if ( $privacy_default == '-1' ) { echo 'checked="checked"'; } ?> type="radio"> <?php _e('Only allow logged in users to see all sites.', 'sitewide-privacy-options'); ?></label>
                <br />
		<label><input name="privacy_default" id="privacy_default" value="-2" <?php if ( $privacy_default == '-2' ) { echo 'checked="checked"'; } ?> type="radio"> <?php _e('Only allow a registered user to see a site for which they are registered to.', 'sitewide-privacy-options'); ?>
                <br />
                <small><?php _e('Even if a user is logged in, they must be a user of the individual site in order to see it.', 'sitewide-privacy-options'); ?></small></label>
                <br />
		<label><input name="privacy_default" id="privacy_default" value="-3" <?php if ( $privacy_default == '-3' ) { echo 'checked="checked"'; } ?> type="radio"> <?php _e('Only allow administrators of a site to view the site for which they are an admin.', 'sitewide-privacy-options'); ?>
                <br />
                <small><?php _e('A Network Admin can always view any site, regardless of any privacy setting. (<em>Note:</em> "Network Admin", not an individual site admin.)', 'sitewide-privacy-options'); ?></small></label>
            </td>
	</tr>
        <tr valign="top">
	    <th scope="row"><?php _e('Allow Override', 'sitewide-privacy-options') ?></th>
	    <td>
	        <input name="privacy_override" id="privacy_override" value="yes" <?php if ( $privacy_override == 'yes' ) { echo 'checked="checked"'; } ?> type="radio"> <?php _e('Yes', 'sitewide-privacy-options'); ?>
	        <br />
	        <input name="privacy_override" id="privacy_override" value="no" <?php if ( $privacy_override == 'no' ) { echo 'checked="checked"'; } ?> type="radio"> <?php _e('No', 'sitewide-privacy-options'); ?>
	        <br />
	        <?php _e('Allow Site Administrators to modify the privacy setting for their site(s). Note that Network Admins will always be able to edit site privacy options.', 'sitewide-privacy-options') ?>
	    </td>
	</tr>
        <?php if (!function_exists('is_edublogs')) { ?>
	<tr valign="top">
	    <th scope="row"><?php _e('Update All Sites', 'sitewide-privacy-options') ?></th>
	    <td>
                <input id="privacy_update_all_blogs" name="privacy_update_all_blogs" value="update" type="checkbox">
	        <br />
		<?php _e('Updates all sites with the default privacy setting. The main site is not updated. Please be patient as this can take a few minutes.', 'sitewide-privacy-options') ?>
	    </td>
	</tr>
        <?php } ?>
    </table>
    <?php
}

/* Update Notifications Notice */
if ( !function_exists( 'wdp_un_check' ) ) {
    function wdp_un_check() {
        if ( !class_exists('WPMUDEV_Update_Notifications') && current_user_can('edit_users') )
            echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
    }
    add_action( 'admin_notices', 'wdp_un_check', 5 );
    add_action( 'network_admin_notices', 'wdp_un_check', 5 );
}

?>
