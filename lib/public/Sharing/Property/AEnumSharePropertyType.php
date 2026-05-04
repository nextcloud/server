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
 * @psalm-import-type SharingPropertyEnum from Share
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
abstract readonly class AEnumSharePropertyType implements ISharePropertyType {
	/**
	 * @return non-empty-list<string>
	 */
	abstract public function getValidValues(): array;

	#[\Override]
	public function validateValue(string $value): true|string {
		$validValues = $this->getValidValues();
		if (in_array($value, $validValues, true)) {
			return true;
		}

		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Only ' . implode(', ', $validValues) . ' are valid values: ' . $value);
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyEnum
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'enum';
		$property['valid_values'] = $this->getValidValues();
		return $property;
	}
}
