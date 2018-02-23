<?php

// Don't call the file directly and give up info!
if ( ! function_exists( 'add_action' ) ) {
	echo 'Lock it up!';
	exit;
}

function lockr_admin_submit_override_key() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to add a key.' );
	}

	if( ! empty( $_POST ) && check_admin_referer( 'lockr_admin_verify' ) ) {
		if ( is_numeric( $_POST['option_total_number'] ) ) {
			$total_option_value = intval($_POST['option_total_number']);
			$option_path = '';

			for ( $i = 1; $i < $total_option_value + 1; $i++ ) {
				$option_path .= sanitize_key( $_POST['option_value_' . $i] ) . ':';
			}
		} else {
			wp_die( 'This form has been tampered with.' );
		}

		$option_path = substr( $option_path, 0, -1 );
		$key_label = str_replace( ':', ' - ', $option_path );
		$key_label = ucwords( str_replace( '_', ' ', $key_label ) );

		$key_name = preg_replace( '@[^a-z0-9_]+@','_', $option_path );

		if ( $_POST['create_key'] == 'on') {
			// Create a default encryption key
			$client = lockr_key_client();
			$key_value = $client->create(256);
		} else {
			$key_value = sanitize_text_field( $_POST['key_value'] );
		}

		$key_store = lockr_set_key( $key_name, $key_value, $key_label, $option_path );

		if ( $key_store ) {
			// Successfully Added so save the option to replace the value
			$option_name = sanitize_key( $_POST['option_value_1'] );
			$existing_option = get_option( $option_name );
			if ( $existing_option ) {
				if ( is_array ( $existing_option ) ) {
					$new_option_array = explode( ':', $option_path );
					array_shift( $new_option_array );

					$serialized_data_ref = &$existing_option;
					foreach ( $new_option_array as $option_key ) {
						$serialized_data_ref = &$serialized_data_ref[$option_key];
					}
					$serialized_data_ref = 'lockr_' . $key_name;

					update_option( $option_name, $existing_option, false );
				} else {
					update_option( $option_name, 'lockr_' . $key_name, false );
				}
			}

			wp_redirect( admin_url( 'admin.php?page=lockr&message=success' ) );
			exit;
		} else {
			// Failed Addition
			wp_redirect( admin_url( 'admin.php?page=lockr-override-option&message=failed' ) );
			exit;
		}
	}
}

function lockr_override_form() {
	$status = lockr_check_registration();
	$exists = $status['exists'];
	$available = $status['available'];
	$js_url = LOCKR__PLUGIN_URL . '/js/lockr.js';
	$blacklist = array(
		'active_plugins',
		'cron',
		'auto_core_update_notified',
		'recently_activated',
		'rewrite_rules',
		'uninstall_plugins',
		'wp_user_roles'
	);
	$options = array();
	global $wpdb;
	$options_raw = $wpdb->get_results( "SELECT * FROM $wpdb->options ORDER BY option_name" );

	foreach ( (array) $options_raw as $option_raw ) {
		$serialized = false;
		$value = '';
		if ( $option_raw->option_name == '' ) {
			continue;
		}
		if ( is_serialized( $option_raw->option_value ) ) {
			if ( is_serialized_string( $option_raw->option_value ) ) {
				$value = maybe_unserialize( $option_raw->option_value );
				if ( substr( $value, 0, 5) == 'lockr') {
					$value = false;
				}
			} else {
				$value = array();
				$serialized_data = maybe_unserialize( $option_raw->option_value );
				foreach ( $serialized_data as $serial_key => $serial_value ) {
					if ( is_string( $serial_value ) ) {
						if ( substr( $serial_value, 0, 5) != 'lockr') {
							$value[ $serial_key ] = $serial_value;
						}
					} else {
						$value[ $serial_key ] = $serial_value;
					}
				}
			}
		} else {
			if ( substr( $option_raw->option_value, 0, 5 ) != 'lockr') {
				$value = $option_raw->option_value;
			} else {
				$value = false;
			}
		}
		$name = esc_attr( $option_raw->option_name );
		if ( $value && substr( $name, 0, 5 ) != '_site' && substr( $name, 0, 5 ) != 'lockr' && substr( $name, 0, 10 ) != '_transient' && ! in_array( $name, $blacklist) ) {
			$options[ $name ] = $value;
		}
	}
?>
<script type="text/javascript" src="<?php print $js_url; ?>"></script>
<div class="wrap">
	<?php if ( ! $exists ): ?>
		<h1>Register Lockr First</h1>
		<p>Before you can add keys, you must first <a href="<?php echo admin_url( 'admin.php?page=lockr-site-config' ); ?>">register your site</a> with Lockr.</p>
	<?php else: ?>
		<h1>Override an option with Lockr</h1>
			<?php if ( isset( $_GET['message'] ) && $_GET['message'] == 'failed' ): ?>
				<div id='message' class='updated fade'><p><strong>There was an issue in saving your key, please try again.</strong></p></div>
			<?php endif; ?>
			<p> With Lockr you can override any value in the options table with a value in Lockr. This allows you to store any secrets or passwords from plugins safely out of your database.</p>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="lockr_admin_submit_override_key" />
				<input type="hidden" name="option_total_number" id="option-total-number" value="1" />
				<?php wp_nonce_field( 'lockr_admin_verify' ); ?>
				<div class="form-item option-name">
					<label for="option_value_1">Option to override:</label>
					<select name="option_value_1" class="option-override-select" id="option-override-1">
						<option value="">Select an Option</option>
					<?php
						foreach( $options as $option => $value ){
							$value = json_encode( $value );
							print('<option value ="' . esc_attr( $option ) . '" data-option-value="' . esc_attr( htmlentities($value) ) . '" >' . esc_html( $option ) . '</option>' );
						}
					?>
					</select>
				</div>
				<div class="form-item">
					<label for="key_value">Key Value:</label>
					<input type="text" name="key_value" placeholder="Your Key Value" id="key_value"/>
					<input type="checkbox" name="create_key" id="create_key"/>
					<label for="create_key">Create a secure encryption key for me</label>
				</div>
				<br />
				<input type="submit" value="Add Override" class="button-primary"/>
			</form>
	<?php endif; ?>

</div>
<?php }
