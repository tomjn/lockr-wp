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
add_filter( 'mc4wp_settings', 'lockr_mailchimp_load_override', 10, 1);

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

