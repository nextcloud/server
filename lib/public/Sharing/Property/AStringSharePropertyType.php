<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Property;

use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyString from Share
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AStringSharePropertyType implements ISharePropertyType {
	/**
	 * @return ?positive-int
	 * @since 35.0.0
	 */
	abstract public function getMinLength(): ?int;

	/**
	 * @return ?positive-int
	 * @since 35.0.0
	 */
	abstract public function getMaxLength(): ?int;

	/**
	 * @since 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, string $value): true|string {
		if (($minLength = $this->getMinLength()) !== null && mb_strlen($value) < $minLength) {
			return $l10nFactory->get(Application::APP_ID)->t('Need at least ' . $minLength . ' characters: ' . $value);
		}

		if (($maxLength = $this->getMaxLength()) !== null && mb_strlen($value) > $maxLength) {
			return $l10nFactory->get(Application::APP_ID)->t('Provide ' . $maxLength . ' characters at most: ' . $value);
		}

		return true;
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyString
	 * @since 35.0.0
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'string';
		$property['min_length'] = $this->getMinLength();
		$property['max_length'] = $this->getMaxLength();
		return $property;
	}
}
