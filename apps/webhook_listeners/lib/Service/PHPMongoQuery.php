<?php

/**
 * SPDX-FileCopyrightText: 2013 Akkroo Solutions Ltd
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Service;

use Exception;

/**
 * PHPMongoQuery implements MongoDB queries in PHP, allowing developers to query
 * a 'document' (an array containing data) against a Mongo query object,
 * returning a boolean value for pass or fail
 */
abstract class PHPMongoQuery {
	/**
	 * Execute a mongo query on a set of documents and return the documents that pass the query
	 *
	 * @param array $query A boolean value or an array defining a query
	 * @param array $documents The document to query
	 * @param array $options Any options:
	 *                       'debug' - boolean - debug mode, verbose logging
	 *                       'logger' - \Psr\LoggerInterface - A logger instance that implements {@link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#3-psrlogloggerinterface PSR-3}
	 *                       'unknownOperatorCallback' - a callback to be called if an operator can't be found.  The function definition is function($operator, $operatorValue, $field, $document). return true or false.
	 * @throws Exception
	 */
	public static function find(array $query, array $documents, array $options = []): array {
		if (empty($documents) || empty($query)) {
			return [];
		}
		$ret = [];
		$options['_shouldLog'] = !empty($options['logger']) && $options['logger'] instanceof \Psr\Log\LoggerInterface;
		$options['_debug'] = !empty($options['debug']);
		foreach ($documents as $doc) {
			if (static::_executeQuery($query, $doc, $options)) {
				$ret[] = $doc;
			}
		}
		return $ret;
	}

	/**
	 * Execute a Mongo query on a document
	 *
	 * @param mixed $query A boolean value or an array defining a query
	 * @param array $document The document to query
	 * @param array $options Any options:
	 *                       'debug' - boolean - debug mode, verbose logging
	 *                       'logger' - \Psr\LoggerInterface - A logger instance that implements {@link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#3-psrlogloggerinterface PSR-3}
	 *                       'unknownOperatorCallback' - a callback to be called if an operator can't be found.  The function definition is function($operator, $operatorValue, $field, $document). return true or false.
	 * @throws Exception
	 */
	public static function executeQuery($query, array &$document, array $options = []): bool {
		$options['_shouldLog'] = !empty($options['logger']) && $options['logger'] instanceof \Psr\Log\LoggerInterface;
		$options['_debug'] = !empty($options['debug']);
		if ($options['_debug'] && $options['_shouldLog']) {
			$options['logger']->debug('executeQuery called', ['query' => $query, 'document' => $document, 'options' => $options]);
		}

		if (!is_array($query)) {
			return (bool)$query;
		}

		return self::_executeQuery($query, $document, $options);
	}

	/**
	 * Internal execute query
	 *
	 * This expects an array from the query and has an additional logical operator (for the root query object the logical operator is always $and so this is not required)
	 *
	 * @throws Exception
	 */
	private static function _executeQuery(array $query, array &$document, array $options = [], string $logicalOperator = '$and'): bool {
		if ($logicalOperator !== '$and' && (!count($query) || !isset($query[0]))) {
			throw new Exception($logicalOperator . ' requires nonempty array');
		}
		if ($options['_debug'] && $options['_shouldLog']) {
			$options['logger']->debug('_executeQuery called', ['query' => $query, 'document' => $document, 'logicalOperator' => $logicalOperator]);
		}

		// for the purpose of querying documents, we are going to specify that an indexed array is an array which
		// only contains numeric keys, is sequential, the first key is zero, and not empty. This will allow us
		// to detect an array of key->vals that have numeric IDs vs an array of queries (where keys were not specified)
		$queryIsIndexedArray = !empty($query) && array_is_list($query);

		foreach ($query as $k => $q) {
			$pass = true;
			if (is_string($k) && substr($k, 0, 1) === '$') {
				// key is an operator at this level, except $not, which can be at any level
				if ($k === '$not') {
					$pass = !self::_executeQuery($q, $document, $options);
				} else {
					$pass = self::_executeQuery($q, $document, $options, $k);
				}
			} elseif ($logicalOperator === '$and') { // special case for $and
				if ($queryIsIndexedArray) { // $q is an array of query objects
					$pass = self::_executeQuery($q, $document, $options);
				} elseif (is_array($q)) { // query is array, run all queries on field.  All queries must match. e.g { 'age': { $gt: 24, $lt: 52 } }
					$pass = self::_executeQueryOnElement($q, $k, $document, $options);
				} else {
					// key value means equality
					$pass = self::_executeOperatorOnElement('$e', $q, $k, $document, $options);
				}
			} else { // $q is array of query objects e.g '$or' => [{'fullName' => 'Nick'}]
				$pass = self::_executeQuery($q, $document, $options, '$and');
			}
			switch ($logicalOperator) {
				case '$and': // if any fail, query fails
					if (!$pass) {
						return false;
					}
					break;
				case '$or': // if one succeeds, query succeeds
					if ($pass) {
						return true;
					}
					break;
				case '$nor': // if one succeeds, query fails
					if ($pass) {
						return false;
					}
					break;
				default:
					if ($options['_shouldLog']) {
						$options['logger']->warning('_executeQuery could not find logical operator', ['query' => $query, 'document' => $document, 'logicalOperator' => $logicalOperator]);
					}
					return false;
			}
		}
		switch ($logicalOperator) {
			case '$and': // all succeeded, query succeeds
				return true;
			case '$or': // all failed, query fails
				return false;
			case '$nor': // all failed, query succeeded
				return true;
			default:
				if ($options['_shouldLog']) {
					$options['logger']->warning('_executeQuery could not find logical operator', ['query' => $query, 'document' => $document, 'logicalOperator' => $logicalOperator]);
				}
				return false;
		}
	}

	/**
	 * Execute a query object on an element
	 *
	 * @throws Exception
	 */
	private static function _executeQueryOnElement(array $query, string $element, array &$document, array $options = []): bool {
		if ($options['_debug'] && $options['_shouldLog']) {
			$options['logger']->debug('_executeQueryOnElement called', ['query' => $query, 'element' => $element, 'document' => $document]);
		}
		// iterate through query operators
		foreach ($query as $op => $opVal) {
			if (!self::_executeOperatorOnElement($op, $opVal, $element, $document, $options)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if an operator is equal to a value
	 *
	 * Equality includes direct equality, regular expression match, and checking if the operator value is one of the values in an array value
	 *
	 * @param mixed $v
	 * @param mixed $operatorValue
	 */
	private static function _isEqual($v, $operatorValue): bool {
		if (is_array($v) && is_array($operatorValue)) {
			return $v == $operatorValue;
		}
		if (is_array($v)) {
			return in_array($operatorValue, $v);
		}
		if (is_string($operatorValue) && preg_match('/^\/(.*?)\/([a-z]*)$/i', $operatorValue, $matches)) {
			return (bool)preg_match('/' . $matches[1] . '/' . $matches[2], $v);
		}
		return $operatorValue === $v;
	}

	/**
	 * Execute a Mongo Operator on an element
	 *
	 * @param string $operator The operator to perform
	 * @param mixed $operatorValue The value to provide the operator
	 * @param string $element The target element.  Can be an object path eg price.shoes
	 * @param array $document The document in which to find the element
	 * @param array $options Options
	 * @throws Exception Exceptions on invalid operators, invalid unknown operator callback, and invalid operator values
	 */
	private static function _executeOperatorOnElement(string $operator, $operatorValue, string $element, array &$document, array $options = []): bool {
		if ($options['_debug'] && $options['_shouldLog']) {
			$options['logger']->debug('_executeOperatorOnElement called', ['operator' => $operator, 'operatorValue' => $operatorValue, 'element' => $element, 'document' => $document]);
		}

		if ($operator === '$not') {
			return !self::_executeQueryOnElement($operatorValue, $element, $document, $options);
		}

		$elementSpecifier = explode('.', $element);
		$v = & $document;
		$exists = true;
		foreach ($elementSpecifier as $index => $es) {
			if (empty($v)) {
				$exists = false;
				break;
			}
			if (isset($v[0])) {
				// value from document is an array, so we need to iterate through array and test the query on all elements of the array
				// if any elements match, then return true
				$newSpecifier = implode('.', array_slice($elementSpecifier, $index));
				foreach ($v as $item) {
					if (self::_executeOperatorOnElement($operator, $operatorValue, $newSpecifier, $item, $options)) {
						return true;
					}
				}
				return false;
			}
			if (isset($v[$es])) {
				$v = & $v[$es];
			} else {
				$exists = false;
				break;
			}
		}

		switch ($operator) {
			case '$all':
				if (!$exists) {
					return false;
				}
				if (!is_array($operatorValue)) {
					throw new Exception('$all requires array');
				}
				if (count($operatorValue) === 0) {
					return false;
				}
				if (!is_array($v)) {
					if (count($operatorValue) === 1) {
						return $v === $operatorValue[0];
					}
					return false;
				}
				return count(array_intersect($v, $operatorValue)) === count($operatorValue);
			case '$e':
				if (!$exists) {
					return false;
				}
				return self::_isEqual($v, $operatorValue);
			case '$in':
				if (!$exists) {
					return false;
				}
				if (!is_array($operatorValue)) {
					throw new Exception('$in requires array');
				}
				if (count($operatorValue) === 0) {
					return false;
				}
				if (is_array($v)) {
					return count(array_intersect($v, $operatorValue)) > 0;
				}
				return in_array($v, $operatorValue);
			case '$lt':		return $exists && $v < $operatorValue;
			case '$lte':	return $exists && $v <= $operatorValue;
			case '$gt':		return $exists && $v > $operatorValue;
			case '$gte':	return $exists && $v >= $operatorValue;
			case '$ne':		return (!$exists && $operatorValue !== null) || ($exists && !self::_isEqual($v, $operatorValue));
			case '$nin':
				if (!$exists) {
					return true;
				}
				if (!is_array($operatorValue)) {
					throw new Exception('$nin requires array');
				}
				if (count($operatorValue) === 0) {
					return true;
				}
				if (is_array($v)) {
					return count(array_intersect($v, $operatorValue)) === 0;
				}
				return !in_array($v, $operatorValue);

			case '$exists':	return ($operatorValue && $exists) || (!$operatorValue && !$exists);
			case '$mod':
				if (!$exists) {
					return false;
				}
				if (!is_array($operatorValue)) {
					throw new Exception('$mod requires array');
				}
				if (count($operatorValue) !== 2) {
					throw new Exception('$mod requires two parameters in array: divisor and remainder');
				}
				return $v % $operatorValue[0] === $operatorValue[1];

			default:
				if (empty($options['unknownOperatorCallback']) || !is_callable($options['unknownOperatorCallback'])) {
					throw new Exception('Operator ' . $operator . ' is unknown');
				}

				$res = call_user_func($options['unknownOperatorCallback'], $operator, $operatorValue, $element, $document);
				if ($res === null) {
					throw new Exception('Operator ' . $operator . ' is unknown');
				}
				if (!is_bool($res)) {
					throw new Exception('Return value of unknownOperatorCallback must be boolean, actual value ' . $res);
				}
				return $res;
		}
		throw new Exception('Didn\'t return in switch');
	}

	/**
	 * Get the fields this query depends on
	 *
	 * @param array query	The query to analyse
	 * @return array An array of fields this query depends on
	 */
	public static function getDependentFields(array $query) {
		$fields = [];
		foreach ($query as $k => $v) {
			if (is_array($v)) {
				$fields = array_merge($fields, static::getDependentFields($v));
			}
			if (is_int($k) || $k[0] === '$') {
				continue;
			}
			$fields[] = $k;
		}
		return array_unique($fields);
	}
}
