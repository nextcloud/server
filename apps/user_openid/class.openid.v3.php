<?php
/*
	FREE TO USE
        Under License: GPLv3
	Simple OpenID PHP Class
	
	Some modifications by Eddie Roosenmaallen, eddie@roosenmaallen.com
	
-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

This Class was written to make easy for you to integrate OpenID on your website. 
This is just a client, which checks for user's identity. This Class Requires CURL Module.
It should be easy to use some other HTTP Request Method, but remember, often OpenID servers
are using SSL.
We need to be able to perform SSL Verification on the background to check for valid signature.

HOW TO USE THIS CLASS:
  STEP 1)
	$openid = new SimpleOpenID;
	:: SET IDENTITY ::
		$openid->SetIdentity($_POST['openid_url']);
	:: SET RETURN URL ::
		$openid->SetApprovedURL('http://www.yoursite.com/return.php'); // Script which handles a response from OpenID Server
	:: SET TRUST ROOT ::
		$openid->SetTrustRoot('http://www.yoursite.com/');
	:: FETCH SERVER URL FROM IDENTITY PAGE ::  [Note: It is recomended to cache this (Session, Cookie, Database)]
		$openid->GetOpenIDServer(); // Returns false if server is not found
	:: REDIRECT USER TO OPEN ID SERVER FOR APPROVAL ::
	
	:: (OPTIONAL) SET OPENID SERVER ::
		$openid->SetOpenIDServer($server_url); // If you have cached previously this, you don't have to call GetOpenIDServer and set value this directly
		
	STEP 2)
	Once user gets returned we must validate signature
	:: VALIDATE REQUEST ::
		true|false = $openid->ValidateWithServer();
		
	ERRORS:
		array = $openid->GetError(); 	// Get latest Error code
	
	FIELDS:
		OpenID allowes you to retreive a profile. To set what fields you'd like to get use (accepts either string or array):
		$openid->SetRequiredFields(array('email','fullname','dob','gender','postcode','country','language','timezone'));
		 or
		$openid->SetOptionalFields('postcode');
		
IMPORTANT TIPS:
OPENID as is now, is not trust system. It is a great single-sign on method. If you want to 
store information about OpenID in your database for later use, make sure you handle url identities
properly.
  For example:
	https://steve.myopenid.com/
	https://steve.myopenid.com
	http://steve.myopenid.com/
	http://steve.myopenid.com
	... are representing one single user. Some OpenIDs can be in format openidserver.com/users/user/ - keep this in mind when storing identities

	To help you store an OpenID in your DB, you can use function:
		$openid_db_safe = $openid->OpenID_Standarize($upenid);
	This may not be comatible with current specs, but it works in current enviroment. Use this function to get openid
	in one format like steve.myopenid.com (without trailing slashes and http/https).
	Use output to insert Identity to database. Don't use this for validation - it may fail.

*/

class SimpleOpenID{
	var $openid_url_identity;
	var $URLs = array();
	var $error = array();
	var $fields = array(
		'required'	 => array(),
		'optional'	 => array(),
	);
	
	function SimpleOpenID(){
		if (!function_exists('curl_exec')) {
			die('Error: Class SimpleOpenID requires curl extension to work');
		}
	}
	function SetOpenIDServer($a){
		$this->URLs['openid_server'] = $a;
	}
	function SetTrustRoot($a){
		$this->URLs['trust_root'] = $a;
	}
	function SetCancelURL($a){
		$this->URLs['cancel'] = $a;
	}
	function SetApprovedURL($a){
		$this->URLs['approved'] = $a;
	}
	function SetRequiredFields($a){
		if (is_array($a)){
			$this->fields['required'] = $a;
		}else{
			$this->fields['required'][] = $a;
		}
	}
	function SetOptionalFields($a){
		if (is_array($a)){
			$this->fields['optional'] = $a;
		}else{
			$this->fields['optional'][] = $a;
		}
	}
	function SetIdentity($a){ 	// Set Identity URL
 			if ((stripos($a, 'http://') === false)
 			   && (stripos($a, 'https://') === false)){
		 		$a = 'http://'.$a;
		 	}
/*
			$u = parse_url(trim($a));
			if (!isset($u['path'])){
				$u['path'] = '/';
			}else if(substr($u['path'],-1,1) == '/'){
				$u['path'] = substr($u['path'], 0, strlen($u['path'])-1);
			}
			if (isset($u['query'])){ // If there is a query string, then use identity as is
				$identity = $a;
			}else{
				$identity = $u['scheme'] . '://' . $u['host'] . $u['path'];
			}
//*/
			$this->openid_url_identity = $a;
	}
	function GetIdentity(){ 	// Get Identity
		return $this->openid_url_identity;
	}
	function GetError(){
		$e = $this->error;
		return array('code'=>$e[0],'description'=>$e[1]);
	}

	function ErrorStore($code, $desc = null){
		$errs['OPENID_NOSERVERSFOUND'] = 'Cannot find OpenID Server TAG on Identity page.';
		if ($desc == null){
			$desc = $errs[$code];
		}
	   	$this->error = array($code,$desc);
	}

	function IsError(){
		if (count($this->error) > 0){
			return true;
		}else{
			return false;
		}
	}
	
	function splitResponse($response) {
		$r = array();
		$response = explode("\n", $response);
		foreach($response as $line) {
			$line = trim($line);
			if ($line != "") {
				list($key, $value) = explode(":", $line, 2);
				$r[trim($key)] = trim($value);
			}
		}
	 	return $r;
	}
	
	function OpenID_Standarize($openid_identity = null){
		if ($openid_identity === null)
			$openid_identity = $this->openid_url_identity;

		$u = parse_url(strtolower(trim($openid_identity)));
		
		if (!isset($u['path']) || ($u['path'] == '/')) {
			$u['path'] = '';
		}
		if(substr($u['path'],-1,1) == '/'){
			$u['path'] = substr($u['path'], 0, strlen($u['path'])-1);
		}
		if (isset($u['query'])){ // If there is a query string, then use identity as is
			return $u['host'] . $u['path'] . '?' . $u['query'];
		}else{
			return $u['host'] . $u['path'];
		}
	}
	
	function array2url($arr){ // converts associated array to URL Query String
		if (!is_array($arr)){
			return false;
		}
		$query = '';
		foreach($arr as $key => $value){
			$query .= $key . "=" . $value . "&";
		}
		return $query;
	}
	function FSOCK_Request($url, $method="GET", $params = ""){
		$fp = fsockopen("ssl://www.myopenid.com", 443, $errno, $errstr, 3); // Connection timeout is 3 seconds
		if (!$fp) {
			$this->ErrorStore('OPENID_SOCKETERROR', $errstr);
		   	return false;
		} else {
			$request = $method . " /server HTTP/1.0\r\n";
			$request .= "User-Agent: Simple OpenID PHP Class (http://www.phpclasses.org/simple_openid)\r\n";
			$request .= "Connection: close\r\n\r\n";
		   	fwrite($fp, $request);
		   	stream_set_timeout($fp, 4); // Connection response timeout is 4 seconds
		   	$res = fread($fp, 2000);
		   	$info = stream_get_meta_data($fp);
		   	fclose($fp);
		
		   	if ($info['timed_out']) {
		       $this->ErrorStore('OPENID_SOCKETTIMEOUT');
		   	} else {
		      	return $res;
		   	}
		}
	}
	function CURL_Request($url, $method="GET", $params = "") { // Remember, SSL MUST BE SUPPORTED
			if (is_array($params)) $params = $this->array2url($params);
			$curl = curl_init($url . ($method == "GET" && $params != "" ? "?" . $params : ""));
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
			curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
			if ($method == "POST") curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			
			if (curl_errno($curl) == 0){
				$response;
			}else{
				$this->ErrorStore('OPENID_CURL', curl_error($curl));
			}
			return $response;
	}
	
	 function HTML2OpenIDServer($content) {
		$get = array();
		
		// Get details of their OpenID server and (optional) delegate
		preg_match_all('/<link[^>]*rel=[\'"](openid2.provider )?openid.server[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*\/?>/i', $content, $matches1);
		preg_match_all('/<link[^>]*href=\'"([^\'"]+)[\'"][^>]*rel=[\'"](openid2.provider )?openid.server[\'"][^>]*\/?>/i', $content, $matches2);
		$servers = array_merge($matches1[2], $matches2[1]);
		
		preg_match_all('/<link[^>]*rel=[\'"]openid.delegate[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*\/?>/i', $content, $matches1);
		
		preg_match_all('/<link[^>]*href=[\'"]([^\'"]+)[\'"][^>]*rel=[\'"]openid.delegate[\'"][^>]*\/?>/i', $content, $matches2);
		
		$delegates = array_merge($matches1[1], $matches2[1]);
		
		$ret = array($servers, $delegates);
		return $ret;
	}
	
	function GetOpenIDServer(){
		$response = $this->CURL_Request($this->openid_url_identity);
		list($servers, $delegates) = $this->HTML2OpenIDServer($response);
		if (count($servers) == 0){
			$this->ErrorStore('OPENID_NOSERVERSFOUND');
			return false;
		}
		if (isset($delegates[0])
		  && ($delegates[0] != "")){
			$this->SetIdentity($delegates[0]);
		}
		$this->SetOpenIDServer($servers[0]);
		return $servers[0];
	}
	
	function GetRedirectURL(){
		$params = array();
		$params['openid.return_to'] = urlencode($this->URLs['approved']);
		$params['openid.mode'] = 'checkid_setup';
		$params['openid.identity'] = urlencode($this->openid_url_identity);
		$params['openid.trust_root'] = urlencode($this->URLs['trust_root']);
		
		if (isset($this->fields['required'])
		  && (count($this->fields['required']) > 0)) {
			$params['openid.sreg.required'] = implode(',',$this->fields['required']);
		}
		if (isset($this->fields['optional'])
		  && (count($this->fields['optional']) > 0)) {
			$params['openid.sreg.optional'] = implode(',',$this->fields['optional']);
		}
		return $this->URLs['openid_server'] . "?". $this->array2url($params);
	}
	
	function Redirect(){
		$redirect_to = $this->GetRedirectURL();
		if (headers_sent()){ // Use JavaScript to redirect if content has been previously sent (not recommended, but safe)
			echo '<script language="JavaScript" type="text/javascript">window.location=\'';
			echo $redirect_to;
			echo '\';</script>';
		}else{	// Default Header Redirect
			header('Location: ' . $redirect_to);
		}
	}
	
	function ValidateWithServer(){
		$params = array(
			'openid.assoc_handle' => urlencode($_GET['openid_assoc_handle']),
			'openid.signed' => urlencode($_GET['openid_signed']),
			'openid.sig' => urlencode($_GET['openid_sig'])
		);
		// Send only required parameters to confirm validity
		$arr_signed = explode(",",str_replace('sreg.','sreg_',$_GET['openid_signed']));
		for ($i=0; $i<count($arr_signed); $i++){
			$s = str_replace('sreg_','sreg.', $arr_signed[$i]);
			$c = $_GET['openid_' . $arr_signed[$i]];
			// if ($c != ""){
				$params['openid.' . $s] = urlencode($c);
			// }
		}
		$params['openid.mode'] = "check_authentication";

		$openid_server = $this->GetOpenIDServer();
		if ($openid_server == false){
			return false;
		}
		$response = $this->CURL_Request($openid_server,'POST',$params);
		$data = $this->splitResponse($response);

		if ($data['is_valid'] == "true") {
			return true;
		}else{
			return false;
		}
	}
}

?>