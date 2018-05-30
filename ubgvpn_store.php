<?php
/*
Plugin Name: UBG Store
Plugin URI: ubgvpn.xyz
Description: Fuck Da Police
Version: 1.0
Author: UBG CREW
Author URI: ubgvpn.win
License: GPL2
*/


add_action('woocommerce_add_to_cart', 'override_add_to_cart');

// Allow single item in the cart
function override_add_to_cart(
  $cart_item_key, $product_id, $quantity,
  $variation_id, $variation, $cart_item_data) {
  
  WC()->cart->set_quantity($cart_item_key, 1, true);

  $items = WC()->cart->get_cart();

  if (sizeof($items) > 1) {
     foreach($items as $item => $values) { 
       if ($item != $cart_item_key) {
         WC()->cart->remove_cart_item($item);
       }
     } 
  }
}


function my_checkout_msg() {
  // Don't show anything, so practically eliminate all text
  //echo '<p>Check!</p>';
}

function custom_override_checkout_fields( $fields ) {
/*  $fields['billing']['billing_last_name']['required'] = false;
  $fields['billing']['billing_last_name']['class'] = array('hidden');
  $fields['billing']['billing_company']['class'] = array('hidden');
  $fields['billing']['billing_address_1']['class'] = array('hidden');
  $fields['billing']['billing_address_1']['required'] = false;
  $fields['billing']['billing_address_2']['class'] = array('hidden');
  $fields['billing']['billing_city']['class'] = array('hidden');
  $fields['billing']['billing_city']['required'] = false;
 $fields['billing']['billing_state']['class'] = array('hidden');
 $fields['billing']['billing_country']['required'] = false;
 $fields['billing']['billing_country']['class'] = array('hidden');
 $fields['billing']['billing_postcode']['required'] = false;
 $fields['billing']['billing_postcode']['class'] = array('hidden');*/
//unset($fields['billing']['billing_last_name']);
$fields['billing']['billing_last_name']['required'] = false;
  $fields['billing']['billing_last_name']['class'] = array('hidden');
unset($fields['billing']['billing_company']);
unset($fields['billing']['billing_address_1']);
unset($fields['billing']['billing_address_2']);
unset($fields['billing']['billing_city']);
unset($fields['billing']['billing_state']);
unset($fields['billing']['billing_country']);
unset($fields['billing']['billing_postcode']);


  $fields['billing']['billing_first_name']['label'] = 'Please, specify username';
  // Setting to false to handle it here during custom validation.
  $fields['billing']['billing_first_name']['required'] = false;

  $fields['billing']['billing_phone'] = array(
    'label'     => 'Create new account or renew existing one?',
    'required'  => false,
    'type'      => 'select',
    'clear'     => 'true'
  );

  $fields['billing']['billing_phone']['options'] = array(
       'option_new' => 'New account',
       'option_exst' => 'Renew existing account'
  );

  $fields['billing']['billing_email']['class'] = array('form-row-first');

  return $fields;
}

function custom_checkout_fields_validation() {
  $was_error = false;
  if ( ! $_POST['billing_first_name'] ) {
    wc_add_notice( __( 'Please, specify username' ), 'error' );
    $was_error = true;
  }

  if ( ! $_POST['billing_phone'] ) {
    wc_add_notice( __( 'Please, choose whether account is new or the existing one' ), 'error' );
    $was_error = true;
  }

  if ($was_error) return;

  $username = sanitize_text_field($_POST['billing_first_name']);
  $is_existing = sanitize_text_field($_POST['billing_phone']);

  // verify username is valid
  if(!preg_match('/^[a-z0-9\.\_\-]+$/', $username)) {
    $was_error = true;
    wc_add_notice( __( 'Please, use only lowercase letters, digits, . - and _ in the username' ), 'error' );
  } 

  if ($was_error) return;

  $user_exist = check_user_exists($username);

  if ($is_existing == 'option_new') {
    // verify account doesn't exist
    if ($user_exist == 'YES') {
      wc_add_notice( __( 'This username is already taken' ), 'error' );
    }
  } else {
    if ($is_existing == 'option_exst') {
      // verify account exist
      if ($user_exist == 'NO') {
        wc_add_notice( __( 'This user doesn\'t exist' ), 'error' );
      }
    } else {
      wc_add_notice( __( 'Unexpected account option' . $is_existing), 'error' );
      $was_error = true;
    }
  }

 if ($user_exist == 'ERR_NO_CODE') {
    wc_add_notice( __( 'Registration is unavaliable at the moment. Please, contact support' ), 'error' );
    $was_error = true;
  }

  if ($was_error) return;

}

function check_user_exists($username) {
  // Data for CouchDB find username request
  $body_arr = array(
      'selector' => array( 'user' => $username )
  );

  $args = array(
      'timeout'     => 10,
      'redirection' => 1,
      'blocking'    => true,
      'headers' => array('Content-Type' => 'application/json'),
      'body'    => json_encode($body_arr),
      'cookies' => array(),
      'method' => 'POST'
  );

  $url = 'http://127.0.0.1:5984/invite_codes/_find';
  $response = wp_remote_post( $url, $args );
  $result = wp_remote_retrieve_response_code($response);
  if ($result == '200') {
    $docs = json_decode($response['body'])->docs;
    // Should be exactly zero or one element.
    if (count($docs) == '1') {
      return 'YES';
    } else if (count($docs) == '0') {
      return 'NO';
    }
  }

  // Something pathological has happened.
  return 'ERR_NO_CODE';

}

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

add_action('woocommerce_checkout_process', 'custom_checkout_fields_validation');

add_action('woocommerce_before_checkout_form', 'my_checkout_msg');
add_action('woocommerce_checkout_before_customer_details', 'my_checkout_msg');
add_action('woocommerce_before_checkout_billing_form', 'my_checkout_msg');
add_action('woocommerce_before_checkout_registration_form', 'my_checkout_msg');
add_action('woocommerce_before_checkout_shipping_form', 'my_checkout_msg');
add_action('woocommerce_checkout_before_terms_and_conditions', 'my_checkout_msg');
add_action('woocommerce_checkout_billing', 'my_checkout_msg');
add_action('woocommerce_checkout_shipping', 'my_checkout_msg');
add_action('woocommerce_checkout_after_customer_details', 'my_checkout_msg');
add_action('woocommerce_checkout_before_order_review', 'my_checkout_msg');
add_action('woocommerce_checkout_order_review', 'my_checkout_msg');
add_action('woocommerce_checkout_after_order_review', 'my_checkout_msg');
add_action('woocommerce_after_checkout_form', 'my_checkout_msg');

// Avada adjustment of viewport
function custom_viewport_meta($viewport) {
  $viewport = '<meta name="viewport" content="width=device-width, shrink-to-fit=yes" />';
  return $viewport;
}

add_filter('avada_viewport_meta', 'custom_viewport_meta');

?>
