<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Failure;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\IConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class PreviewFailureService {
	public const DEFAULT_RETENTION_DAYS = 30;
	public const DEFAULT_MAX_ROWS = 5000;

	public function __construct(
		private readonly PreviewFailureMapper $mapper,
		private readonly ITimeFactory $timeFactory,
		private readonly IConfig $config,
		private readonly IURLGenerator $urlGenerator,
		private readonly LoggerInterface $logger,
	) {
	}

	public function record(File $file, string $mime, ?string $provider, string $error, bool $onlyIfMissing = false): void {
		try {
			$fileId = $file->getId();
			if ($fileId === null || $fileId <= 0) {
				return;
			}
			$now = $this->timeFactory->getTime();
			$userId = null;
			try {
				$userId = $file->getOwner()?->getUID();
			} catch (\Throwable) {
				$userId = null;
			}
			$path = null;
			try {
				$path = $file->getPath();
			} catch (\Throwable) {
				$path = null;
			}

			$error = $this->sanitizeError($error);

			try {
				$existing = $this->mapper->findByFileId($fileId);
				if ($onlyIfMissing) {
					return;
				}
				$existing->setMime($mime);
				$existing->setProvider($provider);
				$existing->setError($error);
				$existing->setAttempts($existing->getAttempts() + 1);
				$existing->setLastAttempt($now);
				if ($userId !== null) {
					$existing->setUserId($userId);
				}
				if ($path !== null) {
					$existing->setPath($path);
				}
				$this->mapper->update($existing);
			} catch (DoesNotExistException) {
				$failure = new PreviewFailure();
				$failure->setFileId($fileId);
				$failure->setUserId($userId);
				$failure->setPath($path);
				$failure->setMime($mime);
				$failure->setProvider($provider);
				$failure->setError($error);
				$failure->setAttempts(1);
				$failure->setLastAttempt($now);
				$failure->setCreatedAt($now);
				$this->mapper->insert($failure);
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Could not record preview generation failure', [
				'exception' => $e,
			]);
		}
	}

	/**
	 * Record a failed HTTP preview request when Generator did not already store a row.
	 */
	public function recordFromFailedRequest(File $file, string $error): void {
		$message = $error !== '' ? $error : 'Preview request failed';
		try {
			$this->record($file, $file->getMimeType(), null, $message, true);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not record failed preview request', [
				'exception' => $e,
			]);
		}
	}

	public function clearForFile(int $fileId): void {
		try {
			$this->mapper->deleteByFileId($fileId);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not clear preview generation failure', [
				'exception' => $e,
			]);
		}
	}

	public function get(int $id): PreviewFailure {
		return $this->mapper->find($id);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function listFailures(?string $mime = null, ?string $provider = null, ?int $since = null, int $limit = 100): array {
		$limit = max(1, min(500, $limit));
		$rows = [];
		foreach ($this->mapper->search($mime, $provider, $since, $limit) as $failure) {
			$rows[] = $this->toArray($failure);
		}
		return $rows;
	}

	public function delete(int $id): void {
		$failure = $this->mapper->find($id);
		$this->mapper->delete($failure);
	}

	public function clearAll(): void {
		$this->mapper->deleteAll();
	}

	public function cleanup(): void {
		$days = $this->config->getSystemValueInt('preview_failures_retention_days', self::DEFAULT_RETENTION_DAYS);
		if ($days > 0) {
			$cutoff = $this->timeFactory->getTime() - ($days * 86400);
			$this->mapper->deleteOlderThan($cutoff);
		}
		$maxRows = $this->config->getSystemValueInt('preview_failures_max_rows', self::DEFAULT_MAX_ROWS);
		$this->mapper->pruneToMaxRows($maxRows);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(PreviewFailure $failure): array {
		$fileId = $failure->getFileId();
		return [
			'id' => $failure->getId(),
			'fileId' => $fileId,
			'userId' => $failure->getUserId(),
			'path' => $failure->getPath(),
			'mime' => $failure->getMime(),
			'provider' => $failure->getProvider(),
			'error' => $failure->getError(),
			'attempts' => $failure->getAttempts(),
			'lastAttempt' => $failure->getLastAttempt(),
			'createdAt' => $failure->getCreatedAt(),
			'fileUrl' => $this->urlGenerator->linkToRoute('files.viewcontroller.showFile', ['fileid' => $fileId]),
		];
	}

	private function sanitizeError(string $error): string {
		$error = preg_replace('/key=[^&\s]+/i', 'key=***', $error) ?? $error;
		$error = preg_replace('#https?://[^\s]+#i', '[url]', $error) ?? $error;
		if (function_exists('mb_substr')) {
			return mb_substr($error, 0, 2000);
		}
		return substr($error, 0, 2000);
	}
}
