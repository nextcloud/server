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
use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OCP\L10N\IFactory;
use OCP\Share\IManager;
use OCP\Sharing\Property\ADateSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use RuntimeException;

// TODO: Handle per recipient required and default flags.
final class ExpirationDateSharePropertyType extends ADateSharePropertyType implements ISharePropertyTypeFilter {
	private readonly DateTimeImmutable $now;

	public function __construct(
		private readonly IManager $legacyManager,
	) {
		$this->now = new DateTimeImmutable();
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Expiration date');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory, Share $share): ?string {
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
	public function isRequired(Share $share): bool {
		if ($this->hasTokenOrEmailRecipient($share) && $this->legacyManager->shareApiLinkDefaultExpireDateEnforced()) {
			return true;
		}

		if ($this->hasRemoteRecipient($share) && $this->legacyManager->shareApiRemoteDefaultExpireDateEnforced()) {
			return true;
		}
		return $this->hasLocalNonTokenAndEmailRecipient($share) && $this->legacyManager->shareApiInternalDefaultExpireDateEnforced();
	}

	#[\Override]
	public function getDefaultValue(Share $share): ?string {
		return $this->getMaxExpirationDate($share)?->format(DateTimeInterface::ATOM);
	}

	#[\Override]
	public function getMinDate(Share $share): \DateTimeImmutable {
		// Ensure the expiration date is in the future.
		return $this->now->add(new DateInterval('PT5M'));
	}

	#[\Override]
	public function getMaxDate(Share $share): ?DateTimeImmutable {
		if ($this->isRequired($share)) {
			// Allow some time to pass between the user getting the max date and saving the date, as the time will shift in between.
			return $this->getMaxExpirationDate($share)?->add(new DateInterval('PT5M'));
		}

		return null;
	}

	private function getMaxExpirationDate(Share $share): ?DateTimeImmutable {
		$days = INF;

		if ($this->hasTokenOrEmailRecipient($share) && $this->legacyManager->shareApiLinkDefaultExpireDate()) {
			$days = min($days, $this->legacyManager->shareApiLinkDefaultExpireDays());
		}

		if ($this->hasRemoteRecipient($share) && $this->legacyManager->shareApiRemoteDefaultExpireDate()) {
			$days = min($days, $this->legacyManager->shareApiRemoteDefaultExpireDays());
		}

		if ($this->hasLocalNonTokenAndEmailRecipient($share) && $this->legacyManager->shareApiInternalDefaultExpireDate()) {
			$days = min($days, $this->legacyManager->shareApiInternalDefaultExpireDays());
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

	private function hasTokenOrEmailRecipient(Share $share): bool {
		foreach ($share->recipients as $recipient) {
			if ($recipient->class === TokenShareRecipientType::class || $recipient->class === EmailShareRecipientType::class) {
				return true;
			}
		}

		return false;
	}

	private function hasRemoteRecipient(Share $share): bool {
		foreach ($share->recipients as $recipient) {
			if ($recipient->instance !== null) {
				return true;
			}
		}

		return false;
	}

	private function hasLocalNonTokenAndEmailRecipient(Share $share): bool {
		foreach ($share->recipients as $recipient) {
			if ($recipient->instance === null && $recipient->class !== TokenShareRecipientType::class && $recipient->class !== EmailShareRecipientType::class) {
				return true;
			}
		}

		return false;
	}
}
