<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

use JsonSerializable;
use function array_values;

/**
 * @since 20.0.0
 */
final class SearchResult implements JsonSerializable {
	/** @var string */
	private $name;

	/** @var bool */
	private $isPaginated;

	/** @var SearchResultEntry[] */
	private $entries;

	/** @var int|string|null */
	private $cursor;

	/**
	 * @param string $name the translated name of the result section or group, e.g. "Mail"
	 * @param bool $isPaginated
	 * @param SearchResultEntry[] $entries
	 * @param ?int|?string $cursor
	 *
	 * @since 20.0.0
	 */
	private function __construct(string $name,
		bool $isPaginated,
		array $entries,
		$cursor = null) {
		$this->name = $name;
		$this->isPaginated = $isPaginated;
		$this->entries = $entries;
		$this->cursor = $cursor;
	}

	/**
	 * @param SearchResultEntry[] $entries
	 *
	 * @return static
	 *
	 * @since 20.0.0
	 */
	public static function complete(string $name, array $entries): self {
		return new self(
			$name,
			false,
			$entries
		);
	}

	/**
	 * @param SearchResultEntry[] $entries
	 * @param int|string $cursor
	 *
	 * @return static
	 *
	 * @since 20.0.0
	 */
	public static function paginated(string $name,
		array $entries,
		$cursor): self {
		return new self(
			$name,
			true,
			$entries,
			$cursor
		);
	}

	/**
	 * @return array
	 *
	 * @since 20.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'name' => $this->name,
			'isPaginated' => $this->isPaginated,
			'entries' => array_values($this->entries),
			'cursor' => $this->cursor,
		];
	}
}
