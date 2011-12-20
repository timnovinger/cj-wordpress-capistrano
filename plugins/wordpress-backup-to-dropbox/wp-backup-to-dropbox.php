<?php
/*
Plugin Name: WordPress Backup to Dropbox
Plugin URI: http://www.mikeyd.com.au/wordpress-backup-to-dropbox/
Description: A plugin for WordPress that automatically uploads your blogs files and a SQL dump of its database to Dropbox. Giving you piece of mind that your your entire blog including its precious posts, images and metadata regularly backed up.
Version: 0.8
Author: Michael De Wildt
Author URI: http://www.mikeyd.com.au
License: Copyright 2011  Michael De Wildt  (email : michael.dewildt@gmail.com)

        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License, version 2, as
        published by the Free Software Foundation.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
include( 'class-dropbox-facade.php' );
include( 'class-wp-backup.php' );

define( 'BACKUP_TO_DROPBOX_VERSION', '0.8' );

//We need to set the PEAR_Includes folder in the path
ini_set( 'include_path', dirname( __FILE__ ) . '/PEAR_Includes' . PATH_SEPARATOR . DEFAULT_INCLUDE_PATH );

/**
 * A wrapper function that adds an options page to setup Dropbox Backup
 * @return void
 */
function backup_to_dropbox_admin_menu() {
    add_options_page( 'Backup to Dropbox', 'Backup to Dropbox ', 8, 'backup-to-dropbox', 'backup_to_dropbox_admin_menu_contents' );
}

/**
 * A wrapper function that includes the backup to Dropbox options page
 * @return void
 */
function backup_to_dropbox_admin_menu_contents() {
    include( 'wp-backup-to-dropbox-options.php' );
}

/**
 * A wrapper function that executes the backup
 * @return void
 */
function execute_drobox_backup() {
    wp_clear_scheduled_hook( 'monitor_drobox_backup_hook' );
    wp_schedule_event( time(), 'every_min', 'monitor_dropbox_backup_hook' );
    global $wpdb;
    $backup = new WP_Backup( new Dropbox_Facade(), $wpdb );
    $backup->execute();
    wp_clear_scheduled_hook( 'monitor_drobox_backup_hook' );
}

/**
 * @return void
 */
function monitor_dropbox_backup() {
    $last_action = get_option( 'backup-to-dropbox-last-action' );
    //Two mins to allow for socket timeouts
    if ( $last_action < strtotime( '-2 minutes' ) ) {
        $backup = new WP_Backup( new Dropbox_Facade(), null );
        if ( $backup->in_progress() ) {
            $backup->log( WP_Backup::BACKUP_STATUS_FAILED, __( 'The backup process appears to have gone away. Resuming backup.' ) );
            $backup->backup_now();
        }
    }
}

/**
 * Adds a set of custom intervals to the cron schedule list
 * @param  $schedules
 * @return array
 */
function backup_to_dropbox_cron_schedules( $schedules ) {
    $new_schedules = array(
        'every_min' => array(
            'interval' => 60,
            'display' => 'every_min'
        ),
        'daily' => array(
            'interval' => 86400,
            'display' => 'Weekly'
        ),
        'weekly' => array(
            'interval' => 604800,
            'display' => 'Weekly'
        ),
        'fortnightly' => array(
            'interval' => 1209600,
            'display' => 'Fortnightly'
        ),
        'monthly' => array(
            'interval' => 2419200,
            'display' => 'Once Every 4 weeks'
        ),
        'two_monthly' => array(
            'interval' => 4838400,
            'display' => 'Once Every 8 weeks'
        ),
        'three_monthly' => array(
            'interval' => 7257600,
            'display' => 'Once Every 12 weeks'
        ),
    );
    return array_merge( $schedules, $new_schedules );
}

//WordPress filters and actions
add_filter( 'cron_schedules', 'backup_to_dropbox_cron_schedules' );
add_action( 'monitor_dropbox_backup_hook', 'monitor_dropbox_backup' );
add_action( 'execute_periodic_drobox_backup', 'execute_drobox_backup' );
add_action( 'execute_instant_drobox_backup', 'execute_drobox_backup' );
add_action( 'admin_menu', 'backup_to_dropbox_admin_menu' );

//i18n language text domain
load_plugin_textdomain( 'wpbtd', true, 'wordpress-backup-to-dropbox/Languages/' );
