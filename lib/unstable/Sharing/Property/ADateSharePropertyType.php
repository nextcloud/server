<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Property;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use NCU\Sharing\Share;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyDate from Share
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class ADateSharePropertyType implements ISharePropertyType {
	/**
	 * @experimental 35.0.0
	 */
	abstract public function getMinDate(Share $share): ?DateTimeImmutable;

	/**
	 * @experimental 35.0.0
	 */
	abstract public function getMaxDate(Share $share): ?DateTimeImmutable;

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, Share $share, string $value): true|string {
		try {
			$date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);
		} catch (Exception) {
			$date = false;
		}

		if ($date === false) {
			return $l10nFactory->get(Application::APP_ID)->t('Invalid ISO8601 date: %s', [$value]);
		}

		if (($minDate = $this->getMinDate($share)) instanceof DateTimeImmutable && $date->diff($minDate)->invert === 0) {
			$l10n = $l10nFactory->get(Application::APP_ID);
			return $l10n->t('Date needs to be after %s', [$l10n->l('datetime', $minDate)]);
		}

		if (($maxDate = $this->getMaxDate($share)) instanceof DateTimeImmutable && $date->diff($maxDate)->invert === 1) {
			$l10n = $l10nFactory->get(Application::APP_ID);
			return $l10n->t('Date needs to be before %s', [$l10n->l('datetime', $maxDate)]);
		}

		return true;
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyDate
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function format(Share $share, array $property): array {
		$property['type'] = 'date';
		$property['min_date'] = $this->getMinDate($share)?->format(DateTimeInterface::ATOM);
		$property['max_date'] = $this->getMaxDate($share)?->format(DateTimeInterface::ATOM);
		return $property;
	}
}
