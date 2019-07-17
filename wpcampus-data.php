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

defined( 'ABSPATH' ) or die();

require_once wpcampus_data()->plugin_dir . 'inc/class-wpcampus-data-global.php';

final class WPCampus_Data {

	/**
	 * Holds the absolute URL and
	 * the directory path to the
	 * main plugin directory.
	 *
	 * @var string
	 */
	public $plugin_url;
	public $plugin_dir;

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
			$class_name     = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Magic method to output a string if
	 * trying to use the object as a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf( __( '%s Data', 'wpcampus-data' ), 'WPCampus' );
	}

	/**
	 * Method to keep our instance
	 * from being cloned or unserialized
	 * and to prevent a fatal error when
	 * calling a method that doesn't exist.
	 *
	 * @return void
	 */
	public function __clone() {}
	public function __wakeup() {}
	public function __call( $method = '', $args = array() ) {}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		// Store the plugin URL and DIR.
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_path( __FILE__ );

	}

	/**
	 * Get the sessions from all of our events.
	 */
	public function get_sessions( $args = array() ) {
		if ( ! function_exists( 'wpcampus_get_sessions' ) ) {
			return null;
		}

		// Force these defaults for sessions data feed.
		$args['proposal_status'] = 'confirmed';
		$args['get_profiles'] = true;
		$args['get_wp_user'] = true;
		$args['get_subjects'] = true;

		// Make sure subjects are term IDs.
		if ( ! empty( $args['subject'] ) ) {

			// Make sure its an array.
			if ( ! is_array( $args['subject'] ) ) {
				$args['subject'] = explode( ',', str_replace( ' ', '', $args['subject'] ) );
			}

			// Make sure its a term ID.
			foreach ( $args['subject'] as &$subject ) {
				if ( is_numeric( $subject ) ) {
					continue;
				}
				$term = get_term_by( 'slug', $subject, 'subjects' );
				if ( empty( $term->term_id ) ) {
					continue;
				}
				$subject = $term->term_id;
			}
		}

		// Make sure subjects are term IDs.
		if ( ! empty( $args['format'] ) ) {

			// Make sure its an array.
			if ( ! is_array( $args['format'] ) ) {
				$args['format'] = explode( ',', str_replace( ' ', '', $args['format'] ) );
			}

			// Make sure its a term ID.
			foreach ( $args['format'] as &$format ) {
				if ( is_numeric( $format ) ) {
					continue;
				}
				$term = get_term_by( 'slug', $format, 'session_format' );
				if ( empty( $term->term_id ) ) {
					continue;
				}
				$format = $term->term_id;
			}
		}

		// Set default order for date orderby.
		if ( ! empty( $args['orderby'] ) ) {

			// If sorting by date, then desc is default order.
			if ( 'date' == $args['orderby'] && empty( $args['order'] ) ) {
				$args['order'] = 'desc';
			}
		}

		// Change event argument.
		if ( ! empty( $args['event'] ) ) {
			$args['proposal_event'] = $args['event'];
			unset( $args['event'] );
		}

		// Make sure we only get certain events.
		$display_events = get_option( 'options_wpc_sessions_event_display');

		if ( empty( $args['proposal_event'] ) ) {

			if ( ! empty( $display_events ) ) {
				$args['proposal_event'] = $display_events;
			}
		}

		$sessions = wpcampus_get_sessions( $args );

		if ( empty( $sessions ) ) {
			return null;
		}

		// Remove these fields.
		// @TODO did we need this data for session review?
		$session_remove = [
			'post_status',
			'post_type',
			'format_preferred',
			'format_preferred_slug',
			'format_preferred_name',
			'proposal_status',
		];

		$speaker_remove = [
			'slug',
			'wordpress_user',
			'email',
			'phone',
			'nicename',
		];

		/*
		 * @TODO
		 * - session_video_id] => 18226
            [session_video_url] => https://www.youtube.com/watch?v=g7Qnk5jPDfY
            [session_video_thumbnail
		 */

		foreach ( $sessions as &$session ) {

			foreach ( $session_remove as $key ) {
				unset( $session->{$key} );
			}

			if ( ! empty( $session->speakers ) ) {
				foreach ( $session->speakers as &$speaker ) {
					foreach ( $speaker_remove as $key ) {
						unset( $speaker->{$key} );
					}
				}
			}
		}

		return $sessions;
	}

	/**
	 * Get our videos.
	 */
	public function get_videos( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'playlist' => null,
			'category' => null,
			'search'   => null,
		));

		$playlist = '';
		if ( ! empty( $args['playlist'] ) ) {

			$playlist = $args['playlist'];

			if ( ! is_array( $playlist ) ) {
				$playlist = explode( ',', str_replace( ' ', '', $playlist ) );
			}

			$playlist = array_map( 'sanitize_text_field', $playlist );

		}

		$post_type = array( 'podcast', 'video' );

		$podcast_search = ! empty( $playlist ) ? array_search( 'podcast', $playlist ) : false;
		if ( false !== $podcast_search ) {

			unset( $playlist[ $podcast_search ] );

			// This means we're only looking for podcasts.
			if ( empty( $playlist ) ) {
				$post_type = array( 'podcast' );
			}
		}

		$post_type_str = implode( "','", $post_type );

		$category = '';
		if ( ! empty( $args['category'] ) ) {

			$category = $args['category'];

			if ( ! is_array( $category ) ) {
				$category = explode( ',', str_replace( ' ', '', $category ) );
			}

			$category = array_map( 'sanitize_text_field', $category );

		}

		$search = '';
		if ( ! empty( $args['search'] ) ) {
			$search = sanitize_text_field( $args['search'] );
		}

		$select = "SELECT posts.ID,
			posts.post_author,
			posts.post_content,
			IF ( video_title.meta_value IS NOT NULL AND video_title.meta_value != '', video_title.meta_value, posts.post_title ) AS post_title,
			posts.post_name,
			proposal.post_id AS proposal,
			IF ( proposal.post_id IS NOT NULL, ( SELECT post_name FROM {$wpdb->posts} WHERE ID = proposal.post_id AND post_type = 'proposal' AND post_status = 'publish' ), NULL ) AS proposal_slug,
			posts.post_type,
			posts.comment_count,
			playlist_terms.name AS event_name,
			playlist_terms.slug AS event_slug";

		$from = " FROM {$wpdb->posts} posts";

		$join = " LEFT JOIN {$wpdb->postmeta} video_title ON video_title.post_id = posts.ID and video_title.meta_key = 'video_title'
			LEFT JOIN {$wpdb->postmeta} proposal ON proposal.meta_value = posts.ID AND proposal.meta_key = 'session_video'";

		$join .= " INNER JOIN {$wpdb->term_relationships} playlist_rel ON playlist_rel.object_id = posts.ID
			INNER JOIN {$wpdb->term_taxonomy} playlist_tax ON playlist_tax.term_taxonomy_id = playlist_rel.term_taxonomy_id AND playlist_tax.taxonomy = 'playlist'
			INNER JOIN {$wpdb->terms} playlist_terms ON playlist_terms.term_id = playlist_tax.term_id";

		if ( ! empty( $category ) ) {

			$category_str = implode( "','", $category );

			$join .= " INNER JOIN {$wpdb->term_relationships} category_rel ON category_rel.object_id = posts.ID
				INNER JOIN {$wpdb->term_taxonomy} category_tax ON category_tax.term_taxonomy_id = category_rel.term_taxonomy_id AND category_tax.taxonomy = 'category'
				INNER JOIN {$wpdb->terms} category_terms ON category_terms.term_id = category_tax.term_id AND category_terms.slug IN ('" . $category_str . "')";
		}

		$where = " WHERE posts.post_type IN ('" . $post_type_str . "') AND posts.post_status = 'publish'";

		if ( ! empty( $playlist ) ) {
			$where .= " AND playlist_terms.slug IN ('" . implode( "','", $playlist ) . "')";
		}

		if ( ! empty( $search ) ) {
			$where .= " AND ( posts.post_title LIKE '%" . $search . "%' OR posts.post_content LIKE '%" . $search . "%')";
		}

		$groupby = " GROUP BY posts.ID";

		if ( ! empty( $playlist ) ) {
			$havingby = " HAVING event_name IS NOT NULL";

			if ( false !== $podcast_search ) {
				$havingby .= " OR posts.post_type = 'podcast'";
			}
		} else {
			$havingby = '';
		}

		$orderby = " ORDER BY post_title ASC";

		$query = $select . $from . $join . $where . $groupby . $havingby . $orderby;

		$videos = $wpdb->get_results( $query );

		if ( empty( $videos ) ) {
			return $videos;
		}

		$media_exists = function_exists( 'wpcampus_media' );

		foreach ( $videos as &$video ) {

			$video->youtube = $media_exists ? wpcampus_media()->get_youtube_video_id( $video->ID ) : null;
			$video->watch_permalink = ! empty( $video->youtube ) ? wpcampus_media()->get_youtube_watch_url( $video->youtube ) : null;

			if ( 'podcast' == $video->post_type ) {
				$video->permalink = get_permalink( $video->ID );
			} else {

				$video->permalink = null;

				switch( $video->event_slug ) {

					case 'wpcampus-online-2017':
					case 'wpcampus-online-2018':
					case 'wpcampus-online-2019':
						$video->permalink = 'https://online.wpcampus.org/schedule/';
						break;

					case 'wpcampus-2016':
						$video->permalink = 'https://2016.wpcampus.org/schedule/';
						break;

					case 'wpcampus-2017':
						$video->permalink = 'https://2017.wpcampus.org/schedule/';
						break;

					case 'wpcampus-2018':
						$video->permalink = 'https://2018.wpcampus.org/schedule/';
						break;

					case 'wpcampus-2019':
						$video->permalink = 'https://2019.wpcampus.org/schedule/';
						break;

				}

				if ( ! empty( $video->proposal_slug ) ) {
					$video->permalink .= $video->proposal_slug;
				}
			}


			if ( empty( $video->permalink ) && ! empty( $video->watch_permalink ) ) {
				$video->permalink = $video->watch_permalink;
			}

			// Get authors.
			$video->authors = $media_exists ? wpcampus_media()->get_video_authors( $video->ID ) : array();

			// Get the thumbnail.
			$video->thumbnail = null;

			// Get the YouTube snippet to get the thumbnail.
			$snippet = get_post_meta( $video->ID, 'wpc_youtube_video_snippet', true );
			if ( ! empty( $snippet ) ) {

				// Get the thumbnails.
				$thumbnails = $snippet && ! empty( $snippet->thumbnails ) ? (array) $snippet->thumbnails : null;

				// Get the thumbnail.
				if ( $thumbnails ) {
					foreach ( array( 'standard', 'high', 'medium', 'default' ) as $size ) {
						if ( isset( $thumbnails[ $size ] ) && ! empty( $thumbnails[ $size ]->url ) ) {
							$video->thumbnail = $thumbnails[ $size ]->url;
							break;
						}
					}
				}
			}

			// If no thumbnail, use default set at standard size: 640 x 480.
			if ( empty( $video->thumbnail ) ) {
				$video->thumbnail = '/wp-content/plugins/wpcampus-media-plugin/assets/images/video-thumbnail-standard.png';
			}
		}

		return $videos;
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
