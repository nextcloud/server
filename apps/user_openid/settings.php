<?php

$tmpl = new OC_Template( 'user_openid', 'settings');
$identity=OCP\Config::getUserValue(OCP\USER::getUser(),'user_openid','identity','');
$tmpl->assign('identity',$identity);

OCP\Util::addscript('user_openid','settings');

return $tmpl->fetchPage();
?>