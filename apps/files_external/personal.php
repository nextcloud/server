<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use \OCA\Files_External\Service\BackendService;

// we must use the same container
$appContainer = \OC_Mount_Config::$app->getContainer();
$backendService = $appContainer->query('OCA\Files_External\Service\BackendService');
$userStoragesService = $appContainer->query('OCA\Files_External\Service\UserStoragesService');

$tmpl = new OCP\Template('files_external', 'settings');
$tmpl->assign('encryptionEnabled', \OC::$server->getEncryptionManager()->isEnabled());
$tmpl->assign('visibilityType', BackendService::VISIBILITY_PERSONAL);
$tmpl->assign('storages', $userStoragesService->getStorages());
$tmpl->assign('dependencies', OC_Mount_Config::dependencyMessage($backendService->getBackends()));
$tmpl->assign('backends', $backendService->getAvailableBackends());
$tmpl->assign('authMechanisms', $backendService->getAuthMechanisms());
$tmpl->assign('allowUserMounting', $backendService->isUserMountingAllowed());
return $tmpl->fetchPage();
