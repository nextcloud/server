<?php

require_once('../lib/base.php');
require( 'template.php' );
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}


$tmpl = new OC_TEMPLATE( "settings", "index", "admin" );
$tmpl->printPage();

?>
