<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Settings;

use OCA\WebhookListeners\AppInfo\Application;
use OCP\IL10N;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private IL10N $l,
	) {
	}

	public function getID(): string {
		return Application::APP_ID . '-admin';
	}

	public function getName(): string {
		return $this->l->t('Webhooks');
	}

	public function getPriority(): int {
		return 56;
	}

	public function getIcon(): string {
		return '';
	}
}
