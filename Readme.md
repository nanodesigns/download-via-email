# Email Downloads
Embed a form in your pages and posts that accept an email address in exchange for a file to download. The plugin is simpler, quicker, with minimal database usage, and completely in WordPress' way.

Version 1.0.0<br>
**Developers:** Mayeenul Islam ([@mayeenulislam](http://twitter.com/mayeenulislam)), Sisir Kanti Adhikari ([@prionkor](http://twitter.com/prionkor))<br>
**Tested up to:** 4.3<br>
**License:** [GPLv2](http://www.gnu.org/licenses/gpl-2.0.html) or later

## Installation

1. Download the latest version of the plugin from Github release
2. Unzip the plugin, and Upload `email-downloads` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress admin panel
4. Change the necessary settings from the admin panel 'Email Downloads' menu page

Now, put the shortcode into page/post<br>
`[email-downloads file="absolute-path/to/the/file.ext"]`

## Frequently Asked Questions

### What is the shortcode?

<code>[email-downloads file="absolute-path/to/the/file.ext"]</code> is  the shortcode. You have to mention the absolute path of the file to let the user download the file. It is not necessary to upload the file to your WordPress site, it can be any public path, even can be a Dropbox, OneDrive, or Google Drive link

### Will my download link be encrypted?

Yes, we do not believe in sending raw absolute URL of a file, so it's by core encrypted to its maximum strength

### Is the mail HTML-formatted?

Yes, the email is HTML-formatted by default

### For how long the download link will be valid?

By default any link that is generated for an email address would be valid for 12 hours only

## Screenshots

![Email Downloads - how it will look like at the front end](assets/screenshot-1.png "Email Downloads - how it will look like at the front end")<br>
_Email Downloads - how it will look like at the front end_

![Email Downloads - the admin panel settings page](assets/screenshot-2.png "Email Downloads - the admin panel settings page")<br>
_Email Downloads - the admin panel settings page_

## Changelog

### 1.0.0
* A rudimentary WordPress plugin to enable sending download link to email address
* Completely in WordPress database schema - no other table, no column
* Email storage

__________________
Designed &amp; Developed by [**nano**designs](http://nanodesignsbd.com/)