<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../lib/base.php');
OC_Util::checkAdminUser();

OC_Util::addStyle( "settings", "settings" );
OC_Util::addScript( "settings", "admin" );
OC_App::setActiveNavigationEntry( "admin" );

$tmpl = new OC_Template( 'settings', 'admin', 'user');
$forms=OC_App::getForms('admin');
$tmpl->assign('loglevel',OC_Config::getValue( "loglevel", 2 ));
$tmpl->assign('forms',array());
foreach($forms as $form){
	$tmpl->append('forms',$form);
}
$tmpl->printPage();
