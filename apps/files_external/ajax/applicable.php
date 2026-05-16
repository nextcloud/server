<?php

use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
\OC_JSON::checkAppEnabled('files_external');
\OC_JSON::callCheck();

$currentUser = \OC::$server->getUserSession()->getUser();
if ($currentUser === null) {
	\OC_JSON::error(['message' => 'Not logged in']);
	exit();
}
$groupManager = \OC::$server->getGroupManager();
$authorizedGroupMapper = \OC::$server->get(\OC\Settings\AuthorizedGroupMapper::class);
$isAdmin = $groupManager->isAdmin($currentUser->getUID());
$isDelegated = in_array(\OCA\Files_External\Settings\Admin::class, $authorizedGroupMapper->findAllClassesForUser($currentUser), true);
if (!$isAdmin && !$isDelegated) {
	\OC_JSON::error(['message' => 'Not authorized']);
	exit();
}

$pattern = '';
$limit = null;
$offset = null;
if (isset($_GET['pattern'])) {
	$pattern = (string)$_GET['pattern'];
}
if (isset($_GET['limit'])) {
	$limit = (int)$_GET['limit'];
}
if (isset($_GET['offset'])) {
	$offset = (int)$_GET['offset'];
}

$groups = [];
foreach (Server::get(IGroupManager::class)->search($pattern, $limit, $offset) as $group) {
	$groups[$group->getGID()] = $group->getDisplayName();
}

$users = [];
foreach (Server::get(IUserManager::class)->searchDisplayName($pattern, $limit, $offset) as $user) {
	$users[$user->getUID()] = $user->getDisplayName();
}

$results = ['groups' => $groups, 'users' => $users];

\OC_JSON::success($results);
