<?php
/**
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

$mounts = OC_Mount_Config::getSystemMountPoints();
$hasId = true;
foreach ($mounts as $mount) {
	if (!isset($mount['id'])) {
		// some mount points are missing ids
		$hasId = false;
		break;
	}
}

if (!$hasId) {
	$service = new \OCA\Files_external\Service\GlobalStoragesService();
	// this will trigger the new storage code which will automatically
	// generate storage config ids
	$service->getAllStorages();
	// re-read updated config
	$mounts = OC_Mount_Config::getSystemMountPoints();
	// TODO: use the new storage config format in the template
}

$tmpl = new OCP\Template('files_external', 'settings');
$tmpl->assign('encryptionEnabled', \OC::$server->getEncryptionManager()->isEnabled());
$tmpl->assign('isAdminPage', true);
$tmpl->assign('mounts', $mounts);
$tmpl->assign('backends', $backends);
$tmpl->assign('personal_backends', $personal_backends);
$tmpl->assign('dependencies', OC_Mount_Config::checkDependencies());
$tmpl->assign('allowUserMounting', OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes'));
return $tmpl->fetchPage();
