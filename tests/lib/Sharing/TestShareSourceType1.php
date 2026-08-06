<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use NCU\Sharing\Icon\ShareIconSVG;
use NCU\Sharing\Icon\ShareIconURL;
use NCU\Sharing\Source\IShareSourceMetadata;
use NCU\Sharing\Source\IShareSourceType;
use NCU\Sharing\Source\ShareSourceMetadata;
use OCP\Interaction\InteractionResource;
use OCP\L10N\IFactory;

class TestShareSourceType1 implements IShareSourceType {
	public function __construct(
		/** @var array<string, non-empty-string> $validSources */
		private readonly array $validSources,
	) {
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	#[\Override]
	public function validateSource(string $source): bool {
		return array_key_exists($source, $this->validSources);
	}

	#[\Override]
	public function getSourceMetadata(string $source): ?IShareSourceMetadata {
		if (isset($this->validSources[$source])) {
			return new ShareSourceMetadata(
				$this->validSources[$source],
				new ShareIconSVG('<svg/>'),
			);
		} else {
			return null;
		}
	}

	#[\Override]
	public function getSourceInteractionResource(string $userId, string $source): InteractionResource {
		return new TestInteractionResource($source);
	}
}
