<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OC\User;

use JsonException;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\User\IAvailabilityCoordinator;
use OCP\User\IOutOfOfficeData;
use Psr\Log\LoggerInterface;

class AvailabilityCoordinator implements IAvailabilityCoordinator {
	private ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private AbsenceMapper $absenceMapper,
		private LoggerInterface $logger,
	) {
		$this->cache = $cacheFactory->createLocal('OutOfOfficeData');
	}

	private function getCachedOutOfOfficeData(IUser $user): ?OutOfOfficeData {
		$cachedString = $this->cache->get($user->getUID());
		if ($cachedString === null) {
			return null;
		}

		try {
			$cachedData = json_decode($cachedString, true, 10, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			$this->logger->error('Failed to deserialize cached out-of-office data: ' . $e->getMessage(), [
				'exception' => $e,
				'json' => $cachedString,
			]);
			return null;
		}

		return new OutOfOfficeData(
			$cachedData['id'],
			$user,
			$cachedData['startDate'],
			$cachedData['endDate'],
			$cachedData['shortMessage'],
			$cachedData['message'],
		);
	}

	private function setCachedOutOfOfficeData(IOutOfOfficeData $data): void {
		try {
			$cachedString = json_encode([
				'id' => $data->getId(),
				'startDate' => $data->getStartDate(),
				'endDate' => $data->getEndDate(),
				'shortMessage' => $data->getShortMessage(),
				'message' => $data->getMessage(),
			], JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			$this->logger->error('Failed to serialize out-of-office data: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return;
		}

		$this->cache->set($data->getUser()->getUID(), $cachedString, 300);
	}

	public function getCurrentOutOfOfficeData(IUser $user): ?IOutOfOfficeData {
		$cachedData = $this->getCachedOutOfOfficeData($user);
		if ($cachedData !== null) {
			return $cachedData;
		}

		try {
			$absenceData = $this->absenceMapper->findByUserId($user->getUID());
		} catch (DoesNotExistException $e) {
			return null;
		}

		$data = $absenceData->toOutOufOfficeData($user);
		$this->setCachedOutOfOfficeData($data);
		return $data;
	}
}
