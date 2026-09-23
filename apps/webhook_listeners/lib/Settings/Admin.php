<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Settings;

use OCA\WebhookListeners\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\IDelegatedSettings;

/**
 * Empty settings class, used only for admin delegation for now as there is no UI
 * ATTENTION this admin delegation can create tokens with full admin rights and is therefore considered as a full admin role
 *
 */
class Admin implements IDelegatedSettings {
	/**
	 * Empty template response
	 */
	#[\Override]
	public function getForm(): TemplateResponse {
		throw new \Exception('Admin delegation settings should never be rendered');
	}

	#[\Override]
	public function getSection(): string {
		return Application::APP_ID . '-admin';
	}

	#[\Override]
	public function getPriority(): int {
		return 0;
	}

	#[\Override]
	public function getName(): ?string {
		/* Use section name alone */
		return 'Attention: can create tokens with full admin rights';
	}

	#[\Override]
	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
