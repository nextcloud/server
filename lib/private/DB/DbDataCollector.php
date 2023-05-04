<?php

declare(strict_types = 1);
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\DB;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;

class DbDataCollector extends \OCP\DataCollector\AbstractDataCollector {
	protected ?BacktraceDebugStack $debugStack = null;
	private Connection $connection;

	/**
	 * DbDataCollector constructor.
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	public function setDebugStack(BacktraceDebugStack $debugStack, $name = 'default'): void {
		$this->debugStack = $debugStack;
	}

	/**
	 * @inheritDoc
	 */
	public function collect(Request $request, Response $response, \Throwable $exception = null): void {
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
		if (null === $query['params']) {
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
			return ['⚠ '.$error->getMessage(), false, false];
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
