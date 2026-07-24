<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\Interaction\InteractionResource;
use OCP\L10N\IFactory;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Source\IShareSourceType;

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
	public function getSourceDisplayName(string $source): ?string {
		return $this->validSources[$source];
	}

	#[\Override]
	public function getSourceIcon(string $source): null|ShareIconSVG|ShareIconURL {
		return new ShareIconSVG('<svg/>');
	}

	#[\Override]
	public function getSourceInteractionResource(string $userId, string $source): InteractionResource {
		return new TestInteractionResource($source);
	}
}
