<?
require_once('../../../lib/base.php');
require_once(OC::$CLASSPATH['OC_Gallery_Album']);
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

function handleRename($oldname, $newname) {
  OC_Gallery_Album::rename($oldname, $newname, OC_User::getUser());
}

function handleRemove($name) {
  OC_Gallery_Album::remove(OC_User::getUser(), $name);
}

if ($_GET['operation']) {
  switch($_GET['operation']) {
	case "rename":
	  handleRename($_GET['oldname'], $_GET['newname']);
	  OC_JSON::success(array('newname' => $_GET['newname']));
	break;
	case "remove":
	  handleRemove($_GET['name']);
	  OC_JSON::success();
	  break;
    default:
     OC_JSON::error(array('cause' => "Unknown operation"));
  }
}
?>
