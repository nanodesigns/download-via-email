<?php
/**
 * Plugin Name: Download via Email
 * Plugin URI: http://nanodesignsbd.com/
 * Description: Embed a form using shortcode (<code>[email-downloads file="absolute-path/to/the/file.ext"]</code>) in your pages and posts that accept an email address in exchange for a file to download. The plugin is simpler, quicker, with minimal database usage, and completely in WordPress' way.
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
 * The Database version.
 * By which we can update the table with new versions.
 *
 * @since 1.0.1
 * ------------------------------------------------------------------------------
 */
global $nano_db_version;
$nano_db_version = "1.0";


/**
 * Set basic settings on the activation of the plugin.
 * - Saved in 'options' table.
 * - Creating custom table for storing email addresses.
 * ------------------------------------------------------------------------------
 */
function nanodesigns_email_downloads_activate() {

    /**
     * Creating a custom table.
     * @since 1.0.1
     * -----------
     */
    global $wpdb, $nano_db_version;

    $table = $wpdb->prefix .'download_email';

    if( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
        $sql = "CREATE TABLE $table (
                  id mediumint(9) NOT NULL AUTO_INCREMENT,
                  email tinytext NOT NULL,
                  UNIQUE KEY id (id)
                );";

        //reference to upgrade.php file
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    } //endif($wpdb->get_var

    update_option( "nano_ed_db_version", $nano_db_version );


	/**
     * Add the necessary default settings to the 'options table'.
     * @since 1.0.0
     * -----------
     */
    $noreply_email = noreply_email();
    $admin_email = get_option( 'admin_email' );
    $admin_user = get_user_by( 'email', $admin_email );

    $ed_settings = array(
            'ed_sender_email'   => $noreply_email,
            'ed_sender_name'    => $admin_user->display_name
        );    
    update_option( 'email_downloads_settings', $ed_settings );

}
register_activation_hook( __FILE__, 'nanodesigns_email_downloads_activate' );


/**
 * Shortcode.
 * Usage: [email-downloads file="http://path/to/file.ext"].
 * 
 * @param  array $atts  Attributes that passed through shortcode.
 * @return string       Formatted form.
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
                nanodesigns_ed_store_emails( $email );
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
            <p><input type="email" name="download_email" id="download-email" placeholder="type your email address here" value="<?php echo isset($_POST['download_email']) ? $_POST['download_email'] : ''; ?>" autocomplete="off" size="50"></p>
            <button type="submit" name="download_submit"><?php _e( 'Send me the File', 'email-downloads' ); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'email-downloads', 'nanodesigns_email_downloads_shortcode' );


/**
 * The Actual download link processor.
 *
 * The function to process the link and let the user download the file or not.
 * ------------------------------------------------------------------------------
 */
function nanodesigns_ed_let_the_user_download() {
    if( isset($_GET['download_token']) ){
        $download_token = sanitize_text_field( $_GET['download_token'] );
        $transient_data = get_transient( $download_token );
        $file_path = $transient_data ? $transient_data : false;
        $file = basename( $file_path );

        if( $transient_data ) {

            //forcing download with appropriate headers
            //header('Expires: 0');
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Type: '. nano_ed_get_mime_type( $file ));
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="'. $file .'"');
            header('Content-Length: '. @filesize( $file ));
            header('Cache-Control: must-revalidate');

            //clean output buffering to let the user download larger files
            ob_clean();
            flush();

            //download the file
            readfile( $file_path );
            exit();

        } else {
            //transient is expired
            exit('<strong>Sorry!</strong> You are trying to explore an expired link.<br><a href="'. esc_url( home_url() ) .'">&laquo; Home Page</a>');
        }
    }
}
add_action( 'template_redirect', 'nanodesigns_ed_let_the_user_download' );


/**
 * Download link mailer.
 * 
 * @param  string $email         The user submitted email address.
 * @param  string $download_link The author submitted file path (hashed).
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
 * Storing email addresses into our table.
 * 
 * @param  string $email The user submitted email address.
 * ------------------------------------------------------------------------------
 */
function nanodesigns_ed_store_emails( $email ) {
	if( $email && is_email( $email ) ) :
		
		if( nano_ed_email_exists( $email ) ) {
			//don't duplicate email addresses
			return;
		} else {
            global $wpdb;
            $table = $wpdb->prefix .'download_email';
            $wpdb->insert( $table, array( 'email' => $email ), array( '%s' ) );
		}

	endif;
}



/**
 * Get the user's IP address.
 * 
 * @author Barış Ünver
 * @link http://code.tutsplus.com/articles/creating-a-simple-contact-form-for-simple-needs--wp-27893
 * 
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
 * 
 * @param  integer $email Email address to check.
 * @return boolean        Exists or not.
 * ------------------------------------------------------------------------------
 */
function nano_ed_email_exists( $email ) {
    $emails = nano_ed_email_lists(); //all the emails

	if( in_array( $email, $emails ) )
		return true;
	else
		return false;
}


/**
 * Email lists query.
 * 
 * @param  integer $posts_per_page Limiting the query.
 * @param  integer $offset         Escaping no. of items.
 * @return array                   Emails that are stored.
 * ------------------------------------------------------------------------------
 */
function nano_ed_email_lists( $posts_per_page = null, $offset = null ) {
    global $wpdb;
    $table = $wpdb->prefix .'download_email';
    
    $_email_data = wp_cache_get( 'nano_ed_email_storage' );
    if ( false === $_email_data ) {
        $query = "SELECT email FROM $table";

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
    if( $_email_data ) :
        foreach( $_email_data as $_email ) :
            $emails[] = $_email;
        endforeach;
    endif;

    return $emails;
}

/**
 * Get the MIME Type.
 * @link http://snipplr.com/view.php?codeview&id=1937
 * 
 * @param  string $filename Filename with extension.
 * @return string           File MIME type.
 * ------------------------------------------------------------------------------
 */
function nano_ed_get_mime_type( $filename ) {
    $extension = pathinfo( $filename, PATHINFO_EXTENSION );

    $mime_types = array("323" => "text/h323",
                    "acx" => "application/internet-property-stream",
                    "ai" => "application/postscript",
                    "aif" => "audio/x-aiff",
                    "aifc" => "audio/x-aiff",
                    "aiff" => "audio/x-aiff",
                    "asf" => "video/x-ms-asf",
                    "asr" => "video/x-ms-asf",
                    "asx" => "video/x-ms-asf",
                    "au" => "audio/basic",
                    "avi" => "video/x-msvideo",
                    "axs" => "application/olescript",
                    "bas" => "text/plain",
                    "bcpio" => "application/x-bcpio",
                    "bin" => "application/octet-stream",
                    "bmp" => "image/bmp",
                    "c" => "text/plain",
                    "cat" => "application/vnd.ms-pkiseccat",
                    "cdf" => "application/x-cdf",
                    "cer" => "application/x-x509-ca-cert",
                    "class" => "application/octet-stream",
                    "clp" => "application/x-msclip",
                    "cmx" => "image/x-cmx",
                    "cod" => "image/cis-cod",
                    "cpio" => "application/x-cpio",
                    "crd" => "application/x-mscardfile",
                    "crl" => "application/pkix-crl",
                    "crt" => "application/x-x509-ca-cert",
                    "csh" => "application/x-csh",
                    "css" => "text/css",
                    "dcr" => "application/x-director",
                    "der" => "application/x-x509-ca-cert",
                    "dir" => "application/x-director",
                    "dll" => "application/x-msdownload",
                    "dms" => "application/octet-stream",
                    "doc" => "application/msword",
                    "dot" => "application/msword",
                    "dvi" => "application/x-dvi",
                    "dxr" => "application/x-director",
                    "eps" => "application/postscript",
                    "etx" => "text/x-setext",
                    "evy" => "application/envoy",
                    "exe" => "application/octet-stream",
                    "fif" => "application/fractals",
                    "flr" => "x-world/x-vrml",
                    "gif" => "image/gif",
                    "gtar" => "application/x-gtar",
                    "gz" => "application/x-gzip",
                    "h" => "text/plain",
                    "hdf" => "application/x-hdf",
                    "hlp" => "application/winhlp",
                    "hqx" => "application/mac-binhex40",
                    "hta" => "application/hta",
                    "htc" => "text/x-component",
                    "htm" => "text/html",
                    "html" => "text/html",
                    "htt" => "text/webviewhtml",
                    "ico" => "image/x-icon",
                    "ief" => "image/ief",
                    "iii" => "application/x-iphone",
                    "ins" => "application/x-internet-signup",
                    "isp" => "application/x-internet-signup",
                    "jfif" => "image/pipeg",
                    "jpe" => "image/jpeg",
                    "jpeg" => "image/jpeg",
                    "jpg" => "image/jpeg",
                    "js" => "application/x-javascript",
                    "latex" => "application/x-latex",
                    "lha" => "application/octet-stream",
                    "lsf" => "video/x-la-asf",
                    "lsx" => "video/x-la-asf",
                    "lzh" => "application/octet-stream",
                    "m13" => "application/x-msmediaview",
                    "m14" => "application/x-msmediaview",
                    "m3u" => "audio/x-mpegurl",
                    "man" => "application/x-troff-man",
                    "mdb" => "application/x-msaccess",
                    "me" => "application/x-troff-me",
                    "mht" => "message/rfc822",
                    "mhtml" => "message/rfc822",
                    "mid" => "audio/mid",
                    "mny" => "application/x-msmoney",
                    "mov" => "video/quicktime",
                    "movie" => "video/x-sgi-movie",
                    "mp2" => "video/mpeg",
                    "mp3" => "audio/mpeg",
                    "mpa" => "video/mpeg",
                    "mpe" => "video/mpeg",
                    "mpeg" => "video/mpeg",
                    "mpg" => "video/mpeg",
                    "mpp" => "application/vnd.ms-project",
                    "mpv2" => "video/mpeg",
                    "ms" => "application/x-troff-ms",
                    "mvb" => "application/x-msmediaview",
                    "nws" => "message/rfc822",
                    "oda" => "application/oda",
                    "p10" => "application/pkcs10",
                    "p12" => "application/x-pkcs12",
                    "p7b" => "application/x-pkcs7-certificates",
                    "p7c" => "application/x-pkcs7-mime",
                    "p7m" => "application/x-pkcs7-mime",
                    "p7r" => "application/x-pkcs7-certreqresp",
                    "p7s" => "application/x-pkcs7-signature",
                    "pbm" => "image/x-portable-bitmap",
                    "pdf" => "application/pdf",
                    "pfx" => "application/x-pkcs12",
                    "pgm" => "image/x-portable-graymap",
                    "pko" => "application/ynd.ms-pkipko",
                    "pma" => "application/x-perfmon",
                    "pmc" => "application/x-perfmon",
                    "pml" => "application/x-perfmon",
                    "pmr" => "application/x-perfmon",
                    "pmw" => "application/x-perfmon",
                    "pnm" => "image/x-portable-anymap",
                    "pot" => "application/vnd.ms-powerpoint",
                    "ppm" => "image/x-portable-pixmap",
                    "pps" => "application/vnd.ms-powerpoint",
                    "ppt" => "application/vnd.ms-powerpoint",
                    "prf" => "application/pics-rules",
                    "ps" => "application/postscript",
                    "pub" => "application/x-mspublisher",
                    "qt" => "video/quicktime",
                    "ra" => "audio/x-pn-realaudio",
                    "ram" => "audio/x-pn-realaudio",
                    "ras" => "image/x-cmu-raster",
                    "rgb" => "image/x-rgb",
                    "rmi" => "audio/mid",
                    "roff" => "application/x-troff",
                    "rtf" => "application/rtf",
                    "rtx" => "text/richtext",
                    "scd" => "application/x-msschedule",
                    "sct" => "text/scriptlet",
                    "setpay" => "application/set-payment-initiation",
                    "setreg" => "application/set-registration-initiation",
                    "sh" => "application/x-sh",
                    "shar" => "application/x-shar",
                    "sit" => "application/x-stuffit",
                    "snd" => "audio/basic",
                    "spc" => "application/x-pkcs7-certificates",
                    "spl" => "application/futuresplash",
                    "src" => "application/x-wais-source",
                    "sst" => "application/vnd.ms-pkicertstore",
                    "stl" => "application/vnd.ms-pkistl",
                    "stm" => "text/html",
                    "svg" => "image/svg+xml",
                    "sv4cpio" => "application/x-sv4cpio",
                    "sv4crc" => "application/x-sv4crc",
                    "t" => "application/x-troff",
                    "tar" => "application/x-tar",
                    "tcl" => "application/x-tcl",
                    "tex" => "application/x-tex",
                    "texi" => "application/x-texinfo",
                    "texinfo" => "application/x-texinfo",
                    "tgz" => "application/x-compressed",
                    "tif" => "image/tiff",
                    "tiff" => "image/tiff",
                    "tr" => "application/x-troff",
                    "trm" => "application/x-msterminal",
                    "tsv" => "text/tab-separated-values",
                    "txt" => "text/plain",
                    "uls" => "text/iuls",
                    "ustar" => "application/x-ustar",
                    "vcf" => "text/x-vcard",
                    "vrml" => "x-world/x-vrml",
                    "wav" => "audio/x-wav",
                    "wcm" => "application/vnd.ms-works",
                    "wdb" => "application/vnd.ms-works",
                    "wks" => "application/vnd.ms-works",
                    "wmf" => "application/x-msmetafile",
                    "wps" => "application/vnd.ms-works",
                    "wri" => "application/x-mswrite",
                    "wrl" => "x-world/x-vrml",
                    "wrz" => "x-world/x-vrml",
                    "xaf" => "x-world/x-vrml",
                    "xbm" => "image/x-xbitmap",
                    "xla" => "application/vnd.ms-excel",
                    "xlc" => "application/vnd.ms-excel",
                    "xlm" => "application/vnd.ms-excel",
                    "xls" => "application/vnd.ms-excel",
                    "xlt" => "application/vnd.ms-excel",
                    "xlw" => "application/vnd.ms-excel",
                    "xof" => "x-world/x-vrml",
                    "xpm" => "image/x-xpixmap",
                    "xwd" => "image/x-xwindowdump",
                    "z" => "application/x-compress",
                    "zip" => "application/zip"
                );
    foreach ( $mime_types as $ext => $mime_type ) {
        if( $ext === $extension )
            return $mime_type;
    }
}


/**
 * Making noReply Email from Host URL.
 * @author  Sisir Kanti Adhikari
 * @return string noreply@yourdomain.dom
 * ------------------------------------------------------------------------------
 */
function noreply_email(){
    $info = parse_url( home_url() );
    $host = $info['host'];
    $domain = preg_replace( '/^www./', '', $host );
    return 'noreply@'. $domain;
}


/**
 * Add a Settings link to Plugins page.
 * @param  array $links Default Links.
 * @return array        Merged with our defined links to settings page.
 * ------------------------------------------------------------------------------
 */
function nano_ed_add_plugin_action_links ( $links ) {
    $settings_page_link = array(
        '<a href="'. admin_url( 'admin.php?page=email_downloads' ) .'">'. __( 'Settings', 'email-downloads' ) .'</a>',
    );
    return array_merge( $links, $settings_page_link );
}
add_filter( 'plugin_action_links_'. plugin_basename(__FILE__), 'nano_ed_add_plugin_action_links' );


/**
 * Plugin Options Page (Settings).
 * using Settings API.
 * ------------------------------------------------------------------------------
 */
require_once 'ed-options.php';