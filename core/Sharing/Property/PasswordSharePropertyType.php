<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Security\IHasher;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Sharing\Property\APasswordSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;

final class PasswordSharePropertyType extends APasswordSharePropertyType implements ISharePropertyTypeFilter {
	private ?IManager $legacyManager = null;

	private ?IHasher $hasher = null;

	private function getLegacyManager(): IManager {
		return $this->legacyManager ??= Server::get(IManager::class);
	}

	private function getHasher(): IHasher {
		return $this->hasher ??= Server::get(IHasher::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Password');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 80;
	}

	#[\Override]
	public function getRequired(): bool {
		// TODO: Enable group memberships check based on the owner.
		return $this->getLegacyManager()->shareApiLinkEnforcePassword(false);
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		return null;
	}

	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		$argument = $accessContext->arguments[self::class] ?? null;
		if (!is_string($argument)) {
			return true;
		}

		if (($property = $share->properties[self::class] ?? null) !== null && $property->value !== null) {
			// TODO: Check if the hash has to be updated and save it.
			return !$this->getHasher()->verify($argument, $property->value);
		}

		return false;
	}
}
