<?php

$tmpl = new OCP\Template( 'user_openid', 'settings');
$identity=OCP\Config::getUserValue(OCP\USER::getUser(),'user_openid','identity','');
$tmpl->assign('identity',htmlentities($identity));

OCP\Util::addscript('user_openid','settings');

return $tmpl->fetchPage();
?>