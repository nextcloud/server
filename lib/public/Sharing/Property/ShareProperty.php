<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Property;

use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Share;
use RuntimeException;

/**
 * @psalm-import-type SharingPropertyDate from Share
 * @psalm-import-type SharingPropertyEnum from Share
 * @psalm-import-type SharingPropertyBoolean from Share
 * @psalm-import-type SharingPropertyPassword from Share
 * @psalm-import-type SharingPropertyString from Share
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareProperty {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		/** @var class-string<ISharePropertyType> $class */
		public string $class,
		public ?string $value,
	) {
	}

	/**
	 * @return SharingPropertyBoolean|SharingPropertyDate|SharingPropertyEnum|SharingPropertyPassword|SharingPropertyString
	 * @since 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory): array {
		if (($propertyType = ($registry->getPropertyTypes()[$this->class] ?? null)) === null) {
			throw new RuntimeException('The property type is not registered: ' . $this->class);
		}

		return $propertyType->format([
			'class' => $this->class,
			'display_name' => $propertyType->getDisplayName($l10nFactory),
			'hint' => $propertyType->getHint($l10nFactory),
			'priority' => $propertyType->getPriority(),
			'advanced' => $propertyType->isAdvanced(),
			'required' => $propertyType->isRequired(),
			'value' => $this->value,
		]);
	}
}
