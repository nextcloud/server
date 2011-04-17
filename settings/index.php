<?php

require_once('../lib/base.php');
require( 'template.php' );
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}


$tmpl = new OC_TEMPLATE( "settings", "index", "admin");
$used=OC_FILESYSTEM::filesize('/');
$free=disk_free_space(OC_CONFIG::getValue('datadirectory'));
$total=$free+$used;
$relative=round(($used/$total)*100);
$tmpl->assign('usage',OC_HELPER::humanFileSize($used));
$tmpl->assign('total_space',OC_HELPER::humanFileSize($total));
$tmpl->assign('usage_relative',$relative);
$tmpl->printPage();

?>
