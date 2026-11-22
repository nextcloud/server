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
	#[\Override]
	public function getForm(): TemplateResponse {
		throw new \Exception('Admin delegation settings should never be rendered');
	}

	#[\Override]
	public function getSection(): ?string {
		return 'usersdelegation';
	}

	#[\Override]
	public function getPriority(): int {
		return 0;
	}

	#[\Override]
	public function getName(): ?string {
		/* Use section name alone */
		return null;
	}

	#[\Override]
	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
