<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Property;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Server;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\IRegistry;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingPropertyDate from Share
 * @psalm-import-type SharingPropertyEnum from Share
 * @psalm-import-type SharingPropertyBoolean from Share
 * @psalm-import-type SharingPropertyPassword from Share
 * @psalm-import-type SharingPropertyString from Share
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class ShareProperty {
	public function __construct(
		/** @var class-string<ISharePropertyType> $class */
		public string $class,
		public ?string $value,
	) {
	}

	/**
	 * @return SharingPropertyBoolean|SharingPropertyDate|SharingPropertyEnum|SharingPropertyPassword|SharingPropertyString
	 */
	public function format(): array {
		if (($propertyType = (Server::get(IRegistry::class)->getPropertyTypes()[$this->class] ?? null)) === null) {
			throw new ShareInvalidException('The property type is not registered: ' . $this->class);
		}

		$out = [
			'class' => $this->class,
			'display_name' => $propertyType->getDisplayName(),
			'priority' => $propertyType->getPriority(),
			'required' => $propertyType->getRequired(),
			'value' => $this->value,
		];
		if (($hint = $propertyType->getHint()) !== null) {
			$out['hint'] = $hint;
		}

		return $propertyType->format($out);
	}
}
