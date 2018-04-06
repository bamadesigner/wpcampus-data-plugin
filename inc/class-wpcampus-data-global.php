<?php
/**
 * The class that sets up
 * global plugin functionality.
 *
 * This class is initiated on every page
 * load and does not have to be instantiated.
 *
 * @class       WPCampus_Data_Global
 * @category    Class
 * @package     WPCampus Data
 */
final class WPCampus_Data_Global {

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();

		// Load our text domain.
		add_action( 'init', array( $plugin, 'textdomain' ) );

		// Setup the REST API.
		add_action( 'rest_api_init', array( $plugin, 'setup_rest_api' ) );

	}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-data', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Setup the API.
	 */
	function setup_rest_api() {

		// Load our API class.
		require_once wpcampus_data()->plugin_dir . 'inc/class-wpcampus-data-api.php';

	}
}
WPCampus_Data_Global::register();
