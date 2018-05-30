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

function html_show_success_form($login, $user_entry) {
  echo '<h2> Success! </h2>';
  echo 'The following is your login.<p style="font-weight:bold; font-style:normal"> ' . esc_attr($login) . '</p><br/>';
  $expire = date('d-m-Y h:m:s', $user_entry->expire);
  echo 'The following is expiration date of your VPN access.<p style="font-weight:bold; font-style:normal"> ' . esc_attr($expire) . '</p><br/>'; 
}

function html_show_register_form($login) {
  echo 'The following is your login.<p style="font-weight:bold; font-style:normal"> ' . esc_attr($login) . '</p><br/>';

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
  $user_entry = get_entry_of_user($login);
  $invite_code = $user_entry->key;

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
  $return_code = wp_remote_retrieve_response_code($response);
  $result = $return_code == '201';
  if ($result) {
    $result = update_expire_date_in_leap_db($login, $user_entry->expire) != 'ERR_NO_CODE';
  }
  // TODO: graceful error handling

  // Debug info
  if ($result) {
    // Leap user created. Update expire data.
    
    echo '<script language="javascript">';
    echo 'alert("Success!")';
    echo '</script>';
    html_show_success_form($login, $user_entry);
    //print_r("REGISTRATION COMPLETE");
  } else {
    echo '<script language="javascript">';
    echo 'alert("Fail!")';
    echo '</script>';
    print_r("REGISTRATION FAILED");
    print_r('\n');
    print_r($response);
  }

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

  $order = wc_get_order( $order_no );
  $username = $order->get_billing_first_name();
  $new_or_exist = $order->get_billing_phone();

  // Hacking attempt?
  if ($username != $order->get_billing_first_name() ||
      ($new_or_exist != 'option_new' && $new_or_exist !='option_exst')) {
    wc_add_notice( __( 'Please, contact support' ), 'error' );
    return ob_get_clean();
  }

  if ($new_or_exist == 'option_new') {
    // Show user form for entering desired password.
    // Password is not POSTed, instead SRP secure data is POSTed.
    // On POST make request to LEAP for new user registration.
    if (isset( $_POST["srp-submitted"] )) {
      register_user($user);
    } else {
      html_show_register_form($user);
    }
  } else {
    $user_entry = get_entry_of_user($user);
    // Show new expire date
    html_show_success_form($user, $user_entry);
  }

  return ob_get_clean();
}

add_shortcode( 'bitmask_register_form', 'bitmask_register_shortcode' );

// Assign invite code to a user once he payed for it.
add_action( 'woocommerce_payment_complete', 'payment_complete' );

function payment_complete($order_id) {
  // Either creating new account or updating the existing one.
  $order = wc_get_order( $order_id );
  $new_or_exist = $order->get_billing_phone();
  if ($new_or_exist == 'option_new') {
    add_new_account_and_key($order_id); 
  } else { // option_exst
    update_expire_date_of_existing_account($order_id);
  }
}

function update_expire_date_of_existing_account($order_id) {
  $order = wc_get_order( $order_id );
  $username = $order->get_billing_first_name();
  $item = array_shift($order->get_items());
  $months = $item->get_product()->get_attribute('duration');

  $user_data = get_entry_of_user($username);
  $current_expire_date = $user_data->expire;
  $new_expire_date = strtotime('now');
  if (time() - $current_expire_date > 0) {
    // update starting from now
    $new_expire_date = strtotime('+' . $months .' months');
  } else {
    $new_expire_date =
      strtotime($current_expire_date . '+' . $months .' months');
  }

  $success = assign_user_data($username, $user_data, $new_expire_date);

  $success = $success &&
             (update_expire_date_in_leap_db($username, $new_expire_date) != 'ERR_NO_CODE');

  if (!$success) {
    trigger_error('Fatal error during updating account
                   Please, contact support with the following order id' . $order_id);
  }
}

// Note: two buyes at the same time may raise conflict on assigning invite code.
// 5 attempts are allowed to resolve such conflict.
function add_new_account_and_key($order_id) {  
  // Retrieve order data
  $order = wc_get_order( $order_id );
  $username = $order->get_billing_first_name();
  $item = array_shift($order->get_items());
  $months = $item->get_product()->get_attribute('duration');
  $expire = strtotime('+' . $months .' months');

  $entry = get_entry_of_user('na');
  $success = assign_user_data($username, $entry, $expire);
  $num_attempts = 1;
  while (!$success && $num_attempts < 5) {
    $entry = get_entry_of_user('na');
    $success = assign_user_data($username, $entry, $expire);
    $num_attempts++;
  }
  // Pathological situation, CouchDB instance is down?
  if (!$success) {
    trigger_error('Fatal error during registration
                   Please, contact support with the following order id' . $order_id);
  }
}

function get_entry_of_user($username) {
  // Data for CouchDB find single invite code request
  $body_arr = array(
      'selector' => array( 'user' => $username ),
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
  $result = wp_remote_retrieve_response_code($response);
  if ($result == '200') {
    $docs = json_decode($response['body'])->docs;
    // Should be exactly one element.
    if (count($docs) == '1') {
      $entry = array_shift($docs);
      return $entry;
    }
  }
  // Something pathological has happened.
  return 'ERR_NO_CODE';
}

function assign_user_data($username, $entry, $expire) {
  if ($entry == 'ERR_NO_CODE')
    return false;

  $key = $entry->key;
  $id = $entry->_id;
  $rev = $entry->_rev;

  // Data for CouchDB document update request
  $body_arr = array(
      'user' => $username,
      'expire' => $expire,
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
  $result = wp_remote_retrieve_response_code($response);
  return $result == '201';
}

function update_expire_date_in_leap_db($username, $expire) {

  $url = 'https://46.173.218.124:15984/users/_design/User/_view/by_login?key="'. $username .
         '"&reduce=false&include_docs=true';

  $COUCHDB_LOGIN = 'admin';
  $COUCHDB_PASSWORD = 'yvxFLDMFSUnMaBh4FEX3P22zJEu3NBEj';

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_SSLCERT, "/home/certs/ca.crt");
  curl_setopt($ch, CURLOPT_SSLKEY, "/home/certs/ca.key");
  curl_setopt($ch, CURLOPT_CAINFO, "/home/certs/ca.crt");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
  # auth
  curl_setopt($ch, CURLOPT_USERPWD, "$COUCHDB_LOGIN:$COUCHDB_PASSWORD");

  $data = curl_exec($ch);

  $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($result == '200') {
    $docs = json_decode($data)->rows;
    // Should be exactly one element.
    if (count($docs) == '1') {
      $entry = array_shift($docs);
      $entry = $entry->doc;
      $entry->expire = $expire;
      $url = 'https://46.173.218.124:15984/users/' . $entry->_id;

      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($entry));
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
 
      $data = curl_exec($ch);
      $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      return $result == '201' ? "true" : 'ERR_NO_CODE';
    }
  }

  curl_close($ch);
  // Something pathological has happened.
  return 'ERR_NO_CODE';
}
?>
