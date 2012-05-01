<?php

OC_Util::checkAdminUser();

OCP\Util::addscript( 'files_versions', 'versions' );

$tmpl = new OC_Template( 'files_versions', 'settings');

return $tmpl->fetchPage();
?>
