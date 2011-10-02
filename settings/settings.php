<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../lib/base.php');
OC_Util::checkLoggedIn();

OC_Util::addStyle( 'settings', 'settings' );
OC_App::setActiveNavigationEntry( 'settings' );

$tmpl = new OC_Template( 'settings', 'settings', 'user');
$forms=OC_App::getForms('settings');
$tmpl->assign('forms',array());
foreach($forms as $form){
	$tmpl->append('forms',$form);
}
$tmpl->printPage();
