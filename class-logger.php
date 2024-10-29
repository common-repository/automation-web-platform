<?php
/**
 * Automation Web Platform Plugin.
 *
 * @package AutomationWebPlatform.
 */

class awp_logger {
	private $_handles;
	private $log_directory;

	public function __construct() {
		$upload_dir          = wp_upload_dir();
		$this->log_directory = $upload_dir['basedir'] . '/wawp-log/';
		wp_mkdir_p( $this->log_directory );
	}

	private function open( $handle ) {
		if ( isset( $this->_handles[ $handle ] ) ) {
			return true;
		}

		$log_file = $this->log_directory . $handle . '.log';

		if ( $this->_handles[ $handle ] = @fopen( $log_file, 'a' ) ) {
			// Set the file permissions to 0600 (read and write for owner only)
			@chmod( $log_file, 0600 );
			return true;
		}

		return false;
	}

	public function add( $handle, $message ) {
		if ( $this->open( $handle ) ) {
			@fwrite( $this->_handles[ $handle ], "$message\n" );
		}
	}

	public function clear( $handle ) {
		$log_file = $this->log_directory . $handle . '.log';

		// Check if the file exists
		if ( file_exists( $log_file ) ) {
			// Attempt to delete the file
			if ( @unlink( $log_file ) ) {
				// Remove the handle from the array if deletion was successful
				unset( $this->_handles[ $handle ] );
			} else {
				// Handle the error, e.g., log it or display a message
				error_log( "Failed to delete log file: $log_file" );
			}
		}
	}

	public function get_log_file( $handle ) {
		$log_file = $this->log_directory . "{$handle}.log";
		if ( file_exists( $log_file ) ) {
			return file_get_contents( $log_file );
		}
	}
}

