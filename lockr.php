<?php
/**
 * Main functions and functionality for Lockr in WordPress.
 *
 * @package Lockr
 */

/*
Plugin Name: Lockr
Plugin URI: https://lockr.io/
Description: Integrate with the Lockr hosted secrets management platform. Secure all your plugin passwords, API tokens and encryption keys according to industry best practices. With Lockr, secrets management is easy.
Version: 2.4
Author: Lockr
Author URI: htts://lockr.io/
License: GPLv2 or later
Text Domain: lockr
*/

// Don't call the file directly and give up info!
if ( ! function_exists( 'add_action' ) ) {
	echo 'Lock it up!';
	exit;
}

define( 'LOCKR__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOCKR__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Create database table for keys in the system.
 *
 * @file
 */

register_activation_hook( __FILE__, 'lockr_install' );

/**
 * Hook implementations and callbacks for lockr.
 *
 * @file
 */

use Lockr\Exception\LockrException;
use Lockr\Exception\LockrClientException;
use Lockr\KeyClient;
use Lockr\Lockr;
use Lockr\NullPartner;
use Lockr\Partner;
use Lockr\SiteClient;

/**
 * Include our autoloader.
 */
require_once LOCKR__PLUGIN_DIR . '/lockr-autoload.php';

/**
 * Include our partners.
 */
require_once LOCKR__PLUGIN_DIR . '/lockr-partners.php';

/**
 * Include our overrides.
 */
require_once LOCKR__PLUGIN_DIR . '/lockr-overrides.php';

/**
 * Include our post encryption filters.
 */
require_once LOCKR__PLUGIN_DIR . '/lockr-secure-posts.php';

/**
 * Include our WP CLI Commands if available.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/lockr-command.php';
}

/**
 * Include our admin functions.
 */
if ( is_admin() ) {
	require_once LOCKR__PLUGIN_DIR . '/lockr-admin.php';
}

/**
 * Set our db version which will be updated should the schema change.
 */
global $lockr_db_version;
$lockr_db_version = '1.1';

/**
 * Initial setup when the plugin is activated.
 */
function lockr_install() {
	global $wpdb;
	global $lockr_db_version;
	$current_lockr_db_version = get_option( 'lockr_db_version' );

	if ( $current_lockr_db_version !== $lockr_db_version ) {
		$table_name      = $wpdb->prefix . 'lockr_keys';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT null AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT null,
			key_name tinytext NOT null,
			key_value text NOT null,
			key_label text NOT null,
			key_abstract text,
			option_override text,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		add_option( 'lockr_db_version', $lockr_db_version );
	}

	$partner = lockr_get_partner();

	if ( $partner ) {
		add_option( 'lockr_partner', $partner['name'] );
	}

	lockr_auto_register( $partner );
}

/**
 * Create Lockr certificate files.
 *
 * @param string $dir The directory to save the files to.
 * @param array  $texts The text of the certificate files.
 */
function lockr_write_cert_pair( $dir, $texts ) {
	@mkdir( $dir, 0700, true );

	$ht_file = fopen( "{$dir}/.htaccess", 'w' );
	fwrite( $ht_file, "Order deny,allow\nDeny from all\n" );
	fclose( $ht_file );

	$key_file = "{$dir}/key.pem";
	$key_fd   = fopen( $key_file, 'w' );
	fwrite( $key_fd, $texts['key_text'] );
	fclose( $key_fd );
	chmod( $key_file, 0600 );

	$cert_file = "{$dir}/crt.pem";
	$cert_fd   = fopen( $cert_file, 'w' );
	fwrite( $cert_fd, $texts['cert_text'] );
	fclose( $cert_fd );
	chmod( $cert_file, 0600 );

	$pair_file = "{$dir}/pair.pem";
	$pair_fd   = fopen( $pair_file, 'w' );
	fwrite( $pair_fd, $texts['key_text'] );
	fwrite( $pair_fd, $texts['cert_text'] );
	fclose( $pair_fd );
	chmod( $pair_file, 0600 );
}

/**
 * Check if any DB updates are needed, and if so run install over again.
 */
function lockr_update_db_check() {
	global $lockr_db_version;
	if ( get_option( 'lockr_db_version' ) !== $lockr_db_version ) {
		lockr_install();
	}
}
add_action( 'plugins_loaded', 'lockr_update_db_check' );

/**
 * Returns the Lockr site client.
 */
function lockr_site_client() {
	$base_client = lockr_client();

	if ( false === $base_client ) {
		return false;
	}

	$client = new SiteClient( $base_client );

	return $client;
}

/**
 * Returns the Lockr key client.
 */
function lockr_key_client() {
	$base_client = lockr_client();

	if ( false === $base_client ) {
		return false;
	}

	$client = new KeyClient( $base_client );

	return $client;
}

/**
 * Returns the Lockr client for this site.
 */
function lockr_client() {
	static $client;

	if ( ! isset( $client ) ) {
		$client = Lockr::create( lockr_partner() );
	}

	return $client;
}

/**
 * Returns the current partner for this site.
 */
function lockr_partner() {
	$region = get_option( 'lockr_region', 'us' );

	if ( get_option( 'lockr_cert', false ) ) {

		$partner = get_option( 'lockr_partner', null );

		if ( 'custom' === $partner ) {
			$cert_path = get_option( 'lockr_cert' );
			if ( $cert_path ) {
				return new Partner( $cert_path, 'custom', $region );
			}

			return new NullPartner( $region );
		}
	}

	$detected_partner = lockr_get_partner();
	if ( ! $detected_partner ) {
		return new NullPartner( $region );
	}

	return new Partner(
		$detected_partner['cert'],
		$detected_partner['name'],
		$region
	);
}

/**
 * Returns if this site is currently registered with Lockr.
 *
 * @return bool
 * true if this site is registered, false if not.
 */
function lockr_check_registration() {
	$status = array(
		'cert_valid' => false,
		'exists'     => false,
		'available'  => false,
		'has_cc'     => false,
		'info'       => array( 'partner' => null ),
	);

	$client = lockr_site_client();

	try {
		if ( $client ) {
			$status = $client->exists();

			$partner = lockr_get_partner();
			lockr_auto_register( $partner, $status['info']['env'] );
		}
	} catch ( LockrClientException $e ) {
		$status = array(
			'cert_valid' => false,
			'exists'     => false,
			'available'  => false,
			'has_cc'     => false,
			'info'       => array( 'partner' => null ),
		);
	}

	return $status;
}

/**
 * Encrypt plaintext using a key from Lockr.
 *
 * @param string $plaintext The plaintext to be encrypted.
 * @param string $key_name The key name in Lockr.
 *
 * @return string|null
 *   The encrypted and encoded ciphertext or null if encryption fails.
 */
function lockr_encrypt( $plaintext, $key_name = 'lockr_default_key' ) {
	$cipher = MCRYPT_RIJNDAEL_256;
	$mode   = MCRYPT_MODE_CBC;

	$key = lockr_get_key( $key_name );
	if ( ! $key ) {
		return null;
	}

	$key = base64_decode( $key );

	$iv_len = mcrypt_get_iv_size( $cipher, $mode );
	$iv     = mcrypt_create_iv( $iv_len );

	$ciphertext = mcrypt_encrypt( $cipher, $key, $plaintext, $mode, $iv );
	if ( false === $ciphertext ) {
		return null;
	}

	$iv = base64_encode( $iv );
	if ( false === $iv ) {
		return null;
	}

	$ciphertext = base64_encode( $ciphertext );
	if ( false === $ciphertext ) {
		return null;
	}

	$parts   = array(
		'cipher'     => $cipher,
		'mode'       => $mode,
		'key_name'   => $key_name,
		'iv'         => $iv,
		'ciphertext' => $ciphertext,
	);
	$encoded = wp_json_encode( $parts );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return null;
	}

	return $encoded;
}

/**
 * Decrypt ciphertext using a key from Lockr.
 *
 * @param string $encoded The encrypted and encoded ciphertext.
 *
 * @return string|null The plaintext or null if decryption fails.
 */
function lockr_decrypt( $encoded ) {
	$parts = json_decode( $encoded, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return null;
	}

	if ( ! isset( $parts['cipher'] ) ) {
		return null;
	}
	$cipher = $parts['cipher'];

	if ( ! isset( $parts['mode'] ) ) {
		return null;
	}
	$mode = $parts['mode'];

	if ( ! isset( $parts['key_name'] ) ) {
		return null;
	}
	$key = lockr_get_key( $parts['key_name'] );
	if ( ! $key ) {
		return null;
	}
	$key = base64_decode( $key );

	if ( ! isset( $parts['iv'] ) ) {
		return null;
	}
	$iv = base64_decode( $parts['iv'] );
	if ( false === $iv ) {
		return null;
	}

	if ( ! isset( $parts['ciphertext'] ) ) {
		return null;
	}
	$ciphertext = base64_decode( $parts['ciphertext'] );
	if ( false === $ciphertext ) {
		return null;
	}

	$plaintext = mcrypt_decrypt( $cipher, $key, $ciphertext, $mode, $iv );
	if ( false === $plaintext ) {
		return null;
	}

	return trim( $plaintext );
}

/**
 * Gets a key from Lockr.
 *
 * @param string $key_name The key name.
 * @return string | false
 * Returns the key value, or false on failure.
 */
function lockr_get_key( $key_name ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'lockr_keys';
	$key_store  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE key_name = %s", array( $key_name ) ) ); // WPCS: unprepared SQL OK.

	if ( null === $key_store ) {
		return false;
	}

	$encoded = $key_store[0]->key_value;
	$client  = lockr_key_client();

	try {
		if ( $client ) {
			return $client->encrypted( $encoded )->get( $key_name );
		} else {
			return false;
		}
	} catch ( \Exception $e ) {
		return false;
	}
}

/**
 * Sets a key value in lockr.
 *
 * @param string      $key_name The key name.
 * @param string      $key_value The key value.
 * @param string      $key_label The key label.
 * @param string|bool $option_override The exisiting key metadata if it exists.
 *
 * @return bool true if they key set successfully, false if not.
 */
function lockr_set_key( $key_name, $key_value, $key_label, $option_override = null ) {
	global $wpdb;
	$table_name   = $wpdb->prefix . 'lockr_keys';
	$key_abstract = '**************' . substr( $key_value, -4 );
	$key_exists   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE key_name = %s", array( $key_name ) ) ); // WPCS: unprepared SQL OK.
	if ( empty( $key_exists ) ) {
		$key_exists = null;
		$encoded    = null;
	} else {
		$encoded = $key_exists[0]->key_value;
	}

	$client = lockr_key_client();

	if ( false === $client ) {
		return false;
	}
	$client = $client->encrypted();

	try {
		$key_remote = $client->set( $key_name, $key_value, $key_label, $encoded );
	} catch ( LockrClientException $e ) {

		if ( 'Not Paid' === $e->title ) {
			return 'NOTE: Key was not set. Please go to <a href="https://lockr.io/">Lockr</a> and add a payment method to your account.';
		}
	} catch ( \Exception $e ) {
		return false;
	}

	if ( false !== $key_remote ) {
		// Setup our storage array.
		$key_data = array(
			'time'            => date( 'Y-m-d H:i:s' ),
			'key_name'        => $key_name,
			'key_label'       => $key_label,
			'key_value'       => $key_remote,
			'key_abstract'    => $key_abstract,
			'option_override' => $option_override,
		);

		if ( ! empty( $key_exists ) ) {
			$key_id    = array( 'id' => $key_exists[0]->id );
			$key_store = $wpdb->update( $table_name, $key_data, $key_id );
		} else {
			$key_store = $wpdb->insert( $table_name, $key_data );
		}

		return $key_store;
	}

	return false;
}

/**
 * Deletes a key from Lockr.
 *
 * @param string $key_name The key name.
 */
function lockr_delete_key( $key_name ) {
	$key_value = lockr_get_key( $key_name );

	$client = lockr_key_client();
	if ( $client ) {
		global $wpdb;
		global $lockr_all_keys;
		$table_name = $wpdb->prefix . 'lockr_keys';

		try {
			$client->delete( $key_name );
		} catch ( LockrException $e ) {
			return false;
		}

		if ( isset( $lockr_all_keys[ $key_name ] ) ) {
			$key = $lockr_all_keys[ $key_name ];
			// Set the value back into the option value.
			$new_option_array = explode( ':', $key->option_override );
			$option_name      = array_shift( $new_option_array );
			$existing_option  = get_option( $option_name );

			if ( $existing_option ) {
				if ( is_array( $existing_option ) ) {

					$serialized_data_ref = &$existing_option;
					foreach ( $new_option_array as $option_key ) {
						$serialized_data_ref = &$serialized_data_ref[ $option_key ];
					}
					$serialized_data_ref = $key_value;
					unset( $lockr_all_keys[ $key_name ] );
					update_option( $option_name, $existing_option );
				} else {
					unset( $lockr_all_keys[ $key_name ] );
					update_option( $option_name, $key_value );
				}
			} else {
				unset( $lockr_all_keys[ $key_name ] );
				update_option( $key->option_override, $key_value );
			}
		}

		$key_store  = array( 'key_name' => $key_name );
		$key_delete = $wpdb->delete( $table_name, $key_store );
		if ( ! empty( $key_delete ) ) {
			return true;
		}
	}
}

/**
 * Performs a generic option-override.
 *
 * @param string $option_name The name of the option to override.
 * @param string $key_name The key name in Lockr to override with.
 * @param string $key_desc The description of the key to be stored.
 */
function lockr_override_option( $option_name, $key_name, $key_desc ) {
	$option_value = get_option( $option_name );

	if ( '' === $option_value || substr( $option_value, 0, 5 ) === 'lockr' ) {
		return;
	}

	if ( lockr_set_key( $key_name, $option_value, $key_desc ) ) {
		update_option( $option_name, $key_name );
	}
}

/**
 * Gets a possibly overridden option value.
 *
 * @param string $option_name The name of the overridden option.
 */
function lockr_get_override_value( $option_name ) {
	$option_value = get_option( $option_name );

	if ( substr( $option_value, 0, 5 ) !== 'lockr' ) {
		return $option_value;
	}

	$lockr_key = lockr_get_key( $option_value );
	if ( $lockr_key ) {
		return $lockr_key;
	}

	return $option_value;
}
