<?php
/**
 * Base class for plugin
 *
 * @package WooCommerce Product Inventory Data Store
 * @author Automattic
 */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}
/**
 * WC_Order_Datastore class.
 */
class Ayobro_WC_Data_Store {

	public function __construct() {
		add_filter( 'woocommerce_data_stores', array( $this, 'install_data_store' ) );
	}

	public function install_data_store( $stores ) {
		include_once dirname( __FILE__ ) . '/class-ayobro-wc-order-data-store.php';

		$instance = new Ayobro_WC_Order_Data_Store( $stores['order'] );
		$stores['order'] = $instance;

		return $stores;
	}
	
}

new Ayobro_WC_Data_Store();