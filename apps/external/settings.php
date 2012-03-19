<?php

OC_Util::checkAdminUser();

OC_Util::addScript( "external", "admin" );

$tmpl = new OC_Template( 'external', 'settings');

return $tmpl->fetchPage();
?>
