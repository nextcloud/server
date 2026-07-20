<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Sharing\Property\AStringSharePropertyType;

final class NoteSharePropertyType extends AStringSharePropertyType {
	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Note to recipients');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 90;
	}

	#[\Override]
	public function isAdvanced(): bool {
		return false;
	}

	#[\Override]
	public function isRequired(): bool {
		return false;
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		return null;
	}

	#[\Override]
	public function getMinLength(): ?int {
		return null;
	}

	#[\Override]
	public function getMaxLength(): int {
		return 1000;
	}
}
