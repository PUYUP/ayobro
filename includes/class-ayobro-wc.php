<?php
class Ayobro_WC {

	protected $param_prefixs = array( 'shipping', 'billing' );
	protected $param_fields = array( 'latitude', 'longitude' );

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
		foreach( $this->param_prefixs as $prefix ) {
			foreach( $this->param_fields as $field ) {
				$value = $object->get_meta( $object->get_id(), "_{$prefix}_{$field}");
				$response->data[$prefix][$field] = $value;
			}
		}
		
		// Append variation description
		$line_items = $response->data['line_items'];

		foreach( $line_items as $key => $value ) {
			$variation_id = $line_items[$key]['variation_id'];
			$variation = new WC_Product_Variation( $variation_id );
			$variation_image = wp_get_attachment_image_src( $variation->get_image_id(), 'thumbnail' );

			$line_items[$key]['variation_description'] = get_post_meta( $variation_id, '_variation_description', true );	
			$line_items[$key]['variation_image'] = $variation_image ? $variation_image[0] : '';	
		}
		
		$response->data['line_items'] = $line_items;
		return $response;
	}

	/**
	 * Prepare order before insert to database
	 */
	public function woocommerce_rest_pre_insert_shop_order_object( $order, $request, $creating ) {
		foreach( $this->param_prefixs as $prefix ) {
			if ( isset( $request[$prefix] ) ) {
				foreach( $this->param_fields as $field ) {
					if ( isset( $request[$prefix][$field] ) ) {
						$order->update_meta_data( "_{$prefix}_{$field}", $request[$prefix][$field] );
					}
				}
			}
		}

		return $order;
	}

	/**
	 * Custom post status
	 */
	public function shop_order_post_statuses_extend() {
		register_post_status( 'wc-waiting', array(
			'label'                     => _x( 'Waiting', 'Order status', 'woocommerce' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count'               => _n_noop( 'Waiting <span class="count">(%s)</span>', 'Waiting <span class="count">(%s)</span>', 'woocommerce' ),
		) );

		register_post_status( 'wc-confirmed', array(
			'label'                     => _x( 'Confirmed', 'Order status', 'woocommerce' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'woocommerce' ),
		) );
	}

	/**
	 * Custom order status
	 */
	public function wc_order_statuses_extend( $order_statuses ) {
		$new_order_statuses = array();

		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses['wc-waiting'] = _x( 'Waiting', 'Order status', 'woocommerce' );
			$new_order_statuses['wc-confirmed'] = _x( 'Confirmed', 'Order status', 'woocommerce' );
			$new_order_statuses[$key] = $status;
		}

		return $new_order_statuses;
	}

	/**
	 * Added custom bulk action to order list
	 */
	function custom_dropdown_bulk_actions_shop_order( $actions ) {
		$new_actions = array();

		// Add new custom order status after processing
		foreach ($actions as $key => $action) {
			$new_actions['mark_waiting'] = __( 'Change status to waiting', 'woocommerce' );
			$new_actions['mark_confirmed'] = __( 'Change status to confirmed', 'woocommerce' );
			$new_actions[$key] = $action;
		}

		return $new_actions;
	}

	/**
	 * Add more data to line items
	 */
	public function woocommerce_order_get_items_extend( $items, $object, $types ) {
		if ( $types == 'line_items' ) {
			
		}

		

		return $items;
	}

}