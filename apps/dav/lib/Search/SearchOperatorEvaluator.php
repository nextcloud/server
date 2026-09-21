<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Search;

use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

/**
 * Evaluates an `ISearchOperator` tree against one already-fetched CardDAV or CalDAV object.
 *
 * Neither backend takes an AND/OR/NOT tree — `CardDavBackend::search()` and `ICalendar::search()`
 * both take one pattern or a short list of named filters — so every candidate is read and the real
 * tree is evaluated here instead. Grouping means what the caller wrote, not what a single `LIKE`
 * could express.
 *
 * Deliberately not narrowed by the property index either way: `oc_cards_properties` and
 * `oc_calendarobjects_props` both store `mb_strcut($value, 0, 254)`, so a match starting past that
 * offset would be missed by the index while still being a real match here.
 */
final class SearchOperatorEvaluator {
	/**
	 * @param callable(string): list<mixed> $values field name -> the card's values for it
	 */
	public static function matches(?ISearchOperator $operator, callable $values): bool {
		if ($operator === null) {
			return true;
		}

		return self::node($operator, $values);
	}

	/**
	 * @param callable(string): list<mixed> $values
	 */
	private static function node(ISearchOperator $operator, callable $values): bool {
		if ($operator instanceof ISearchComparison) {
			return self::condition($operator, $values($operator->getField()));
		}

		if (!$operator instanceof ISearchBinaryOperator) {
			return true;
		}

		return match ($operator->getType()) {
			ISearchBinaryOperator::OPERATOR_OR => array_reduce(
				$operator->getArguments(),
				static fn (bool $carry, ISearchOperator $child): bool => $carry || self::node($child, $values),
				false,
			),
			ISearchBinaryOperator::OPERATOR_NOT => !self::node($operator->getArguments()[0], $values),
			default => array_reduce(
				$operator->getArguments(),
				static fn (bool $carry, ISearchOperator $child): bool => $carry && self::node($child, $values),
				true,
			),
		};
	}

	/**
	 * @param list<mixed> $values
	 */
	private static function condition(ISearchComparison $comparison, array $values): bool {
		$needle = $comparison->getValue();

		return match ($comparison->getType()) {
			ISearchComparison::COMPARE_LIKE, ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE => self::likeCondition(
				(string)$needle,
				$comparison->getType() === ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE,
				$values,
			),
			ISearchComparison::COMPARE_EQUAL => self::any($values, static fn (string $v): bool
				=> strcasecmp($v, trim((string)$needle)) === 0),
			ISearchComparison::COMPARE_GREATER_THAN_EQUAL,
			ISearchComparison::COMPARE_LESS_THAN_EQUAL,
			ISearchComparison::COMPARE_GREATER_THAN,
			ISearchComparison::COMPARE_LESS_THAN => self::numericCondition($comparison->getType(), $values, $needle),
			// Never false: an unsupported operator is a bug, not "no results".
			default => throw new \RuntimeException(
				'Cannot evaluate operator "' . $comparison->getType() . '" on field "' . $comparison->getField() . '"',
			),
		};
	}

	/**
	 * @param list<mixed> $values
	 */
	private static function likeCondition(string $pattern, bool $caseSensitive, array $values): bool {
		$regex = self::likeToRegex($pattern) . ($caseSensitive ? '' : 'i');

		return self::any($values, static fn (string $v): bool => preg_match($regex, $v) === 1);
	}

	/**
	 * A SQL LIKE pattern as a regex: `%` becomes "anything", `_` becomes "one character", and
	 * `\%`/`\_`/`\\` are the literal characters, escaped for the regex like everything else.
	 */
	private static function likeToRegex(string $pattern): string {
		$regex = '';
		$length = strlen($pattern);
		for ($i = 0; $i < $length; $i++) {
			$char = $pattern[$i];
			if ($char === '\\' && $i + 1 < $length) {
				$regex .= preg_quote($pattern[++$i], '/');

				continue;
			}
			$regex .= match ($char) {
				'%' => '.*',
				'_' => '.',
				default => preg_quote($char, '/'),
			};
		}

		return '/^' . $regex . '$/';
	}

	/**
	 * @param list<mixed> $values
	 */
	private static function numericCondition(string $type, array $values, mixed $needle): bool {
		$bound = self::toFloat($needle);
		if ($bound === null) {
			// Neither a number nor a date: matches nothing, rather than crashing on a cast.
			return false;
		}

		return match ($type) {
			ISearchComparison::COMPARE_GREATER_THAN_EQUAL => self::anyNumeric($values, static fn (float $v): bool => $v >= $bound),
			ISearchComparison::COMPARE_LESS_THAN_EQUAL => self::anyNumeric($values, static fn (float $v): bool => $v <= $bound),
			ISearchComparison::COMPARE_GREATER_THAN => self::anyNumeric($values, static fn (float $v): bool => $v > $bound),
			ISearchComparison::COMPARE_LESS_THAN => self::anyNumeric($values, static fn (float $v): bool => $v < $bound),
			default => false,
		};
	}

	private static function toFloat(mixed $value): ?float {
		if ($value instanceof \DateTime) {
			return (float)$value->getTimestamp();
		}

		return is_numeric($value) ? (float)$value : null;
	}

	/**
	 * @param list<mixed> $values
	 * @param callable(string): bool $test
	 */
	private static function any(array $values, callable $test): bool {
		foreach ($values as $value) {
			if ($value !== null && !is_array($value) && $test((string)$value)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param list<mixed> $values
	 * @param callable(float): bool $test
	 */
	private static function anyNumeric(array $values, callable $test): bool {
		foreach ($values as $value) {
			if (is_numeric($value) && $test((float)$value)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * A numeric bound every matching item must satisfy, for pushing a range into a backend query —
	 * CalDAV's `event_start`/`event_end` are the current use, since unlike free text they are
	 * indexed at full width and narrowing by them is sound.
	 *
	 * Only conditions conjoined all the way up to the root qualify: under an `or` or a `not`, the
	 * bound constrains one branch rather than the whole result, so using it to narrow the backend
	 * query would drop items that matched some other branch.
	 */
	public static function mandatoryBound(?ISearchOperator $operator, string $field, string $comparisonType): ?int {
		if ($operator === null) {
			return null;
		}

		$bound = null;
		self::eachMandatory($operator, static function (ISearchComparison $c) use ($field, $comparisonType, &$bound): void {
			if ($c->getField() !== $field || $c->getType() !== $comparisonType) {
				return;
			}
			$number = self::toFloat($c->getValue());
			if ($number === null) {
				return;
			}
			$value = (int)$number;
			$bound = $bound === null
				? $value
				: ($comparisonType === ISearchComparison::COMPARE_GREATER_THAN_EQUAL ? max($bound, $value) : min($bound, $value));
		});

		return $bound;
	}

	/**
	 * Visit comparisons that are ANDed all the way to the root.
	 *
	 * @param callable(ISearchComparison): void $fn
	 */
	private static function eachMandatory(ISearchOperator $operator, callable $fn): void {
		if ($operator instanceof ISearchComparison) {
			$fn($operator);

			return;
		}

		if ($operator instanceof ISearchBinaryOperator && $operator->getType() === ISearchBinaryOperator::OPERATOR_AND) {
			foreach ($operator->getArguments() as $child) {
				self::eachMandatory($child, $fn);
			}
		}
	}
}
