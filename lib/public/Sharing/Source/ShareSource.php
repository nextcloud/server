<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Source;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Server;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\IRegistry;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingSource from Share
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class ShareSource {
	public function __construct(
		/** @var class-string<IShareSourceType> $class */
		public string $class,
		/** @var non-empty-string $value */
		public string $value,
	) {
	}

	/**
	 * @return SharingSource
	 */
	public function format(bool $isUnique): array {
		if (($sourceType = (Server::get(IRegistry::class)->getSourceTypes()[$this->class] ?? null)) === null) {
			throw new ShareInvalidException('The source type is not registered: ' . $this->class);
		}

		$displayName = $sourceType->getSourceDisplayName($this->value) ?? $this->value;
		if (!$isUnique) {
			$displayName .= ' (' . $sourceType->getDisplayName() . ': ' . $this->value . ')';
		}

		return [
			'class' => $this->class,
			'value' => $this->value,
			'display_name' => $displayName,
		];
	}

	/**
	 * @param list<self> $sources
	 * @return list<SharingSource>
	 */
	public static function formatMultiple(array $sources): array {
		$sourceTypes = Server::get(IRegistry::class)->getSourceTypes();

		$sourceDisplayNames = [];
		foreach ($sources as $source) {
			$displayName = $sourceTypes[$source->class]?->getSourceDisplayName($source->value) ?? $source->value;
			$sourceDisplayNames[$displayName] ??= 0;
			++$sourceDisplayNames[$displayName];
		}

		return array_map(static fn (ShareSource $source): array => $source->format($sourceDisplayNames[$sourceTypes[$source->class]?->getSourceDisplayName($source->value) ?? $source->value] === 1), $sources);
	}
}
