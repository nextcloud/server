<?php

require_once('../../lib/base.php');
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}

if(isset($_POST['input_identity'])){
	OC_PREFERENCES::setValue(OC_USER::getUser(),'user_openid','identity',$_POST['input_identity']);
}

OC_APP::setActiveNavigationEntry( "user_openid_settings" );

$identity=OC_PREFERENCES::getValue(OC_USER::getUser(),'user_openid','identity','');

$tmpl = new OC_TEMPLATE( "user_openid", "settings", "admin");
$tmpl->assign('identity',$identity);
$tmpl->assign('user',OC_USER::getUser());

$tmpl->printPage();

?>
