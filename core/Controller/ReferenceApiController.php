<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\Collaboration\Reference\IDiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\IRequest;

class ReferenceApiController extends \OCP\AppFramework\OCSController {
	private IReferenceManager $referenceManager;
	private ?string $userId;

	public function __construct(string $appName,
								IRequest $request,
								IReferenceManager $referenceManager,
								?string $userId) {
		parent::__construct($appName, $request);
		$this->referenceManager = $referenceManager;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function extract(string $text, bool $resolve = false, int $limit = 1): DataResponse {
		$references = $this->referenceManager->extractReferences($text);

		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ >= $limit) {
				break;
			}

			$result[$reference] = $resolve ? $this->referenceManager->resolveReference($reference) : null;
		}

		return new DataResponse([
			'references' => $result
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function resolveOne(string $reference): DataResponse {
		$resolvedReference = $this->referenceManager->resolveReference(trim($reference));

		$response = new DataResponse(['references' => [ $reference => $resolvedReference ]]);
		$response->cacheFor(3600, false, true);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string[] $references
	 */
	public function resolve(array $references, int $limit = 1): DataResponse {
		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ >= $limit) {
				break;
			}

			$result[$reference] = $this->referenceManager->resolveReference($reference);
		}

		return new DataResponse([
			'references' => array_filter($result)
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getProvidersInfo(): DataResponse {
		$providers = $this->referenceManager->getDiscoverableProviders();
		$jsonProviders = array_map(static function (IDiscoverableReferenceProvider $provider) {
			return $provider->jsonSerialize();
		}, $providers);
		return new DataResponse($jsonProviders);
	}

	/**
	 * @NoAdminRequired
	 */
	public function touchProvider(string $providerId, ?int $timestamp = null): DataResponse {
		if ($this->userId !== null) {
			$success = $this->referenceManager->touchProvider($this->userId, $providerId, $timestamp);
			return new DataResponse(['success' => $success]);
		}
		return new DataResponse(['success' => false]);
	}
}
