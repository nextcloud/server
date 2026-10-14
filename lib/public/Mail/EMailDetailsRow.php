<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Mail;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Labelled row of an {@see EMailDetails} card. Each part is rendered on its own line.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class EMailDetailsRow {
	/**
	 * @since 36.0.0
	 */
	public const PART_TEXT = 'text';
	/**
	 * @since 36.0.0
	 */
	public const PART_LINK = 'link';
	/**
	 * @since 36.0.0
	 */
	public const PART_MUTED = 'muted';

	/** @var list<array{type: self::PART_*, text: string, url: string}> */
	private array $parts = [];

	/**
	 * @since 36.0.0
	 */
	public function __construct(
		private string $label,
	) {
	}

	/**
	 * @since 36.0.0
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * @since 36.0.0
	 */
	public function text(string $text): self {
		$this->parts[] = ['type' => self::PART_TEXT, 'text' => $text, 'url' => ''];
		return $this;
	}

	/**
	 * @since 36.0.0
	 */
	public function link(string $text, string $url): self {
		$this->parts[] = ['type' => self::PART_LINK, 'text' => $text, 'url' => $url];
		return $this;
	}

	/**
	 * Secondary information, rendered in a lighter color
	 *
	 * @since 36.0.0
	 */
	public function muted(string $text): self {
		$this->parts[] = ['type' => self::PART_MUTED, 'text' => $text, 'url' => ''];
		return $this;
	}

	/**
	 * @return list<array{type: self::PART_*, text: string, url: string}>
	 * @since 36.0.0
	 */
	public function getParts(): array {
		return $this->parts;
	}
}
