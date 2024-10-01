<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;
use OCP\User\Events\BeforeUserIdUnassignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;

// Check user and app status
\OC_JSON::checkAdminUser();
\OC_JSON::checkAppEnabled('user_ldap');
\OC_JSON::callCheck();

$subject = (string)$_POST['ldap_clear_mapping'];
$mapping = null;
try {
	if ($subject === 'user') {
		$mapping = Server::get(UserMapping::class);
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = Server::get(IEventDispatcher::class);
		$result = $mapping->clearCb(
			function (string $uid) use ($dispatcher): void {
				$dispatcher->dispatchTyped(new BeforeUserIdUnassignedEvent($uid));
				\OC::$server->getUserManager()->emit('\OC\User', 'preUnassignedUserId', [$uid]);
			},
			function (string $uid) use ($dispatcher): void {
				$dispatcher->dispatchTyped(new UserIdUnassignedEvent($uid));
				\OC::$server->getUserManager()->emit('\OC\User', 'postUnassignedUserId', [$uid]);
			}
		);
	} elseif ($subject === 'group') {
		$mapping = new GroupMapping(\OC::$server->getDatabaseConnection());
		$result = $mapping->clear();
	}

	if ($mapping === null || !$result) {
		$l = \OCP\Util::getL10N('user_ldap');
		throw new \Exception($l->t('Failed to clear the mappings.'));
	}
	\OC_JSON::success();
} catch (\Exception $e) {
	\OC_JSON::error(['message' => $e->getMessage()]);
}
