<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

OC_Util::checkLoggedIn();

// Load the files we need
OC_Util::addStyle( "settings", "settings" );
\OC::$server->getNavigationManager()->setActiveEntry('help');


if(isset($_GET['mode']) and $_GET['mode'] === 'admin') {
	$url=\OCP\Util::linkToAbsolute( 'core', 'doc/admin/index.html' );
	$style1='';
	$style2=' active';
}else{
	$url=\OCP\Util::linkToAbsolute( 'core', 'doc/user/index.html' );
	$style1=' active';
	$style2='';
}

$url1=\OC::$server->getURLGenerator()->linkToRoute('settings_help').'?mode=user';
$url2=\OC::$server->getURLGenerator()->linkToRoute('settings_help').'?mode=admin';

$tmpl = new OC_Template( "settings", "help", "user" );
$tmpl->assign( "admin", OC_User::isAdminUser(OC_User::getUser()));
$tmpl->assign( "url", $url );
$tmpl->assign( "url1", $url1 );
$tmpl->assign( "url2", $url2 );
$tmpl->assign( "style1", $style1 );
$tmpl->assign( "style2", $style2 );
$tmpl->printPage();
