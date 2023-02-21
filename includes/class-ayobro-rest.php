<?php 
class Ayobro_REST_Server extends WP_REST_Server {

	public function rest_pre_echo_response_handler( $result, $served, $request ) {
		$code = ( isset( $result['code'] ) && ! empty( $result['code'] ) ) ? $result['code'] : '';
		
		// Username exist
		switch ( $code ) {
			case 'existing_user_login':
				$result['message'] = __( 'Sorry, that phone number already exists!' );
			break;
		}

		return $result;
	}

}