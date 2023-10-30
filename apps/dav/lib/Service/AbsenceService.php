<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Service;

use OCA\DAV\Db\Absence;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class AbsenceService {
	public function __construct(
		private AbsenceMapper $absenceMapper,
	) {
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function createOrUpdateAbsence(
		string $userId,
		string $firstDay,
		string $lastDay,
		string $status,
		string $message,
	): Absence {
		try {
			$absence = $this->absenceMapper->findByUserId($userId);
		} catch (DoesNotExistException) {
			$absence = new Absence();
		}

		$absence->setUserId($userId);
		$absence->setFirstDay($firstDay);
		$absence->setLastDay($lastDay);
		$absence->setStatus($status);
		$absence->setMessage($message);

		if ($absence->getId() === null) {
			return $this->absenceMapper->insert($absence);
		}
		return $this->absenceMapper->update($absence);
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function clearAbsence(string $userId): void {
		$this->absenceMapper->deleteByUserId($userId);
	}
}

