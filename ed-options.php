<?php
/**
 * Options Page.
 *
 * Page to set all the basic settings for the plugin.
 *
 * @package  email-downloads
 */

function email_downloads_add_admin_menu() {
	add_menu_page(
		__( 'Email Downloads', 'email-downloads' ),
		__( 'Email Downloads', 'email-downloads' ),
		'manage_options',
		'email_downloads',
		'email_downloads_options_page_callback',
		'dashicons-email-alt'
	);
}
add_action( 'admin_menu', 'email_downloads_add_admin_menu' );

function email_downloads_settings_init() { 

	register_setting( 'ed', 'email_downloads_settings' );

	add_settings_section(
		'email_downloads_ed_section',						//id
		__( 'Basic Settings', 'email-downloads' ),			//title
		'email_downloads_settings_section_callback',		//callback
		'ed'												//page
	);

	add_settings_field( 
		'ed_sender_email', 
		__( 'Sender Email', 'email-downloads' ), 
		'email_downloads_sender_email_render', 
		'ed', 
		'email_downloads_ed_section' 
	);

	add_settings_field( 
		'ed_sender_name', 
		__( 'Name of the Sender', 'email-downloads' ), 
		'email_downloads_sender_name_render', 
		'ed', 
		'email_downloads_ed_section' 
	);

}
add_action( 'admin_init', 'email_downloads_settings_init' );

function email_downloads_sender_email_render() {
	$options = get_option( 'email_downloads_settings' ); ?>

	<input type="email" class="regular-text" name="email_downloads_settings[ed_sender_email]" value="<?php echo $options['ed_sender_email'] ? $options['ed_sender_email'] : get_option( 'admin_email' ); ?>"> <em class="howto"><span class="dashicons dashicons-info"></span> <?php _e( "<strong>default:</strong> administrator's email address. The email function works better with something like <code>yourname@yourdomain.com</code>. Otherwise the email may not be sent properly.", "email-downloads" ); ?></em>

	<?php
}

function email_downloads_sender_name_render() {
	$options = get_option( 'email_downloads_settings' );
	$admin_email = get_option( 'admin_email' );
	$admin_user = get_user_by( 'email', $admin_email );
	?>

	<input type="text" class="regular-text" name="email_downloads_settings[ed_sender_name]" value="<?php echo $options['ed_sender_name'] ? $options['ed_sender_name'] : $admin_user->display_name; ?>"> <em class="howto"><span class="dashicons dashicons-info"></span> <?php _e( "<strong>default:</strong> administrator's display name", "email-downloads" ); ?></em>

	<?php
}

function email_downloads_settings_section_callback() {
	_e( 'Settings that will introduce you on every email', 'email-downloads' );
}

function email_downloads_options_page_callback() { ?>
	<form action='options.php' method='post'>		
		<h2><span class="dashicons dashicons-email-alt"></span> <?php _e( 'Email Downloads', 'email-downloads' ); ?></h2>		
		<?php
		settings_fields( 'ed' );
		do_settings_sections( 'ed' );
		submit_button();
		?>		
	</form>
	<?php
}