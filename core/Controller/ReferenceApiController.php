<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Collaboration\Reference\IDiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\Reference;
use OCP\IRequest;

/**
 * @psalm-import-type CoreReference from ResponseDefinitions
 * @psalm-import-type CoreReferenceProvider from ResponseDefinitions
 */
class ReferenceApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IReferenceManager $referenceManager,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Extract references from a text
	 *
	 * @param string $text Text to extract from
	 * @param bool $resolve Resolve the references
	 * @param int $limit Maximum amount of references to extract
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, CoreReference|null>}, array{}>
	 *
	 * 200: References returned
	 */
	public function extract(string $text, bool $resolve = false, int $limit = 1): DataResponse {
		$references = $this->referenceManager->extractReferences($text);

		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ >= $limit) {
				break;
			}

			$result[$reference] = $resolve ? $this->referenceManager->resolveReference($reference)->jsonSerialize() : null;
		}

		return new DataResponse([
			'references' => $result
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Resolve a reference
	 *
	 * @param string $reference Reference to resolve
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, ?CoreReference>}, array{}>
	 *
	 * 200: Reference returned
	 */
	public function resolveOne(string $reference): DataResponse {
		/** @var ?CoreReference $resolvedReference */
		$resolvedReference = $this->referenceManager->resolveReference(trim($reference))?->jsonSerialize();

		$response = new DataResponse(['references' => [$reference => $resolvedReference]]);
		$response->cacheFor(3600, false, true);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 *
	 * Resolve multiple references
	 *
	 * @param string[] $references References to resolve
	 * @param int $limit Maximum amount of references to resolve
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, CoreReference|null>}, array{}>
	 *
	 * 200: References returned
	 */
	public function resolve(array $references, int $limit = 1): DataResponse {
		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ >= $limit) {
				break;
			}

			$result[$reference] = $this->referenceManager->resolveReference($reference)?->jsonSerialize();
		}

		return new DataResponse([
			'references' => $result
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get the providers
	 *
	 * @return DataResponse<Http::STATUS_OK, CoreReferenceProvider[], array{}>
	 *
	 * 200: Providers returned
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
	 *
	 * Touch a provider
	 *
	 * @param string $providerId ID of the provider
	 * @param int|null $timestamp Timestamp of the last usage
	 * @return DataResponse<Http::STATUS_OK, array{success: bool}, array{}>
	 *
	 * 200: Provider touched
	 */
	public function touchProvider(string $providerId, ?int $timestamp = null): DataResponse {
		if ($this->userId !== null) {
			$success = $this->referenceManager->touchProvider($this->userId, $providerId, $timestamp);
			return new DataResponse(['success' => $success]);
		}
		return new DataResponse(['success' => false]);
	}
}
