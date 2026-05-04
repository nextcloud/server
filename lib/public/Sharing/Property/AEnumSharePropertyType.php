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
 * @psalm-import-type SharingPropertyEnum from Share
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AEnumSharePropertyType implements ISharePropertyType {
	/**
	 * @return non-empty-list<string>
	 * @since 35.0.0
	 */
	abstract public function getValidValues(): array;

	/**
	 * @since 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, string $value): true|string {
		$validValues = $this->getValidValues();
		if (in_array($value, $validValues, true)) {
			return true;
		}

		return $l10nFactory->get(Application::APP_ID)->t('Only ' . implode(', ', $validValues) . ' are valid values: ' . $value);
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyEnum
	 * @since 35.0.0
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'enum';
		$property['valid_values'] = $this->getValidValues();
		return $property;
	}
}
