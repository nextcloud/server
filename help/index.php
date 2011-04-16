<?php

require_once('../lib/base.php');
require( 'template.php' );
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}

OC_APP::setActiveNavigationEntry( "help" );

$kbe=OC_OCSCLIENT::getKnownledgebaseEntries();


$tmpl = new OC_TEMPLATE( "help", "index", "user" );
$tmpl->assign( "kbe", $kbe );
$tmpl->printPage();

?>
