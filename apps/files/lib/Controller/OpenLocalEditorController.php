<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OCA\Files\Db\OpenLocalEditor;
use OCA\Files\Db\OpenLocalEditorMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class OpenLocalEditorController extends OCSController {
	public const TOKEN_LENGTH = 128;
	public const TOKEN_DURATION = 600; // 10 Minutes
	public const TOKEN_RETRIES = 50;

	public function __construct(
		string $appName,
		IRequest $request,
		protected ITimeFactory $timeFactory,
		protected OpenLocalEditorMapper $mapper,
		protected ISecureRandom $secureRandom,
		protected LoggerInterface $logger,
		protected ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Create a local editor
	 *
	 * @param string $path Path of the file
	 *
	 * @return DataResponse<Http::STATUS_OK, array{userId: ?string, pathHash: string, expirationTime: int, token: string}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, list<empty>, array{}>
	 *
	 * 200: Local editor returned
	 */
	#[NoAdminRequired]
	#[UserRateLimit(limit: 10, period: 120)]
	public function create(string $path): DataResponse {
		$pathHash = sha1($path);

		$entity = new OpenLocalEditor();
		$entity->setUserId($this->userId);
		$entity->setPathHash($pathHash);
		$entity->setExpirationTime($this->timeFactory->getTime() + self::TOKEN_DURATION); // Expire in 10 minutes

		for ($i = 1; $i <= self::TOKEN_RETRIES; $i++) {
			$token = $this->secureRandom->generate(self::TOKEN_LENGTH, ISecureRandom::CHAR_ALPHANUMERIC);
			$entity->setToken($token);

			try {
				$this->mapper->insert($entity);

				return new DataResponse([
					'userId' => $this->userId,
					'pathHash' => $pathHash,
					'expirationTime' => $entity->getExpirationTime(),
					'token' => $entity->getToken(),
				]);
			} catch (Exception $e) {
				if ($e->getCode() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// Only retry on unique constraint violation
					throw $e;
				}
			}
		}

		$this->logger->error('Giving up after ' . self::TOKEN_RETRIES . ' retries to generate a unique local editor token for path hash: ' . $pathHash);
		return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
	}

	/**
	 * Validate a local editor
	 *
	 * @param string $path Path of the file
	 * @param string $token Token of the local editor
	 *
	 * @return DataResponse<Http::STATUS_OK, array{userId: string, pathHash: string, expirationTime: int, token: string}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Local editor validated successfully
	 * 404: Local editor not found
	 */
	#[NoAdminRequired]
	#[BruteForceProtection(action: 'openLocalEditor')]
	public function validate(string $path, string $token): DataResponse {
		$pathHash = sha1($path);

		try {
			$entity = $this->mapper->verifyToken($this->userId, $pathHash, $token);
		} catch (DoesNotExistException $e) {
			$response = new DataResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle(['userId' => $this->userId, 'pathHash' => $pathHash]);
			return $response;
		}

		$this->mapper->delete($entity);

		if ($entity->getExpirationTime() <= $this->timeFactory->getTime()) {
			$response = new DataResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle(['userId' => $this->userId, 'pathHash' => $pathHash]);
			return $response;
		}

		return new DataResponse([
			'userId' => $this->userId,
			'pathHash' => $pathHash,
			'expirationTime' => $entity->getExpirationTime(),
			'token' => $entity->getToken(),
		]);
	}

}
