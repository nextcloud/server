<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/xrd+xml");

/**
 * To include your app in the webfinger XML, add a new script with file name
 * 'webfinger.php' to /apps/yourapp/appinfo/, which prints out the XML parts
 * to be included. That script can make use of the constants WF_USER (e. g.
 * "user"), WF_ADDRESS ("user@host") and WF_ROOT ("https://host/owncloud").
 * An example could look like this:
 * 
 * <Link
 * 	rel="myProfile"
 * 	type="text/html"
 * 	href="<?php echo WF_ROOT; ?>/apps/myApp/profile.php?user=<?php echo WF_USER; ?>">
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

$id = $_GET['q'];
if($_GET['q']) {
	$bits = explode('@', $_GET['q']);
	$userName = $bits[0];
} else {
	$id = '';
	$userName = '';
}
if(substr($userName, 0, 5) == 'acct:') {
	$userName = substr($userName, 5);
}
if(isset($_SERVER['HTTPS'])) {
	$baseAddress = 'https://';
} else {
	$baseAddress = 'http://';
}
$baseAddress .= $_SERVER['SERVER_NAME'].OC::$WEBROOT;
define('WF_USER', $userName);
define('WF_ADDRESS', $id);
define('WF_ROOT', $baseAddress);
echo "<";
?>
?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0" xmlns:hm="http://host-meta.net/xrd/1.0">
	<hm:Host xmlns="http://host-meta.net/xrd/1.0"><?php echo $_SERVER['SERVER_NAME']; ?></hm:Host>
    <Subject>acct:<?php echo $userName . '@' . $_SERVER['SERVER_NAME'] ?></Subject>
<?php
$apps = OC_Appconfig::getApps();
foreach($apps as $app) {
	//echo "checking $app...\n";
	if(OC_App::isEnabled($app)) {
		//echo "is enabled\n";
		if(is_file(OC::$APPSROOT . '/apps/' . $app . '/appinfo/webfinger.php')) {
			//echo "has webfinger.php\n";
			require($app . '/appinfo/webfinger.php');
		}
	}
}
?>
</XRD>
