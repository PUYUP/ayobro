<?php
class Ayobro_WC {

	/**
	 * Added custom checkout fields
	 */
	public function woocommerce_checkout_fields_extend( $fields ) {
		// Shipping
		$fields['shipping']['latitude'] = array(
			'label' => __( 'Latitude', 'ayobro' ),
			'placeholder' => _x( 'Latitude', 'placeholder', 'ayobro' ),
			'required' => true,
		);
		
		$fields['shipping']['longitude'] = array(
			'label' => __( 'Longitude', 'ayobro' ),
			'placeholder' => _x( 'Longitude', 'placeholder', 'ayobro' ),
			'required' => true,
		);

		// Billing
		$fields['billing']['latitude'] = array(
			'label' => __( 'Latitude', 'ayobro' ),
			'placeholder' => _x( 'Latitude', 'placeholder', 'ayobro' ),
			'required' => true,
		);

		$fields['billing']['longitude'] = array(
			'label' => __( 'Longitude', 'ayobro' ),
			'placeholder' => _x( 'Longitude', 'placeholder', 'ayobro' ),
			'required' => true,
		);

		return $fields;
	}

	/**
	 * Extending default address fields
	 */
	public function woocommerce_default_address_fields_extend( $fields ) {
		$fields[ 'latitude' ]  = array(
			'label'        => __( 'Latitude', 'ayobro' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'my-custom-class' ),
			'priority'     => 20,
			'placeholder'  => _x( 'Latitude', 'placeholder', 'ayobro' ),
		);

		$fields[ 'longitude' ]  = array(
			'label'        => __( 'Longitude', 'ayobro' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'my-custom-class' ),
			'priority'     => 20,
			'placeholder'  => _x( 'Longitude', 'placeholder', 'ayobro' ),
		);

		return $fields;
	}

	/**
	 * Core WC rest api namescape
	 */
	public function woocommerce_rest_api_get_rest_namespaces_extend( $namespaces ) {
		$namespaces['wc/v3']['orders'] = 'Ayobro_WC_REST_Orders';
		
		return $namespaces;
	}

	/**
	 * Extending response
	 */
	public function woocommerce_rest_prepare_shop_order_object( $response, $object, $request ) {
		$response->data['billing']['latitude'] = (float) get_post_meta( $object->get_id(), '_billing_latitude', true );
		$response->data['billing']['longitude'] = (float) get_post_meta( $object->get_id(), '_billing_longitude', true );
		$response->data['shipping']['latitude'] = (float) get_post_meta( $object->get_id(), '_shipping_latitude', true );
		$response->data['shipping']['longitude'] = (float) get_post_meta( $object->get_id(), '_shipping_longitude', true );

		return $response;
	}

}