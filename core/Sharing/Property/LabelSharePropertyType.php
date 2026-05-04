<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use OCA\Files\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Property\AStringSharePropertyType;

final readonly class LabelSharePropertyType extends AStringSharePropertyType {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Label');
	}

	#[\Override]
	public function getHint(): ?string {
		// TODO: Implement getHint() method.
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		// TODO: Implement getPriority() method.
		return 1;
	}

	#[\Override]
	public function getMinLength(): int {
		return 1;
	}

	#[\Override]
	public function getMaxLength(): int {
		return 30;
	}

	#[\Override]
	public function getRequired(): bool {
		return false;
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		return null;
	}
}
