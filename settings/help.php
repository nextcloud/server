<?php
/**
 * Copyright (c) 2011, Frank Karlitschek karlitschek@kde.org
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../lib/base.php');
OC_Util::checkLoggedIn();


// Load the files we need
OC_Util::addStyle( "settings", "settings" );
OC_App::setActiveNavigationEntry( "help" );

$pagesize=7;
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
