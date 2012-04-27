<?php

OC_JSON::checkAppEnabled('files_sharing');
OC_JSON::checkAdminUser();
if ($_POST['resharing'] == true) {
	OC_Appconfig::setValue('files_sharing', 'resharing', 'yes');
} else {
	OC_Appconfig::setValue('files_sharing', 'resharing', 'no');
}

?>
