<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCP\AppFramework\Db;

use OCP\DB\Exception;
use OCP\IDBConnection;
use Throwable;

/**
 * Helper trait for transactional operations
 *
 * @since 24.0.0
 */
trait TTransactional {
	/**
	 * Run an atomic database operation
	 *
	 * - Commit if no exceptions are thrown, return the callable result
	 * - Revert otherwise and rethrows the exception
	 *
	 * @template T
	 * @param callable $fn
	 * @psalm-param callable():T $fn
	 * @param IDBConnection $db
	 *
	 * @return mixed the result of the passed callable
	 * @psalm-return T
	 *
	 * @throws Exception for possible errors of commit or rollback or the custom operations within the closure
	 * @throws Throwable any other error caused by the closure
	 *
	 * @since 24.0.0
	 * @see https://docs.nextcloud.com/server/latest/developer_manual/basics/storage/database.html#transactions
	 */
	protected function atomic(callable $fn, IDBConnection $db) {
		$db->beginTransaction();
		try {
			$result = $fn();
			$db->commit();
			return $result;
		} catch (Throwable $e) {
			$db->rollBack();
			throw $e;
		}
	}
}
