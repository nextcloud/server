<?php

require_once('../../../lib/base.php');

OC_JSON::checkAppEnabled('files_sharing');
OC_JSON::checkAdminUser();
error_log($_POST['resharing']);
if ($_POST['resharing'] == true) {
	error_log("enabling");
	OC_Appconfig::setValue('files_sharing', 'resharing', 'yes');
} else {
	error_log("disabling");
	OC_Appconfig::setValue('files_sharing', 'resharing', 'no');
}

?>
