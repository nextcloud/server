<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Files\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Property\ADateSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use RuntimeException;

final readonly class ExpirationDateSharePropertyType extends ADateSharePropertyType implements ISharePropertyTypeFilter {
	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		foreach ($share->properties as $property) {
			if ($property->class === self::class && $property->value !== null) {
				$date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $property->value);
				if ($date === false) {
					throw new RuntimeException('Invalid date.');
				}

				return (new DateTimeImmutable())->diff($date)->invert === 1;
			}
		}

		return false;
	}

	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Expiration date');
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
	public function getMinDate(): \DateTimeImmutable {
		return new DateTimeImmutable();
	}

	#[\Override]
	public function getMaxDate(): ?DateTimeImmutable {
		// TODO
		return null;
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		// TODO: Implement getDefaultValue() method.
		return null;
	}
}
