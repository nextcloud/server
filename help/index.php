<?php

require_once('../lib/base.php');
require( 'template.php' );
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}

// Load the files we need
OC_UTIL::addStyle( "help", "help" );

OC_APP::setActiveNavigationEntry( "help" );

$kbe=OC_OCSCLIENT::getKnownledgebaseEntries();


$tmpl = new OC_TEMPLATE( "help", "index", "user" );
$tmpl->assign( "kbe", $kbe );
$tmpl->printPage();

?>
