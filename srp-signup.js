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
         var cacert = jQuery(data).find('#cacert').text();
         console.log(cert);
         console.log(config);
         //alert(cert);
         jQuery("#cert-dialog").text(cert);
         jQuery("#config-dialog").text(config);
         jQuery("#cacert-dialog").text(cacert);
         var key = cert.substring(0, cert.indexOf("-----BEGIN CERTIFICATE-----"));
         var certificate = cert.substring(key.length);
         gen_config_for_download(config, cacert, certificate, key);
       }
  );
}

// expects LEAP output as arg
function gen_config_for_download(config, ca, cert, key) {
  var config_var = JSON.parse(config);

  var conf_file = "client\ndev tun\nremote-cert-tls server\nremote-random\nnobind\nscript-security 2\nverb 3";

  var auth = config_var["openvpn_configuration"]["auth"];
  var cipher = config_var["openvpn_configuration"]["cipher"];
  var tlscipher = config_var["openvpn_configuration"]["tls-cipher"];
  var gateway = config_var["gateways"][0]["ip_address"];
  var port = config_var["gateways"][0]["capabilities"]["ports"][0];

  conf_file += "\nauth " + auth + "\ncipher " + cipher + "\ntls-cipher " + tlscipher;
  conf_file += "\n\nremote " + gateway + " "+ port +" tcp";

  conf_file +="\n\n<ca>\n"+ca+"</ca>\n<cert>\n"+cert+"\n</cert>\n<key>\n" + key + "\n</key>";

  console.log(conf_file);

  jQuery("#download_conf").attr("href", 'data:text/plain;charset=utf-8,' + encodeURIComponent(conf_file));
  jQuery("#download_conf").css("display", "block");

  // jQuery("#download_conf").append('<iframe width="0" height="0" frameborder="0" src="data:text/plain;charset=utf-8,' + encodeURIComponent(conf_file) +'"></iframe>'); 
}
