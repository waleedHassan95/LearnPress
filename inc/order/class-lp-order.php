<?php

/**
 * Class LP_Order
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Order {

	/**
	 * Store post object
	 *
	 * @var null|WP_Post object
	 */
	public $post = null;

	/**
	 * @var null|LP_Order object
	 */
	protected static $_instance = null;

	/**
	 * @param $order
	 */
	function __construct( $order ) {
		$this->init( $order );
	}

	/**
	 * Init/load the order object. Called from the constructor.
	 *
	 * @param  int|object|LP_Order $order Order to init
	 */
	protected function init( $order ) {
		if ( is_numeric( $order ) ) {
			$this->id   = absint( $order );
			$this->post = get_post( $order );
			$this->get_order( $this->id );
		} elseif ( $order instanceof LP_Order ) {
			$this->id   = absint( $order->id );
			$this->post = $order->post;
			$this->get_order( $this->id );
		} elseif ( isset( $order->ID ) ) {
			$this->id   = absint( $order->ID );
			$this->post = $order;
			$this->get_order( $this->id );
		}
	}

	function get_order( $id ){
		if( ! $id){
			return false;
		}

		if ( $result = get_post( $id ) ) {

			$this->id                  = $result->ID;
			$this->order_date          = $result->post_date;
			$this->modified_date       = $result->post_modified;
			$this->customer_message    = $result->post_excerpt;
			$this->customer_note       = $result->post_excerpt;
			$this->post_status         = $result->post_status;

			return true;
		}

		return false;
	}
	/**
	 * Get current status of order
	 *
	 * @return mixed
	 */
	public function get_status() {
		$this->post->post_status = get_post_status( $this->id );
		return apply_filters( 'lp_order_get_status', 'lp-' === substr( $this->post->post_status, 0, 3 ) ? substr( $this->post->post_status, 3 ) : $this->post->post_status, $this );
	}

	/**
	 * Checks to see if current order has status as passed
	 *
	 * @param $status
	 *
	 * @return mixed
	 */
	public function has_status( $status ) {
		return apply_filters( 'lp_order_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
	}

	/**
	 * Updates order to new status if needed
	 *
	 * @param string $new_status
	 */
	function update_status( $new_status = 'pending' ) {
		// Standardise status names.
		$new_status = 'lp-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		$old_status = $this->get_status();

		if ( $new_status !== $old_status || !in_array( $this->post_status, array_keys( learn_press_get_order_statuses() ) ) ) {

			// Update the order
			wp_update_post( array( 'ID' => $this->id, 'post_status' => 'lp-' . $new_status ) );
			$this->post_status = 'lp-' . $new_status;

			// Status was changed
			do_action( 'learn_press_order_status_' . $new_status, $this->id );
			do_action( 'learn_press_order_status_' . $old_status . '_to_' . $new_status, $this->id );
			do_action( 'learn_press_order_status_changed', $this->id, $old_status, $new_status );

			switch ( $new_status ) {

				case 'completed' :

					break;

				case 'processing' :

					break;
			}
			// backward compatible
			do_action( 'learn_press_update_order_status', $new_status, $this->id );
		}
	}

	/**
	 * Format order number id
	 * @return string
	 */
	function get_order_number() {
		return '#' . sprintf( "%'.010d", $this->id );
	}

	/**
	 * Mark order as complete
	 *
	 * @param string - transaction ID provided payment gateway
	 */
	function payment_complete( $transaction_id = '' ) {
		do_action( 'learn_press_pre_payment_complete', $this->id );

		delete_transient( 'order_awaiting_payment' );

		$valid_order_statuses = apply_filters( 'learn_press_valid_order_statuses_for_payment_complete', array( 'pending' ), $this );

		if ( $this->id && $this->has_status( $valid_order_statuses ) ) {

			$this->update_status( 'completed' );

			if ( !empty( $transaction_id ) ) {
				add_post_meta( $this->id, '_transaction_id', $transaction_id, true );
			}

			do_action( 'learn_press_payment_complete', $this->id );
		} else {
			do_action( 'learn_press_payment_complete_order_status_' . $this->get_status(), $this->id );
		}
	}

	/**
	 * Get checkout order success url
	 *
	 * @return mixed
	 */
	public function get_checkout_order_received_url() {
		$received_url = home_url();//hb_get_endpoint_url( 'order-received', $this->id, hb_get_page_permalink( 'search' ) );
		$received_url = add_query_arg( 'key', $this->order_key, $received_url );
		return apply_filters( 'learn_press_get_checkout_order_received_url', $received_url, $this );
	}

	/*********************************/

	function get_items() {
		if ( !$this->post ) return false;
		return learn_press_get_order_items( $this->post->ID );
	}

	function remove_order_items(){
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare( "
				DELETE FROM itemmeta
				USING {$wpdb->learnpress_order_itemmeta} itemmeta
				INNER JOIN {$wpdb->learnpress_order_items} items
				WHERE itemmeta.learnpress_order_item_id = items.order_item_id
				AND items.order_id = %d",
				$this->id
			)
		);
		$wpdb->query(
			$wpdb->prepare( "
				DELETE FROM {$wpdb->learnpress_order_items}
				WHERE order_id = %d",
				$this->id
			)
		);
	}

	function add_item( $item, $quantity = 1, $meta = array() ){
		$item_id = learn_press_add_order_item( $this->id, $item );
		if( ! $item_id ){
			return false;
		}
		learn_press_add_order_item_meta( $item_id, '_course_id', $item['item_id'] );
		learn_press_add_order_item_meta( $item_id, '_quantity', $item['quantity'] );
		learn_press_add_order_item_meta( $item_id, '_subtotal', $item['subtotal'] );
		learn_press_add_order_item_meta( $item_id, '_total', $item['total'] );
		return $item_id;
	}

	function get_user( $field = '' ) {

		$user = learn_press_get_user( $this->user_id );
		if ( $field ) {
			return $user->{$field};
		}
		return $user;
	}

	public function __get( $key ) {
		$value = null;
		if( ! isset( $this->{$key} ) ){
			$value = get_post_meta( $this->id, '_' . $key, true );
		}
		return $value;
	}

	function get_checkout_payment_url() {

	}

	function get_formatted_order_total() {
		$order_items     = learn_press_get_order_items( $this->post->ID );
		$currency_symbol = learn_press_get_currency_symbol( $order_items->currency );
		return learn_press_format_price( $order_items->sub_total, $currency_symbol );
	}

	function get_payment_method_title() {
		return learn_press_payment_method_from_slug( $this->post->ID );
	}

	/*********************************/

	/**
	 * Get an instance of LP_Order by post ID or WP_Post object
	 *
	 * @param $order
	 * @return LP_Order
	 */
	static function instance( $order ) {
		$post = $order;
		if ( $order instanceof WP_Post ) {
			$id = $order->ID;
		} elseif ( is_object( $order ) && isset( $order->ID ) ) {
			$id = $order->ID;
		} else {
			$id = $order;
		}
		if ( empty( self::$_instance[$id] ) ) {
			self::$_instance[$id] = new self( $post );
		}
		return self::$_instance[$id];
	}
}