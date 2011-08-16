<?php

require_once('../lib/base.php');
if( !OC_User::isLoggedIn()){
    header( "Location: ".OC_Helper::linkTo( "", "index.php" ));
    exit();
}


// Load the files we need
OC_Util::addStyle( "settings", "settings" );
OC_App::setActiveNavigationEntry( "help" );

$pagesize=5;
if(isset($_GET['page'])) $page=$_GET['page']; else $page=0;
$kbe=OC_OCSClient::getKnownledgebaseEntries($page,$pagesize);
$totalitems=$kbe['totalitems'];
unset($kbe['totalitems']);
$pagecount=ceil($totalitems/$pagesize);

$tmpl = new OC_Template( "settings", "help", "user" );
$tmpl->assign( "kbe", $kbe );
$tmpl->assign( "pagecount", $pagecount );
$tmpl->assign( "page", $page );
$tmpl->printPage();

?>
