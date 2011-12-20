<?php
/**
 * A class with functions the perform a backup of WordPress
 *
 * @copyright Copyright (C) 2011 Michael De Wildt. All rights reserved.
 * @author Michael De Wildt (http://www.mikeyd.com.au/)
 * @license This program is free software; you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation; either version 2 of the License, or
 *          (at your option) any later version.
 *
 *          This program is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          GNU General Public License for more details.
 *
 *          You should have received a copy of the GNU General Public License
 *          along with this program; if not, write to the Free Software
 *          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
 */
class WP_Backup {

    const BACKUP_STATUS_STARTED = 0;
    const BACKUP_STATUS_FINISHED = 1;
    const BACKUP_STATUS_WARNING = 2;
    const BACKUP_STATUS_FAILED = 3;

    const MAX_HISTORY_ITEMS = 100;

    /**
     * The users Dropbox options
     * @var object
     */
    private $options = null;

    /**
     * The users backup schedule
     * @var object
     */
    private $schedule = null;

    /**
     * The users backup history
     * @var object
     */
    private $history = null;

    /**
     * The Dropbox facade object
     * @var Dropbox_Facade
     */
    private $dropbox = null;

    /**
     * The database object
     * @var WpDb
     */
    private $database = null;

    /**
     * These files cannot be uploaded to Dropbox
     * @var array
     */
    private static $ignored_files = array( '.DS_Store', 'Thumbs.db' );

    /**
     * Construct the Backup class and pre load the schedule, history and options
     * @param Dropbox_Facade $dropbox
     * @param $wpdb
     * @return \WP_Backup
     */
    public function __construct( $dropbox, $wpdb ) {
        $this->dropbox = $dropbox;
        $this->database = $wpdb;

        //Load the history
        $this->history = get_option( 'backup-to-dropbox-history' );
        if ( !$this->history ) {
            add_option( 'backup-to-dropbox-history', array(), null, 'no' );
            $this->history = array();
        }

        //Load the options
        $this->options = get_option( 'backup-to-dropbox-options' );
        if ( !$this->options ) {
            //Options: Local backup location, Dropbox backup location, Keep local backups, Max backups to keep
            $this->options = array( 'wp-content/backups', 'WordPressBackup' );
            add_option( 'backup-to-dropbox-options', $this->options, null, 'no' );
        }

        //Load the schedule
        $time = wp_next_scheduled( 'execute_periodic_drobox_backup' );
        $frequency = wp_get_schedule( 'execute_periodic_drobox_backup' );
        if ( $time && $frequency ) {
            //Convert the time to the blogs timezone
            $blog_time = strtotime( date( 'Y-m-d H', strtotime( current_time( 'mysql' ) ) ) . ':00:00' );
            $blog_time += $time - strtotime( date( 'Y-m-d H' ) . ':00:00' );
            $this->schedule = array( $blog_time, $frequency );
        }

        if ( !get_option( 'backup-to-dropbox-last-action' ) ) {
            add_option( 'backup-to-dropbox-last-action', time(), null, 'no' );
        }
    }

    /**
     * Zips up all the files within this wordpress installation, compresses them and then saves the compressed
     * archive on the server.
     * @param $max_execution_time
     * @return string - Path to the database dump
     */
    public function backup_website( $max_execution_time ) {
        list( , $dropbox_location, , ) = $this->get_options();

        //Grab the memory limit setting in the php ini to ensure we do not exceed it
        $memory_limit_string = ini_get( 'memory_limit' );
        $memory_limit = ( preg_replace( '/\D/', '', $memory_limit_string ) * 1048576 );
        $max_file_size = $memory_limit / 2.5;

        $last_backup_time = $this->get_last_backup_time();
        $backup_stop_time = time() + $max_execution_time;

        if ( file_exists( ABSPATH ) ) {
            $source = realpath( ABSPATH );
            $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
            foreach ( $files as $file ) {

                if ( $max_execution_time && time() > $backup_stop_time ) {
                    $this->log( self::BACKUP_STATUS_FAILED, __( 'Backup did not complete because the maximum script execution time was reached.', 'wpbtd' ) );
                    return false;
                }

                $file = realpath( $file );
				if ( is_file( $file ) ) {
					//To ensure we don't exceed our memory requirements skip files that exceed our max
					if ( filesize( $file ) > $max_file_size ) {
						$this->log( self::BACKUP_STATUS_WARNING,
									sprintf( __( "file '%s' exceeds 40 percent of your PHP memory limit. The limit must be increased to back up this file.", 'wpbtd' ), basename( $file ) ) );
						continue;
					}

					//Is the file on the exclude list?
					$trimmed_file = basename( $file );
					if ( in_array( $trimmed_file, self::$ignored_files ) ) {
						continue;
					}

					//Get the path to where the file will reside in Dropbox
					$dropbox_path = $dropbox_location . DIRECTORY_SEPARATOR . str_replace( $source . DIRECTORY_SEPARATOR, '', $file );
					if ( PHP_OS == 'WINNT' ) {
						//The dropbox api requires a forward slash as the directory separator
            			$dropbox_path = str_replace( DIRECTORY_SEPARATOR, '/', $dropbox_path );
        			}
					
					//If the file does no exist in Dropbox or it has changed on the server then upload it
					$directory_contents = $this->dropbox->get_directory_contents( dirname( $dropbox_path ) );
					if ( !in_array( $trimmed_file, $directory_contents ) || filectime( $file ) > $last_backup_time ) {
						try {
							$this->dropbox->upload_file( $dropbox_path, $file );
						} catch ( Exception $e ) {
							if ( $e->getMessage() == 'Unauthorized' ) {
								$this->log( self::BACKUP_STATUS_FAILED, __( 'The plugin is no longer authorized with Dropbox.', 'wpbtd' ) );
								return false;
							}
							$msg = sprintf( __( "Could not upload '%s' due to the following error: %s", 'wpbtd' ), $file, $e->getMessage() );
							$this->log( self::BACKUP_STATUS_WARNING, $msg );
						}
					}
				}
				update_option( 'backup-to-dropbox-last-action', time() );
            }
            return true;
        }
        return false;
    }

    /**
     * Backs up the current WordPress database and saves it to
     * @return string
     */
    public function backup_database() {
        $db_error = __( 'Error while accessing database.', 'wpbtd' );

        $tables = $this->database->get_results( 'SHOW TABLES', ARRAY_N );
        if ( $tables === false ) {
            throw new Exception( $db_error . ' (ERROR_1)' );
        }

        list( $dump_location, , , ) = $this->get_options();

        $filename = ABSPATH . $dump_location . DIRECTORY_SEPARATOR . DB_NAME . '-backup.sql';
        $handle = fopen( $filename, 'w+' );
        if ( !$handle ) {
            throw new Exception( __( 'Error creating sql dump file.', 'wpbtd' ) . ' (ERROR_2)' );
        }

        $blog_time = strtotime( current_time( 'mysql' ) );

        //Some header information 
        $this->write_to_file( $handle, "-- WordPress Backup to Dropbox SQL Dump\n" );
        $this->write_to_file( $handle, "-- Version " . BACKUP_TO_DROPBOX_VERSION . "\n" );
        $this->write_to_file( $handle, "-- http://www.mikeyd.com.au/wordpress-backup-to-dropbox/\n" );
        $this->write_to_file( $handle, "-- Generation Time: " . date( "F j, Y", $blog_time ) . " at " . date( "H:i", $blog_time ) . "\n\n" );
        $this->write_to_file( $handle, 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\n\n" );

        //I got this out of the phpMyAdmin database dump to make sure charset is correct
        $this->write_to_file( $handle, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n" );
        $this->write_to_file( $handle, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n" );
        $this->write_to_file( $handle, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n" );
        $this->write_to_file( $handle, "/*!40101 SET NAMES utf8 */;\n\n" );

        //Create database statement
        $this->write_to_file( $handle, "--\n-- Create and use the backed up database\n--\n\n" );
        $this->write_to_file( $handle, "CREATE DATABASE " . DB_NAME . ";\n" );
        $this->write_to_file( $handle, "USE " . DB_NAME . ";\n\n" );

        foreach ( $tables as $t ) {
            $table = $t[ 0 ];

            //Header comment
            $this->write_to_file( $handle, "--\n-- Table structure for table `$table`\n--\n\n" );

            //Print the create table statement
            $table_create = $this->database->get_row( "SHOW CREATE TABLE $table", ARRAY_N );
            if ( $table_create === false ) {
                throw new Exception( $db_error . ' (ERROR_3)' );
            }
            $this->write_to_file( $handle, $table_create[ 1 ] . ";\n\n" );

            //Print the insert data statements
            $table_data = $this->database->get_results( "SELECT * FROM $table", ARRAY_A );
            if ( $table_data === false ) {
                throw new Exception( $db_error . ' (ERROR_4)' );
            }

            if ( empty( $table_data ) ) {
                $this->write_to_file( $handle, "--\n-- Table `$table` is empty\n--\n\n" );
                continue;
            }

            //Data comment
            $this->write_to_file( $handle, "--\n-- Dumping data for table `$table`\n--\n\n" );

            $fields = '`' . implode( '`, `', array_keys( $table_data[ 0 ] ) ) . '`';
            $this->write_to_file( $handle, "INSERT INTO `$table` ($fields) VALUES \n" );

            $out = '';
            foreach ( $table_data as $data ) {
                $data_out = '(';
                foreach ( $data as $value ) {
                    $value = addslashes( $value );
                    $value = str_replace( "\n", "\\n", $value );
                    $value = str_replace( "\r", "\\r", $value );
                    $data_out .= "'$value', ";
                }
                $out .= rtrim( $data_out, ' ,' ) . "),\n";
            }
            $this->write_to_file( $handle, rtrim( $out, ",\n" ) . ";\n" );
        }

        if ( !fclose( $handle ) ) {
            throw new Exception( __( 'Error closing sql dump file.' ) . ' (ERROR_5)' );
        }

        return true;
    }

    /**
     * Write the contents of out to the handle provided. Raise an exception if this fails
     * @throws Exception
     * @param  $handle
     * @param  $out
     * @return void
     */
    private function write_to_file( $handle, $out ) {
        if ( !fwrite( $handle, $out ) ) {
            throw new Exception( __( 'Error writing to sql dump file.', 'wpbtd' ) . ' (ERROR_6)' );
        }
    }

    /**
     * Schedules a backup to start now
     * @return void
     */
    public function backup_now() {
        wp_schedule_single_event( time(), 'execute_instant_drobox_backup' );
    }

    /**
     * Sets the day, time and frequency a wordpress backup is to be performed
     * @param  $day
     * @param  $time
     * @param  $frequency
     * @return void
     */
    public function set_schedule( $day, $time, $frequency ) {
        $blog_time = strtotime( date( 'Y-m-d H', strtotime( current_time( 'mysql' ) ) ) . ':00:00' );

        //Grab the date in the blogs timezone
        $date = date( 'Y-m-d', $blog_time );

        //Check if we need to schedule the backup in the future
        $time_arr = explode( ':', $time );
        $current_day = date( 'D', $blog_time );
        if ( $day && ( $current_day != $day ) ) {
            $date = date( 'Y-m-d', strtotime( "next $day" ) );
        } else if ( (int)$time_arr[ 0 ] <= (int)date( 'H', $blog_time ) ) {
            if ( $day ) {
                $date = date( 'Y-m-d', strtotime( "+7 days", $blog_time ) );
            } else {
                $date = date( 'Y-m-d', strtotime( "+1 day", $blog_time ) );
            }
        }

        $timestamp = wp_next_scheduled( 'execute_periodic_drobox_backup' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'execute_periodic_drobox_backup' );
        }

        //This will be in the blogs timezone
        $scheduled_time = strtotime( $date . ' ' . $time );

        //Convert the selected time to that of the server
        $server_time = strtotime( date( 'Y-m-d H' ) . ':00:00' ) + ( $scheduled_time - $blog_time );

        wp_schedule_event( $server_time, $frequency, 'execute_periodic_drobox_backup' );

        $this->schedule = array( $scheduled_time, $frequency );
    }

    /**
     * Return the backup schedule
     * @return array - day, time, frequency
     */
    public function get_schedule() {
        return $this->schedule;
    }

    /**
     * Set the dropbox backup options
     * @param  $dump_location - Local backup location
     * @param  $dropbox_location - Dropbox backup location
     * @return array()
     */
    public function set_options( $dump_location, $dropbox_location ) {
        static $regex = '/[^A-Za-z0-9-_\/]/';
        $errors = array();
        $error_msg = __( 'Invalid directory path. Path must only contain alphanumeric characters and the forward slash (\'/\') to separate directories.', 'wpbtd' );

        preg_match( $regex, $dump_location, $matches );
        if ( !empty( $matches ) ) {
            $errors[ 'dump_location' ] = array(
                'original' => $dump_location,
                'message' => $error_msg
            );
        }

        preg_match( $regex, $dropbox_location, $matches );
        if ( !empty( $matches ) ) {
            $errors[ 'dropbox_location' ] = array(
                'original' => $dropbox_location,
                'message' => $error_msg
            );
        }

        if ( empty( $errors ) ) {
            //Remove leading slashes
            $dump_location = ltrim( $dump_location, '/' );
            $dropbox_location = ltrim( $dropbox_location, '/' );

            //Remove tailing slashes
            $dump_location = rtrim( $dump_location, '/' );
            $dropbox_location = rtrim( $dropbox_location, '/' );

            //Replace extea slashes in between dirs
            $dump_location = preg_replace( '/[\/]+/', '/', $dump_location );
            $dropbox_location = preg_replace( '/[\/]+/', '/', $dropbox_location );

            $this->options = array( $dump_location, $dropbox_location );
            update_option( 'backup-to-dropbox-options', $this->options );
        }

        return $errors;
    }

    /**
     * Get the dropbox backup options if we don't have any options set the defaults
     * @return array - Dump location, Dropbox location, Keep local, Backup count
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Returns the backup history of this wordpress installation
     * @return array - time, status, message
     */
    public function get_history() {
        $hist = $this->history;
        krsort( $hist );
        return $hist;
    }

    /**
     * Updates the backup history option
     * @param $status
     * @param  $msg
     * @return void
     */
    public function log( $status, $msg = null ) {
        if ( count( $this->history ) >= self::MAX_HISTORY_ITEMS ) {
            array_shift( $this->history );
        }
        $this->history[ ] = array( strtotime( current_time( 'mysql' ) ), $status, $msg );
        update_option( 'backup-to-dropbox-history', $this->history );
    }

    /**
     * Execute the backup
     * @return bool
     */
    public function execute() {
        try {
            $this->log( WP_Backup::BACKUP_STATUS_STARTED );

            if ( !$this->dropbox->is_authorized() ) {
                $this->log( WP_Backup::BACKUP_STATUS_FAILED, __( "Your Dropbox account is not authorized yet.", 'wpbtd' ) );
                return false;
            }

            list( $dump_location, ) = $this->get_options();
            $dump_dir = ABSPATH . $dump_location;

            if ( !file_exists( $dump_dir ) ) {
                if ( !mkdir( $dump_dir ) ) {
                    throw new Exception( __( 'Error while creating the local dump directory.', 'wpbtd' ) );
                }
            }

            $this->create_htaccess_file( $dump_dir );

            $this->backup_database();
            if ( $this->backup_website( $this->set_time_limit() ) ) {
                $this->log( WP_Backup::BACKUP_STATUS_FINISHED );
                return true;
            }
        } catch ( Exception $e ) {
            $this->log( WP_Backup::BACKUP_STATUS_FAILED, "Exception - " . $e->getMessage() );
        }
        return false;
    }

    /**
     * Creates a htaccess file within the dump directory, if it does not already exist, so the public cannot see the sql
     * backup within the backup directory
     * @throws Exception
     * @param  $dump_dir
     * @return void
     */
    public function create_htaccess_file( $dump_dir ) {
        $htaccess = $dump_dir . '/.htaccess';
        if ( !file_exists( $htaccess ) ) {
            $fh = fopen( $htaccess, 'w' );
            $fw = fwrite( $fh, 'deny from all' );
            $fc = fclose( $fh );
            if ( !$fh || !$fw || !$fc ) {
                throw new Exception( __( 'error while creating htaccess file.', 'wpbtd' ) );
            }
        }
    }

    /**
     * If safe_mode is enabled then we need warn the user that the script may not finish
     * @throws Exception
     * @return int
     */
    public function set_time_limit() {
        if ( ini_get( 'safe_mode' ) ) {
            if ( ini_get( 'max_execution_time' ) != 0 ) {
                $this->log( self::BACKUP_STATUS_WARNING,
                            __( 'This php installation is running in safe mode so the time limit cannot be set.', 'wpbtd' ) . ' ' .
                            sprintf( __( 'Click %s for more information.', 'wpbtd' ),
                                     '<a href="http://www.mikeyd.com.au/2011/05/24/setting-the-maximum-execution-time-when-php-is-running-in-safe-mode/">' . __( 'here', 'wpbtd' ) . '</a>' ) );
                return ini_get( 'max_execution_time' ) - 5; //Lets leave 5 seconds of padding
            }
        } else {
            set_time_limit( 0 );
        }
        return 0;
    }

    /**
     * Returns the time of the last backup
     * @return int
     */
    public function get_last_backup_time() {
        $func = create_function( '$arr', 'return ( $arr[1] == WP_Backup::BACKUP_STATUS_FINISHED || $arr[1] == WP_Backup::BACKUP_STATUS_FAILED );' );
        $hist = array_filter( $this->history, $func );
        if ( !empty ( $hist ) ) {
            krsort( $hist );
            $hist = array_values( $hist );
            return $hist[ 0 ][ 0 ];
        }
        return false;
    }

    /**
     * Returns tre if a backup is in progress
     * @return bool
     */
    public function in_progress() {
        $hist = $this->get_history();
        list( , $status, ) = array_shift( $hist );
        if ( $status === null ) {
            return false;
        }
        return !( $status == self::BACKUP_STATUS_FINISHED || $status == self::BACKUP_STATUS_FAILED );
    }
}
