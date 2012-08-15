<?php
if (!OCP\App::isEnabled("user_webfinger")) {
	return;
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/xrd+json");

/**
 * To include your app in the webfinger JSON, add a new script with file name
 * 'webfinger.php' to /apps/yourapp/appinfo/, which prints out the XML parts
 * to be included. That script can make use of the constants WF_USER (e. g.
 * "user"), WF_ID (user@host) and WF_BASEURL (e. g. https://host/owncloud).
 * An example could look like this:
 * 
 * {
 * 	"rel":"myProfile",
 * 	"type":"text/html",
 * 	"href":"<?php echo WF_BASEURL; ?>/apps/myApp/profile.php?user=<?php echo WF_USER; ?>"
 * }
 *
 * but can also use complex database queries to generate the webfinger result
 **/

$userName = '';
$hostName = '';
$request = strip_tags(urldecode($_GET['q']));
if($_GET['q']) {
	$reqParts = explode('@', $request);
	if(count($reqParts)==2) {
		$userName = $reqParts[0];
		$hostName = $reqParts[1];
	}
}
if(substr($userName, 0, 5) == 'acct:') {
	$userName = substr($userName, 5);
}
if($userName == "") {
	$id = "";
} else {
	$id = $userName . '@' . $hostName;
}
if(isset($_SERVER['HTTPS'])) {
	$baseAddress = 'https://';
} else {
	$baseAddress = 'http://';
}
$baseAddress .= $_SERVER['SERVER_NAME'].OC::$WEBROOT;
if(empty($id)) {
	header("HTTP/1.0 400 Bad Request");
}
define('WF_USER', $userName);
define('WF_ID', $id);
define('WF_BASEURL', $baseAddress);
echo "{\"links\":[";
$apps = OC_Appconfig::getApps();
foreach($apps as $app) {
	if(OCP\App::isEnabled($app)) {
		if(is_file(OC_App::getAppPath($app). '/appinfo/webfinger.php')) {
			require($app . '/appinfo/webfinger.php');
		}
	}
}
echo "]}";
