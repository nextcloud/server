<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Settings;

use OCP\WorkflowEngine\IManager;

class Personal extends ASettings {
	#[\Override]
	public function getScope(): int {
		return IManager::SCOPE_USER;
	}

	#[\Override]
	public function getSection(): ?string {
		if (!$this->manager->isUserScopeEnabled()) {
			return null;
		}

		foreach ($this->manager->getOperatorList() as $operator) {
			if ($operator->isAvailableForScope($this->getScope())) {
				return parent::getSection();
			}
		}

		return null;
	}
}
