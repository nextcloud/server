<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\FullTextSearch\Model;

use JsonSerializable;
use OCP\FullTextSearch\Model\ISearchRequestSimpleQuery;

/**
 * @since 17.0.0
 *
 * Class SearchRequestSimpleQuery
 *
 * @package OC\FullTextSearch\Model
 */
final class SearchRequestSimpleQuery implements ISearchRequestSimpleQuery, JsonSerializable {
	private array $values = [];


	/**
	 * SearchRequestQuery constructor.
	 *
	 * @since 17.0.0
	 */
	public function __construct(
		private string $field,
		private int $type,
	) {
	}


	/**
	 * Get the compare type of the query
	 *
	 * @since 17.0.0
	 */
	public function getType(): int {
		return $this->type;
	}


	/**
	 * Get the field to apply query
	 *
	 * @since 17.0.0
	 */
	public function getField(): string {
		return $this->field;
	}

	/**
	 * Set the field to apply query
	 *
	 * @since 17.0.0
	 */
	public function setField(string $field): ISearchRequestSimpleQuery {
		$this->field = $field;

		return $this;
	}


	/**
	 * Get the value to compare (string)
	 *
	 * @since 17.0.0
	 */
	public function getValues(): array {
		return $this->values;
	}


	/**
	 * Add value to compare (string)
	 *
	 * @since 17.0.0
	 */
	public function addValue(string $value): ISearchRequestSimpleQuery {
		$this->values[] = $value;

		return $this;
	}

	/**
	 * Add value to compare (int)
	 *
	 * @since 17.0.0
	 */
	public function addValueInt(int $value): ISearchRequestSimpleQuery {
		$this->values[] = $value;

		return $this;
	}

	/**
	 * Add value to compare (array)
	 *
	 * @since 17.0.0
	 */
	public function addValueArray(array $value): ISearchRequestSimpleQuery {
		$this->values[] = $value;

		return $this;
	}

	/**
	 * Add value to compare (bool)
	 *
	 * @since 17.0.0
	 */
	public function addValueBool(bool $value): ISearchRequestSimpleQuery {
		$this->values[] = $value;

		return $this;
	}


	/**
	 * @since 17.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'type' => $this->getType(),
			'field' => $this->getField(),
			'values' => $this->getValues()
		];
	}
}
