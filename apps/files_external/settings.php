<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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
*/

OC_Util::checkAdminUser();

OCP\Util::addScript('files_external', 'settings');
OCP\Util::addStyle('files_external', 'settings');
OCP\Util::addScript('files_external', '../3rdparty/select2/select2');
OCP\Util::addStyle('files_external', '../3rdparty/select2/select2');
$tmpl = new OCP\Template('files_external', 'settings');
$tmpl->assign('isAdminPage', true);
$tmpl->assign('mounts', OC_Mount_Config::getSystemMountPoints());
$tmpl->assign('backends', OC_Mount_Config::getBackends());
$tmpl->assign('userDisplayNames', OC_User::getDisplayNames());
$tmpl->assign('dependencies', OC_Mount_Config::checkDependencies());
$tmpl->assign('allowUserMounting', OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes'));
return $tmpl->fetchPage();
