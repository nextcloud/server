<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use OC\Preview\Failure\PreviewFailureService;
use OC\Preview\PreviewAdminConfig;
use OCA\Settings\Settings\Admin\Previews;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IPreview;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class PreviewAdminController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private PreviewAdminConfig $previewAdminConfig,
		private PreviewFailureService $failureService,
		private IClientService $clientService,
		private IPreview $preview,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param array $settings
	 */
	#[AuthorizedAdminSetting(settings: Previews::class)]
	#[PasswordConfirmationRequired]
	public function update(array $settings): DataResponse {
		try {
			$this->previewAdminConfig->setSettings($settings);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->previewAdminConfig->getSettings());
	}

	#[AuthorizedAdminSetting(settings: Previews::class)]
	public function testImaginary(?string $url = null, ?string $key = null): DataResponse {
		try {
			$target = $this->previewAdminConfig->validateImaginaryUrl($url ?? '');
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([
				'status' => 'unreachable',
				'error' => $e->getMessage(),
			], Http::STATUS_BAD_REQUEST);
		}

		if ($target === '') {
			return new DataResponse([
				'status' => 'unconfigured',
			]);
		}

		try {
			$client = $this->clientService->newClient();
			$options = [
				'timeout' => 3,
				'connect_timeout' => 3,
				'nextcloud' => ['allow_local_address' => true],
			];
			if (is_string($key) && $key !== '') {
				$options['query'] = ['key' => $key];
			}
			$response = $client->get($target, $options);
			$statusCode = $response->getStatusCode();
			$reachable = $statusCode >= 200 && $statusCode < 500;
			return new DataResponse([
				'status' => $reachable ? 'reachable' : 'unreachable',
				'httpCode' => $statusCode,
			]);
		} catch (\Throwable $e) {
			$this->logger->info('Imaginary connection test failed', [
				'exception' => $e,
			]);
			return new DataResponse([
				'status' => 'unreachable',
				'error' => $e->getMessage(),
			]);
		}
	}

	#[AuthorizedAdminSetting(settings: Previews::class)]
	public function listFailures(?string $mime = null, ?string $provider = null, ?string $range = null): DataResponse {
		$since = $this->rangeToSince($range);
		return new DataResponse([
			'failures' => $this->failureService->listFailures($mime, $provider, $since),
		]);
	}

	#[AuthorizedAdminSetting(settings: Previews::class)]
	public function retryFailure(int $id): DataResponse {
		try {
			$failure = $this->failureService->get($id);
		} catch (DoesNotExistException) {
			return new DataResponse(['error' => 'Unknown failure'], Http::STATUS_NOT_FOUND);
		}

		$nodes = $this->rootFolder->getById($failure->getFileId());
		$file = null;
		foreach ($nodes as $node) {
			if ($node instanceof File) {
				$file = $node;
				break;
			}
		}
		if ($file === null) {
			return new DataResponse(['error' => 'File not found'], Http::STATUS_NOT_FOUND);
		}

		try {
			$this->preview->getPreview($file);
			$this->failureService->clearForFile($failure->getFileId());
			return new DataResponse(['status' => 'ok']);
		} catch (NotFoundException|\InvalidArgumentException $e) {
			return new DataResponse([
				'status' => 'failed',
				'error' => $e->getMessage(),
			], Http::STATUS_BAD_REQUEST);
		}
	}

	#[AuthorizedAdminSetting(settings: Previews::class)]
	public function deleteFailure(int $id): DataResponse {
		try {
			$this->failureService->delete($id);
		} catch (DoesNotExistException) {
			return new DataResponse(['error' => 'Unknown failure'], Http::STATUS_NOT_FOUND);
		}
		return new DataResponse(['status' => 'ok']);
	}

	#[AuthorizedAdminSetting(settings: Previews::class)]
	public function clearFailures(): DataResponse {
		$this->failureService->clearAll();
		return new DataResponse(['status' => 'ok']);
	}

	private function rangeToSince(?string $range): ?int {
		return match ($range) {
			'24h' => time() - 86400,
			'7d' => time() - 7 * 86400,
			'30d' => time() - 30 * 86400,
			default => null,
		};
	}
}
