<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/xrd+xml");

/**
 * To include your app in the webfinger XML, add a new script with file name
 * 'webfinger.php' to /apps/yourapp/appinfo/, which prints out the XML parts
 * to be included. That script can make use of the constants WF_USER (e. g.
 * "user"), WF_ID (user@host) and WF_BASEURL (e. g. https://host/owncloud).
 * An example could look like this:
 * 
 * <Link
 * 	rel="myProfile"
 * 	type="text/html"
 * 	href="<?php echo WF_BASEURL; ?>/apps/myApp/profile.php?user=<?php echo WF_USER; ?>">
 * </Link>
 *
 '* but can also use complex database queries to generate the webfinger result
 **/
// calculate the documentroot
// modified version of the one in lib/base.php that takes the .well-known symlink into account
/*$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
$SERVERROOT=str_replace("\\",'/',dirname(dirname(dirname(dirname(__FILE__)))));
$SUBURI=substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen($SERVERROOT));
$WEBROOT=substr($SUBURI,0,-34);
*/

require_once('../../lib/base.php');
$request = urldecode($_GET['q']);
if($_GET['q']) {
	$reqParts = explode('@', $request);
	$userName = $reqParts[0];
	$hostName = $reqParts[1];
} else {
	$userName = '';
	$hostName = '';
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
define('WF_USER', $userName);
define('WF_ID', $id);
define('WF_BASEURL', $baseAddress);
echo "<";
?>
?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0" xmlns:hm="http://host-meta.net/xrd/1.0">
    <hm:Host xmlns="http://host-meta.net/xrd/1.0"><?php echo $_SERVER['SERVER_NAME']; ?></hm:Host>
    <Subject>acct:<?php echo $id ?></Subject>
<?php
$apps = OC_Appconfig::getApps();
foreach($apps as $app) {
	if(OC_App::isEnabled($app)) {
		if(is_file(OC::$APPSROOT . '/apps/' . $app . '/appinfo/webfinger.php')) {
			require($app . '/appinfo/webfinger.php');
		}
	}
}
?>
</XRD>
