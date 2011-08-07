<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$userDirectory = "/".OC_User::getUser()."/files";
$source = $userDirectory.$_GET['source'];
$users = OC_Share::getMySharedItem($source);
$source = dirname($source);
while ($source != "" && $source != "/" && $source != "." && $source != $userDirectory) {
	$parentUsers = array();
	$values = array_values(OC_Share::getMySharedItem($source));
	for ($i = 0; $i < count($values); $i++) {
		$parentUsers[basename($source)."-".$i] = $values[$i];
	}
	$users = array_merge($users, $parentUsers);
	$source = dirname($source);
}
echo json_encode($users);

?>