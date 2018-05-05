function signup() {
  srp.session = new srp.Session();
  var data = srp.session.update();
  console.log(data);
  document.getElementById("srp-salt").value = data.password_salt;
  document.getElementById("srp-verifier").value = data.password_verifier;
}

function handshake() {
  srp.session = new srp.Session();
  var data = srp.session.handshake();
  console.log(data);
  document.getElementById("srp-A").value = data.A;
  document.getElementById("srp-A2").value = data.A;
  document.getElementById("srp-login2").value = data.login;
  document.getElementById("srp-login").value = data.login;

  jQuery.post( '/my-account/', jQuery('form#handshake-form').serialize(),
       function(data) {
         //console.log(data);
         var salt = jQuery(data).find('#srp-salt').val();
         var B = jQuery(data).find('#srp-B').val();
         var session_id = jQuery(data).find('#srp-session-id').val();
         console.log("salt: " + salt+" B: "+B + " ssid: " + session_id);
         srp.session.calculations(salt, B);
         auth(srp.session, session_id);
       }
  );
}

function auth(session, session_id) {
  srp.session = session;

  document.getElementById("srp-client-auth").value = srp.session.getM();
  document.getElementById("srp-session-id").value = session_id;

  jQuery.post( '/my-account/', jQuery('form#auth-form').serialize(),
       function(data) {
         //console.log(data);
         var token = jQuery(data).find('#srp-token').val();
         console.log("token: " + token);
         var cert = jQuery(data).find('#cert').text();
         var config = jQuery(data).find('#config').text();
         console.log(cert);
         console.log(config);
         //alert(cert);
         jQuery("#cert-dialog").text(cert);
         jQuery("#config-dialog").text(config);
       }
  );
}

