<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Source;

use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Share;
use RuntimeException;

/**
 * @psalm-import-type SharingSource from Share
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareSource {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		/** @var class-string<IShareSourceType> $class */
		public string $class,
		/** @var non-empty-string $value */
		public string $value,
	) {
	}

	/**
	 * @return SharingSource
	 * @since 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory, bool $isUnique): array {
		if (($sourceType = ($registry->getSourceTypes()[$this->class] ?? null)) === null) {
			throw new RuntimeException('The source type is not registered: ' . $this->class);
		}

		$displayName = $sourceType->getSourceDisplayName($this->value) ?? $this->value;
		if (!$isUnique) {
			$displayName .= ' (' . $sourceType->getDisplayName($l10nFactory) . ': ' . $this->value . ')';
		}

		return [
			'class' => $this->class,
			'value' => $this->value,
			'display_name' => $displayName,
			'icon' => $sourceType->getSourceIcon($this->value)?->format(),
		];
	}

	/**
	 * @param list<self> $sources
	 * @return list<SharingSource>
	 * @since 35.0.0
	 */
	public static function formatMultiple(ISharingRegistry $registry, IFactory $l10nFactory, array $sources): array {
		$sourceTypes = $registry->getSourceTypes();

		$sourceDisplayNames = [];
		foreach ($sources as $source) {
			$displayName = $sourceTypes[$source->class]?->getSourceDisplayName($source->value) ?? $source->value;
			$sourceDisplayNames[$displayName] ??= 0;
			++$sourceDisplayNames[$displayName];
		}

		return array_map(static fn (ShareSource $source): array => $source->format($registry, $l10nFactory, $sourceDisplayNames[$sourceTypes[$source->class]?->getSourceDisplayName($source->value) ?? $source->value] === 1), $sources);
	}
}
