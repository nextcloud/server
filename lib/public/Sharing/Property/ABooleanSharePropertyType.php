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
 * @psalm-import-type SharingPropertyBoolean from Share
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class ABooleanSharePropertyType implements ISharePropertyType {
	/**
	 * @since 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, string $value): true|string {
		if ($value === 'true' || $value === 'false') {
			return true;
		}

		return $l10nFactory->get(Application::APP_ID)->t('Only true and false are valid values: %s', [$value]);
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyBoolean
	 * @since 35.0.0
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'boolean';
		return $property;
	}
}
