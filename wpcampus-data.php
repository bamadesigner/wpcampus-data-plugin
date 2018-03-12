<?php

/**
 * Plugin Name:       WPCampus: Data
 * Plugin URI:        https://wpcampus.org
 * Description:       Manages data for the WPCampus network of sites.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcampus
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPCampus_Data {

	/**
	 * Holds the class instance.
	 *
	 * @access	private
	 * @var		WPCampus_Data
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return	WPCampus_Data
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		// Load our text domain.
		add_action( 'init', array( $this, 'textdomain' ) );

		// Setup the REST API.
		add_action( 'rest_api_init', array( $this, 'setup_rest_api' ) );

	}

	/**
	 * Method to keep our instance
	 * from being cloned or unserialized.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}
	private function __wakeup() {}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Setup the API.
	 */
	function setup_rest_api() {

		// Load the class.
		require_once plugin_dir_path( __FILE__ ) . 'inc/class-wpcampus-data-api.php';

		// Initialize our class.
		$wpcampus_data_api = new WPCampus_Data_API();

		// Register our routes.
		$wpcampus_data_api->register_routes();

	}

	/**
	 * Get the sessions from all of our events.
	 *
	 * @TODO:
	 * - Update to use new system.
	 */
	public function get_event_sessions() {
		global $wpdb;

		// Will hold sessions.
		$sessions = array();

		// Do we have any filters?
		$filters = array();
		$allowed_filters = array( 'e' );
		if ( ! empty( $_GET ) ) {
			foreach ( $_GET as $get_filter_key => $get_filter_value ) {
				if ( ! in_array( $get_filter_key, $allowed_filters ) ) {
					continue;
				}
				$filters[ $get_filter_key ] = explode( ',', sanitize_text_field( $get_filter_value ) );
			}
		}

		// Store info for event sites.
		$event_sites = array(
			array(
				'site_id'   => 4,
				'title'     => 'WPCampus 2016',
				'slug'      => 'wpcampus-2016',
			),
			array(
				'site_id'   => 6,
				'title'     => 'WPCampus Online',
				'slug'      => 'wpcampus-online',
			),
			array(
				'site_id'   => 7,
				'title'     => 'WPCampus 2017',
				'slug'      => 'wpcampus-2017',
			),
		);
		foreach ( $event_sites as $event ) {

			// If filtering by event, remove those not in the filter.
			if ( ! empty( $filters['e'] ) && ! in_array( $event['slug'], $filters['e'] ) ) {
				continue;
			}

			// Set the ID and title
			$event_site_id = $event['site_id'];

			// Get the site's DB prefix.
			$event_site_prefix = $wpdb->get_blog_prefix( $event_site_id );

			// Get the schedule URL for the site.
			$event_site_schedule_url = get_site_url( $event_site_id, '/schedule/' );

			// Get the sessions.
			$site_sessions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT posts.ID,
					%d AS blog_id,
					%s AS event,
					%s AS event_slug,
					posts.post_title,
					posts.post_name,
					posts.post_parent,
					posts.post_content,
					CONCAT( %s, posts.post_name, '/') AS permalink,
					posts.guid
					FROM {$event_site_prefix}posts posts
					INNER JOIN {$event_site_prefix}postmeta meta ON meta.post_ID = posts.ID AND meta.meta_key = 'wpcampus_session' AND meta.meta_value = '1'
					WHERE posts.post_type = 'schedule' AND posts.post_status = 'publish'",
					$event_site_id, $event['title'], $event['slug'], $event_site_schedule_url
				)
			);

			// Add to complete list.
			if ( ! empty( $site_sessions ) ) {
				$sessions = array_merge( $sessions, $site_sessions );
			}
		}

		// Sort by title.
		usort( $sessions, function( $a, $b ) {
			if ( $a->post_title == $b->post_title ) {
				return 0;
			}
			return ( $a->post_title < $b->post_title ) ? -1 : 1;
		});

		return $sessions;
	}
}

/**
 * Returns the instance of our main WPCampus_Data class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @access	public
 * @return	WPCampus_Data
 */
function wpcampus_data() {
	return WPCampus_Data::instance();
}

// Let's get this show on the road
wpcampus_data();
