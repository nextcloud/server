<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search;

use OCP\Files\Search\ISearchComparison;

/**
 * @psalm-import-type ParamValue from ISearchComparison
 */
class SearchComparison implements ISearchComparison {
	private array $hints = [];

	public function __construct(
		private string $type,
		private string $field,
		/** @var ParamValue $value */
		private \DateTime|int|string|bool|array $value,
		private string $extra = ''
	) {
	}

	public function getType(): string {
		return $this->type;
	}

	public function getField(): string {
		return $this->field;
	}

	public function getValue(): string|int|bool|\DateTime|array {
		return $this->value;
	}

	public function getExtra(): string {
		return $this->extra;
	}

	public function getQueryHint(string $name, $default) {
		return $this->hints[$name] ?? $default;
	}

	public function setQueryHint(string $name, $value): void {
		$this->hints[$name] = $value;
	}

	public static function escapeLikeParameter(string $param): string {
		return addcslashes($param, '\\_%');
	}

	public function __toString(): string {
		return $this->field . ' ' . $this->type . ' ' . json_encode($this->value);
	}
}
