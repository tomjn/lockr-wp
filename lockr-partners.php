<?php
/**
 * Setup Lockr Partner Automation.
 *
 * @package Lockr
 */

use Lockr\Exception\LockrException;
use Lockr\Exception\LockrClientException;
use Lockr\KeyClient;
use Lockr\Lockr;
use Lockr\NullPartner;
use Lockr\Partner;
use Lockr\SiteClient;

/**
 * Returns the detected partner, if available.
 */
function lockr_get_partner() {

	if ( defined( 'PANTHEON_BINDING' ) ) {
		$desc = <<<EOL
			The Pantheor is strong with this one.
			We're detecting you're on Pantheon and a friend of theirs is a friend of ours.
			Welcome to Lockr!
EOL;

		return array(
			'name'          => 'pantheon',
			'title'         => 'Pantheon',
			'description'   => $desc,
			'cert'          => '/srv/bindings/' . PANTHEON_BINDING . '/certs/binding.pem',
			'force_prod'    => false,
			'partner_certs' => true,
		);
	}

	if ( array_key_exists( 'KINSTA_CACHE_ZONE', $_SERVER ) ) {
		$desc = <<<EOL
			We're detecting you're on Kinsta and a friend of theirs is a friend of ours.
			Welcome to Lockr! We have already setup your connection automatically.
EOL;

		$staging = false;
		if ( defined( 'KINSTA_DEV_ENV' ) && KINSTA_DEV_ENV ) {
			$staging = true;
		}
		$dirname = ABSPATH . '.lockr';

		$dn = array(
			'countryName'         => 'US',
			'stateOrProvinceName' => 'California',
			'localityName'        => 'Los Angeles',
			'organizationName'    => 'Kinsta',
		);

		if ( $staging || ! file_exists( $dirname . '/prod/pair.pem' ) ) {
			$cert = $dirname . '/dev/pair.pem';
		} else {
			$cert = $dirname . '/prod/pair.pem';
		}
		return array(
			'name'          => 'custom',
			'title'         => 'Kinsta',
			'description'   => $desc,
			'cert'          => $cert,
			'dn'            => $dn,
			'dirname'       => $dirname,
			'force_prod'    => true,
			'partner_certs' => false,
		);
	}

	if ( defined( 'FLYWHEEL_CONFIG_DIR' ) ) {
		$desc = <<<EOL
			We're detecting you're on Flywheel and a friend of theirs is a friend of ours.
			Welcome to Lockr! We have already setup your connection automatically.
EOL;

		$staging = false;
		$dirname = '/www/.lockr';

		$dn = array(
			'countryName'         => 'US',
			'stateOrProvinceName' => 'Nebraska',
			'localityName'        => 'Omaha',
			'organizationName'    => 'Flywheel',
		);

		if ( $staging || ! file_exists( $dirname . '/prod/pair.pem' ) ) {
			$cert = $dirname . '/dev/pair.pem';
		} else {
			$cert = $dirname . '/prod/pair.pem';
		}
		return array(
			'name'          => 'custom',
			'title'         => 'Flywheel',
			'description'   => $desc,
			'cert'          => $cert,
			'dn'            => $dn,
			'dirname'       => $dirname,
			'force_prod'    => true,
			'partner_certs' => false,
		);
	}

	if ( isset( $_SERVER['IS_WPE'] ) ) {
		$desc = <<<EOL
			We're detecting you're on WP Engine and a friend of theirs is a friend of ours.
			Welcome to Lockr! We have already setup your connection automatically.
EOL;

		$staging = false;
		$dirname = ABSPATH . '.lockr';

		if ( isset( $_SERVER['WPENGINE_ACCOUNT'] ) ) {
			$account_name = ' - ' . sanitize_text_field( wp_unslash( $_SERVER['WPENGINE_ACCOUNT'] ) );
		} else {
			$account_name = '';
		}

		$dn = array(
			'countryName'         => 'US',
			'stateOrProvinceName' => 'Texas',
			'localityName'        => 'Austin',
			'organizationName'    => 'WP Engine' . $account_name,
		);

		if ( $staging || ! file_exists( $dirname . '/prod/pair.pem' ) ) {
			$cert = $dirname . '/dev/pair.pem';
		} else {
			$cert = $dirname . '/prod/pair.pem';
		}
		return array(
			'name'          => 'custom',
			'title'         => 'WPEngine',
			'description'   => $desc,
			'cert'          => $cert,
			'dn'            => $dn,
			'dirname'       => $dirname,
			'force_prod'    => true,
			'partner_certs' => false,
		);
	}

	if ( defined( 'GD_VIP' ) ) {
		$desc = <<<EOL
			We're detecting you're on GoDaddy and a friend of theirs is a friend of ours.
			Welcome to Lockr! We have already setup your connection automatically.
EOL;

		$staging = false;
		$dirname = ABSPATH . '.lockr';

		$dn = array(
			'countryName'         => 'US',
			'stateOrProvinceName' => 'Arizona',
			'localityName'        => 'Scottsdale',
			'organizationName'    => 'GoDaddy',
		);

		if ( $staging || ! file_exists( $dirname . '/prod/pair.pem' ) ) {
			$cert = $dirname . '/dev/pair.pem';
		} else {
			$cert = $dirname . '/prod/pair.pem';
		}
		return array(
			'name'          => 'custom',
			'title'         => 'GoDaddy',
			'description'   => $desc,
			'cert'          => $cert,
			'dn'            => $dn,
			'dirname'       => $dirname,
			'force_prod'    => true,
			'partner_certs' => false,
		);
	}

	if ( isset( $_SERVER['SERVER_ADMIN'] ) && false !== strpos( 'siteground', sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADMIN'] ) ) ) ) {
		$desc = <<<EOL
			We're detecting you're on Siteground and a friend of theirs is a friend of ours.
			Welcome to Lockr! We have already setup your connection automatically.
EOL;

		$staging = false;
		$dirname = ABSPATH . '.lockr';

		$dn = array(
			'countryName'         => 'BG',
			'stateOrProvinceName' => 'Sofia City',
			'localityName'        => 'Sofia',
			'organizationName'    => 'Siteground',
		);

		if ( $staging || ! file_exists( $dirname . '/prod/pair.pem' ) ) {
			$cert = $dirname . '/dev/pair.pem';
		} else {
			$cert = $dirname . '/prod/pair.pem';
		}
		return array(
			'name'          => 'custom',
			'title'         => 'Siteground',
			'description'   => $desc,
			'cert'          => $cert,
			'dn'            => $dn,
			'dirname'       => $dirname,
			'force_prod'    => true,
			'partner_certs' => false,
		);
	}

	if ( false !== strpos( gethostname(), 'bluehost' ) ) {
		$desc = <<<EOL
			We're detecting you're on Bluehost and a friend of theirs is a friend of ours.
			Welcome to Lockr! We have already setup your connection automatically.
EOL;
		$staging = false;

		if ( 'staging' === get_option( 'staging_environment' ) ) {
			$staging = true;
		}

		if ( $staging ) {
			$dirname = ABSPATH . '../../.lockr';
		} else {
			$dirname = ABSPATH . '.lockr';
		}

		$dn = array(
			'countryName'         => 'US',
			'stateOrProvinceName' => 'Utah',
			'localityName'        => 'Provo',
			'organizationName'    => 'Bluehost',
		);

		if ( $staging || ! file_exists( $dirname . '/prod/pair.pem' ) ) {
			$cert = $dirname . '/dev/pair.pem';
		} else {
			$cert = $dirname . '/prod/pair.pem';
		}
		return array(
			'name'          => 'custom',
			'title'         => 'Bluehost',
			'description'   => $desc,
			'cert'          => $cert,
			'dn'            => $dn,
			'dirname'       => $dirname,
			'force_prod'    => true,
			'partner_certs' => false,
		);
	}

	if ( defined( 'LWMWP_SITE' ) ) {
		$desc = <<<EOL
			We're detecting you're on Liquid Web and a friend of theirs is a friend of ours.
			Welcome to Lockr! We have already setup your connection automatically.
EOL;
		$staging = false;
		if ( defined( 'LWMWP_STAGING_SITE' ) && LWMWP_STAGING_SITE ) {
			$staging = true;
		}

		$dirname = ABSPATH . '.lockr';

		$dn = array(
			'countryName'         => 'US',
			'stateOrProvinceName' => 'Michigan',
			'localityName'        => 'Lansing',
			'organizationName'    => 'LiquidWeb',
		);

		if ( $staging || ! file_exists( $dirname . '/prod/pair.pem' ) ) {
			$cert = $dirname . '/dev/pair.pem';
		} else {
			$cert = $dirname . '/prod/pair.pem';
		}
		return array(
			'name'          => 'custom',
			'title'         => 'Liquid Web',
			'description'   => $desc,
			'cert'          => $cert,
			'dn'            => $dn,
			'dirname'       => $dirname,
			'force_prod'    => true,
			'partner_certs' => false,
		);
	}

	return null;
}

/**
 * Setup the necessary auto registration certs.
 *
 * @param array  $partner The Partner array.
 * @param string $env The Envrionment to register.
 */
function lockr_auto_register( $partner = array(), $env = null ) {

	if ( empty( $partner['title'] ) ) {

		// If there's no partner, then auto create the certs.
		$dirname = ABSPATH . '.lockr';

		$dn = array(
			'countryName'         => 'US',
			'stateOrProvinceName' => 'Washington',
			'localityName'        => 'Tacoma',
			'organizationName'    => 'Lockr',
		);

		$force_prod = false;
	}

	// Sanitize the $env for use below.
	if ( 'dev' !== $env && 'prod' !== $env && null !== $env ) {
		$env = null;
	}

	if ( isset( $partner['dn'] ) && isset( $partner['dirname'] ) ) {
		$dn            = $partner['dn'];
		$dirname       = $partner['dirname'];
		$force_prod    = $partner['force_prod'];
		$partner_certs = $partner['partner_certs'];
	}

	// Now that we have the information, let's create the certs.
	create_partner_certs( $dn, $dirname, $env, $force_prod );
}

/**
 * Setup the necessary auto registration certs.
 *
 * @param array   $dn The dn array for the CSR.
 * @param string  $dirname The directory to put the certificates in.
 * @param string  $env The Environment we are creating certificates for.
 * @param boolean $force_prod Force creating the production cert.
 * @param boolean $partner_certs Whether the parnter already has certificates in place.
 */
function create_partner_certs( $dn = array(), $dirname = ABSPATH . '.lockr', $env = null, $force_prod = false, $partner_certs = false ) {

	if ( null === $env ) {
		$partner_null   = new NullPartner( 'us' );
		$partner_client = Lockr::create( $partner_null );
		$dev_client     = new SiteClient( $partner_client );

		try {
			$result = $dev_client->createCert( $dn );
		} catch ( LockrClientException $e ) {
			// No need to do anything as the certificate can be created manually.
			return;
		} catch ( LockrServerException $e ) {
			// No need to do anything as the certificate can be created manually.
			return;
		}

		if ( ! empty( $result['cert_text'] ) ) {
			lockr_write_cert_pair( $dirname . '/dev', $result );
			update_option( 'lockr_partner', 'custom' );
		}
	}

	if ( 'dev' === $env && ! file_exists( $dirname . '/prod/pair.pem' ) && $force_prod && ! $partner_certs ) {
		$partner_dev    = new Partner( $dirname . '/dev/pair.pem', 'custom', 'us' );
		$partner_client = Lockr::create( $partner_dev );
		$prod_client    = new SiteClient( $partner_client );

		try {
			$result = $prod_client->createCert( $dn );
		} catch ( LockrClientException $e ) {
			// No need to do anything as the certificate can be created manually.
			return;
		} catch ( LockrServerException $e ) {
			// No need to do anything as the certificate can be created manually.
			return;
		}

		if ( ! empty( $result['cert_text'] ) ) {
			lockr_write_cert_pair( $dirname . '/prod', $result );
		}
	}
}
