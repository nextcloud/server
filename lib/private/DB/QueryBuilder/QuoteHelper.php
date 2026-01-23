<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder;

use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryFunction;

class QuoteHelper {
	public function quoteColumnNames(array|string|ILiteral|IParameter|IQueryFunction $strings): array|string {
		if (!is_array($strings)) {
			return $this->quoteColumnName($strings);
		}

		$return = [];
		foreach ($strings as $string) {
			$return[] = $this->quoteColumnName($string);
		}

		return $return;
	}

	public function quoteColumnName(string|ILiteral|IParameter|IQueryFunction $string): string {
		if ($string instanceof IParameter || $string instanceof ILiteral || $string instanceof IQueryFunction) {
			return (string)$string;
		}

		if ($string === 'null' || $string === '*') {
			return $string;
		}

		$string = str_replace(' AS ', ' as ', $string);
		if (substr_count($string, ' as ')) {
			return implode(' as ', array_map($this->quoteColumnName(...), explode(' as ', $string, 2)));
		}

		if (substr_count($string, '.')) {
			[$alias, $columnName] = explode('.', $string, 2);

			if ($columnName === '*') {
				return '`' . $alias . '`.*';
			}

			return '`' . $alias . '`.`' . $columnName . '`';
		}

		return '`' . $string . '`';
	}
}
