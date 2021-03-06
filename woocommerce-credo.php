<?php
/*
Plugin Name: WooCommerce Credo Payment Gateway
Plugin URI: http://credocentral.com
Description: Credo payment gateway for Credo.
Version: 1.0.0
Author: Credo, Joshua Uzor
Author URI: http://twitter.com/joshuauzor
  Copyright: © 2021 Joshua Uzor.
  License: MIT License
*/


if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

define( 'CREDO_WC_PLUGIN_FILE', __FILE__ );
define( 'CREDO_WC_DIR_PATH', plugin_dir_path( CREDO_WC_PLUGIN_FILE ) );

add_action('plugins_loaded', 'credo_woocommerce_credo_init', 0);

function credo_woocommerce_credo_init() {

  if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

  require_once( CREDO_WC_DIR_PATH . 'includes/class.credo_wc_payment_gateway.php' );

  add_filter('woocommerce_payment_gateways', 'credo_woocommerce_add_credo_gateway' );

  /**
   * Add the Settings link to the plugin
   *
   * @param  Array $links Existing links on the plugin page
   *
   * @return Array          Existing links with our settings link added
   */
  function credo_plugin_action_links( $links ) {

    $credo_settings_url = esc_url( get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=credo' ) );
    array_unshift( $links, "<a title='Credo Settings Page' href='$credo_settings_url'>Settings</a>" );

    return $links;

  }

  add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'credo_plugin_action_links' );

  /**
   * Add the Gateway to WooCommerce
   *
   * @param  Array $methods Existing gateways in WooCommerce
   *
   * @return Array          Gateway list with our gateway added
   */
  function credo_woocommerce_add_credo_gateway($methods) {

    $methods[] = 'CREDO_WC_Payment_Gateway';
    return $methods;

  }
}
