<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;

class DbDataCollector extends AbstractDataCollector {
	protected ?BacktraceDebugStack $debugStack = null;

	/**
	 * DbDataCollector constructor.
	 */
	public function __construct(
		private Connection $connection,
	) {
	}

	public function setDebugStack(BacktraceDebugStack $debugStack, $name = 'default'): void {
		$this->debugStack = $debugStack;
	}

	/**
	 * @inheritDoc
	 */
	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {
		$queries = $this->sanitizeQueries($this->debugStack->queries);

		$this->data = [
			'queries' => $queries,
		];
	}

	public function getName(): string {
		return 'db';
	}

	public function getQueries(): array {
		return $this->data['queries'];
	}

	private function sanitizeQueries(array $queries): array {
		foreach ($queries as $i => $query) {
			$queries[$i] = $this->sanitizeQuery($query);
		}

		return $queries;
	}

	private function sanitizeQuery(array $query): array {
		$query['explainable'] = true;
		$query['runnable'] = true;
		if ($query['params'] === null) {
			$query['params'] = [];
		}
		if (!\is_array($query['params'])) {
			$query['params'] = [$query['params']];
		}
		if (!\is_array($query['types'])) {
			$query['types'] = [];
		}
		foreach ($query['params'] as $j => $param) {
			$e = null;
			if (isset($query['types'][$j])) {
				// Transform the param according to the type
				$type = $query['types'][$j];
				if (\is_string($type)) {
					$type = Type::getType($type);
				}
				if ($type instanceof Type) {
					$query['types'][$j] = $type->getBindingType();
					try {
						$param = $type->convertToDatabaseValue($param, $this->connection->getDatabasePlatform());
					} catch (\TypeError $e) {
					} catch (ConversionException $e) {
					}
				}
			}

			[$query['params'][$j], $explainable, $runnable] = $this->sanitizeParam($param, $e);
			if (!$explainable) {
				$query['explainable'] = false;
			}

			if (!$runnable) {
				$query['runnable'] = false;
			}
		}

		return $query;
	}

	/**
	 * Sanitizes a param.
	 *
	 * The return value is an array with the sanitized value and a boolean
	 * indicating if the original value was kept (allowing to use the sanitized
	 * value to explain the query).
	 */
	private function sanitizeParam($var, ?\Throwable $error): array {
		if (\is_object($var)) {
			return [$o = new ObjectParameter($var, $error), false, $o->isStringable() && !$error];
		}

		if ($error) {
			return ['⚠ ' . $error->getMessage(), false, false];
		}

		if (\is_array($var)) {
			$a = [];
			$explainable = $runnable = true;
			foreach ($var as $k => $v) {
				[$value, $e, $r] = $this->sanitizeParam($v, null);
				$explainable = $explainable && $e;
				$runnable = $runnable && $r;
				$a[$k] = $value;
			}

			return [$a, $explainable, $runnable];
		}

		if (\is_resource($var)) {
			return [sprintf('/* Resource(%s) */', get_resource_type($var)), false, false];
		}

		return [$var, true, true];
	}
}
