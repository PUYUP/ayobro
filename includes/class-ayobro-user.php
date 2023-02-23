<?php
class WP_User_Extend extends WP_User {

	public function authenticate_extend( $user, $username, $password ) {
		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();
			$error_message = str_replace( 'username', 'phone number', $user->get_error_message( $error_code ) );
			
			return new WP_Error(
				$error_code,
				wp_strip_all_tags( $error_message ),
				[
					'status' => 403,
				]
			);
		}

		if ( null == $user ) {
			// TODO: What should the error message be? (Or would these even happen?)
			// Only needed if all authentication handlers fail to return anything.
			return new WP_Error( 'authentication_failed', __( '<strong>Error:</strong> Invalid phone number, email address or incorrect password.' ) );
		}

		return $user;
	}

	public function jwt_auth_token_before_dispatch_extend( $data, $user ) {
		$data['id'] = $user->ID;
		
		return $data;
	}

}