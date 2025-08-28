<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Service;

use OC\Files\FilenameValidator;
use OCA\Files\AppInfo\Application;
use OCA\Files\BackgroundJob\SanitizeFilenames;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\Config\IUserConfig;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class SettingsService {

	protected const WINDOWS_EXTENSION = [
		' ',
		'.',
	];

	protected const WINDOWS_BASENAMES = [
		'con', 'prn', 'aux', 'nul', 'com0', 'com1', 'com2', 'com3', 'com4', 'com5',
		'com6', 'com7', 'com8', 'com9', 'com¹', 'com²', 'com³', 'lpt0', 'lpt1', 'lpt2',
		'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9', 'lpt¹', 'lpt²', 'lpt³',
	];

	protected const WINDOWS_CHARACTERS = [
		'<', '>', ':',
		'"', '|', '?',
		'*',
	];

	public const STATUS_WCF_UNKNOWN = 0;
	public const STATUS_WCF_SCHEDULED = 1;
	public const STATUS_WCF_RUNNING = 2;
	public const STATUS_WCF_DONE = 3;
	public const STATUS_WCF_ERROR = 4;

	public function __construct(
		private IConfig $config,
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
		private FilenameValidator $filenameValidator,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private IJobList $jobList,
	) {
	}

	public function hasFilesWindowsSupport(): bool {
		return empty(array_diff(self::WINDOWS_BASENAMES, $this->filenameValidator->getForbiddenBasenames()))
			&& empty(array_diff(self::WINDOWS_CHARACTERS, $this->filenameValidator->getForbiddenCharacters()))
			&& empty(array_diff(self::WINDOWS_EXTENSION, $this->filenameValidator->getForbiddenExtensions()));
	}

	public function setFilesWindowsSupport(bool $enabled = true): void {
		if ($enabled) {
			$basenames = array_unique(array_merge(self::WINDOWS_BASENAMES, $this->filenameValidator->getForbiddenBasenames()));
			$characters = array_unique(array_merge(self::WINDOWS_CHARACTERS, $this->filenameValidator->getForbiddenCharacters()));
			$extensions = array_unique(array_merge(self::WINDOWS_EXTENSION, $this->filenameValidator->getForbiddenExtensions()));
		} else {
			$basenames = array_unique(array_values(array_diff($this->filenameValidator->getForbiddenBasenames(), self::WINDOWS_BASENAMES)));
			$characters = array_unique(array_values(array_diff($this->filenameValidator->getForbiddenCharacters(), self::WINDOWS_CHARACTERS)));
			$extensions = array_unique(array_values(array_diff($this->filenameValidator->getForbiddenExtensions(), self::WINDOWS_EXTENSION)));
		}
		$values = [
			'forbidden_filename_basenames' => empty($basenames) ? null : $basenames,
			'forbidden_filename_characters' => empty($characters) ? null : $characters,
			'forbidden_filename_extensions' => empty($extensions) ? null : $extensions,
		];
		$this->config->setSystemValues($values);

		// reset any sanitization status
		$this->appConfig->deleteAppValue('sanitize_filenames_status');
		$this->appConfig->deleteAppValue('sanitize_filenames_index');
		$this->userConfig->deleteKey(Application::APP_ID, 'sanitize_filenames_errors');
	}

	public function isFilenameSanitizationRunning(): bool {
		$jobs = $this->jobList->getJobsIterator(SanitizeFilenames::class, 1, 0);
		foreach ($jobs as $job) {
			return true;
		}
		return false;
	}

	/**
	 * Get the current status of the filename sanitization.
	 *
	 * @psalm-return array{total: int, processed: int, status: self::STATUS_WCF_*, errors: array<string, string>}
	 */
	public function getSanitizationStatus(): array {
		/** @var self::STATUS_WCF_* */
		$status = $this->appConfig->getAppValueInt('sanitize_filenames_status');
		$index = $this->appConfig->getAppValueInt('sanitize_filenames_index', -1);
		$total = $this->userManager->countSeenUsers();
		/** @var array<string, string> */
		$errors = $this->userConfig->getValuesByUsers(Application::APP_ID, 'sanitize_filenames_errors');

		if ($status === 0 && $this->isFilenameSanitizationRunning()) {
			$status = 1; // we know its scheduled
		}

		return ['status' => $status, 'processed' => $index, 'total' => $total, 'errors' => $errors];
	}
}
