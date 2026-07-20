<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Property;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyDate from Share
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class ADateSharePropertyType implements ISharePropertyType {
	/**
	 * @since 35.0.0
	 */
	abstract public function getMinDate(): ?DateTimeImmutable;

	/**
	 * @since 35.0.0
	 */
	abstract public function getMaxDate(): ?DateTimeImmutable;

	/**
	 * @since 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, string $value): true|string {
		try {
			$date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);
		} catch (Exception) {
			$date = false;
		}

		if ($date === false) {
			return $l10nFactory->get(Application::APP_ID)->t('Invalid ISO date: ' . $value);
		}

		if (($minDate = $this->getMinDate()) instanceof DateTimeImmutable && $date->diff($minDate)->invert === 0) {
			return $l10nFactory->get(Application::APP_ID)->t('Date needs to be after ' . $minDate->format(DateTimeInterface::ATOM) . ': ' . $value);
		}

		if (($maxDate = $this->getMaxDate()) instanceof DateTimeImmutable && $date->diff($maxDate)->invert === 1) {
			return $l10nFactory->get(Application::APP_ID)->t('Date needs to be before ' . $maxDate->format(DateTimeInterface::ATOM) . ': ' . $value);
		}

		return true;
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyDate
	 * @since 35.0.0
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'date';
		$property['min_date'] = $this->getMinDate()?->format(DateTimeInterface::ATOM);
		$property['max_date'] = $this->getMaxDate()?->format(DateTimeInterface::ATOM);
		return $property;
	}
}
