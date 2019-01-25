<?php
/**
 * Class to handle all the metadata like wrapping keys used to store secrets in Lockr.
 *
 * @package Lockr
 */

use Lockr\SecretInfoInterface;

/**
 * Class to handle all the metadata like wrapping keys used to store secrets in Lockr.
 */
class Lockr_WP_Secret_Info implements SecretInfoInterface {

	/**
	 * Data array for secrets stored in Lockr
	 *
	 * @var array $data the data array of secrets stored in Lockr.
	 * **/
	private $data;

	/**
	 * Get all the data on secrets stored in Lockr on when constructed.
	 **/
	public function __construct() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'lockr_keys';
		$query      = "SELECT `key_name`, `key_value` * FROM $table_name ";
		$keys       = $wpdb->query( $query ); // WPCS: unprepared SQL OK.
		$this->data = $keys ?: [];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $name The name of the key stored in Lockr.
	 */
	public function getSecretInfo( $name ) {
		return isset( $this->data[ $name ] ) ? json_decode( $this->data[ $name ], true ) : [];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $name The name of the key stored in Lockr.
	 * @param array  $info The array of information used to find and decrypt the value.
	 */
	public function setSecretInfo( $name, array $info ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'lockr_keys';
		$key_data = array(
			'key_value' => wp_json_encode( $info ),
		);
		$where = array(
			'key_name' => $name,
		);
		$wpdb->update( $table_name, $key_data, $where );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAllSecretInfo() {
		return $this->data;
	}

}
