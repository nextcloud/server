<?php

require_once('../lib/base.php');
if( !OC_User::isLoggedIn()){
    header( "Location: ".OC_Helper::linkTo( "index.php" ));
    exit();
}

//hardcode for testing
$pagecount=8;
$page=2;


// Load the files we need
OC_Util::addStyle( "help", "help" );
OC_App::setActiveNavigationEntry( "help" );

$kbe=OC_OCSClient::getKnownledgebaseEntries();


$tmpl = new OC_Template( "help", "index", "admin" );
$tmpl->assign( "kbe", $kbe );
$tmpl->assign( "pagecount", $pagecount );
$tmpl->assign( "page", $page );
$tmpl->printPage();

?>
