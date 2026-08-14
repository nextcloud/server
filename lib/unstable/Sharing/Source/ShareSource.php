<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Source;

use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Share;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use RuntimeException;

/**
 * @psalm-import-type SharingSource from Share
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class ShareSource {
	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		/** @var class-string<IShareSourceType> $class */
		public readonly string $class,
		/** @var non-empty-string $value */
		public readonly string $value,
		private ?IShareSourceMetadata $metadata = null,
	) {
	}

	private function getMetadata(ISharingRegistry $registry): IShareSourceMetadata {
		if (($sourceType = ($registry->getSourceTypes()[$this->class] ?? null)) === null) {
			throw new RuntimeException('The source type is not registered: ' . $this->class);
		}

		if (!$this->metadata instanceof \NCU\Sharing\Source\IShareSourceMetadata) {
			$this->metadata = $sourceType->getSourceMetadata($this->value) ?? new ShareSourceMetadata($this->value, null);
		}

		return $this->metadata;
	}

	/**
	 * @return SharingSource
	 * @experimental 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory, bool $isUnique): array {
		if (($sourceType = ($registry->getSourceTypes()[$this->class] ?? null)) === null) {
			throw new RuntimeException('The source type is not registered: ' . $this->class);
		}

		$metadata = $this->getMetadata($registry);
		$displayName = $metadata->getDisplayName();
		if (!$isUnique) {
			$displayName .= ' (' . $sourceType->getDisplayName($l10nFactory) . ': ' . $this->value . ')';
		}

		return [
			'class' => $this->class,
			'value' => $this->value,
			'display_name' => $displayName,
			'icon' => $metadata->getIcon()?->format(),
		];
	}

	/**
	 * @param list<self> $sources
	 * @return list<SharingSource>
	 * @experimental 35.0.0
	 */
	public static function formatMultiple(ISharingRegistry $registry, IFactory $l10nFactory, array $sources): array {
		$sourceDisplayNames = [];
		foreach ($sources as $source) {
			$displayName = $source->getMetadata($registry)->getDisplayName();
			$sourceDisplayNames[$displayName] ??= 0;
			++$sourceDisplayNames[$displayName];
		}

		return array_map(static fn (ShareSource $source): array => $source->format($registry, $l10nFactory, $sourceDisplayNames[$source->getMetadata($registry)->getDisplayName()] === 1), $sources);
	}

	/**
	 * @experimental 35.0.0
	 */
	public function equals(ShareSource $other): bool {
		return $this->class === $other->class && $this->value === $other->value;
	}
}
