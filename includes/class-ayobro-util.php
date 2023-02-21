<?php
/**
 * Check given number valid msisdn or not
 * 
 * @param string $msisdn
 * @return boolean true if valid, false invalid
 */
function validate_msisdn( $msisdn ) {
	if ( ! is_numeric( $msisdn ) ) {
		return false;
	}

	$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

	try {
		$proto = $phoneUtil->parse( $msisdn, 'ID' );
		$isValid = $phoneUtil->isValidNumber( $proto );

		return $isValid;
	}
	catch (\libphonenumber\NumberParseException $e) {
		return false;
	}
}

/**
 * User slug a.k.a user_nicename checker
 */
function user_nicename_exists( $slug ) {
	$user = get_user_by( 'slug', $slug );
	if ( $user ) {
		$user_id = $user->ID;
	} else {
		$user_id = false;
	}

	/**
	 * Filters whether the given slug exists.
	 *
	 * @since 4.9.0
	 *
	 * @param int|false $user_id  The user ID associated with the slug,
	 *                            or false if the slug does not exist.
	 * @param string    $slug The slug to check for existence.
	 */
	return apply_filters( 'slug_exists', $user_id, $slug );
}