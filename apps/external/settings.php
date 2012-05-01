<?php

OCP\User::checkAdminUser();

OCP\Util::addscript( "external", "admin" );

$tmpl = new OC_Template( 'external', 'settings');

return $tmpl->fetchPage();
?>
