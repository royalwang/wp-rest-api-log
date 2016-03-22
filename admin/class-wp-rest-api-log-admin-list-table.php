<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Admin_List_Table' ) ) {

	class WP_REST_API_Log_Admin_List_Table {

		private $_post      = null;
		private $_post_id   = 0;

		public function plugins_loaded() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}


		public function admin_init() {
			$post_type = WP_REST_API_Log_Db::POST_TYPE;

			add_filter( 'post_row_actions',                          array( $this, 'post_row_actions' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_columns" ,         array( $this, 'custom_columns' ) );
			add_action( "manage_{$post_type}_posts_custom_column",   array( $this, 'custom_column' ), 10, 2 );
			add_filter( "bulk_actions-edit-{$post_type}",            array( $this, 'remove_edit_bulk_action' ) ) ;

			add_action( 'restrict_manage_posts',                     array( $this, 'add_method_dropdown' ) );
			add_action( 'restrict_manage_posts',                     array( $this, 'add_status_dropdown' ) );
		}


		public function post_row_actions( $actions, $post ) {

			if ( WP_REST_API_Log_Db::POST_TYPE === $post->post_type ) {

				// turn off items
				unset( $actions['edit'] );
				unset( $actions[ 'inline hide-if-no-js' ] );

			}

			return $actions;
		}


		public function custom_columns( $columns ) {

			unset( $columns['author'] );
			$columns['method'] = 'Method';
			$columns = array(
				'cb'         => '<input type="checkbox" />',
				'date'       => __( 'Date' ),
				'method'     => __( 'Method', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'title'      => __( 'Title' ),
				'status'     => __( 'Status', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'elapsed'    => __( 'Elapsed Time', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'length'     => __( 'Response Length', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'ip-address' => __( 'IP Address', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				);


			return $columns;
		}


		public function custom_column( $column, $post_id ) {
			$entry = $this->get_entry( $post_id );

			if ( ! empty( $entry ) ) {

				switch ( $column ) {
					case 'method';
						echo esc_html( $entry->method );
						break;
					case 'status';
						echo esc_html( $entry->status );
						break;
					case 'elapsed';
						echo esc_html( number_format( $entry->milliseconds ) );
						break;
					case 'length';
						echo esc_html( number_format( strlen( $entry->response->body ) ) );
						break;
					case 'ip-address';
						echo esc_html( $entry->ip_address );
						break;
				}

			}

		}

		public function add_method_dropdown( $post_type ) {
			if ( 'wp-rest-api-log' === $post_type ) {

				$selected_method   = filter_input( INPUT_GET, WP_REST_API_Log_DB::TAXONOMY_METHOD, FILTER_SANITIZE_STRING );
				$methods           = array( 'GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS' );

				?>
					<label for="wp-rest-api-log-methods" class="screen-reader-text"><?php esc_html_e( 'Methods', 'wp-rest-api-log' ); ?></label>
					<select name="<?php echo esc_attr( WP_REST_API_Log_DB::TAXONOMY_METHOD ); ?>" id="wp-rest-api-log-methods">
						<option value=""><?php esc_html_e( 'All Methods', 'wp-rest-api-log' ); ?></option>
						<?php foreach( $methods as $method ) : ?>
							<option value="<?php echo esc_attr( $method ); ?>" <?php selected( $method, $selected_method ); ?>><?php echo esc_html( $method ); ?></option>
						<?php endforeach; ?>
					</select>

				<?php
			}
		}

		public function add_status_dropdown( $post_type ) {
			if ( 'wp-rest-api-log' === $post_type ) {

				$selected_status   = filter_input( INPUT_GET, WP_REST_API_Log_DB::TAXONOMY_STATUS, FILTER_SANITIZE_STRING );
				$statuses          = get_terms( WP_REST_API_Log_DB::TAXONOMY_STATUS );

				?>
					<label for="wp-rest-api-log-statuses" class="screen-reader-text"><?php esc_html_e( 'Status', 'wp-rest-api-log' ); ?></label>
					<select name="<?php echo esc_attr( WP_REST_API_Log_DB::TAXONOMY_STATUS ); ?>" id="wp-rest-api-log-statuses">
						<option value=""><?php esc_html_e( 'All Statuses', 'wp-rest-api-log' ); ?></option>
						<?php foreach( $statuses as $status ) : ?>
							<option value="<?php echo esc_attr( $status->slug ); ?>" <?php selected( $status->slug, $selected_status ); ?>><?php echo esc_html( $status->name ); ?></option>
						<?php endforeach; ?>
					</select>

				<?php
			}
		}

		private function get_entry( $post_id ) {
			if ( empty( $this->_post ) || $post_id !== $this->_post_id ) {
				$this->_post = new WP_REST_API_Log_Entry( $post_id );
				$this->_post_id = $post_id;
			}
			return $this->_post;
		}

		public function remove_edit_bulk_action( $actions ) {
			unset( $actions['edit'] );
			return $actions;
		}

	}

}