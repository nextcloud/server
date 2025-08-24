<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User;

use JsonException;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Service\AbsenceService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\User\IAvailabilityCoordinator;
use OCP\User\IOutOfOfficeData;
use Psr\Log\LoggerInterface;

class AvailabilityCoordinator implements IAvailabilityCoordinator {
	private ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private IConfig $config,
		private AbsenceService $absenceService,
		private LoggerInterface $logger,
		private TimezoneService $timezoneService,
	) {
		$this->cache = $cacheFactory->createLocal('OutOfOfficeData');
	}

	public function isEnabled(): bool {
		return $this->config->getAppValue(Application::APP_ID, 'hide_absence_settings', 'no') === 'no';
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
			$cachedData['replacementUserId'],
			$cachedData['replacementUserDisplayName'],
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
				'replacementUserId' => $data->getReplacementUserId(),
				'replacementUserDisplayName' => $data->getReplacementUserDisplayName(),
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
		$timezone = $this->getCachedTimezone($user->getUID());
		if ($timezone === null) {
			$timezone = $this->timezoneService->getUserTimezone($user->getUID()) ?? $this->timezoneService->getDefaultTimezone();
			$this->setCachedTimezone($user->getUID(), $timezone);
		}

		$data = $this->getCachedOutOfOfficeData($user);
		if ($data === null) {
			$absenceData = $this->absenceService->getAbsence($user->getUID());
			if ($absenceData === null) {
				return null;
			}
			$data = $absenceData->toOutOufOfficeData($user, $timezone);
		}

		$this->setCachedOutOfOfficeData($data);
		return $data;
	}

	private function getCachedTimezone(string $userId): ?string {
		return $this->cache->get($userId . '_timezone') ?? null;
	}

	private function setCachedTimezone(string $userId, string $timezone): void {
		$this->cache->set($userId . '_timezone', $timezone, 3600);
	}

	public function clearCache(string $userId): void {
		$this->cache->set($userId, null, 300);
		$this->cache->set($userId . '_timezone', null, 3600);
	}

	public function isInEffect(IOutOfOfficeData $data): bool {
		return $this->absenceService->isInEffect($data);
	}
}
