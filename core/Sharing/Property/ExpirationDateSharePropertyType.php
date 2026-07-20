<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Sharing\Property\ADateSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use RuntimeException;

// TODO: Handle per recipient required and default flags.
final class ExpirationDateSharePropertyType extends ADateSharePropertyType implements ISharePropertyTypeFilter {
	private readonly DateTimeImmutable $now;

	private ?IManager $legacyManager = null;

	private function getLegacyManager(): IManager {
		return $this->legacyManager ??= Server::get(IManager::class);
	}

	public function __construct() {
		$this->now = new DateTimeImmutable();
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Expiration date');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 70;
	}

	#[\Override]
	public function isAdvanced(): bool {
		return true;
	}

	#[\Override]
	public function isRequired(): bool {
		if ($this->getLegacyManager()->shareApiLinkDefaultExpireDateEnforced()) {
			return true;
		}

		if ($this->getLegacyManager()->shareApiRemoteDefaultExpireDateEnforced()) {
			return true;
		}

		return $this->getLegacyManager()->shareApiInternalDefaultExpireDateEnforced();
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		return $this->getMaxExpirationDate()?->format(DateTimeInterface::ATOM);
	}

	#[\Override]
	public function getMinDate(): \DateTimeImmutable {
		// Ensure the expiration date is in the future.
		return $this->now->add(new DateInterval('PT5M'));
	}

	#[\Override]
	public function getMaxDate(): ?DateTimeImmutable {
		if ($this->isRequired()) {
			// Allow some time to pass between the user getting the max date and saving the date, as the time will shift in between.
			return $this->getMaxExpirationDate()?->add(new DateInterval('PT5M'));
		}

		return null;
	}

	private function getMaxExpirationDate(): ?DateTimeImmutable {
		// We do not have any distinction between link/remote/internal, so we just apply the lowest expiration days count to be safe.
		$days = INF;
		if ($this->getLegacyManager()->shareApiLinkDefaultExpireDate()) {
			$days = min($days, $this->getLegacyManager()->shareApiLinkDefaultExpireDays());
		}

		if ($this->getLegacyManager()->shareApiRemoteDefaultExpireDate()) {
			$days = min($days, $this->getLegacyManager()->shareApiRemoteDefaultExpireDays());
		}

		if ($this->getLegacyManager()->shareApiInternalDefaultExpireDate()) {
			$days = min($days, $this->getLegacyManager()->shareApiInternalDefaultExpireDays());
		}

		if ($days !== INF) {
			return $this->now->add(new DateInterval('P' . $days . 'D'));
		}

		return null;
	}

	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		if (($property = $share->properties[self::class] ?? null) !== null && $property->value !== null) {
			$date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $property->value);
			if ($date === false) {
				throw new RuntimeException('Invalid date.');
			}

			return $this->now->diff($date)->invert === 1;
		}

		return false;
	}
}
