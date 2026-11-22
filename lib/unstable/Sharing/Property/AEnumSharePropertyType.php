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
 * @psalm-import-type SharingPropertyEnum from Share
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AEnumSharePropertyType implements ISharePropertyType {
	/**
	 * @return non-empty-list<string>
	 * @experimental 35.0.0
	 */
	abstract public function getValidValues(): array;

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, Share $share, string $value): true|string {
		$validValues = $this->getValidValues();
		if (in_array($value, $validValues, true)) {
			return true;
		}

		return $l10nFactory->get(Application::APP_ID)->t('Only %1$s are valid values: %2$s', [implode(', ', $validValues), $value]);
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyEnum
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function format(Share $share, array $property): array {
		$property['type'] = 'enum';
		$property['valid_values'] = $this->getValidValues();
		return $property;
	}
}
