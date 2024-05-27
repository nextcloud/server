<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

class AppManagement extends Action {

	/**
	 * @param string $appName
	 */
	public function enableApp(string $appName): void {
		$this->log('App "%s" enabled',
			['app' => $appName],
			['app']
		);
	}

	/**
	 * @param string $appName
	 * @param string[] $groups
	 */
	public function enableAppForGroups(string $appName, array $groups): void {
		$this->log('App "%1$s" enabled for groups: %2$s',
			['app' => $appName, 'groups' => implode(', ', $groups)],
			['app', 'groups']
		);
	}

	/**
	 * @param string $appName
	 */
	public function disableApp(string $appName): void {
		$this->log('App "%s" disabled',
			['app' => $appName],
			['app']
		);
	}
}
