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

use Lockr\Exception\ClientException;
use Lockr\Exception\ServerException;

//MailChimp for Wordpress Module
function lockr_mailchimp_load_override( $settings ) {
	if ( isset( $settings['api_key'] ) && $settings['api_key'] != '' && substr( $settings['api_key'], 0, 5 ) != 'lockr' ){
		//Save key to Lockr
		$key_name = 'lockr_mc4wp_api_key';
		$key_desc = 'Mailchimp API Key';
		if ( lockr_set_key( $key_name, $settings['api_key'], $key_desc ) ){
			$settings['api_key'] = $key_name;
			update_option( 'mc4wp', $settings );
		}
	}
	if ( substr( $settings['api_key'], 0, 5 ) == 'lockr' ) {
		$lockr_key = lockr_get_key( $settings['api_key'] );
		if ( $lockr_key ) {
			$settings['api_key'] = $lockr_key;
			return $settings;
		}
	}
	return $settings;
}

add_filter( 'mc4wp_settings', 'lockr_mailchimp_load_override', 10, 1 );

//Give Module **EXPERIMENTAL**
function lockr_give_get ( $value, $key, $default ) {
	$secure_keys = array(
		'live_secret_key',
		'plaid_secret_key',
	);

	//Get key from Lockr
	if( in_array( $key, $secure_keys ) ) {

		$key_value = lockr_get_key( $value );

		if ( $key_value ) {
			return $key_value;
		}
		else {
			return $value;
		}
	}
	else {
		return $value;
	}
}

add_filter( 'give_get_option', 'lockr_give_get', 10, 3);

function lockr_give_cmb2_get( $return, $options, $cbm2 ) {
	$secure_keys = array(
		'live_secret_key',
		'plaid_secret_key',
	);

	// This function doesn't really override options, but is responsible
	// for getting them.
	$options = get_option( 'give_settings', $options );

	foreach ( $secure_keys as $key ) {
		if ( ! isset( $options[$key] ) ) {
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

function lockr_give_set ( $return, $options, $cmb2 ) {
	$secure_keys = array(
		'live_secret_key',
		'plaid_secret_key',
		'dwolla_api_secret',
		'braintree_privateKey',
		'twocheckout-private-key',
		'give_mailchimp_api',
		'paytm_mer_access_key',
		'give_constant_contact_api',
		'give_constant_contact_access_token',
		'live_paypal_api_secret',
		'paymill_live_key',
		'wepay_client_secret',
		'wepay_access_token',
		'pagseguro_token',
		'mercadopago_client_secret',
		'ccavenue_live_access_code',
		'ccavenue_live_working_key',
		'payumoney_live_merchant_key',
		'payumoney_live_salt_key',
		'give_transaction_key',
		'iats_live_agent_password',
	);

	foreach( $secure_keys as $key ) {

		if( isset( $options[ $key ] ) ) {
			$value = $options[ $key ];

			//Save key to Lockr
			$key_name = 'give_' . $key;
			$key_desc = 'Give ' . $key;
			if ( lockr_set_key( $key_name, $value, $key_desc ) ){
				$options[ $key ] = $key_name;
			}
		}
	}

	return update_option( 'give_settings', $options );
}

add_filter( 'cmb2_override_option_save_give_settings', 'lockr_give_set', 10, 3 );
