<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use OC\AppFramework\Http\Request;
use OC\Diagnostics\Query;
use OCP\AppFramework\Http\Response;
use OCP\Diagnostics\IQueryLogger;

class DbDataCollector extends \OCP\DataCollector\AbstractDataCollector {
	public function __construct(
		private readonly IQueryLogger $queryLogger,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {
		$queries = $this->sanitizeQueries($this->queryLogger->getQueries());

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

	private function sanitizeQuery(Query $queryObject): array {
		$query = [
			'sql' => $queryObject->getSql(),
			'params' => $queryObject->getParams() ?? [],
			'types' => $queryObject->getTypes() ?? [],
			'executionMS' => $queryObject->getDuration(),
			'backtrace' => $queryObject->getStacktrace(),
			'explainable' => true,
			'runnable' => true,
		];

		foreach ($query['params'] as $j => $param) {
			[$query['params'][$j], $explainable, $runnable] = $this->sanitizeParam($param);
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
	private function sanitizeParam($var): array {
		if (\is_object($var)) {
			return [$o = new ObjectParameter($var, null), false, $o->isStringable()];
		}

		if (\is_array($var)) {
			$a = [];
			$explainable = $runnable = true;
			foreach ($var as $k => $v) {
				[$value, $e, $r] = $this->sanitizeParam($v);
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
