<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Sharing\Property;

use NCU\Sharing\Property\ABooleanSharePropertyType;
use NCU\Sharing\Share;
use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;

final class NodeNicknameSharePropertyType extends ABooleanSharePropertyType {
	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Always ask recipients for a nickname before uploading files.');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory, Share $share): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 75;
	}

	#[\Override]
	public function isAdvanced(): bool {
		return true;
	}

	#[\Override]
	public function isRequired(Share $share): bool {
		return false;
	}

	#[\Override]
	public function getDefaultValue(Share $share): string {
		return 'false';
	}
}
