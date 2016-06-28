<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_ElasticPress' ) ) {

	class WP_REST_API_Log_ElasticPress {


		/**
		 * plugins_loaded WordPress hook
		 * @return void
		 */
		static public function plugins_loaded() {


			add_action( 'ep_add_query_log', 'WP_REST_API_Log_ElasticPress::log_query' );

			add_filter( 'ep_post_sync_kill', 'WP_REST_API_Log_ElasticPress::sync_kill', 10, 3 );

		}

		static function sync_kill( $kill, $post_args, $post_id ) {
			// don't sync our log entries to ElasticSearch
			if ( ! empty( $post_args ) && ! empty( $post_args['post_type'] ) && WP_REST_API_Log_DB::POST_TYPE === $post_args['post_type'] ) {
				$kill = false;
			}

			return $kill;
		}


		/**
		 * Logs an ElasticPress search and results to the database
		 *
		 * @param  object $query the ElasticPress query
		 * @return void
		 */
		static public function log_query( $query ) {

			if ( empty( $query ) ) {
				return false;
			}

			// don't log anything if logging is not enabled
			$logging_enabled = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-setting-is-enabled',
				true,
				'elasticpress',
				'logging-enabled'
				);

			if ( ! $logging_enabled ) {
				return false;
			}


			$log_query = true;
			$route = '';
			if ( ! empty( $query['url'] ) && ! empty( $query['host'] ) ) {
				$route = $query['url'];
				// don't log the _stats/indexing request by default
				if ( false !== strpos( $query['url'], '_stats/indexing' ) ) {
					return false;
				}
			}


			// set up some defaults
			$args = array(
				//'ip_address'            => $_SERVER['REMOTE_ADDR'],
				'route'                 => $route,
				'method'                => '',
				'status'                => '',
				'source'                => 'ElasticPress',
				'milliseconds'          => 0,
				'request'               => array(
					'body'                 => '',
					'headers'              => array(),
					),
				'response'              => array(
					'body'                 => '',
					'headers'              => array(),
					),
				);


			// add elapsed time
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$args['milliseconds'] = absint( ( $query['time_finish'] * 1000 ) - ( $query['time_start'] * 1000 ) );
			}

			if ( ! empty( $query['args'] ) ) {

				// reformat the request body
				if ( ! empty( $query['args']['body'] ) ) {
					$args['request']['body'] = json_encode( json_decode( $query['args']['body'] ), JSON_PRETTY_PRINT );
				}

				// add the method
				if ( ! empty( $query['args']['method'] ) ) { 
					$args['method'] = $query['args']['method'];
				}

			}

			if ( ! empty( $query['request'] ) ) {

				// actually the response headers
				if ( ! empty( $query['request']['headers'] ) && is_array( $query['request']['headers'] ) ) {

					foreach( $query['request']['headers'] as $header => $value ) {
						$args['response']['headers'][ $header ] = $value;
					}
				}

				if ( ! empty( $query['request']['response'] ) && ! empty( $query['request']['response']['code'] ) ) {
					$args['status'] = $query['request']['response']['code'];
				}

				if ( ! empty( $query['request']['body'] ) ) {
					$args['response']['body'] = json_decode( $query['request']['body'] );
				}

			}

			// log the EP request/response
			do_action( WP_REST_API_Log_Common::PLUGIN_NAME . '-insert', $args );

		}
	}
}

