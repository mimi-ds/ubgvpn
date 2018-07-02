<?php
/*
Plugin Name: Leap Login
Plugin URI: ubgvpn.xyz
Description: Fuck Da Police
Version: 1.0
Author: UBG CREW
Author URI: ubgvpn.win
License: GPL2
*/

function bitmask_login_register_js() {
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

function bitmask_login_html_form_code() {
  // session[login] and session[password] will be picked up by JS.
  echo '<div style="width: 30%; padding-left: 30px;">';
  echo '<br/>';
  echo '<p>';
  echo '<b class="furore-font" >Your login</b> <br/>';
  echo '<input type="login" class="input-text" id="srp_username" name="session[login]"/>';
  echo '</p>';
  echo '<p>';
  echo '<b class="furore-font">Your password</b> <br/>';
  echo '<input type="password" class="input-text" id="srp_password" name="session[password]"></input>';
  echo '</p>';

  // Out of form button, it will start multi-stage auth process.
  echo '<p><input type="submit" name="srp-submitted" id="srp-submitted" value="Check" onclick="handshake()"></p>';
  echo '</div>';

  // Invisible form, will POST data for further handshakes with LEAP.
  echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post" id="handshake-form">';
  echo '<input type="hidden" name="srp-login" id="srp-login"></input>';
  echo '<input type="hidden" name="srp-A" id="srp-A"></input>';
  echo '<input type="hidden" name="srp-handshake" id="srp-handshake" value="Log"></input>';
  echo '</form>';

  // Invisible form, will POST data for further auth. with LEAP
  echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post" id="auth-form">';
  echo '<p>';
  echo '<input type="hidden" name="srp-login" id="srp-login2"></input>';
  echo '<input type="hidden" name="srp-client-auth" id="srp-client-auth"></input>';
  echo '<input type="hidden" name="srp-session-id" id="srp-session-id"></input>';
  echo '<input type="hidden" name="srp-A" id="srp-A2"></input>';
  echo '<input type="hidden" name="srp-auth" id="srp-auth" value="Log"></input>';
  echo '</form>';

  echo '<a id="download_conf" href="#" style="display:none;" download="asapvpn.ovpn">Download configuration file</a>';

  // Debug output
  echo '<table>';
  echo '<tr>';
  echo '<td>';
  echo '<textarea id="cert-dialog" cols="40" rows="50" disabled> </textarea>';
  echo '</td>';
  echo '<td>';
  echo '<textarea id="config-dialog" cols="40" rows="50" disabled> </textarea>';
  echo '</td>';
  echo '<td>';
  echo '<textarea id="cacert-dialog" cols="40" rows="50" disabled> </textarea>';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
}

// Stage 2, use POSTed data to make handshake request to LEAP
function bitmask_login2_html_form_code() {

  if ( isset( $_POST["srp-handshake"] ) &&  isset( $_POST["srp-A"] ) ) {
    $A = esc_attr( $_POST["srp-A"] );
    $login = esc_attr( $_POST["srp-login"] );
    $url = 'https://ubgvpn.xyz/1/sessions.json';
    $args = array(
        'timeout'     => 45,
        'redirection' => 5,
        'blocking'    => true,
        'headers' => null,
        'body'    => array( 'A' => $A, 'login' => $login ),
        'cookies' => array()
    );
    $response = wp_remote_post( $url, $args );
    $response_code = wp_remote_retrieve_response_code( $response );
    // TODO: Graceful error handling
    // Retrieve data.
    $salt = json_decode($response['body']) -> salt;
    $B =  json_decode($response['body']) -> B;
    $cookies = $response['cookies'];
    $session_id = "nocookie";
    foreach ($cookies as $cookie) {
      if ( $cookie->name == '_session_id' ) {
        $session_id = $cookie->value;
      }
    }

    // Debug output
    echo '<p>response' . $response_code . ' salt: ' . $salt .' B: ' . $B . '</p>';

    // This will be picked up by JS
    echo '<input type="hidden" name="srp-salt" id="srp-salt" value="' . $salt . '"></input>';
    echo '<input type="hidden" name="srp-B" id="srp-B" value="' . $B . '"></input>';
    echo '<input type="hidden" name="srp-session-id" id="srp-session-id" value="' . $session_id . '"></input>';
  }
}

function bitmask_login3_html_form_code() {
  if ( isset( $_POST["srp-client-auth"] ) &&  isset( $_POST["srp-A"] ) ) {
    $A = esc_attr( $_POST["srp-A"] );
    $login = esc_attr( $_POST["srp-login"] );
    $client_auth = esc_attr( $_POST["srp-client-auth"] );
    $session_id = esc_attr( $_POST["srp-session-id"] );
    // TODO: Remove hardcoded URL
    $url = 'https://ubgvpn.xyz/1/sessions/' . $login . '.json';
    $cookie_string = '_session_id=' . $session_id;
    // Combine request with handshake data for final auth.
    $args = array(  
        'timeout'     => 45,
        'redirection' => 1,
        'blocking'    => true,
        'headers' => array( 'Cookie' => $cookie_string ),
        'body'    => array( 'A' => $A, 'client_auth' => $client_auth ),
        'cookies' => array(),
        'method' => 'PUT'
    );
    $response = wp_remote_post( $url, $args );
    $response_code = wp_remote_retrieve_response_code( $response );
    // TODO: Graceful error handling
    $id = json_decode($response['body']) -> id;
    $M2 =  json_decode($response['body']) -> M2;
    $token =  json_decode($response['body']) -> token;
    // Debug output.
    echo '<p>response' . $response_code . ' id: ' . $id .' token: ' . $token . '</p>';

    // This will be picked up by JS
    echo '<input type="hidden" name="srp-token" id="srp-token" value="' . $token . '"></input>';

    // TODO: Remove hardcoded URLs
    echo '<div id="cert">';
    echo retrieve_leap_data('https://ubgvpn.xyz/1/cert', $cookie_string, $token);
    echo '</div>';
    echo '<div id="config">';
    echo retrieve_leap_data('https://ubgvpn.xyz/1/configs/eip-service.json', $cookie_string, $token);
    echo '</div>';
    echo '<div id="cacert">';
    echo retrieve_leap_data('https://ubgvpn.xyz/ca.crt', $cookie_string, $token);
    echo '</div>';
  }
}

function retrieve_leap_data($url, $cookie_string, $token) {
  // Retrieve certificate with authenticated request.
  // Using handshake cookie and auth. token.
  $headers = array(
      'Cookie' => $cookie_string,
      'Authorization' => 'Token token=' . $token
  );

  $args_cert = array( 
      'timeout'     => 30,
      'redirection' => 1,
      'blocking'    => true,
      'headers' => $headers,
      'body'    => array(),
      'cookies' => array(),
      'method' => 'GET'
  );

  $response = wp_remote_post($url, $args_cert);
  // TODO: Graceful error handling
  return $response['body'];
}

function bitmask_login_shortcode($atts = [], $content = null, $tag = '') {
  ob_start();
 
  bitmask_login_register_js();

  // There are 3 stages of this.
  // 1. User input of login password.
  //    On client side SRP secure data is calculated and then
  //    posted back to this page.
  // 2. Handshake with LEAP.
  //    Server receives POSTed data, does handshake with LEAP
  //    on behalf of the user and forms a page with LEAP response.
  // 3. Authentication.
  //    Client receives handhshake data and POST again with
  //    after calculating necessary response. Server will receive
  //    token and now able to perform authenticated requests.
  if ( isset( $_POST["srp-auth"] ) ) {
    // Final authenticated stage.
    bitmask_login3_html_form_code();
  } else {
    if ( isset( $_POST["srp-handshake"] ) ) {
      // Handshake stage
      bitmask_login2_html_form_code();
    } else {
      // User login/password input stage (first).
      bitmask_login_html_form_code();
    }
  }

  return ob_get_clean();
}


add_shortcode( 'bitmask_log_in_form', 'bitmask_login_shortcode' );

?>


