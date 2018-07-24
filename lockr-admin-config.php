<?php

use Lockr\Exception\LockrClientException;
use Lockr\Exception\LockrServerException;

// Don't call the file directly and give up info!
if ( ! function_exists( 'add_action' ) ) {
	echo 'Lock it up!';
	exit;
}

function register_lockr_settings() {
	register_setting( 'lockr_options', 'lockr_options', 'lockr_options_validate' );
	wp_register_style( 'lockrStylesheet', plugins_url( 'css/lockr.css', __FILE__ ) );
	add_settings_section(
		'lockr_email',
		'Email Address',
		'lockr_email_text',
		'lockr'
	);
	add_settings_field(
		'lockr_account_email',
		'Email Address',
		'lockr_account_email_input',
		'lockr',
		'lockr_email'
	);

	add_settings_section(
		'lockr_password',
		'Account Password',
		'lockr_password_text',
		'lockr'
	);
	add_settings_field(
		'lockr_account_password',
		'Account Password',
		'lockr_account_password_input',
		'lockr',
		'lockr_password'
	);

	add_settings_section(
		'lockr_advanced',
		'Lockr Advanced Settings',
		'lockr_advanced_text',
		'lockr'
	);
	add_settings_field(
		'lockr_region',
		'Lockr Region',
		'lockr_region_input',
		'lockr',
		'lockr_region'
	);
	add_settings_field(
		'lockr_cert_path',
		'Custom Certificate Location',
		'lockr_cert_path_input',
		'lockr',
		'lockr_advanced'
	);

	add_settings_section(
		'lockr_csr',
		'Certificate Signing Request',
		'lockr_csr_text',
		'lockr'
	);
	add_settings_field(
		'lockr_csr_country',
		'Country',
		'lockr_csr_country_input',
		'lockr',
		'lockr_csr'
	);
	add_settings_field(
		'lockr_csr_state',
		'State or Province',
		'lockr_csr_state_input',
		'lockr',
		'lockr_csr'
	);
	add_settings_field(
		'lockr_csr_city',
		'Locality',
		'lockr_csr_city_input',
		'lockr',
		'lockr_csr'
	);
	add_settings_field(
		'lockr_csr_org',
		'Organization',
		'lockr_csr_org_input',
		'lockr',
		'lockr_csr'
	);
}

function lockr_email_text() {
}

function lockr_advanced_text() {
}

function lockr_request_text() {
}

function lockr_csr_text() {
}

function lockr_csr_country_input() {
?>
<input id="lockr_csr_country"
	   name="lockr_options[lockr_csr_country]"
	   placeholder="US" />
<?php
}

function lockr_csr_state_input() {
?>
<input id="lockr_csr_state"
	   name="lockr_options[lockr_csr_state]"
	   placeholder="Washington" />
<?php
}

function lockr_csr_city_input() {
?>
<input id="lockr_csr_city"
	   name="lockr_options[lockr_csr_city]"
	   placeholder="Seattle" />
<?php
}

function lockr_csr_org_input() {
?>
<input id="lockr_csr_org"
	   name="lockr_options[lockr_csr_org]"
	   placeholder="ACME Inc." />
<?php
}

function lockr_register_text() {
	echo "<p style='width: 80%;'>You're just one step away from secure key management! To register your site with Lockr, simply input an email address you'd like to associate your account with. If you're already a Lockr user, you can enter the email and password to login to your account and register this site. Dont' worry, we won't store your password locally.</p>";
}

function lockr_account_email_input() {
	$options = get_option( 'lockr_options' );
	$value = isset( $options['account_email'] )
		? $options['account_email']
		: '';
?>
<input id="lockr_account_email"
	   name="lockr_options[account_email]"
	   size="60"
	   type="email"
	   value="<?php echo $value; ?>" />
<?php
}

function lockr_partner_name_input() {
?>
<input id="lockr_partner_name"
       name="lockr_options[partner_name]"
       size="60"
	   type="text" />
<?php
}

function lockr_cert_path_input() {
	if ( get_option( 'lockr_partner' ) === 'custom' ) {
		$partner = false;
		$cert_path = get_option( 'lockr_cert' );
	} else {
		$partner = true;
		$cert_path = '';
	}
	?>
	<?php if ( ! $partner ): ?>
	  <p>
	  Use the following field to set the location of your custom certificate.
	  If you are on a supported hosting provider you do not need to enter any value here.
	  </p>
	<?php else: ?>
	  <p>
	  <strong>Our system has detected that your website is hosted on one of our supported providers,
	  this setting is not necessary under regular usage.</strong>
	  </p>
	  <p>Use the following field to set the location of your custom certificate.</p>
	<?php endif; ?>

<input id="lockr_cert_path"
       name="lockr_options[lockr_cert_path]"
       size="60"
       type="text"
       value="<?php echo $cert_path; ?>" />
<?php
}

function lockr_account_password_input() {
	$options = get_option( 'lockr_options' );
?>
<input id="lockr_account_email"
	   name="lockr_options[account_password]"
	   size="60"
	   type="password"
	   value="<?php echo $options['account_password']; ?>" />
<?php
}

function lockr_write_cert_pair( $dir, $texts ) {
	@mkdir( $dir, 0700, true );

	$ht_file = fopen( "{$dir}/.htaccess", 'w' );
	fwrite( $ht_file, "Order deny,allow\nDeny from all\n" );
	fclose( $ht_file );

	$key_file = "{$dir}/key.pem";
	$key_fd = fopen( $key_file, 'w' );
	fwrite( $key_fd, $texts['key_text'] );
	fclose( $key_fd );
	chmod( $key_file, 0600 );

	$cert_file = "{$dir}/crt.pem";
	$cert_fd = fopen( $cert_file, 'w' );
	fwrite( $cert_fd, $texts['cert_text'] );
	fclose( $cert_fd );
	chmod( $cert_file, 0600 );

	$pair_file = "{$dir}/pair.pem";
	$pair_fd = fopen( $pair_file, 'w' );
	fwrite( $pair_fd, $texts['key_text'] );
	fwrite( $pair_fd, $texts['cert_text'] );
	fclose( $pair_fd );
	chmod( $pair_file, 0600 );
}

function lockr_region_input() {
	$region = get_option( 'lockr_region' );
?>
<p>
Lockr speeds up key retrievals and helps to achieve compliance with data
protection laws by providing multiple regions to store your keys in.
<br><strong>Note: changing this after storing keys will require you to
re-input all keys stored in Lockr.</strong>
</p><br>
<fieldset>
  <label>
    <input type="radio"
	       name="lockr_options[lockr_region]"
	       id="lockr-region-us"
		   <?php if ( $region === 'us' || $region === false ): ?>
	       checked="checked"
		   <?php endif; ?>
	       value="us" />
	<span>United States</span>
  </label>
  <br>
  <label>
	<input type="radio"
	       name="lockr_options[lockr_region]"
		   id="lockr-region-eur"
		   <?php if ( $region === 'eu' ): ?>
		   checked="checked"
           <?php endif; ?>
		   value="eu" />
    <span>European Union</span>
  </label>
</fieldset>
<?php
}

function lockr_options_validate( $input ) {
	$options = get_option( 'lockr_options' );
	if (!is_array($options)) {
		$options = array();
	}

	if ( ! isset( $input['lockr_op'] ) ) {
		return $options;
	}
	$op = $input['lockr_op'];

	if ( $op === 'gencert' ) {
		$dn = array(
			'countryName' => $input['lockr_csr_country'],
			'stateOrProvinceName' => $input['lockr_csr_state'],
			'localityName' => $input['lockr_csr_city'],
			'organizationName' => $input['lockr_csr_org'],
		);
		delete_option( 'lockr_cert' );

		$site_client = lockr_site_client();

		try {
			$result = $site_client->createCert( $dn );
		} catch ( LockrClientException $e ) {
			add_settings_error(
				'lockr_options',
				'lockr-csr',
				'Please check form inputs.'
			);
			return $options;
		} catch ( LockrServerException $e ) {
			add_settings_error(
				'lockr_options',
				'lockr-csr',
				'Lockr encountered an unexpected'
			);
			return $options;
		}

		$rand_bytes = openssl_random_pseudo_bytes(4);
		$dir = WP_CONTENT_DIR . '/lockr/dev-' . bin2hex($rand_bytes);

		lockr_write_cert_pair( $dir, $result );

		update_option( 'lockr_cert', "{$dir}/pair.pem" );
	} elseif ( $op === 'migrate' ) {
		$cert_file = get_option( 'lockr_cert' );
		$cert_info = openssl_x509_parse(file_get_contents($cert_file));

		$subject = $cert_info['subject'];
		$dn = array(
			'countryName' => $subject['C'],
			'stateOrProvinceName' => $subject['ST'],
			'localityName' => $subject['L'],
			'organizationName' => $subject['O'],
		);

		$site_client = lockr_site_client();

		try {
			$result = $site_client->createCert( $dn );
		} catch ( LockrClientException $e ) {
			add_settings_error(
				'lockr_options',
				'lockr-csr',
				'Please make sure that the current Lockr certificate is valid.'
			);
			return $options;
		} catch ( LockrServerException $e ) {
			add_settings_error(
				'lockr_options',
				'lockr-csr',
				'Lockr encountered an unexpected'
			);
			return $options;
		}

		$rand_bytes = openssl_random_pseudo_bytes(4);
		$dir = WP_CONTENT_DIR . '/lockr/prod-' . bin2hex($rand_bytes);

		lockr_write_cert_pair( $dir, $result );

		update_option( 'lockr_cert', "{$dir}/pair.pem" );

		$dirs = scandir( WP_CONTENT_DIR . '/lockr' );
		foreach ( $dirs as $dir ) {
			if ( substr( $dir, 0, 3 ) === 'dev' ) {
				_lockr_rmtree( WP_CONTENT_DIR . "/lockr/{$dir}" );
			}
		}
	} elseif ( $op === 'advanced' ) {
		$cert_path = trim( $input['lockr_cert_path'] );

		if ( $cert_path ) {
			if ( $cert_path[0] !== '/' ) {
				$cert_path = ABSPATH . $cert_path;
			}

			if ( ! is_readable($cert_path) ) {
				add_settings_error(
					'lockr_options',
					'lockr-cert-path',
					"{$cert_path} must be a readable file."
				);

				return $options;
			}

			update_option( 'lockr_partner', 'custom' );
			update_option( 'lockr_cert', $cert_path );
		} else {
			$partner = lockr_get_partner();
			if ( $partner ) {
				update_option( 'lockr_partner', $partner['name'] );
				delete_option( 'lockr_cert' );
			} else {
				update_option( 'lockr_partner', '' );
				delete_option('lockr_cert');
			}
		}

		update_option( 'lockr_region', $input['lockr_region'] );
	} elseif ( $op === 'register' ) {
		$options['account_email'] = trim( $input['account_email'] );
		if( isset( $input['account_password'] ) ){
			$options['account_password'] = trim( $input['account_password'] );
		} else{
			$options['account_password'] = '';
		}

		$name = get_bloginfo( 'name', 'display' );

		if ( ! filter_var( $options['account_email'], FILTER_VALIDATE_EMAIL ) ) {
			add_settings_error( 'lockr_options', 'lockr-email', $options['account_email'] . ' is not a proper email address. Please try again.', 'error' );
			$options['account_email'] = '';
		} else {
			// I guess this form double-posts? Seems like Wordpress weirdness.
			$status = lockr_check_registration();
			$exists = $status['exists'];
			if ( ! $exists ) {
				try {
					lockr_site_client()->register( $options['account_email'], null, $name );
				} catch ( LockrClientException $e ) {
					if ( ! $options['account_password'] ) {
						add_settings_error( 'lockr_options', 'lockr-password', 'Please enter your password to add this site to your Lockr account.', 'error' );
						return $options;
					}
					try {
						lockr_site_client()->register( $options['account_email'], $options['account_password'], $name );
					} catch ( LockrClientException $e ) {
						add_settings_error( 'lockr_options', 'lockr-email', 'Login credentials incorrect, please try again.', 'error' );
					} catch ( LockrServerException $e ) {
						add_settings_error( 'lockr_options', 'lockr-email', 'An unknown error has occurred, please try again later.', 'error' );
					}
				} catch ( LockrServerException $e ) {
					add_settings_error( 'lockr_options', 'lockr-email', 'An unknown error has occurred, please try again later.', 'error' );
				}
			}
		}
		$options['account_password'] = '';
		return $options;
	}
}

function lockr_configuration_form() {
	require_once( LOCKR__PLUGIN_DIR . '/lockr-admin-status.php' );
	try {
		$status = lockr_check_registration();
	} catch ( LockrServerException $e ) {
?>
<p class='error'>The Lockr service has returned an error. Please try again.</p>
<?php
		return;
	}

	$errors = get_settings_errors();
	$error_codes = array();
	foreach ( $errors as $error ){
		$error_codes[] = $error['code'];
	}
?>
<div class="wrap lockr-config">
  <h1>Lockr Registration</h1>
<?php
	settings_errors();
	$cert_valid = $status['cert_valid'];
	$exists = $status['exists'];

	if ( $exists ) {
?>
<p>
All systems are go!
Your site is registered, your certificate is valid, and everything seems
good on our end.
The table below will give you the status of all elements.
Should anything look out of the ordinary just let us know on the Slack
channel and we'd be happy to help.
Happy Keying!
</p>
<?php
	} else {
?>
<p>
Welcome to Lockr!
You're just a few steps away from storing your keys safe and secure.
To make things simple we've laid out a few key elements (pun intended)
that the system requires in order to run.
Fill out the forms below and once all rows on the table are green,
you're good to go!
It's that simple!
If you'd like assistance in getting up and running be sure to
<a href="mailto:support@lockr.io">email us</a>
or connect with us on <a href="http://slack.lockr.io">Slack</a>.
</p>
<h2>Status Table</h2>
<?php
	}
	$status_table = new Lockr_Status();
	$status_table->prepare_items();
	$status_table->display();
	$partner = lockr_get_partner();
?>
  <form method="post" action="options.php">
<?php
	settings_fields( 'lockr_options' );

	if ( $partner ) {
?>
<h3><?php echo $partner['description']; ?></h3>
<?php
	} elseif ( ! $cert_valid ) {
?>
<table class="form-table">
  <?php do_settings_fields( 'lockr', 'lockr_csr' ); ?>
</table>
<input id="lockr_op"
	   name="lockr_options[lockr_op]"
	   type="hidden"
	   value="gencert" />
<?php submit_button( 'Generate Cert' ); ?>
<?php
	} elseif ( $exists && $status['info']['env'] === 'dev' ) {
?>
<p>
Click the button below to deploy this site to production.
This should only be done in your production enfironment as it writes
a new certificate to the file system.
</p>
<input id="lockr_op"
	   name="lockr_options[lockr_op]"
	   type="hidden"
	   value="migrate" />
<?php submit_button( 'Migrate to Production' ); ?>
<?php
	}

	if ( ! $exists && $cert_valid ) {
?>
<table class="form-table">
  <?php do_settings_fields( 'lockr', 'lockr_email' ); ?>
</table>

<?php if ( in_array( 'lockr-password', $error_codes ) ): ?>
  <table class="form-table">
    <?php do_settings_fields( 'lockr', 'lockr_password' ); ?>
  </table>
<?php endif; ?>
<input id="lockr_op"
	   name="lockr_options[lockr_op]"
	   type="hidden"
	   value="register" />
<?php submit_button( 'Register Site' ); ?>
<hr>
<?php
	}
?>
</form>
<hr>
<hr>
<h1>Advanced Configuration</h1>
<form method="post" action="options.php">
  <?php settings_fields( 'lockr_options' ); ?>
  <table class="form-table">
	  <?php do_settings_fields( 'lockr', 'lockr_advanced' ); ?>
    <?php do_settings_fields( 'lockr', 'lockr_region' ); ?>
  </table>
  <input id="lockr_op"
	     name="lockr_options[lockr_op]"
	     type="hidden"
	     value="advanced" />
  <?php submit_button( 'Save Advanced Settings', 'secondary' ); ?>
</form>
</div>
<?php
}

function _lockr_rmtree( $path ) {
	if ( is_dir( $path ) ) {
		foreach ( scandir( $path, SCANDIR_SORT_NONE ) as $name ) {
			if ( $name === '.' || $name === '..' ) {
				continue;
			}

			_lockr_rmtree( "{$path}/{$name}" );
		}
	}
}
