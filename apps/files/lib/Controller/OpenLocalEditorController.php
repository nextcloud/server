<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Controller;

use OCA\Files\Db\OpenLocalEditor;
use OCA\Files\Db\OpenLocalEditorMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
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

	protected ITimeFactory $timeFactory;
	protected OpenLocalEditorMapper $mapper;
	protected ISecureRandom $secureRandom;
	protected LoggerInterface $logger;
	protected ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		ITimeFactory $timeFactory,
		OpenLocalEditorMapper $mapper,
		ISecureRandom $secureRandom,
		LoggerInterface $logger,
		?string $userId
	) {
		parent::__construct($appName, $request);

		$this->timeFactory = $timeFactory;
		$this->mapper = $mapper;
		$this->secureRandom = $secureRandom;
		$this->logger = $logger;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=10, period=120)
	 *
	 * Create a local editor
	 *
	 * @param string $path Path of the file
	 *
	 * @return DataResponse<Http::STATUS_OK, array{userId: ?string, pathHash: string, expirationTime: int, token: string}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array<empty>, array{}>
	 *
	 * 200: Local editor returned
	 */
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
	 * @NoAdminRequired
	 * @BruteForceProtection(action=openLocalEditor)
	 *
	 * Validate a local editor
	 *
	 * @param string $path Path of the file
	 * @param string $token Token of the local editor
	 *
	 * @return DataResponse<Http::STATUS_OK, array{userId: string, pathHash: string, expirationTime: int, token: string}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Local editor validated successfully
	 * 404: Local editor not found
	 */
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
