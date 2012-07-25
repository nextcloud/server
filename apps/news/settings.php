<?php

//OCP\Util::addscript( "news", "admin" );

$tmpl = new OCP\Template( 'news', 'settings');

return $tmpl->fetchPage();

