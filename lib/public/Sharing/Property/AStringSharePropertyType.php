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
use OCP\Server;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyString from Share
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
abstract readonly class AStringSharePropertyType implements ISharePropertyType {
	/**
	 * @return ?positive-int
	 */
	abstract public function getMinLength(): ?int;

	/**
	 * @return ?positive-int
	 */
	abstract public function getMaxLength(): ?int;

	#[\Override]
	public function validateValue(string $value): true|string {
		if (($minLength = $this->getMinLength()) !== null && mb_strlen($value) < $minLength) {
			return Server::get(IFactory::class)->get(Application::APP_ID)->t('Need at least ' . $minLength . ' characters: ' . $value);
		}

		if (($maxLength = $this->getMaxLength()) !== null && mb_strlen($value) > $maxLength) {
			return Server::get(IFactory::class)->get(Application::APP_ID)->t('Provide ' . $maxLength . ' characters at most: ' . $value);
		}

		return true;
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyString
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'string';

		if (($minLength = $this->getMinLength()) !== null) {
			$property['min_length'] = $minLength;
		}

		if (($maxLength = $this->getMaxLength()) !== null) {
			$property['max_length'] = $maxLength;
		}

		return $property;
	}
}
