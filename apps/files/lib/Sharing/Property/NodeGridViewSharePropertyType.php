<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Property;

use OCA\Files\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Property\ABooleanSharePropertyType;

final readonly class NodeGridViewSharePropertyType extends ABooleanSharePropertyType {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Show files and folders in a grid');
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
	public function getRequired(): bool {
		return false;
	}

	#[\Override]
	public function getDefaultValue(): string {
		return 'false';
	}
}
