<?php

require_once('lib_remoteStorage.php');
$tmpl = new OC_Template( 'remoteStorage', 'settings');

return $tmpl->fetchPage();
?>
