<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\IDelegatedSettings;

/**
 * Empty settings class, used only for admin delegation.
 */
class Users implements IDelegatedSettings {
	public function getForm(): TemplateResponse {
		throw new \Exception('Admin delegation settings should never be rendered');
	}

	public function getSection(): ?string {
		return 'usersdelegation';
	}

	public function getPriority(): int {
		return 0;
	}

	public function getName(): ?string {
		/* Use section name alone */
		return null;
	}

	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
