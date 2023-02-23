<?php
class Ayobro_WC_Order extends WC_Order {

	public function __construct() {
		parent::__construct();

		$this->data['billing']['latitude'] = '';
		$this->data['billing']['longitude'] = '';
		$this->data['shipping']['latitude'] = '';
		$this->data['shipping']['longitude'] = '';
	}

	// -------------------
	// SETTER
	// -------------------

	/**
	 * Set billing latitude.
	 *
	 * @param string $value Billing latitude.
	 * @throws WC_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_billing_latitude( $value ) {
		$this->set_address_prop( 'latitude', 'billing', $value );
	}

	/**
	 * Set billing longitude.
	 *
	 * @param string $value Billing longitude.
	 * @throws WC_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_billing_longitude( $value ) {
		$this->set_address_prop( 'longitude', 'billing', $value );
	}

	/**
	 * Set shipping latitude.
	 *
	 * @param string $value Billing latitude.
	 * @throws WC_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_shipping_latitude( $value ) {
		$this->set_address_prop( 'latitude', 'shipping', $value );
	}

	/**
	 * Set shipping longitude.
	 *
	 * @param string $value Billing longitude.
	 * @throws WC_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_shipping_longitude( $value ) {
		$this->set_address_prop( 'longitude', 'shipping', $value );
	}

	// -------------------
	// SETTER
	// -------------------

	/**
	 * Get billing latitude.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_billing_latitude( $context = 'view' ) {
		return $this->get_address_prop( 'latitude', 'billing', $context );
	}

	/**
	 * Get billing longitude.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_billing_longitude( $context = 'view' ) {
		return $this->get_address_prop( 'longitude', 'billing', $context );
	}

	/**
	 * Get shipping latitude.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_shipping_latitude( $context = 'view' ) {
		return $this->get_address_prop( 'latitude', 'shipping', $context );
	}

	/**
	 * Get shipping longitude.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_shipping_longitude( $context = 'view' ) {
		return $this->get_address_prop( 'longitude', 'shipping', $context );
	}

}