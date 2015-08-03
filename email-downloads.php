<?php
/**
 * Plugin Name: Email Downloads
 * Plugin URI: http://nanodesignsbd.com/
 * Description: Embed a form in your pages and posts that accept an email address in exchange for a file download.
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


/**
 * Define the necessary data first
 */

$_sender = 'Mayeenul Islam';
$_from_email = 'info@nanodesignsbd.com';


/**
 * Shortcode
 * Usage: [email-downloads file="http://path/to/file.ext"]
 * @param  array $atts attributes that passed through shortcode.
 * @return string       formatted form.
 */
function nanodesigns_email_downalods_shortcode( $atts ) {    
    $atts = shortcode_atts( array( 'file' => '' ), $atts );
    $file_path = $atts['file'];

    if( isset( $_POST['download_submit'] ) ) {

        $email      = $_POST['download_email'];

        if( $email && is_email( $email ) )
            nanodesigns_email_downloads( $email, $file_path );

    }

    ob_start();
    ?>
    <div class="email-downloads">
        <form action="" enctype="multipart/form-data" method="post">
            <p><?php _e( 'Enter your email address to download the file', 'email-downloads' ); ?></p>
            <input type="email" name="download_email" id="download-email" value=""><br>
            <button type="submit" name="download_submit"><?php _e( 'Send me the File', 'email-downloads' ); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'email-downloads', 'nanodesigns_email_downalods_shortcode' );


function nanodesigns_email_downloads( $email, $file_path ) {
    if( $email && is_email($email) && $file_path ) :
        
        global $_sender, $_from_email;

        $to_email       = $email;
        $subject        = __( 'Download is ready!', 'email-downloads' );

        ob_start(); ?>

            <html lang="en">
                <head>
                    <title><?php echo $subject; ?></title>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                </head>
                <body style="line-height: 1; font-family: Georgia, 'Times New Roman', serif; font-size: 15px;">
                    <h2><?php _e('Wow! Download is Ready.', 'email-downloads' ); ?></h2>
                    <p><?php _e('Please follow the following link to download the file:', 'email-downloads' ); ?></p>
                    <p><a class="download-link" href="<?php echo esc_url( $file_path ); ?>" target="_blank" style="background-color: #E43435; color: #fff; padding: 4px 10px; border-radius: 4px; text-decoration: none;"><?php _e( 'Download File', 'email-downloads' ); ?></a></p>
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

        if( $sent )
            _e( '<p>The download link is sent to your email address. Check your inbox please', 'email-downloads</p>' );
        else
            _e( 'Sorry, an error occured', 'email-downloads' );

    endif;
}