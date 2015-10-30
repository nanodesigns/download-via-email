<?php
/**
 * Options Page.
 *
 * Page to set all the basic settings for the plugin.
 *
 * @package  download-via-email
 */

/**
 * Enqueue Stylesheet for admin Screen.
 * ------------------------------------------------------------------------------
 */
function email_downloads_enqueue_styles() {
	$screen = get_current_screen();
	if( $screen->id === 'toplevel_page_email_downloads' ) {
		wp_enqueue_style( 'nano_downloads_email', plugins_url('css/admin-stylesheet.css', __FILE__) );
	}
}
add_action( 'admin_enqueue_scripts', 'email_downloads_enqueue_styles' );

/**
 * Admin Menu Page.
 * ------------------------------------------------------------------------------
 */
function email_downloads_add_admin_menu() {
	add_menu_page(
		__( 'Downloads via Email', 'email-downloads' ),
		__( 'Downloads via Email', 'email-downloads' ),

		/**
		 * Filter the admin menu access.
		 * Filter hook: 'nano_ed_role'
		 * Default access privilege: administrator.
		 */
		apply_filters( 'nano_ed_role', 'manage_options' ),
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

	<input type="email" class="regular-text" name="email_downloads_settings[ed_sender_email]" value="<?php echo $options['ed_sender_email'] ? $options['ed_sender_email'] : noreply_email(); ?>"> <em class="howto"><span class="dashicons dashicons-info"></span> <?php printf( __( "Make sure to put an on-domain email address like <code>%1\$s</code>, otherwise the email may not be sent. <strong>default:</strong> %1\$s", "email-downloads" ), noreply_email() ); ?></em>

	<?php
}

function email_downloads_sender_name_render() {
	$options = get_option( 'email_downloads_settings' );
	$noreply_email = noreply_email();
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
	<div class="wrap">
		
		<form action='options.php' method='post'>

			<h2><?php printf( __( 'Downloads via Email <small>by <a class="link-none" href="%s"><strong>nano</strong>designs</a></small>', 'email-downloads' ), 'http://nanodesignsbd.com/' ); ?></h2>

			<div class="ed-row">
				<div class="ed-column-left">
					<?php
					settings_fields( 'ed' );
					do_settings_sections( 'ed' );
					submit_button();
					?>
				</div>
				<div class="ed-column-right">
					<?php require_once '__nanodesigns_promo.php'; ?>
				</div>
				<div class="ed-clearfix"></div>
			</div>

		</form>
			


	<?php
	/**
	 * Pagination in action.
	 * @author  Tareq Hasan
	 * @link http://tareq.wedevs.com/2011/07/simple-pagination-system-in-your-wordpress-plugins/
	 * --------------
	 */
	$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

	$posts_per_page = get_option( 'posts_per_page' );
	$offset = ( $pagenum - 1 ) * $posts_per_page;

	$get_emails = nano_ed_email_lists( $posts_per_page, $offset );

	if( $get_emails ) :
		$_counter = 0; ?>
			<hr>

			<h2><?php _e( 'Stored Email Address', 'email-downloads' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Serial', 'email-downloads' ); ?></th>
						<th><?php _e( 'Email', 'email-downloads' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($get_emails as $emails) :
					$_counter++;
					?>				
					<tr>
						<td><?php echo $_counter; ?></td>
						<td><?php echo $emails->email; ?></td>
					</tr>
				<?php
				endforeach;
				?>
				</tbody>
			</table>
			<?php
			global $wpdb;
			$table = $wpdb->prefix .'download_email';
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} GROUP BY id" );
			$num_of_pages = ceil( $total / $posts_per_page );

			$page_links = paginate_links( array(
			    'base'		=> add_query_arg( 'pagenum', '%#%' ),
			    'format'	=> '',
			    'prev_text'	=> '&laquo;',
			    'next_text'	=> '&raquo;',
			    'total'		=> $num_of_pages,
			    'current'	=> $pagenum
			) );
			 
			if ( $page_links ) {
			    echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">'. $page_links .'</div></div>';
			}
			?>
	<?php endif; ?>

	</div> <!-- .wrap -->
	<?php
}