<?php

OCP\JSON::checkAppEnabled('files_external');

$view = \OCP\Files::getStorage("files_external");
$from = $_FILES['rootcert_import']['tmp_name'];
$to = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath("").$_FILES['rootcert_import']['name'];
move_uploaded_file($from, $to);

header("Location: settings/personal.php");
exit;
?>