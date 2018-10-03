<?php
/**
 * Autoloader for the Lockr php library.
 *
 * @package Lockr
 */

// Don't call the file directly and give up info!
if ( ! function_exists( 'add_action' ) ) {
	echo 'Lock it up!';
	exit;
}

/**
 * Lockr autoloader.
 *
 * @param string $class The class to load.
 */
function lockr_autoload( $class ) {
	if ( substr( $class, 0, 6 ) !== 'Lockr\\' ) {
		return false;
	}
	$file = __DIR__ . '/src/' . str_replace( '\\', '/', $class ) . '.php';
	if ( file_exists( $file ) ) {
		include_once $file;
		return true;
	}
	return false;
}

spl_autoload_register( 'lockr_autoload' );
