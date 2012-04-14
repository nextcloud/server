<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind1991@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once('../lib/base.php');
OC_Util::checkAdminUser();

// Load the files we need
OC_Util::addStyle( "settings", "settings" );
OC_Util::addScript( "settings", "apps" );
OC_App::setActiveNavigationEntry( "core_log" );

$entries=OC_Log_Owncloud::getEntries();

OC_Util::addScript('settings','log');
OC_Util::addStyle('settings','settings');

function compareEntries($a,$b){
	return $b->time - $a->time;
}
usort($entries, 'compareEntries');

$tmpl = new OC_Template( "settings", "log", "user" );
$tmpl->assign('entries',$entries);

$tmpl->printPage();
