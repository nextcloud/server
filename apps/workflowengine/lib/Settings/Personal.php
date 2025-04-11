<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Settings;

use OCP\WorkflowEngine\IManager;

class Personal extends ASettings {
	public function getScope(): int {
		return IManager::SCOPE_USER;
	}

	public function getSection(): ?string {
		return $this->manager->isUserScopeEnabled() ? 'workflow' : null;
	}
}
