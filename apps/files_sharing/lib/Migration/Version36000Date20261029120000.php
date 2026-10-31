<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Migration;

use Closure;
use OC\Core\AppInfo\ConfigLexicon as CoreConfigLexicon;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Initialize `core/hide_disabled_user_shares_scope` from the deprecated
 * `files_sharing/hide_disabled_user_shares`.
 */
class Version36000Date20261029120000 extends SimpleMigrationStep {
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if ($this->appConfig->hasKey('core', CoreConfigLexicon::SHARE_HIDE_DISABLED_USER_SHARES_SCOPE, null)
			|| !$this->appConfig->hasKey(Application::APP_ID, ConfigLexicon::HIDE_DISABLED_USER_SHARES, null)) {
			return;
		}

		try {
			$hideAll = $this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::HIDE_DISABLED_USER_SHARES);
		} catch (AppConfigTypeConflictException) {
			$output->warning('Could not read ' . Application::APP_ID . '/' . ConfigLexicon::HIDE_DISABLED_USER_SHARES . ', core/' . CoreConfigLexicon::SHARE_HIDE_DISABLED_USER_SHARES_SCOPE . ' keeps its default value');
			return;
		}

		$this->appConfig->setValueString(
			'core',
			CoreConfigLexicon::SHARE_HIDE_DISABLED_USER_SHARES_SCOPE,
			$hideAll ? 'all' : 'external',
		);
	}
}
