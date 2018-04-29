<?php
/*
Plugin Name: Leap Register
Plugin URI: ubgvpn.xyz
Description: Fuck Da Police
Version: 1.0
Author: UBG CREW
Author URI: ubgvpn.win
License: GPL2
*/

function html_form_code($login) {
  echo 'You specified the following login.<p style="font-weight:bold; font-style:normal"> ' .
       esc_attr($login) .
       '</p><br/>';

  // JS will look for session[login] and session[password]
  echo '<input type="hidden" id="srp_username" name="session[login]" value="' . esc_attr($login) . '" disabled/>';
  echo '<br/>';
  echo 'Please choose password (required) <br/>';
  echo '<input type="password" id="srp_password" name="session[password]"></input>';
  // TODO: add password confirmation

  // Below form has only button as visible element
  echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post" onsubmit="signup();">';
  echo '<input type="hidden" name="srp-salt" id="srp-salt"></input>';
  echo '<input type="hidden" name="srp-verifier" id="srp-verifier"></input>';
  echo '<p><input type="submit" name="srp-submitted" value="Register" onclick=""></p>';
  echo '</form>';
}

function register_user($login) {
  // POSTed secure data
  $srp_salt = sanitize_text_field( $_POST["srp-salt"] );
  $srp_verifier =  sanitize_text_field( $_POST["srp-verifier"] );
  // Retrieves code from local CouchDB instance
  $invite_code = get_user_invite_code($login);

  // Data for LEAP
  $body_arr = array(
      "user[login]" => $login,
      "user[password_salt]" => $srp_salt,
      "user[password_verifier]" => $srp_verifier,
      "user[invite_code]" => $invite_code
  );

  $args = array(
       'timeout'     => 45,
       'redirection' => 1,
       'blocking'    => true,
       'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
       'body'    =>  http_build_query($body_arr),
       'cookies' => array(),
       'method' => 'POST'
  );

  // TODO: Remove hardcoded URLs
  $url = 'https://ubgvpn.xyz/1/users.json';
  $response = wp_remote_post( $url, $args );
  $result = wp_remote_retrieve_response_code($response);
  // TODO: graceful error handling

  // Debug info
  if ($result == '201') {
    echo '<script language="javascript">';
    echo 'alert("Success!")';
    echo '</script>';
    print_r("REGISTRATION COMPLETE");
  } else {
    echo '<script language="javascript">';
    echo 'alert("Fail!")';
    echo '</script>';
    print_r("REGISTRATION FAILED");
  }

  print_r('\n');
  print_r($response);
  // TODO: print fancy success message
}

function bitmask_register_signup_srp() {
  wp_enqueue_script('jquery');
  wp_register_script('srp-lib-MD5', '/js/srp_js-master/lib/MD5.js');
  wp_enqueue_script('srp-lib-MD5');
  wp_register_script('srp-lib-SHA1', '/js/srp_js-master/lib/SHA1.js');
  wp_enqueue_script('srp-lib-SHA1');
  wp_register_script('srp-lib-SHA256', '/js/srp_js-master/lib/SHA256.js');
  wp_enqueue_script('srp-lib-SHA256');
  wp_register_script('srp-lib-aes', '/js/srp_js-master/lib/aes.js');
  wp_enqueue_script('srp-lib-aes');
  wp_register_script('srp-lib-cryptoHelpers', '/js/srp_js-master/lib/cryptoHelpers.js');
  wp_enqueue_script('srp-lib-cryptoHelpers');
  wp_register_script('srp-lib-jsbn', '/js/srp_js-master/lib/jsbn.js');
  wp_enqueue_script('srp-lib-jsbn');
  wp_register_script('srp-lib-jsbn2', '/js/srp_js-master/lib/jsbn2.js');
  wp_enqueue_script('srp-lib-jsbn2');
  wp_register_script('srp-lib-prng4', '/js/srp_js-master/lib/prng4.js');
  wp_enqueue_script('srp-lib-prng4');
  wp_register_script('srp-lib-rng', '/js/srp_js-master/lib/rng.js');
  wp_enqueue_script('srp-lib-rng');

  wp_register_script('srp-src-srp', '/js/srp_js-master/src/srp.js');
  wp_enqueue_script('srp-src-srp');
  wp_register_script('srp-src-srp_account', '/js/srp_js-master/src/srp_account.js');
  wp_enqueue_script('srp-src-srp_account');
  wp_register_script('srp-src-srp_calculate', '/js/srp_js-master/src/srp_calculate.js');
  wp_enqueue_script('srp-src-srp_calculate');
  wp_register_script('srp-src-srp_session', '/js/srp_js-master/src/srp_session.js');
  wp_enqueue_script('srp-src-srp_session');

  wp_register_script('srp-src-signup', '/js/srp-signup.js');
  wp_enqueue_script('srp-src-signup');
}

function bitmask_register_shortcode($atts = [], $content = null, $tag = '') {
  ob_start();

  bitmask_register_signup_srp();

  // normalize attribute keys, lowercase
  $atts = array_change_key_case((array)$atts, CASE_LOWER);
 
  // override default attributes with user attributes
  $wporg_atts = shortcode_atts([ 'order_no'=>'0'], $atts, $tag);
  $user = strtolower($content);
  $order_no = esc_html__($wporg_atts['order_no'], 'wporg');

  // Show user form for entering desired password.
  // Password is not POSTed, instead SRP secure data is POSTed.
  // On POST make request to LEAP for new user registration.
  // Unique login is the combined username+order number
  if (isset( $_POST["srp-submitted"] )) {
    register_user($user.$order_no);
  } else {
    html_form_code($user.$order_no);
  }

  return ob_get_clean();
}

add_shortcode( 'bitmask_register_form', 'bitmask_register_shortcode' );

// Assign invite code to a user once he payed for it.
add_action( 'woocommerce_payment_complete', 'payment_complete' );

// TODO: Handle possible race condition
// two buyes at the same time may raise conflict on assigning invite code.
function payment_complete($order_id) {  
  $entry = get_available_invite_code();
  assign_invite_code($order_id, $entry->key, $entry->_id, $entry->_rev);
}

function get_user_invite_code($user) {
  $body_arr = array( 'selector' => array( 'user' => $user ),
                     'limit' => 1
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
  // TODO: graceful error handling
  $docs = json_decode($response['body'])->docs;
  $entry = array_shift($docs);
  return $entry->key;
}

function get_available_invite_code() {
  // Data for CouchDB find single invite code request
  $body_arr = array(
      'selector' => array( 'user' => 'na' ),
      'limit' => 1
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
  // TODO: graceful error handling
  $docs = json_decode($response['body'])->docs;
  $entry = array_shift($docs);
  return $entry;
}

function assign_invite_code($order_id, $key, $id, $rev) {
  // Retrieve WooCommerce data by order_id
  $order = wc_get_order( $order_id );
  $username = $order->get_billing_first_name();
  $order_number = $order->get_order_number();
 
  // Data for CouchDB document update request
  $body_arr = array(
      'user' => $username.$order_number,
      'key'  => $key,
      '_rev' => $rev,
  );

  $args = array(
      'timeout'     => 10,
      'redirection' => 1,
      'blocking'    => true,
      'headers' => array('Content-Type' => 'application/json'),
      'body'    => json_encode($body_arr),
      'cookies' => array(),
      'method' => 'PUT'
  );

  $url = 'http://127.0.0.1:5984/invite_codes/'.$id;
  $response = wp_remote_post( $url, $args );
}

?>
