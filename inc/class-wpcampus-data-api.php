<?php

/**
 * Our data API class.
 */
final class WPCampus_Data_API {

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Register our API routes.
	 */
	public static function register() {
		$plugin = new self();

		// Get WPCampus data.
		register_rest_route( 'wpcampus', '/data/set/(?P<set>[a-z\_\-]+)', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $plugin, 'get_data_set' ),
			'permission_callback' => function () {
				return true;
			},
		));

		// Get list of all WPCampus sessions.
		register_rest_route( 'wpcampus', '/data/sessions', array(
			'methods'  => 'GET',
			'callback' => array( $plugin, 'get_sessions' ),
		));

		// Get list of all WPCampus videos.
		register_rest_route( 'wpcampus', '/data/videos', array(
			'methods'  => 'GET',
			'callback' => array( $plugin, 'get_videos' ),
		));
	}

	/**
	 * Respond with particular data for our data sets.
	 */
	public function get_data_set( WP_REST_Request $request ) {

		// Build the response.
		$response = null;

		/*
		 * @TODO: move all of these data functions to the plugin.
		 */

		switch ( $request['set'] ) {

			case 'no-of-interested':
				$response = wpcampus_get_interested_count();
				break;

			case 'affiliation':
				$response = array(
					'work_in_higher_ed'         => wpcampus_get_work_in_higher_ed_count(),
					'work_for_company'          => wpcampus_get_work_for_company_count(),
					'work_outside_higher_ed'    => wpcampus_get_work_outside_higher_ed_count(),
				);
				break;

			case 'attend-preference':
				$response = array(
					'attend_in_person'      => wpcampus_get_attend_in_person_count(),
					'attend_live_stream'    => wpcampus_get_attend_live_stream_count(),
				);
				break;

			case 'attend-has-location':
				$response = wpcampus_get_interested_has_location_count();
				break;

			case 'attend-country':
				$response = wpcampus_get_interest_by_country();
				break;

			case 'best-time-of-year':
				$response = wpcampus_get_interest_best_time_of_year();
				break;

			case 'sessions':
				$response = wpcampus_get_interest_sessions();
				break;

			case 'universities':
				$response = wpcampus_get_interest_universities();
				break;

			case 'vote-on-new-name':
				$response = wpcampus_get_vote_on_new_name();
				break;
		}

		// If no response, return an error.
		if ( ! $response ) {
			return new WP_Error( 'wpcampus', __( 'This data set is either invalid or does not contain information.', 'wpcampus-data' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $response );
	}

	/**
	 * Respond with our event sessions.
	 */
	public function get_sessions( WP_REST_Request $request ) {

		$args    = [];
		$filters = [
			'assets'   => [ 'slides', 'video' ],
			'orderby'  => [ 'date', 'title' ],
			'order'    => [ 'asc', 'desc' ],
			'event'    => [
				'wpcampus-2019',
				'wpcampus-2018',
				'wpcampus-2017',
				'wpcampus-2016',
				'wpcampus-online-2019',
				'wpcampus-online-2018',
				'wpcampus-online-2017'
			],
			'search'  => [],
			'format'  => [],
			'subject' => [],
		];

		foreach ( $filters as $filter => $options ) {

			if ( ! empty( $_GET[ $filter ] ) ) {

				$filter_val = strtolower( str_replace( ' ', '', $_GET[ $filter ] ) );

				$has_open_value = in_array( $filter, array( 'search', 'subject', 'format' ) );

				if ( $has_open_value ) {
					$filter_val = sanitize_text_field( $filter_val );
				}

				// Means it has a comma so convert to array.
				if ( strpos( $filter_val, ',' ) !== false ) {

					$filter_val = explode( ',', $filter_val );

					$filtered_values = [];
					foreach ( $filter_val as $value ) {

						if ( $has_open_value || in_array( $value, $options ) ) {
							$filtered_values[] = $value;
						}
					}

					$args[ $filter ] = $filtered_values;

					continue;
				}

				if ( $has_open_value || in_array( $filter_val, $options ) ) {
					$args[ $filter ] = $filter_val;
				}
			}
		}

		// Build the response with the sessions.
		$response = wpcampus_data()->get_sessions( $args );

		if ( empty( $response ) ) {
			$response = [];
		}

		// If no response, return an error.
		if ( false === $response ) {
			return new WP_Error( 'wpcampus', __( 'This data set is either invalid or does not contain information.', 'wpcampus-data' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $response );
	}

	/**
	 * Respond with our videos.
	 */
	public function get_videos( WP_REST_Request $request ) {

		$args = array();

		if ( ! empty( $_GET['playlist'] ) ) {
			$args['playlist'] = sanitize_text_field( $_GET['playlist'] );
		}

		if ( ! empty( $_GET['category'] ) ) {
			$args['category'] = sanitize_text_field( $_GET['category'] );
		}

		if ( ! empty( $_GET['search'] ) ) {
			$args['search'] = sanitize_text_field( $_GET['search'] );
		}

		// Build the response with the videos.
		$response = wpcampus_data()->get_videos( $args );

		// If no response, return an error.
		if ( false === $response ) {
			return new WP_Error( 'wpcampus', __( 'This data set is either invalid or does not contain information.', 'wpcampus-data' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $response );
	}
}
WPCampus_Data_API::register();
