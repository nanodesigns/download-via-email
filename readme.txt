=== Email Downloads ===
Contributors: wzislam, prionkor
Tags: email, downloads, email before download, download link to email
Requires at least: 3.0
Tested up to: 4.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Embed a form in your pages and posts that accept an email address in exchange for a file to download. The plugin is simpler, quicker, with minimal database usage, and completely in WordPress' way.

== Description ==

Embed a form in your pages and posts that accept an email address in exchange for a file to download. The plugin is simpler, quicker, with minimal database usage, and completely in WordPress' way.

== Installation ==

1. Download the latest version of the plugin from Github release
2. Unzip the plugin, and Upload `email-downloads` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress admin panel
4. Change the necessary settings from the admin panel 'Email Downloads' menu page

Now, put the shortcode into page/post
<code>[email-downloads file="path/to/the/file.ext"]</code>

== Frequently Asked Questions ==

= What is the shortcode =

<code>[email-downloads file="path/to/the/file.ext"]</code> is  the shortcode. You have to mention the absolute path of the file to let the user download the file. It is not necessary to upload the file to your WordPress site, it can be any public path, even can be a dropbox, onedrive, or google drive link

= Will my download link be encrypted =

Yes, we do not believe in sending raw absolute URL of a file, so it's by core encrypted to its maximum strength

= Is the mail HTML formatted =

Yes, the email is HTML formatted by default

= For how long the link will be valid? =

By default any link that is generated for an email address would be valid for 12 hours only

== Screenshots ==

1. **Email Downloads** - how it will look like at the front end
2. **Email Downloads** - the admin panel settings page

== Changelog ==

= 1.0.0 =
* A rudimentary WordPress plugin to enable sending download link to email address

== Upgrade Notice ==

= 1.0.0 =
A rudimentary WordPress plugin to enable sending download link to email address