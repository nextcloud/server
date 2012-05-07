<?php

require_once('lib_remoteStorage.php');
$tmpl = new OCP\Template( 'remoteStorage', 'settings');

return $tmpl->fetchPage();
?>
