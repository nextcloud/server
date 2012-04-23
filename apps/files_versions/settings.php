<?php

OC_Util::checkAdminUser();

OC_Util::addScript( 'files_versions', 'versions' );

$tmpl = new OC_Template( 'files_versions', 'settings');

return $tmpl->fetchPage();
?>
