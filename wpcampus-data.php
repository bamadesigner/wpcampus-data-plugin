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
	 * Get the topics from all of our events.
	 */
	public function get_event_topics() {
		global $wpdb;

		// Will hold topics.
		$topics = array();

		// Store info for event sites.
		$event_sites = array(
			array(
				'site_id'   => 4,
				'title'     => 'WPCampus 2016',
			),
			array(
				'site_id'   => 6,
				'title'     => 'WPCampus Online',
			),
			array(
				'site_id'   => 7,
				'title'     => 'WPCampus 2017',
			),
		);
		foreach ( $event_sites as $event ) {

			// Set the ID and title
			$event_site_id = $event['site_id'];
			$event_title = $event['title'];

			// Get the site's DB prefix.
			$event_site_prefix = $wpdb->get_blog_prefix( $event_site_id );

			// Get the schedule URL for the site.
			$event_site_schedule_url = get_site_url( $event_site_id, '/schedule/' );

			// Get the topics.
			$site_topics = $wpdb->get_results( $wpdb->prepare( "SELECT posts.ID,
				%d AS blog_id,
				%s AS event,
				posts.post_title,
				posts.post_name,
				posts.post_parent,
				posts.post_content,
				CONCAT( %s, posts.post_name, '/') AS permalink,
				posts.guid
				FROM {$event_site_prefix}posts posts WHERE posts.post_type = 'schedule' AND posts.post_status = 'publish'", $event_site_id, $event_title, $event_site_schedule_url ) );

			// Add to complete list.
			if ( ! empty( $site_topics ) ) {
				$topics = array_merge( $topics, $site_topics );
			}
		}

		//array_multisort( $topics['post_title'], SORT_ASC, SORT_NATURAL );
		usort( $topics, function( $a, $b ) {
			if ( $a->post_title == $b->post_title ) {
				return 0;
			}
			return ( $a->post_title < $b->post_title ) ? -1 : 1;
		});

		return $topics;
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
