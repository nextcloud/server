<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Property;

use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Share;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use RuntimeException;

/**
 * @psalm-import-type SharingPropertyDate from Share
 * @psalm-import-type SharingPropertyEnum from Share
 * @psalm-import-type SharingPropertyBoolean from Share
 * @psalm-import-type SharingPropertyPassword from Share
 * @psalm-import-type SharingPropertyString from Share
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareProperty {
	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		/** @var class-string<ISharePropertyType> $class */
		public string $class,
		public ?string $value,
	) {
	}

	/**
	 * @return SharingPropertyBoolean|SharingPropertyDate|SharingPropertyEnum|SharingPropertyPassword|SharingPropertyString
	 * @experimental 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory, Share $share): array {
		if (($propertyType = ($registry->getPropertyTypes()[$this->class] ?? null)) === null) {
			throw new RuntimeException('The property type is not registered: ' . $this->class);
		}

		return $propertyType->format($share, [
			'class' => $this->class,
			'display_name' => $propertyType->getDisplayName($l10nFactory),
			'hint' => $propertyType->getHint($l10nFactory, $share),
			'priority' => $propertyType->getPriority(),
			'advanced' => $propertyType->isAdvanced(),
			'required' => $propertyType->isRequired($share),
			'value' => $this->value,
		]);
	}
}
