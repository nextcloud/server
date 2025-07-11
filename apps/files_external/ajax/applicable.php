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

\OC_JSON::checkAdminUser();

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
