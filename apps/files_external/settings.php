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

\OC_Util::addVendorScript('select2/select2');
\OC_Util::addVendorStyle('select2/select2');

$backends = OC_Mount_Config::getBackends();
$personal_backends = array();
$enabled_backends = explode(',', OCP\Config::getAppValue('files_external', 'user_mounting_backends', ''));
foreach ($backends as $class => $backend)
{
	if ($class != '\OC\Files\Storage\Local')
	{
		$personal_backends[$class] = array(
			'backend'	=> $backend['backend'],
			'enabled'	=> in_array($class, $enabled_backends),
		);
	}
}

$tmpl = new OCP\Template('files_external', 'settings');
$tmpl->assign('isAdminPage', true);
$tmpl->assign('mounts', OC_Mount_Config::getSystemMountPoints());
$tmpl->assign('backends', $backends);
$tmpl->assign('personal_backends', $personal_backends);
$tmpl->assign('dependencies', OC_Mount_Config::checkDependencies());
$tmpl->assign('allowUserMounting', OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes'));
return $tmpl->fetchPage();
