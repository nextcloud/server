<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2012 Frank Karlitschek frank@owncloud.org
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

OC_Util::checkAdminUser();
OC_App::loadApps();

// Load the files we need
OC_Util::addStyle( "settings", "settings" );
OC_App::setActiveNavigationEntry( "core_apps" );

function app_sort( $a, $b ) {

	if ($a['active'] != $b['active']) {

		return $b['active'] - $a['active'];

	}

	if ($a['internal'] != $b['internal']) {
		return $b['internal'] - $a['internal'];
	}

	return strcmp($a['name'], $b['name']);

}

$combinedApps = OC_App::listAllApps();
usort( $combinedApps, 'app_sort' );

$tmpl = new OC_Template( "settings", "apps", "user" );

$tmpl->assign('apps', $combinedApps);

$appid = (isset($_GET['appid'])?strip_tags($_GET['appid']):'');

$tmpl->assign('appid', $appid);

$tmpl->printPage();

