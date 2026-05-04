<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use OCA\Files\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Security\IHasher;
use OCP\Server;
use OCP\Sharing\Property\APasswordSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;

final readonly class PasswordSharePropertyType extends APasswordSharePropertyType implements ISharePropertyTypeFilter {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Password');
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
		// TODO
		return false;
	}

	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		$argument = $accessContext->arguments[self::class] ?? null;
		if (!is_string($argument)) {
			return true;
		}

		foreach ($share->properties as $property) {
			if ($property->class === self::class) {
				if ($property->value === null) {
					return false;
				}

				// TODO: Check if the hash has to be updated and save it.
				return !Server::get(IHasher::class)->verify($argument, $property->value);
			}
		}

		return false;
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		// TODO: Implement getDefaultValue() method.
		return null;
	}
}
