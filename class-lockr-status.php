<?php
/**
 * Create a simple status table to easily see the status of the Lockr integration.
 *
 * @package Lockr
 */

// Don't call the file directly and give up info!
if ( ! function_exists( 'add_action' ) ) {
	echo 'Lock it up!';
	exit;
}

/**
 * Create a simple status table to easily see the status of the Lockr integration.
 */
class Lockr_Status extends WP_List_Table {

	/**
	 * Get things started with the table.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Status', 'lockr' ),
				'plural'   => __( 'Statuses', 'lockr' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Text displayed when no data is available.
	 */
	public function no_items() {
		esc_html_e( 'No status', 'sp' );
	}

	/**
	 * Get columns and their names.
	 */
	public function get_columns() {
		return array(
			'title'  => 'Title',
			'status' => 'Status',
		);
	}

	/**
	 * Create the row data for Lock status.
	 *
	 * @param array  $item The row Item to display.
	 * @param string $column_name The column to put the data into.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'title':
				return $item['title'];
			case 'status':
				$td = "<p><span class='status {$item['severity']}'>{$item['value']}</span>";
				if ( isset( $item['description'] ) ) {
					$td .= " - {$item['description']}</p>";
				}
				return $td;
		}
	}

	/**
	 * Prepare the row items for display.
	 */
	public function prepare_items() {
		$status = lockr_check_registration();

		$cert_valid = $status['cert_valid'];
		$exists     = $status['exists'];
		$created    = isset( $status['created'] ) ? $status['created'] : false;

		$items = array();

		if ( $cert_valid ) {
			$text    = <<<EOL
You're a certified Lockr user!
We've found your certificate and it validates with our system.
EOL;
			$items[] = array(
				'title'       => 'Certificate Valid',
				'value'       => 'Yes',
				'description' => $text,
				'severity'    => 'lockr-ok',
			);
			$items[] = array(
				'title'    => 'Environment',
				'value'    => $status['info']['env'],
				'severity' => 'lockr-info',
			);
		} else {
			$text    = <<<EOL
Oops!
Looks like we need to know who you are before we give you the keys to the castle.
Your certificate is not valid, please register for one.
If you've already gotten a certificate, we are unable to find it.
Please check the advanced settings to ensure your path is correct
(or if you're on a hosting partner contact their support).
EOL;
			$items[] = array(
				'title'       => 'Certificate Valid',
				'value'       => 'No',
				'description' => $text,
				'severity'    => 'lockr-error',
			);
		}

		if ( $exists ) {
			$text    = <<<EOL
You're one of the family.
We've got your site registered and you're all good to go!
EOL;
			$items[] = array(
				'title'       => 'Site Registered',
				'value'       => 'Yes',
				'description' => $text,
				'severity'    => 'lockr-ok',
			);
		} else {

			$text    = <<<EOL
Who are you again?
We don't have your site registered with Lockr.
Please use the form below to register your site.
EOL;
			$items[] = array(
				'title'       => 'Site Registered',
				'value'       => 'No',
				'description' => $text,
				'severity'    => 'lockr-error',
			);
		}

		if ( $cert_valid ) {
			$has_cc = $status['has_cc'];

			if ( $created ) {
				$expires = ( new \DateTime() )
					->setTimestamp( $created )
					->add( new \DateInterval( 'P14D' ) );
				if ( $expires > ( new \DateTime() ) ) {
					$items[] = array(
						'title'    => 'Trial Expiration Date',
						'value'    => $expires->format( 'M jS, Y' ),
						'severity' => 'lockr_ok',
					);
				} elseif ( ! $has_cc ) {
					$items[] = array(
						'title'    => 'Trial Expiration Date',
						'value'    => $expires->format( 'M jS, Y' ),
						'severity' => 'lockr_error',
					);
				}
			}

			$partner        = $status['info']['partner'];
			$is_custom      = in_array( $partner, array( 'custom', 'lockr' ) );
			$default        = $is_custom ? 'lockr-error' : 'lockr-warning';
			$is_custom_text = <<<EOL
Uh oh!
Without a credit card we cannot issue a production certificate.
Please add one before migrating to production.
EOL;

			$is_not_custom_text  = "Since you're on a partnering host, a credit card is not necessary to move to production. However, please make sure you get a card on file ASAP. We will contact you if there is no card on file within 30 days of moving to production use.";
			$default_description = $is_custom ? $is_custom_text : $is_not_custom_text;
			$has_cc_text         = <<<EOL
We've got your credit card safely on file and you'll be receiving regular
invoice for your key usage.
EOL;

			$items[] = array(
				'title'       => 'Credit Card on File',
				'value'       => $has_cc ? 'Yes' : 'No',
				'description' => $has_cc ? $has_cc_text : $default_description,
				'severity'    => $has_cc ? 'lockr-ok' : $default,
			);
		}

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			array(),
		);

		$this->items = $items;
	}

}

