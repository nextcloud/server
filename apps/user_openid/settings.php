<?php

require_once('../../lib/base.php');
if( !OC_User::isLoggedIn()){
    header( "Location: ".OC_Helper::linkTo( "index.php" ));
    exit();
}

if(isset($_POST['input_identity'])){
	OC_Preferences::setValue(OC_User::getUser(),'user_openid','identity',$_POST['input_identity']);
}

OC_App::setActiveNavigationEntry( "user_openid_settings" );

$identity=OC_Preferences::getValue(OC_User::getUser(),'user_openid','identity','');

$tmpl = new OC_Template( "user_openid", "settings", "admin");
$tmpl->assign('identity',$identity);
$tmpl->assign('user',OC_User::getUser());

$tmpl->printPage();

?>
