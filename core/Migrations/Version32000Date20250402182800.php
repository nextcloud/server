<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add `allow_user_to_change_email` system config
 */
class Version32000Date20250402182800 extends SimpleMigrationStep {

	public function __construct(
		private IConfig $config,
	) {
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$allowDisplayName = $this->config->getSystemValue('allow_user_to_change_display_name', null);
		$allowEmail = $this->config->getSystemValue('allow_user_to_change_email', null);

		// if displayname was set, but not the email setting, then set the email setting to the same as the email setting
		if ($allowDisplayName !== null && $allowEmail === null) {
			$this->config->setSystemValue('allow_user_to_change_email', $allowDisplayName === true);
		}
	}

}
