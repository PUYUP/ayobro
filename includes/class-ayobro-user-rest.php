<?php
class WP_REST_Users_Controller_Extend extends WP_REST_Users_Controller {

	/**
	 * Extend pre insert user
	 */
	public function extend_rest_pre_insert_user( $prepared_user, $request ) {
		// If nickname empty filled with name
		if ( empty( $prepared_user->nickname ) && isset( $prepared_user->display_name ) ) {
			$prepared_user->nickname = $prepared_user->display_name;
		}

		return $prepared_user;
	}

	/**
	 * Registers the routes for users.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/me',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => '__return_true',
					'callback'            => array( $this, 'get_current_item' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_current_item' ),
					'permission_callback' => array( $this, 'update_current_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_current_item' ),
					'permission_callback' => array( $this, 'delete_current_item_permissions_check' ),
					'args'                => array(
						'force'    => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Required to be true, as users do not support trashing.' ),
						),
						'reassign' => array(
							'type'              => 'integer',
							'description'       => __( 'Reassign the deleted user\'s posts and links to this user ID.' ),
							'required'          => true,
							'sanitize_callback' => array( $this, 'check_reassign' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * By default wordpress prevent registration from rest api
	 * this function allow registration with specific roles
	 * accepted roles is 'subscriber' only
	 */
	public function create_item_permissions_check( $request ) {
		$roles = ! empty( $request['roles'] ) ? $request['roles'] : array();

		if ( ! current_user_can( 'create_users' ) && ! in_array( 'subscriber', $roles ) ) {
			return new WP_Error(
				'rest_cannot_create_user',
				__( 'Sorry, you are only allowed to create new users with the ' . implode( ', ', $roles ) . ' role.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
        
		return true;
	}

	/**
	 * Creates a single user.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error(
				'rest_user_exists',
				__( 'Cannot create existing user.' ),
				array( 'status' => 400 )
			);
		}

		$schema = $this->get_item_schema();

		if ( ! empty( $request['roles'] ) && ! empty( $schema['properties']['roles'] ) ) {
			$check_permission = $this->check_role_update( $request['id'], $request['roles'] );

			if ( is_wp_error( $check_permission ) ) {
				return $check_permission;
			}
		}

		$user = $this->prepare_item_for_database( $request );

		if ( is_multisite() ) {
			$ret = wpmu_validate_user_signup( $user->user_login, $user->user_email );

			if ( is_wp_error( $ret['errors'] ) && $ret['errors']->has_errors() ) {
				$error = new WP_Error(
					'rest_invalid_param',
					__( 'Invalid user parameter(s).' ),
					array( 'status' => 400 )
				);

				foreach ( $ret['errors']->errors as $code => $messages ) {
					foreach ( $messages as $message ) {
						$error->add( $code, $message );
					}

					$error_data = $error->get_error_data( $code );

					if ( $error_data ) {
						$error->add_data( $error_data, $code );
					}
				}
				return $error;
			}
		}

		if ( is_multisite() ) {
			$user_id = wpmu_create_user( $user->user_login, $user->user_pass, $user->user_email );

			if ( ! $user_id ) {
				return new WP_Error(
					'rest_user_create',
					__( 'Error creating new user.' ),
					array( 'status' => 500 )
				);
			}

			$user->ID = $user_id;
			$user_id  = wp_update_user( wp_slash( (array) $user ) );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$result = add_user_to_blog( get_site()->id, $user_id, '' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		} else {
			$user_id = wp_insert_user( wp_slash( (array) $user ) );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
		}

		$user = get_user_by( 'id', $user_id );

		/**
		 * Fires immediately after a user is created or updated via the REST API.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_User         $user     Inserted or updated user object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a user, false when updating.
		 */
		do_action( 'rest_insert_user', $user, $request, true );

		if ( ! empty( $request['roles'] ) && ! empty( $schema['properties']['roles'] ) ) {
			array_map( array( $user, 'add_role' ), $request['roles'] );
		}

		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $user_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$user          = get_user_by( 'id', $user_id );
		$fields_update = $this->update_additional_fields_for_object( $user, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/**
		 * Fires after a user is completely created or updated via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_User         $user     Inserted or updated user object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a user, false when updating.
		 */
		do_action( 'rest_after_insert_user', $user, $request, true );

		$response = $this->prepare_item_for_response( $user, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $user_id ) ) );

		return $response;
	}

	/**
	 * Updates the current user.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_current_item( $request ) {
		$request['id'] = get_current_user_id();

		return $this->update_item( $request );
	}

	/**
	 * Check a username for the REST API.
	 *
	 * Performs a couple of checks like edit_user() in wp-admin/includes/user.php.
	 *
	 * @since 4.7.0
	 *
	 * @param string          $value   The username submitted in the request.
	 * @param WP_REST_Request $request Full details about the request.
	 * @param string          $param   The parameter name.
	 * @return string|WP_Error The sanitized username, if valid, otherwise an error.
	 */
	public function check_username( $value, $request, $param ) {
		$username = (string) $value;

		if ( ! validate_username( $username ) ) {
			return new WP_Error(
				'rest_user_invalid_username',
				__( 'This username is invalid because it uses illegal characters. Please enter a valid username.' ),
				array( 'status' => 400 )
			);
		}

		/** This filter is documented in wp-includes/user.php */
		$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

		if ( in_array( strtolower( $username ), array_map( 'strtolower', $illegal_logins ), true ) ) {
			return new WP_Error(
				'rest_user_invalid_username',
				__( 'Sorry, that username is not allowed.' ),
				array( 'status' => 400 )
			);
		}

		if ( ! validate_msisdn( $username ) ) {
			return new WP_Error(
				'rest_user_invalid_username',
				__( 'This username is invalid because it uses illegal characters. Please use valid phone number or msisdn.' ),
				array( 'status' => 400 )
			);
		}

		return $username;
	}

	/**
	 * Sanitize slug a.k.a user_nicename
	 * We use slug as username
	 * And username as msisdn
	 */
	public function sanitize_slug( $value ) {
		$slug = (string) $value;

		if ( ! validate_username( $slug ) ) {
			return new WP_Error(
				'rest_user_invalid_nicename',
				__( 'This slug is invalid because it uses illegal characters. Please enter a valid slug.' ),
				array( 'status' => 400 )
			);
		}

		/** This filter is documented in wp-includes/user.php */
		$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

		if ( in_array( strtolower( $slug ), array_map( 'strtolower', $illegal_logins ), true ) ) {
			return new WP_Error(
				'rest_user_invalid_slug',
				__( 'Sorry, that slug is not allowed.' ),
				array( 'status' => 400 )
			);
		}

		if ( user_nicename_exists( $slug ) ) {
			return new WP_Error(
				'rest_user_slug_exist',
				__( 'Sorry, that slug already exists!' ),
				array( 'status' => 400 )
			);
		}
 
		return $slug;
	}

	/**
	 * Update username
	 */
	public function update_username( $orig_user, $updated_user, $request, $is_new ) {
		global $wpdb;

		if ( ! empty( $request['username'] ) && $request['username'] !== $orig_user->user_login && ! $is_new ) {
			// Keep user nicename because this field updated if username changed
			$orig_user->user_nicename = $updated_user->user_login;
			$orig_user->user_login = sanitize_user( $orig_user->user_login, true );

			if ( username_exists( $orig_user->user_login ) ) {
				return new WP_Error(
					'rest_user_username_exist',
					__( 'Sorry, that phone number already exists!' ),
					array( 'status' => 400 )
				);
			}

			$user_id = $wpdb->update( 
				$wpdb->users, 
				array( 'user_login' => $orig_user->user_login ), 
				array( 'ID' => $updated_user->ID ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false === $user_id ) {
				return new WP_Error(
					'rest_user_username_failed',
					__( 'Sorry update username error!' ),
					array( 'status' => 400 )
				);
			}
			else {
				return $updated_user->ID;
			}
		}
	}

	/**
	 * Updates a single user.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		global $wpdb;

		$user = $this->get_user( $request['id'] );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$id = $user->ID;

		$owner_id = false;
		if ( is_string( $request['email'] ) ) {
			$owner_id = email_exists( $request['email'] );
		}

		if ( $owner_id && $owner_id !== $id ) {
			return new WP_Error(
				'rest_user_invalid_email',
				__( 'Invalid email address.' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $request['slug'] ) && $request['slug'] !== $user->user_nicename && get_user_by( 'slug', $request['slug'] ) ) {
			return new WP_Error(
				'rest_user_invalid_slug',
				__( 'Invalid slug.' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $request['roles'] ) ) {
			$check_permission = $this->check_role_update( $id, $request['roles'] );

			if ( is_wp_error( $check_permission ) ) {
				return $check_permission;
			}
		}

		$old_user_login = $user->user_login;
		$new_user_login = ! empty( $request['username'] ) && sanitize_user( $request['username'], true ) ? $request['username'] : '';
		$user = $this->prepare_item_for_database( $request );

		// Ensure we're operating on the same user we already checked.
		$user->ID = $id;
		$user->user_nicename = $new_user_login;
		$user_id = wp_update_user( wp_slash( (array) $user ) );

		if ( ! empty( $new_user_login ) && $new_user_login !== $old_user_login ) {
			if ( username_exists( $new_user_login ) ) {
				return new WP_Error(
					'rest_user_username_exist',
					__( 'Sorry, that phone number already exists!' ),
					array( 'status' => 400 )
				);
			}

			$row_id = $wpdb->update( 
				$wpdb->users, 
				array( 'user_login' => $new_user_login ), 
				array( 'ID' => $user_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false === $row_id ) {
				return new WP_Error(
					'rest_user_username_failed',
					__( 'Sorry update username error!' ),
					array( 'status' => 400 )
				);
			}
			else {
				wp_cache_delete( $user->ID, 'users' );
				wp_cache_delete( $user->user_login, 'userlogins' );
				wp_cache_delete( $user->user_nicename, 'userslugs' );
				wp_cache_delete( $user->user_email, 'useremail' );
			}
		}

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user = get_user_by( 'id', $user_id );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-users-controller.php */
		do_action( 'rest_insert_user', $user, $request, false );

		if ( ! empty( $request['roles'] ) ) {
			array_map( array( $user, 'add_role' ), $request['roles'] );
		}

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}
		
		$user          = get_user_by( 'id', $user_id );
		$fields_update = $this->update_additional_fields_for_object( $user, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-users-controller.php */
		do_action( 'rest_after_insert_user', $user, $request, false );

		$response = $this->prepare_item_for_response( $user, $request );
		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Retrieves the user's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = parent::get_item_schema();

		$schema['properties']['username']['context'] = array( 'edit', 'view' );
		$schema['properties']['roles']['context'] = array( 'edit', 'view' );
		$schema['properties']['email']['required'] = false;

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

}