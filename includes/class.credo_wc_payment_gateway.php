<?php

  if( ! defined( 'ABSPATH' ) ) { exit; }

  /**
   * Main Credo Gateway Class
   */
  class CREDO_WC_Payment_Gateway extends WC_Payment_Gateway {
    private $retries;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {

      $this->retries = 3;
      $this->base_url = 'https://credo-js.nugitech.com';
      $this->verify_base_url = 'https://api.credocentral.com/credo-payment/v1';
      $this->id = 'credo';
      $this->icon = null;
      $this->has_fields         = false;
      $this->method_title       = __( 'Credo', 'credo-payments' );
      $this->method_description = __( 'Credo Payment Gateway', 'credo-payments' );
      $this->supports = array(
        'products',
      );

      $this->init_form_fields();
      $this->init_settings();

      $this->title        = __( 'Pay with your bank account or credit/debit card using Credo', 'credo-payments' );
      $this->description  = __( 'Pay with your bank account or credit/debit card using Credo', 'credo-payments' );
      $this->enabled      = $this->get_option( 'enabled' );
      $this->public_key   = $this->get_option( 'public_key' );
      $this->secret_key   = $this->get_option( 'secret_key' );
      $this->go_live      = $this->get_option( 'go_live' );
      $this->payment_method = $this->get_option( 'payment_method' );
      $this->country = $this->get_option( 'country' );

      add_action( 'admin_notices', array( $this, 'admin_notices' ) );
      add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
      add_action( 'woocommerce_api_credo_wc_payment_gateway', array( $this, 'credo_verify_payment'));

      if ( is_admin() ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      }

      if ( 'yes' === $this->go_live ) {
        $this->base_url = 'https://credocentral.com';
      }

      $this->load_scripts();

    }

    /**
     * Initial gateway settings form fields
     *
     * @return void
     */
    public function init_form_fields() {

      $this->form_fields = array(

        'enabled' => array(
          'title'       => __( 'Enable/Disable', 'credo-payments' ),
          'label'       => __( 'Enable Credo Payment Gateway', 'credo-payments' ),
          'type'        => 'checkbox',
          'description' => __( 'Enable Credo Payment Gateway as a payment option on the checkout page', 'credo-payments' ),
          'default'     => 'no',
          'desc_tip'    => true
        ),
        'go_live' => array(
          'title'       => __( 'Go Live', 'credo-payments' ),
          'label'       => __( 'Switch to live account', 'credo-payments' ),
          'type'        => 'checkbox',
          'description' => __( 'Ensure that you are using a public key and secret key generated from the live account.', 'credo-payments' ),
          'default'     => 'no',
          'desc_tip'    => true
        ),
        'public_key' => array(
          'title'       => __( 'Credo Public Key', 'credo-payments' ),
          'type'        => 'text',
          'description' => __( 'Required! Enter your Credo public key here', 'credo-payments' ),
          'default'     => ''
        ),
        'secret_key' => array(
          'title'       => __( 'Credo Secret Key', 'credo-payments' ),
          'type'        => 'text',
          'description' => __( 'Required! Enter your Credo secret key here', 'credo-payments' ),
          'default'     => ''
        ),
        'payment_method' => array(
          'title'       => __( 'Payment Method', 'credo-payments' ),
          'type'        => 'select',
          'description' => __( 'Optional - Choice of payment method to use. Card, Account or Both. (Default: both)', 'credo-payments' ),
          'options'     => array(
            'both' => esc_html_x( 'Card and Account', 'payment_method', 'credo-payments' ),
            'card'  => esc_html_x( 'Card Only',  'payment_method', 'credo-payments' ),
            'account'  => esc_html_x( 'Account Only',  'payment_method', 'credo-payments' ),
          ),
          'default'     => ''
        ),
        'country' => array(
          'title'       => __( 'Charge Country', 'credo-payments' ),
          'type'        => 'select',
          'description' => __( 'Optional - Charge country. (Default: NG)', 'credo-payments' ),
          'options'     => array(
            'NG' => esc_html_x( 'NG', 'country', 'credo-payments' ),
            'GH' => esc_html_x( 'GH', 'country', 'credo-payments' ),
            'KE' => esc_html_x( 'KE', 'country', 'credo-payments' ),
          ),
          'default'     => ''
        ),
        'modal_title' => array(
          'title'       => __( 'Modal Title', 'credo-payments' ),
          'type'        => 'text',
          'description' => __( 'Optional - The title of the payment modal (default: CREDO PAY)', 'credo-payments' ),
          'default'     => ''
        ),
        'modal_description' => array(
          'title'       => __( 'Modal Description', 'credo-payments' ),
          'type'        => 'text',
          'description' => __( 'Optional - The description of the payment modal (default: CREDO PAY MODAL)', 'credo-payments' ),
          'default'     => ''
        ),
        'modal_logo' => array(
          'title'       => __( 'Modal Custom Logo', 'credo-payments' ),
          'type'        => 'text',
          'description' => __( 'Optional - The store custom logo. It has to be a URL', 'credo-payments' ),
          'default'     => ''
        ),

      );

    }

    /**
     * Process payment at checkout
     *
     * @return int $order_id
     */
    public function process_payment( $order_id ) {

      $order = wc_get_order( $order_id );

      return array(
        'result'   => 'success',
        'redirect' => $order->get_checkout_payment_url( true )
      );

    }

    /**
     * Handles admin notices
     *
     * @return void
     */
    public function admin_notices() {

      if ( 'no' == $this->enabled ) {
        return;
      }

      /**
       * Check if public key is provided
       */
      if ( ! $this->public_key || ! $this->secret_key ) {

        echo '<div class="error"><p>';
        echo sprintf(
          'Provide your Credo "Pay Button" public key and secret key <a href="%s">here</a> to be able to use the WooCommerce Credo Payment Gateway plugin.',
           admin_url( 'admin.php?page=wc-settings&tab=checkout&section=credo' )
         );
        echo '</p></div>';
        return;
      }

    }

    /**
     * Checkout receipt page
     *
     * @return void
     */
    public function receipt_page( $order ) {

      $order = wc_get_order( $order );

      echo '<p>'.__( 'Thank you for your order, please click the button below to pay with Credo.', 'credo-payments' ).'</p>';
      echo '<button class="button alt" id="credo-pay-now-button">Pay Now</button> ';
      echo '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">';
      echo __( 'Cancel order &amp; restore cart', 'credo-payments' ) . '</a>';

    }

    /**
     * Loads (enqueue) static files (js & css) for the checkout page
     *
     * @return void
     */
    public function load_scripts() {

      if ( ! is_checkout_pay_page() ) return;

      wp_enqueue_script( 'credopbf_inline_js', $this->base_url . '/inline.js', array(), '1.0.0', true );
      wp_enqueue_script( 'credo_js', plugins_url( 'assets/js/credo.js', CREDO_WC_PLUGIN_FILE ), array( 'jquery', 'credopbf_inline_js' ), '1.0.0', true );

      $p_key = $this->public_key;
      $payment_method = $this->payment_method;

      if ( get_query_var( 'order-pay' ) ) {
        $order_key = urldecode( $_REQUEST['key'] );
        $order_id  = absint( get_query_var( 'order-pay' ) );
        $order     = wc_get_order( $order_id );
        $txnref    = "WOOC_" . $order_id . '_' . time();
        $amount    = $order->order_total;
        $email     = $order->billing_email;
        $currency  = get_option('woocommerce_currency');
        $country  = $this->country;
        $firstname  = $order->billing_first_name;
        $lastname  = $order->billing_last_name;
        $phone  = $order->billing_phone;

        // echo '<pre>'; print_r($order ); die;

        if ( $order->order_key == $order_key ) {

          $payment_args = compact( 'amount', 'email', 'txnref', 'p_key', 'currency', 'country', 'payment_method', 'phone', 'firstname', 'lastname' );
          $payment_args['cb_url'] = WC()->api_request_url( 'CREDO_WC_Payment_Gateway' );
          $payment_args['desc']   = $this->get_option( 'modal_description' );
          $payment_args['title']  = $this->get_option( 'modal_title' );
          $payment_args['logo'] = $this->get_option( 'modal_logo' );
        }

        update_post_meta( $order_id, '_credo_payment_txn_ref', $txnref );

      }

      wp_localize_script( 'credo_js', 'credo_payment_args', $payment_args );

    }

    /**
     * Verify payment made on the checkout page
     *
     * @return void
     */
    public function credo_verify_payment() {
      // echo '<pre>'; print_r($this); die;
      if ( isset( $_POST['txRef'] ) ) {

        $txn_ref = $_POST['txRef'];
        $o = explode( '_', $txn_ref );
        $order_id = intval( $o[1] );
        $order = wc_get_order($order_id);
        $order_currency = $order->get_currency();

        $txn = $this->_fetchTransaction($txn_ref);

        if ( ! empty($txn->id) && $this->_is_successful( $txn ) ) {

          $order_amount = $order->get_total();
          $charged_amount  = $_POST['amount'];

          if ( $charged_amount != $order_amount ) {

            $order->update_status( 'on-hold' );
            $customer_note  = 'Thank you for your order.<br>';
            $customer_note .= 'Your payment successfully went through, but we have to put your order <strong>on-hold</strong> ';
            $customer_note .= 'because the amount received is different from the total amount of your order. Please, contact us for information regarding this order.';
            $admin_note     = 'Attention: New order has been placed on hold because of incorrect payment amount. Please, look into it. <br>';
            $admin_note    .= "Amount paid: $order_currency $charged_amount <br> Order amount: $order_currency $order_amount <br> Reference: $txn_ref";

            $order->add_order_note( $customer_note, 1 );
            $order->add_order_note( $admin_note );

            wc_add_notice( $customer_note, 'notice' );

          } else {

            $order->payment_complete( $order_id );
            $order->add_order_note( "Payment processed and approved successfully with reference: $txn_ref" );

          }

          WC()->cart->empty_cart();

        } else {

          $order->update_status( 'failed', 'Payment not successful' );

        }

        $redirect_url = $this->get_return_url( $order );
        echo json_encode( array( 'redirect_url' => $redirect_url ) );

      }

      die();

    }

   /**
     * Fetches transaction from credo enpoint
     *
     * @param $tx_ref string the transaction to fetch
     *
     * @return string
     */
    private function _fetchTransaction($txRef) {
      $url = $this->verify_base_url . '/transactions' . '/' . $txRef . '/verify';
      $args = array(
        'headers' => array(
          'Authorization' => $this->secret_key,
          'Accept' => 'application/json'
        ),
        'sslverify' => false,
        'timeout' => 30
      );

      $response = wp_remote_get( $url, $args );
      $body = wp_remote_retrieve_body( $response );
      $result = wp_remote_retrieve_response_code( $response );
      $res = json_decode($body);

      if( $result === 200 ){
            return $res;
      } else {
        if ($this->retries > 0) {
          $this->retries--;
          return $this->_fetchTransaction( $txRef );
        }
      }

      return $result;

    }

    /**
     * Checks if payment is successful
     *
     * @param $data object the transaction object to do the check on
     *
     * @return boolean
     */
    private function _is_successful($data) {
      return $data->paymentStatus->name === 'Successful' || $data->approvalStatus->name === 'Accepted';
    }

  }
?>
