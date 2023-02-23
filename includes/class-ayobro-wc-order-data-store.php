<?php
/**
 * Product Data Store
 * Inventory with pass-thru
 *
 * @package WooCommerce Product Inventory Data Store
 * @author Automattic
 */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}
/**
 * Ayobro_WC_Order_Data_Store.
 */
class Ayobro_WC_Order_Data_Store extends WC_Order_Data_Store_CPT implements WC_Object_Data_Store_Interface, WC_Order_Data_Store_Interface {

	public function __construct( &$parent_data_store ) {
		$this->parent_instance = $this->create_parent_instance( $parent_data_store );

		$this->internal_meta_keys[] = '_shipping_latitude';
		$this->internal_meta_keys[] = '_shipping_longitude';
		$this->internal_meta_keys[] = '_billing_latitude';
		$this->internal_meta_keys[] = '_billing_longitude';
	}

	private function create_parent_instance( $store ) {
		if ( is_object( $store ) ) {
			if ( ! $store instanceof WC_Object_Data_Store_Interface ||
					! $store instanceof WC_Order_Data_Store_Interface ) {
				throw new Exception( __( 'Invalid parent product data store.', 'woocommerce' ) );
			}
			return $store;
		} else {
			if ( ! class_exists( $store ) ) {
				throw new Exception( __( 'Invalid parent product data store.', 'woocommerce' ) );
			}
			return new $store;
		}
	}

	public function get_internal_meta_keys() {
		return $this->internal_meta_keys;
	}

	/**
	 * Read order data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Order $order Order object.
	 * @param object   $post_object Post object.
	 * @since 3.0.0
	 */
	protected function read_order_data( &$order, $post_object ) {
		parent::read_order_data( $order, $post_object );
		$id             = $order->get_id();
		$date_completed = get_post_meta( $id, '_date_completed', true );
		$date_paid      = get_post_meta( $id, '_date_paid', true );

		if ( ! $date_completed ) {
			$date_completed = get_post_meta( $id, '_completed_date', true );
		}

		if ( ! $date_paid ) {
			$date_paid = get_post_meta( $id, '_paid_date', true );
		}

		$order->set_props(
			array(
				'order_key'                    => get_post_meta( $id, '_order_key', true ),
				'customer_id'                  => get_post_meta( $id, '_customer_user', true ),
				'billing_first_name'           => get_post_meta( $id, '_billing_first_name', true ),
				'billing_last_name'            => get_post_meta( $id, '_billing_last_name', true ),
				'billing_company'              => get_post_meta( $id, '_billing_company', true ),
				'billing_address_1'            => get_post_meta( $id, '_billing_address_1', true ),
				'billing_address_2'            => get_post_meta( $id, '_billing_address_2', true ),
				'billing_city'                 => get_post_meta( $id, '_billing_city', true ),
				'billing_state'                => get_post_meta( $id, '_billing_state', true ),
				'billing_postcode'             => get_post_meta( $id, '_billing_postcode', true ),
				'billing_country'              => get_post_meta( $id, '_billing_country', true ),
				'billing_email'                => get_post_meta( $id, '_billing_email', true ),
				'billing_phone'                => get_post_meta( $id, '_billing_phone', true ),
				'billing_latitude'             => get_post_meta( $id, '_billing_latitude', true ),
				'billing_longitude'            => get_post_meta( $id, '_billing_longitude', true ),
				'shipping_first_name'          => get_post_meta( $id, '_shipping_first_name', true ),
				'shipping_last_name'           => get_post_meta( $id, '_shipping_last_name', true ),
				'shipping_company'             => get_post_meta( $id, '_shipping_company', true ),
				'shipping_address_1'           => get_post_meta( $id, '_shipping_address_1', true ),
				'shipping_address_2'           => get_post_meta( $id, '_shipping_address_2', true ),
				'shipping_city'                => get_post_meta( $id, '_shipping_city', true ),
				'shipping_state'               => get_post_meta( $id, '_shipping_state', true ),
				'shipping_postcode'            => get_post_meta( $id, '_shipping_postcode', true ),
				'shipping_country'             => get_post_meta( $id, '_shipping_country', true ),
				'shipping_phone'               => get_post_meta( $id, '_shipping_phone', true ),
				'shipping_latitude'            => get_post_meta( $id, '_shipping_latitude', true ),
				'shipping_longitude'           => get_post_meta( $id, '_shipping_longitude', true ),
				'payment_method'               => get_post_meta( $id, '_payment_method', true ),
				'payment_method_title'         => get_post_meta( $id, '_payment_method_title', true ),
				'transaction_id'               => get_post_meta( $id, '_transaction_id', true ),
				'customer_ip_address'          => get_post_meta( $id, '_customer_ip_address', true ),
				'customer_user_agent'          => get_post_meta( $id, '_customer_user_agent', true ),
				'created_via'                  => get_post_meta( $id, '_created_via', true ),
				'date_completed'               => $date_completed,
				'date_paid'                    => $date_paid,
				'cart_hash'                    => get_post_meta( $id, '_cart_hash', true ),
				'customer_note'                => $post_object->post_excerpt,

				// Operational data props.
				'order_stock_reduced'          => get_post_meta( $id, '_order_stock_reduced', true ),
				'download_permissions_granted' => get_post_meta( $id, '_download_permissions_granted', true ),
				'new_order_email_sent'         => get_post_meta( $id, '_new_order_email_sent', true ),
				'recorded_sales'               => wc_string_to_bool( get_post_meta( $id, '_recorded_sales', true ) ),
				'recorded_coupon_usage_counts' => get_post_meta( $id, '_recorded_coupon_usage_counts', true ),
			)
		);
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param WC_Order $order Order object.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$order ) {
		$updated_props     = array();
		$id                = $order->get_id();
		$meta_key_to_props = array(
			'_order_key'                    => 'order_key',
			'_customer_user'                => 'customer_id',
			'_payment_method'               => 'payment_method',
			'_payment_method_title'         => 'payment_method_title',
			'_transaction_id'               => 'transaction_id',
			'_customer_ip_address'          => 'customer_ip_address',
			'_customer_user_agent'          => 'customer_user_agent',
			'_created_via'                  => 'created_via',
			'_date_completed'               => 'date_completed',
			'_date_paid'                    => 'date_paid',
			'_cart_hash'                    => 'cart_hash',
			'_download_permissions_granted' => 'download_permissions_granted',
			'_recorded_sales'               => 'recorded_sales',
			'_recorded_coupon_usage_counts' => 'recorded_coupon_usage_counts',
			'_new_order_email_sent'         => 'new_order_email_sent',
			'_order_stock_reduced'          => 'order_stock_reduced',
		);

		$props_to_update = $this->get_props_to_update( $order, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $order->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;
			switch ( $prop ) {
				case 'date_paid':
				case 'date_completed':
					$value = ! is_null( $value ) ? $value->getTimestamp() : '';
					break;
				case 'download_permissions_granted':
				case 'recorded_sales':
				case 'recorded_coupon_usage_counts':
				case 'order_stock_reduced':
					if ( is_null( $value ) || '' === $value ) {
						break;
					}
					$value = is_bool( $value ) ? wc_bool_to_string( $value ) : $value;
					break;
				case 'new_order_email_sent':
					if ( is_null( $value ) || '' === $value ) {
						break;
					}
					$value = is_bool( $value ) ? wc_bool_to_string( $value ) : $value;
					$value = 'yes' === $value ? 'true' : 'false'; // For backward compatibility, we store as true/false in DB.
					break;
			}

			// We want to persist internal data store keys as 'yes' or 'no' if they are boolean to maintain compatibility.
			if ( is_bool( $value ) && in_array( $prop, array_values( $this->internal_data_store_key_getters ), true ) ) {
				$value = wc_bool_to_string( $value );
			}

			$updated = $this->update_or_delete_post_meta( $order, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		$address_props = array(
			'billing'  => array(
				'_billing_first_name' => 'billing_first_name',
				'_billing_last_name'  => 'billing_last_name',
				'_billing_company'    => 'billing_company',
				'_billing_address_1'  => 'billing_address_1',
				'_billing_address_2'  => 'billing_address_2',
				'_billing_city'       => 'billing_city',
				'_billing_state'      => 'billing_state',
				'_billing_postcode'   => 'billing_postcode',
				'_billing_country'    => 'billing_country',
				'_billing_email'      => 'billing_email',
				'_billing_phone'      => 'billing_phone',
				'_billing_latitude'   => 'billing_latitude',
				'_billing_longitude'  => 'billing_longitude',
			),
			'shipping' => array(
				'_shipping_first_name' => 'shipping_first_name',
				'_shipping_last_name'  => 'shipping_last_name',
				'_shipping_company'    => 'shipping_company',
				'_shipping_address_1'  => 'shipping_address_1',
				'_shipping_address_2'  => 'shipping_address_2',
				'_shipping_city'       => 'shipping_city',
				'_shipping_state'      => 'shipping_state',
				'_shipping_postcode'   => 'shipping_postcode',
				'_shipping_country'    => 'shipping_country',
				'_shipping_phone'      => 'shipping_phone',
				'_shipping_latitude'   => 'shipping_latitude',
				'_shipping_longitude'  => 'shipping_longitude',
			),
		);

		foreach ( $address_props as $props_key => $props ) {
			$props_to_update = $this->get_props_to_update( $order, $props );
			foreach ( $props_to_update as $meta_key => $prop ) {
				$value   = $order->{"get_$prop"}( 'edit' );
				$value   = is_string( $value ) ? wp_slash( $value ) : $value;
				$updated = $this->update_or_delete_post_meta( $order, $meta_key, $value );

				if ( $updated ) {
					$updated_props[] = $prop;
					$updated_props[] = $props_key;
				}
			}
		}

		parent::update_post_meta( $order );

		// If address changed, store concatenated version to make searches faster.
		if ( in_array( 'billing', $updated_props, true ) || ! metadata_exists( 'post', $id, '_billing_address_index' ) ) {
			update_post_meta( $id, '_billing_address_index', implode( ' ', $order->get_address( 'billing' ) ) );
		}
		if ( in_array( 'shipping', $updated_props, true ) || ! metadata_exists( 'post', $id, '_shipping_address_index' ) ) {
			update_post_meta( $id, '_shipping_address_index', implode( ' ', $order->get_address( 'shipping' ) ) );
		}

		// Legacy date handling. @todo remove in 4.0.
		if ( in_array( 'date_paid', $updated_props, true ) ) {
			$value = $order->get_date_paid( 'edit' );
			// In 2.6.x date_paid was stored as _paid_date in local mysql format.
			update_post_meta( $id, '_paid_date', ! is_null( $value ) ? $value->date( 'Y-m-d H:i:s' ) : '' );
		}

		if ( in_array( 'date_completed', $updated_props, true ) ) {
			$value = $order->get_date_completed( 'edit' );
			// In 2.6.x date_completed was stored as _completed_date in local mysql format.
			update_post_meta( $id, '_completed_date', ! is_null( $value ) ? $value->date( 'Y-m-d H:i:s' ) : '' );
		}

		// If customer changed, update any downloadable permissions.
		if ( in_array( 'customer_id', $updated_props, true ) || in_array( 'billing_email', $updated_props, true ) ) {
			$data_store = WC_Data_Store::load( 'customer-download' );
			$data_store->update_user_by_order_id( $id, $order->get_customer_id(), $order->get_billing_email() );
		}

		// Mark user account as active.
		if ( in_array( 'customer_id', $updated_props, true ) ) {
			wc_update_user_last_active( $order->get_customer_id() );
		}

		do_action( 'woocommerce_order_object_updated_props', $order, $updated_props );
	}

}