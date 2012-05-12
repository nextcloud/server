<?php

OCP\User::checkAdminUser();

OCP\Util::addscript( 'files_versions', 'versions' );

$tmpl = new OCP\Template( 'files_versions', 'settings');

return $tmpl->fetchPage();
?>
