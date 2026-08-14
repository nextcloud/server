<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Property;

use NCU\Sharing\Share;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyString from Share
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AStringSharePropertyType implements ISharePropertyType {
	/**
	 * @return ?positive-int
	 * @experimental 35.0.0
	 */
	abstract public function getMinLength(): ?int;

	/**
	 * @return ?positive-int
	 * @experimental 35.0.0
	 */
	abstract public function getMaxLength(): ?int;

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, Share $share, string $value): true|string {
		if (($minLength = $this->getMinLength()) !== null && mb_strlen($value) < $minLength) {
			return $l10nFactory->get(Application::APP_ID)->t('Need at least %1$s characters: %2$s', [$minLength, $value]);
		}

		if (($maxLength = $this->getMaxLength()) !== null && mb_strlen($value) > $maxLength) {
			return $l10nFactory->get(Application::APP_ID)->t('Provide %1$s characters at most: %2$s', [$maxLength, $value]);
		}

		return true;
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyString
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function format(Share $share, array $property): array {
		$property['type'] = 'string';
		$property['min_length'] = $this->getMinLength();
		$property['max_length'] = $this->getMaxLength();
		return $property;
	}
}
