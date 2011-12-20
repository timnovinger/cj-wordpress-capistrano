=== WordPress Backup to Dropbox ===
Contributors: michael.dewildt
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=38SEXDYP28CFA
Tags: backup, dropbox
Requires at least: 3.0
Tested up to: 3.1.2
Stable tag: trunk

A plugin for WordPress that automatically uploads your blogs files and a SQL dump of its database to Dropbox. Giving you
piece of mind that your entire blog including its precious posts, images and metadata regularly backed up.

== Description ==

WordPress Backup to Dropbox has been created to give you piece of mind that your blog is backed up on a regular basis.

Just choose a day, time and how often you wish yor backup to be performed and kick back and wait for your websites files
and a SQL dump of its database to be dropped in your Dropbox!

You can set where you want your backup stored within Dropbox and on your server

The plugin uses [OAuth](http://en.wikipedia.org/wiki/OAuth) so your Dropbox account details are not stored for the
plugin to gain access.

= Setup =

Once installed, the authorization process is pretty easy -

1. The plugin will ask you to authorize the plugin with Dropbox.

2. A new window open where Dropbox will ask you to authenticate in order allow this plugin access to your Dropbox.

3. Once you have granted access to the plugin click continue to setup your backup

= Errors and Warnings =

During the backup process the plugin may experience problems that will be raised as an error or a warning depending on
its severity.

A warning will be raised if your PHP installation is running in safe mode, if you get this warning please read my blog
post on dealing with this.

If the backup encounters a file that is larger then what can be safely handheld within the memory limit of your PHP
installation, or the file fails to upload to Dropbox it will be skipped and a warning will be raised.

The plugin attempts to recover from an error that may occur during a backup where backup process goes away for an unknown
reason. In this case the backup will be restarted from where it left off. Unfortunately, at this time, it cannot recover
from other errors, however a message should be displayed informing you of the reason for failure.

= Minimum Requirements =

1. PHP 5.2 or higher

2. [A Dropbox account](https://www.dropbox.com/referrals/NTM1NTcwNjc5)

For more information, news and updates please visit my blog - http://www.mikeyd.com.au/wordpress-backup-to-dropbox/

You can pull the source from my BitBucket account - https://bitbucket.org/michaeldewildt/wordpress-backup-to-dropbox

If you notice any bugs or want to request a feature please do so on BitBucket - https://bitbucket.org/michaeldewildt/wordpress-backup-to-dropbox/issues/

= Translators =

* Arabic (ar) - [Saif Maki](www.saif.cz.cc)
* Brazilian Portuguese (pt_BR) - [Techload Informatica](http://www.techload.com.br)
* Galician (gl_ES), Spanish (es_ES), Portuguese (pt_PT) - [WordPress Galego](http://gl.wordpress.org/)

== Installation ==

1. Upload the contents of `wordpress-dropbox-backup.zip` to the `/wp-content/plugins/` directory or use WordPress' built-in plugin install tool
2. Activate the plugin through the 'Plugins' menu within WordPress
3. Authorize the plugin with Dropbox by following the instructions in the settings page found under Settings->Backup to Dropbox

== Frequently Asked Questions ==

= How do I get a free Dropbox account? =

Browse to http://db.tt/szCyl7o and create a free account.

= Why doesn't my backup execute at the exact time I set? =

The backup is executed using WordPress' scheduling system that, unlike a cron job, kicks of tasks the next time your
blog is accessed after the scheduled time.

= Where is my database SQL dump located? =
The database us backed up into a file named '[database name]-backup.sql'. It will be found within the local backup location
you have set. Using the default settings the file will be found at the path 'WordPressBackups/wp-content/backups' within
your Dropbox.

= Can I perform a backup if my PHP installation has safe mode enabled? =
Yes you can, however you need to modify the max execution time in your php.ini manually.
[Please read this blog post for more information.](http://www.mikeyd.com.au/2011/05/24/setting-the-maximum-execution-time-when-php-is-running-in-safe-mode/)

== Screenshots ==

1. The WordPress Backup to Dropbox options page

== Changelog ==

= 0.8 =
* A major change to improve performance. The wordpress files are no longer zipped, instead they are individually uploaded
if they have been modified since the last backup.
* Added validation of the path fields to fix issue #11
* Changed the path include order to fix issue #14
* Disabled the day select list if the daily frequency is selected to fix issue #8
* For more information please visit http://www.mikeyd.com.au/2011/05/26/wordpress-backup-to-dropbox-0-8/

= 0.7.2 =
* Automatically add a htaccess file to the backups directory so your website archives are not exposed to the public

= 0.7.1 =
* Fixed issue #3: Backup starts but fails without an error message due to the zip process running out of memory
* Removed 'double zipping' of archive. Now the SQL dump will appear in 'wp-content/backups'
* Fixed an issue where backup now was removing periodic backups
* Added upload started history item
* Added create database statement to db dump
* Added error messages for missing required php extensions
* Removed extra 'the' resolves issue #7

= 0.7 =
* Added feature #4: Backup now button
* Fixed issue #2: Allow legitimately empty tables in backup
* Fixed some minor look and feel issues
* Added logo artwork, default i18n POT file and a daily schedule interval

= 0.6 =
* Initial stable release

== Upgrade Notice ==

* A major change to improve performance. The WordPress files are no longer zipped, instead they are individually uploaded
if they have been modified since the last backup. It is highly recommended that you upgrade because zipping will no longer
be supported.
