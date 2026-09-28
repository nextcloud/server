<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\BackgroundJob;

use OC\Files\SetupManager;
use OCA\Files\AppInfo\Application;
use OCA\Files\Service\SettingsService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\Config\IUserConfig;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IFilenameValidator;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;

class SanitizeFilenames extends QueuedJob {

	private int $offset;
	private int $limit;
	private int $currentIndex;
	private ?string $charReplacement = null;

	public function __construct(
		ITimeFactory $time,
		private IJobList $jobList,
		private IUserSession $session,
		private IUserManager $manager,
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
		private IRootFolder $rootFolder,
		private SetupManager $setupManager,
		private IFilenameValidator $filenameValidator,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setAllowParallelRuns(false);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 * @throws \Exception
	 */
	public function run($argument) {
		$this->charReplacement = strval($argument['charReplacement']) ?: null;
		if (isset($argument['errorsOnly'])) {
			$this->retryFailedNodes();
			return;
		}

		$this->offset = intval($argument['offset']);
		$this->limit = intval($argument['limit']);
		if ($this->offset === 0) {
			$this->appConfig->setAppValueInt('sanitize_filenames_status', SettingsService::STATUS_WCF_RUNNING);
		}

		$this->currentIndex = 0;
		foreach ($this->manager->getSeenUsers($this->offset) as $user) {
			$this->sanitizeUserFiles($user);
			$this->currentIndex++;
			$this->appConfig->setAppValueInt('sanitize_filenames_index', $this->currentIndex);

			if ($this->currentIndex === $this->limit) {
				break;
			}
		}

		if ($this->currentIndex === $this->limit) {
			$this->offset += $this->limit;
			$this->jobList->add(self::class, ['limit' => $this->limit, 'offset' => $this->offset, 'charReplacement' => $this->charReplacement]);
			return;
		}

		// No index to process anymore, we are done
		$this->appConfig->deleteAppValue('sanitize_filenames_index');

		$hasErrors = !empty($this->userConfig->getValuesByUsers(Application::APP_ID, 'sanitize_filenames_errors'));
		if ($hasErrors) {
			$this->logger->info('Filename sanitization finished with errors. Retrying failed files in next background job run.');
			$this->jobList->add(self::class, ['errorsOnly' => true, 'charReplacement' => $this->charReplacement]);
			return;
		}

		// we are really done!
		$this->appConfig->setAppValueInt('sanitize_filenames_status', SettingsService::STATUS_WCF_DONE);
	}

	/**
	 * Retry to sanitize files that failed in the first run
	 */
	private function retryFailedNodes(): void {
		$this->logger->debug('Retry sanitizing failed filename sanitization.');
		$results = $this->userConfig->getValuesByUsers(Application::APP_ID, 'sanitize_filenames_errors');

		$hasErrors = false;
		foreach ($results as $userId => $errors) {
			$user = $this->manager->get($userId);
			if ($user === null) {
				// user got deleted meanwhile, ignore
				continue;
			}

			$hasErrors = $hasErrors || $this->retryFailedUserNodes($user, $errors);
			$this->userConfig->deleteUserConfig($userId, Application::APP_ID, 'sanitize_filenames_errors');
		}

		if ($hasErrors) {
			$this->appConfig->setAppValueInt('sanitize_filenames_status', SettingsService::STATUS_WCF_ERROR);
			$this->logger->error('Retrying filename sanitization failed permanently.');
		} else {
			$this->appConfig->setAppValueInt('sanitize_filenames_status', SettingsService::STATUS_WCF_DONE);
			$this->logger->info('Retrying filename sanitization succeeded.');
		}
	}

	private function retryFailedUserNodes(IUser $user, array $errors): bool {
		$this->session->setVolatileActiveUser($user);
		$folder = $this->rootFolder->getUserFolder($user->getUID());

		$this->logger->debug("filename sanitization retry: started for user '{$user->getUID()}'");
		$hasErrors = false;
		foreach ($errors as $path) {
			try {
				$node = $folder->get($path);
				$this->sanitizeNode($node);
			} catch (NotFoundException) {
				// file got deleted meanwhile, ignore
			} catch (\Exception $error) {
				$this->logger->error('filename sanitization failed when retried: ' . $path, ['exception' => $error]);
				$hasErrors = true;
			}
		}

		// tear down FS for user to make sure we do not run out of memory due to cached user FS
		$this->setupManager->tearDown();

		return $hasErrors;
	}


	private function sanitizeUserFiles(IUser $user): void {
		// Set an active user so that event listeners can correctly work (e.g. files versions)
		$this->session->setVolatileActiveUser($user);
		$folder = $this->rootFolder->getUserFolder($user->getUID());

		$this->logger->debug("filename sanitization: started for user '{$user->getUID()}'");
		$errors = $this->sanitizeFolder($folder);

		// tear down FS for user to make sure we do not run out of memory due to cached user FS
		$this->setupManager->tearDown();

		if (!empty($errors)) {
			$this->userConfig->setValueArray($user->getUID(), 'files', 'sanitize_filenames_errors', $errors, true);
		}
	}

	/**
	 * Sanitizes the filenames of all nodes in a folder
	 *
	 * @return list<string> list of nodes that could not be sanitized
	 */
	private function sanitizeFolder(Folder $folder): array {
		$errors = [];
		foreach ($folder->getDirectoryListing() as $node) {
			try {
				$this->sanitizeNode($node);
			} catch (LockedException) {
				$this->logger->debug('filename sanitization skipped: ' . $node->getPath() . ' (file is locked)');
				$errors[] = $node->getPath();
			} catch (\Exception $error) {
				$this->logger->warning('filename sanitization failed: ' . $node->getPath(), ['exception' => $error]);
				$errors[] = $node->getPath();
			}

			if ($node instanceof Folder) {
				$errors = array_merge($errors, $this->sanitizeFolder($node));
			}
		}
		return $errors;
	}

	/**
	 * Sanitizes the filename of a single node
	 *
	 * @throws LockedException If the file is locked
	 * @throws \Exception Unknown error
	 */
	private function sanitizeNode(Node $node): void {
		if ($node->isShared() && !$node->isUpdateable()) {
			// we cannot rename files in shares where we do not have permissions - we do it when sanitizing the owner's files
			return;
		}

		try {
			$oldName = $node->getName();
			$newName = $this->filenameValidator->sanitizeFilename($oldName, $this->charReplacement);
			if ($oldName !== $newName) {
				$newName = $node->getParent()->getNonExistingName($newName);
				$path = rtrim(dirname($node->getPath()), '/');

				$node->move("$path/$newName");
			}
		} catch (NotFoundException) {
			// file got deleted meanwhile, ignore
			// or this is shared without permissions to rename it, ignore (owner will rename it)
		}
	}
}
