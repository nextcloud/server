<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Jobs;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\BackgroundService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class RestoreBackgroundImageColor extends QueuedJob {

	public const STAGE_PREPARE = 'prepare';
	public const STAGE_EXECUTE = 'execute';
	// will be saved in appdata/theming/global/
	protected const STATE_FILE_NAME = '30_background_image_color_restoration.json';

	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IAppData $appData,
		private IJobList $jobList,
		private IDBConnection $dbc,
		private LoggerInterface $logger,
		private BackgroundService $service,
	) {
		parent::__construct($time);
	}

	protected function run(mixed $argument): void {
		if (!is_array($argument) || !isset($argument['stage'])) {
			throw new \Exception('Job ' . self::class . ' called with wrong argument');
		}

		switch ($argument['stage']) {
			case self::STAGE_PREPARE:
				$this->runPreparation();
				break;
			case self::STAGE_EXECUTE:
				$this->runMigration();
				break;
			default:
				break;
		}
	}

	protected function runPreparation(): void {
		try {
			$qb = $this->dbc->getQueryBuilder();
			$qb2 = $this->dbc->getQueryBuilder();

			$innerSQL = $qb2->select('userid')
				->from('preferences')
				->where($qb2->expr()->eq('configkey', $qb->createNamedParameter('background_color')));

			// Get those users, that have a background_image set - not the default, but no background_color.
			$result = $qb->selectDistinct('a.userid')
				->from('preferences', 'a')
				->leftJoin('a', $qb->createFunction('(' . $innerSQL->getSQL() . ')'), 'b', 'a.userid = b.userid')
				->where($qb2->expr()->eq('a.configkey', $qb->createNamedParameter('background_image')))
				->andWhere($qb2->expr()->neq('a.configvalue', $qb->createNamedParameter(BackgroundService::BACKGROUND_DEFAULT)))
				->andWhere($qb2->expr()->isNull('b.userid'))
				->executeQuery();

			$userIds = $result->fetchAll(\PDO::FETCH_COLUMN);
			$this->logger->info('Prepare to restore background information for {users} users', ['users' => count($userIds)]);
			$this->storeUserIdsToProcess($userIds);
		} catch (\Throwable $t) {
			$this->jobList->add(self::class, ['stage' => self::STAGE_PREPARE]);
			throw $t;
		}
		$this->jobList->add(self::class, ['stage' => self::STAGE_EXECUTE]);
	}

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	protected function runMigration(): void {
		$allUserIds = $this->readUserIdsToProcess();
		$notSoFastMode = count($allUserIds) > 1000;

		$userIds = array_slice($allUserIds, 0, 1000);
		foreach ($userIds as $userId) {
			$backgroundColor = $this->config->getUserValue($userId, Application::APP_ID, 'background_color');
			if ($backgroundColor !== '') {
				continue;
			}

			$background = $this->config->getUserValue($userId, Application::APP_ID, 'background_image');
			switch ($background) {
				case BackgroundService::BACKGROUND_DEFAULT:
					$this->service->setDefaultBackground($userId);
					break;
				case BackgroundService::BACKGROUND_COLOR:
					break;
				case BackgroundService::BACKGROUND_CUSTOM:
					$this->service->recalculateMeanColor($userId);
					break;
				default:
					// shipped backgrounds
					// do not alter primary color
					$primary = $this->config->getUserValue($userId, Application::APP_ID, 'primary_color');
					if (isset(BackgroundService::SHIPPED_BACKGROUNDS[$background])) {
						$this->service->setShippedBackground($background, $userId);
					} else {
						$this->service->setDefaultBackground($userId);
					}
					// Restore primary
					if ($primary !== '') {
						$this->config->setUserValue($userId, Application::APP_ID, 'primary_color', $primary);
					}
			}
		}

		if ($notSoFastMode) {
			$remainingUserIds = array_slice($allUserIds, 1000);
			$this->storeUserIdsToProcess($remainingUserIds);
			$this->jobList->add(self::class, ['stage' => self::STAGE_EXECUTE]);
		} else {
			$this->deleteStateFile();
		}
	}

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	protected function readUserIdsToProcess(): array {
		$globalFolder = $this->appData->getFolder('global');
		if ($globalFolder->fileExists(self::STATE_FILE_NAME)) {
			$file = $globalFolder->getFile(self::STATE_FILE_NAME);
			try {
				$userIds = \json_decode($file->getContent(), true);
			} catch (NotFoundException $e) {
				$userIds = [];
			}
			if ($userIds === null) {
				$userIds = [];
			}
		} else {
			$userIds = [];
		}
		return $userIds;
	}

	/**
	 * @throws NotFoundException
	 */
	protected function storeUserIdsToProcess(array $userIds): void {
		$storableUserIds = \json_encode($userIds);
		$globalFolder = $this->appData->getFolder('global');
		try {
			if ($globalFolder->fileExists(self::STATE_FILE_NAME)) {
				$file = $globalFolder->getFile(self::STATE_FILE_NAME);
			} else {
				$file = $globalFolder->newFile(self::STATE_FILE_NAME);
			}
			$file->putContent($storableUserIds);
		} catch (NotFoundException $e) {
		} catch (NotPermittedException $e) {
			$this->logger->warning('Lacking permissions to create {file}',
				[
					'app' => 'theming',
					'file' => self::STATE_FILE_NAME,
					'exception' => $e,
				]
			);
		}
	}

	/**
	 * @throws NotFoundException
	 */
	protected function deleteStateFile(): void {
		$globalFolder = $this->appData->getFolder('global');
		if ($globalFolder->fileExists(self::STATE_FILE_NAME)) {
			$file = $globalFolder->getFile(self::STATE_FILE_NAME);
			try {
				$file->delete();
			} catch (NotPermittedException $e) {
				$this->logger->info('Could not delete {file} due to permissions. It is safe to delete manually inside data -> appdata -> theming -> global.',
					[
						'app' => 'theming',
						'file' => $file->getName(),
						'exception' => $e,
					]
				);
			}
		}
	}
}
