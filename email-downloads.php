<?php
/**
 * Plugin Name: Email Downloads
 * Plugin URI: http://nanodesignsbd.com/
 * Description: Embed a form in your pages and posts that accept an email address in exchange for a file to download. The plugin is simpler, quicker, with minimal database usage, and completely in WordPress' way.
 * Version: 1.0.0
 * Author: Mayeenul Islam (@mayeenulislam)
 * Author URI: http://nanodesignsbd.com/mayeenulislam/
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


/*  Copyright 2014 nanodesigns (email: info@nanodesignsbd.com)

    This plugin is a free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// let not call the files directly
if( !defined( 'ABSPATH' ) ) exit;

// define necessary variables';
$plugin_version = '1.0.0';

//time definition in hours
$maximum_link_duration = 12; // in hours


/**
 * Set basic settings on the activation of the plugin.
 * @return void
 * ------------------------------------------------------------------------------
 */
function nanodesigns_email_downloads_activate() {

	/**
	 * Add the necessary default settings to the 'options table'
	 */
    $admin_email = get_option( 'admin_email' );
    $admin_user = get_user_by( 'email', $admin_email );

    $ed_settings = array(
            'ed_sender_email'   => $admin_email,
            'ed_sender_name'    => $admin_user->display_name
        );    
    update_option( 'email_downloads_settings', $ed_settings );

}
register_activation_hook( __FILE__, 'nanodesigns_email_downloads_activate' );


/**
 * Shortcode
 * Usage: [email-downloads file="http://path/to/file.ext"]
 * @param  array $atts attributes that passed through shortcode.
 * @return string       formatted form.
 * ------------------------------------------------------------------------------
 */
function nanodesigns_email_downloads_shortcode( $atts ) {
	global $maximum_link_duration;

    $atts = shortcode_atts( array( 'file' => '' ), $atts );
    $file_path = $atts['file'];

    //Error storage
    $submission_error = array();

    if( isset( $_POST['download_submit'] ) ) {

        $email      = $_POST['download_email'];

        if( !empty( $email ) ) {
            if( is_email( $email ) ) {

                $ip_address     = nanodesigns_get_the_ip(); //grab the user's IP
                $unique_string  = $email . $ip_address . $file_path; //more complex unique string
                $hash           = hash_hmac( 'md5', $unique_string, $ip_address ); //IP address is the key

                //db storage - for 12 hours only
                set_transient( $hash, $file_path, $maximum_link_duration * HOUR_IN_SECONDS );

                /**
                 * Making the download link with parameter
                 * 'download_token' is important.
                 * @var string
                 */
                $download_link  = esc_url( add_query_arg( 'download_token', $hash, site_url() ) );

                //email the download link
                $success = nanodesigns_email_downloads( $email, $download_link );

                //store the email into our database
                nanodesigns_store_emails( $email );
            } else {
                $submission_error[] = __( 'Please enter a valid email address', 'email-downloads' );
            }
        } else {
            $submission_error[] = __( 'Email Address cannot be empty', 'email-downloads' );
        }

    }

    ob_start();
    ?>
    <hr>
    <div class="email-downloads">
    	<form action="" enctype="multipart/form-data" method="post">
            <p><label for="download-email"><?php _e( 'Enter your email address to download the file. An email will be sent to your email address with the download link.', 'email-downloads' ); ?></label></p>
            <?php
	    	//Show errors, if any
	    	if( !empty( $submission_error ) ) {
		        foreach( $submission_error as $error ){
		            echo '<p style="color: red;">'. __( '<strong>Error: </strong>', 'email-downloads' ) . $error .'</p>';
		        }
		    }
		    ?>
            <p><input type="email" name="download_email" id="download-email" placeholder="type your email address here" value="<?php echo isset($_POST['download_email']) ? $_POST['download_email'] : ''; ?>" autocomple="off" size="50"></p>
            <button type="submit" name="download_submit"><?php _e( 'Send me the File', 'email-downloads' ); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'email-downloads', 'nanodesigns_email_downloads_shortcode' );


/**
 * The Actual download link processor
 * @return void
 * ------------------------------------------------------------------------------
 */
function nanodesigns_let_the_user_download() {
    if( isset($_GET['download_token']) ){
        $download_token = sanitize_text_field( $_GET['download_token'] );
        $transient_data = get_transient( $download_token );
        $file_path = $transient_data ? $transient_data : false;

        if( $transient_data ) {

            //forcing download with appropriate headers
            header('Content-Type: application/octet-stream');
            header('Content-Description: File Transfer');
            header('Content-Transfer-Encoding: Binary');
            header('Content-disposition: attachment; filename="'. basename( $file_path ) .'"');
            header('Content-Length: '. filesize( $file_path ));
            header('Cache-Control: must-revalidate');

            //clean output buffering to let the user download larger files
            ob_clean();
            flush();

            //download the file
            readfile( $file_path );
            exit();

        } else {
            //transient is expired
            exit('<strong>Sorry!</strong> You are trying to explore an expired link.<br><a href="'. home_url() .'">&laquo; Home Page</a>');
        }
    }
}
add_action( 'template_redirect', 'nanodesigns_let_the_user_download' );


/**
 * Download link mailer
 * @param  string $email         the user submitted email address
 * @param  string $download_link the author submitted file path (hashed)
 * @return void
 * ------------------------------------------------------------------------------
 */
function nanodesigns_email_downloads( $email, $download_link ) {
    if( $email && is_email( $email ) && $download_link ) :
    	global $maximum_link_duration;
        
        //get basic settings options from 'options' table
        $ed_options     = get_option( 'email_downloads_settings' );
        $_sender        = $ed_options['ed_sender_name'];
        $_from_email    = $ed_options['ed_sender_email'];

        $to_email       = $email;
        $subject        = __( 'Download is ready!', 'email-downloads' );

        ob_start(); ?>

            <html lang="en">
                <head>
                    <title><?php echo $subject; ?></title>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                </head>
                <body style="line-height: 1; font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif; font-size: 15px;">
                    <h2 style="text-transform: uppercase; font-size: 22px"><?php _e('Your Download is ready!', 'email-downloads' ); ?></h2>
                    <p><?php _e('Please follow the following link to download the file:', 'email-downloads' ); ?></p>
                    <p>
                    	<a href="<?php echo esc_url( $download_link ); ?>" style="background-color: #E43435; color: #fff; padding: 10px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block;" target="_blank">
                    		<?php _e( 'DOWNLOAD', 'email-downloads' ); ?>
                    	</a>
                		&nbsp;
                		<small style="color: #666"><?php printf( __( '(the link is valid for %s hours only)', 'email_downloads' ), $maximum_link_duration ); ?></small>
                    </p>
                    <hr style="height: 1px; border: 0; border-top: 1px solid #999; margin: 10px 0">
                    <p><small><a style="text-decoration: none; color: #1965A3;" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></small></p>
                </body>
            </html>

        <?php
        $message = ob_get_clean();

        $headers      = "From: ". $_sender ." <". $_from_email .">\r\n";
        $headers      .= "Reply-To: ". $_from_email ."\r\n";
        $headers      .= "MIME-Version: 1.0\r\n";
        $headers      .= "Content-Type: text/html; charset=UTF-8";

        function nanodesigns_mail_content_type() {
            return "text/html";
        }
        add_filter ("wp_mail_content_type", "nanodesigns_mail_content_type");

        //send the email
        $sent = wp_mail( $to_email, $subject, $message, $headers );

        if( $sent ) {
            _e( '<p style="color: green"><strong>Success!</strong> The download link is sent to your email address. Check your inbox please</p>', 'email-downloads' );
        } else {
            printf( __( '<p style="color: orange;">Sorry, an error occurred. Email cannot be sent.</p><p style="padding-left: 20px">You can try the following temporary link to download the file:<br><a href="%1$s" target="_blank" rel="nofollow">%1$s</a></p>', 'email-downloads' ), $download_link );
        }

    endif;
}


/**
 * Storing email addresses into our table
 * @param  string $email the user submitted email address
 * @return void
 * ------------------------------------------------------------------------------
 */
function nanodesigns_store_emails( $email ) {
	if( $email && is_email( $email ) ) :
		
		if( nano_email_exists( $email ) ) {
			//don't duplicate email addresses
			return;
		} else {
            $currenttimestring = strtotime( date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );
            $ip_address = nanodesigns_get_the_ip();
            $hashed_string = md5( $currenttimestring . $ip_address );

            update_option( "edmail_{$hashed_string}", $email );
		}
	endif;
}



/**
 * Get the user's IP address
 * @author Barış Ünver
 * @link http://code.tutsplus.com/articles/creating-a-simple-contact-form-for-simple-needs--wp-27893
 * @return string IP address, formatted.
 * ------------------------------------------------------------------------------
 */
function nanodesigns_get_the_ip() {
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        return $_SERVER["HTTP_X_FORWARDED_FOR"];
    }
    elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    else {
        return $_SERVER["REMOTE_ADDR"];
    }
}


/**
 * Checking whether the email already exists or not.
 * @param  integer $email email address to check.
 * @return boolean        exists or not.
 * ------------------------------------------------------------------------------
 */
function nano_email_exists( $email ) {
    $emails = nano_email_lists(); //all the emails

	if( in_array( $email, $emails ) )
		return true;
	else
		return false;
}


/**
 * Plugin Options Page (Settings)
 */
require_once 'ed-options.php';



function nano_email_lists( $posts_per_page = null, $offset = null ) {
    global $wpdb;
    
    $_email_data = wp_cache_get( 'nano_ed_email_storage' );
    if ( false === $_email_data ) {
        $query = "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'edmail_%' GROUP BY option_id";

        if( $posts_per_page ) {
            $query .= " LIMIT {$posts_per_page}";
        }

        if( $offset ) {
            $query .= " OFFSET {$offset}";
        }

        $_email_data = $wpdb->get_results( $query );
        wp_cache_set( 'nano_ed_email_storage', $_email_data );
    }

    $emails = array();
    foreach( $_email_data as $_email ) {
        $emails[] = $_email->option_value;
    }

    return $emails;
}