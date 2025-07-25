<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use OCA\User_LDAP\Mapping\AbstractMapping;
use OCA\User_LDAP\Proxy;
use OCA\User_LDAP\User_Proxy;
use OCP\BackgroundJob\QueuedJob;

abstract class UUIDFix extends QueuedJob {
	protected AbstractMapping $mapper;
	protected Proxy $proxy;

	public function run($argument) {
		$isUser = $this->proxy instanceof User_Proxy;
		foreach ($argument['records'] as $record) {
			$access = $this->proxy->getLDAPAccess($record['name']);
			$uuid = $access->getUUID($record['dn'], $isUser);
			if ($uuid === false) {
				// record not found, no prob, continue with the next
				continue;
			}
			if ($uuid !== $record['uuid']) {
				$this->mapper->setUUIDbyDN($uuid, $record['dn']);
			}
		}
	}
}
