<?php

$tmpl = new OC_Template( 'user_openid', 'settings');
$identity=OC_Preferences::getValue(OCP\USER::getUser(),'user_openid','identity','');
$tmpl->assign('identity',$identity);

OCP\Util::addscript('user_openid','settings');

return $tmpl->fetchPage();
?>