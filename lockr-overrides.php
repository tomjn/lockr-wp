<?php

/**
 * @file
 * Form callbacks for Lockr register form.
 */

// Don't call the file directly and give up info!
if ( ! function_exists( 'add_action' ) ) {
	echo 'Lock it up!';
	exit;
}

use Lockr\Exception\LockrClientException;
use Lockr\Exception\LockrServerException;

function lockr_init_all_keys() {
	global $wpdb;
	global $lockr_all_keys;
	$lockr_all_keys = array();

	$table_name = $wpdb->prefix . 'lockr_keys';
	$all_keys = $wpdb->get_results( "SELECT * FROM $table_name" );
	if ( ! is_array( $all_keys ) ) {
		return;
	}
	foreach ( $all_keys as $key ) {
		$lockr_all_keys[$key->key_name] = $key;
	}
}

function lockr_init_overrides_list() {
	global $lockr_all_keys;
	global $lockr_overrides_list;

	$lockr_overrides_list = array();

	foreach ( $lockr_all_keys as $key ) {
		if ( $key->option_override === null ) {
			continue;
		}
		$override_path = $key->option_override;
		$colon_pos = strpos( $override_path, ':' );
		if ( $colon_pos === FALSE ) {
			$override_name = $override_path;
		} else {
			$override_name = substr( $override_path, 0, $colon_pos );
		}
		$lockr_overrides_list[ $override_name ][] = $key->key_name;
	}
}

//Find our overrides
global $lockr_overrides_list;
lockr_init_all_keys();
lockr_init_overrides_list();

function lockr_option_get_override( $value, $option_name ) {
	global $lockr_all_keys;
	global $lockr_overrides_list;

	if ( isset( $lockr_overrides_list[ $option_name ] ) ) {
		$key_array = $lockr_overrides_list[ $option_name ];

		foreach ( $key_array as $key_name ) {
			$key_value = lockr_get_key( $key_name );

			if ( ! isset( $lockr_all_keys[ $key_name ] ) ) {
				continue;
			}

			$key = $lockr_all_keys[ $key_name ];
			// Set the value back into the option value
			$new_option_array = explode( ':', $key->option_override );
			$option_name = array_shift( $new_option_array );
			if ( is_array( $value ) ) {
				$serialized_data_ref = &$value;
				foreach ( $new_option_array as $option_key ) {
					$serialized_data_ref = &$serialized_data_ref[ $option_key ];
				}
				$serialized_data_ref = $key_value;
			} else {
				$value = $key_value;
			}
		}

		return $value;
	}
}

foreach ( array_keys( $lockr_overrides_list ) as $override_name ) {
	add_filter( 'option_' . $override_name, 'lockr_option_get_override', 1000, 2 );
}

function lockr_option_update_override( $option, $old_value, $value ) {
	global $lockr_all_keys;
	global $lockr_overrides_list;

	if ( isset( $lockr_overrides_list[ $option ] ) ) {
		$key_array = $lockr_overrides_list[ $option ];

		foreach( $key_array as $key_name ) {
			if ( ! isset( $lockr_all_keys[ $key_name ] ) ) {
				continue;
			}
			$key = $lockr_all_keys[ $key_name ];
			// Set the new value in Lockr
			$new_option_array = explode(':', $key->option_override );
			$option_name = array_shift( $new_option_array );
			if ( is_array( $value ) ) {
				$serialized_data_ref = &$value;
				foreach ( $new_option_array as $option_key ) {
					$serialized_data_ref = &$serialized_data_ref[ $option_key ];
				}
				$key_value = $serialized_data_ref;

			} else {
				$key_value = $value;
			}

			if( 'lockr_' . $key_name != $key_value ) {
				$key_label = $key->key_label;

				$key_store = lockr_set_key( $key_name, $key_value, $key_label, $key->option_override );
				if ( $key_store ) {
					if ( isset( $serialized_data_ref ) ) {
						$serialized_data_ref = 'lockr_' . $key_name;
					} else {
						$value = 'lockr_' . $key_name;
					}
				} else {
					return false;
				}
			}
		}
		update_option( $option_name, $value, false );
	}
}

add_action( 'updated_option', 'lockr_option_update_override', 1000, 3 );

function lockr_option_delete_override( $option ) {
	global $lockr_overrides_list;
	if ( isset( $lockr_overrides_list[ $option ] ) ) {
		$key_array = $lockr_overrides_list[ $option ];

		foreach ( $key_array as $key_name ) {
			lockr_delete_key( $key_name );
		}
	}
}

add_action( 'deleted_option', 'lockr_option_delete_override', 1000, 3 );

// DEPRECATED OVERRIDES - USE OVERRIDE UI FOR THIS NOW

//MailChimp for Wordpress Module
function lockr_mailchimp_load_override( $settings ) {
	global $lockr_all_keys;
	global $lockr_overrides_list;

	if ( isset( $lockr_overrides_list['mc4wp'] ) ) {
		return $settings;
	}
	if ( substr( $settings['api_key'], 0, 5 ) === 'lockr' ) {
		$api_key = $settings['api_key'];
		$lockr_key = lockr_get_key( $api_key );
		if ( $lockr_key ) {
			if ( isset( $lockr_all_keys[ $api_key ] ) ) {
				global $wpdb;
				$key = $lockr_all_keys[ $api_key ];
				$key_data = array(
					'time' => date( 'Y-m-d H:i:s' ),
					'key_name' => $key->key_name,
					'key_label' => $key->key_label,
					'key_value' => $key->key_value,
					'key_abstract' => $key->key_abstract,
					'option_override' => 'mc4wp:api_key',
				);
				$wpdb->update( $table_name, $key_data, $key->id );
			}
			$settings['api_key'] = $lockr_key;
		}
	}
	return $settings;
}

add_filter( 'mc4wp_settings', 'lockr_mailchimp_load_override', 10, 1 );

// Give Module **EXPERIMENTAL**
function lockr_give_get ( $value, $key, $default ) {
	$secure_keys = array(
		'live_secret_key',
		'plaid_secret_key',
	);

	// Get key from Lockr
	if ( in_array( $key, $secure_keys ) ) {
		global $lockr_overrides_list;
		if ( isset( $lockr_overrides_list['give_settings'] ) ) {
			return $value;
		}

		$key_value = lockr_get_key( $value );

		if ( $key_value ) {
			return $key_value;
		} else {
			return $value;
		}
	} else {
		return $value;
	}
}

add_filter( 'give_get_option', 'lockr_give_get', 10, 3 );

function lockr_give_cmb2_get( $return, $options, $cbm2 ) {
	$secure_keys = array(
		'live_secret_key',
		'plaid_secret_key',
	);

	// This function doesn't really override options, but is responsible
	// for getting them.
	$options = get_option( 'give_settings', $options );

	foreach ( $secure_keys as $key ) {
		if ( ! isset( $options[ $key ] ) ) {
			continue;
		}

		global $lockr_overrides_list;
		if( isset( $lockr_overrides_list['give_settings'] ) ) {
			continue;
		}

		$key_name = $options[ $key ];
		$key_value = lockr_get_key( $key_name );

		if ( $key_value ) {
			$options[ $key ] = $key_value;
		}
	}

	return $options;
}

add_filter( 'cmb2_override_option_get_give_settings', 'lockr_give_cmb2_get', 10, 3 );
