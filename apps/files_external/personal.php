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

OCP\Util::addScript('files_external', 'settings');
OCP\Util::addStyle('files_external', 'settings');
$backends = OC_Mount_Config::getPersonalBackends();

$tmpl = new OCP\Template('files_external', 'settings');
$tmpl->assign('isAdminPage', false);
$tmpl->assign('mounts', OC_Mount_Config::getPersonalMountPoints());
$tmpl->assign('dependencies', OC_Mount_Config::checkDependencies());
$tmpl->assign('backends', $backends);
return $tmpl->fetchPage();
