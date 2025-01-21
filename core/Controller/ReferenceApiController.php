<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
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
	private const LIMIT_MAX = 15;

	public function __construct(
		string $appName,
		IRequest $request,
		private IReferenceManager $referenceManager,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Extract references from a text
	 *
	 * @param string $text Text to extract from
	 * @param bool $resolve Resolve the references
	 * @param int $limit Maximum amount of references to extract
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, CoreReference|null>}, array{}>
	 *
	 * 200: References returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/extract', root: '/references')]
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
	 * Extract references from a text
	 *
	 * @param string $text Text to extract from
	 * @param string $sharingToken Token of the public share
	 * @param bool $resolve Resolve the references
	 * @param int $limit Maximum amount of references to extract, limited to 15
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, CoreReference|null>}, array{}>
	 *
	 * 200: References returned
	 */
	#[ApiRoute(verb: 'POST', url: '/extractPublic', root: '/references')]
	#[PublicPage]
	#[AnonRateLimit(limit: 10, period: 120)]
	public function extractPublic(string $text, string $sharingToken, bool $resolve = false, int $limit = 1): DataResponse {
		$references = $this->referenceManager->extractReferences($text);

		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ >= min($limit, self::LIMIT_MAX)) {
				break;
			}

			$result[$reference] = $resolve ? $this->referenceManager->resolveReference($reference, true, $sharingToken)?->jsonSerialize() : null;
		}

		return new DataResponse([
			'references' => $result
		]);
	}

	/**
	 * Resolve a reference
	 *
	 * @param string $reference Reference to resolve
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, ?CoreReference>}, array{}>
	 *
	 * 200: Reference returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/resolve', root: '/references')]
	public function resolveOne(string $reference): DataResponse {
		/** @var ?CoreReference $resolvedReference */
		$resolvedReference = $this->referenceManager->resolveReference(trim($reference))?->jsonSerialize();

		$response = new DataResponse(['references' => [$reference => $resolvedReference]]);
		$response->cacheFor(3600, false, true);
		return $response;
	}

	/**
	 * Resolve from a public page
	 *
	 * @param string $reference Reference to resolve
	 * @param string $sharingToken Token of the public share
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, ?CoreReference>}, array{}>
	 *
	 * 200: Reference returned
	 */
	#[ApiRoute(verb: 'GET', url: '/resolvePublic', root: '/references')]
	#[PublicPage]
	#[AnonRateLimit(limit: 25, period: 120)]
	public function resolveOnePublic(string $reference, string $sharingToken): DataResponse {
		/** @var ?CoreReference $resolvedReference */
		$resolvedReference = $this->referenceManager->resolveReference(trim($reference), true, trim($sharingToken))?->jsonSerialize();

		$response = new DataResponse(['references' => [$reference => $resolvedReference]]);
		$response->cacheFor(3600, false, true);
		return $response;
	}

	/**
	 * Resolve multiple references
	 *
	 * @param list<string> $references References to resolve
	 * @param int $limit Maximum amount of references to resolve
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, CoreReference|null>}, array{}>
	 *
	 * 200: References returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/resolve', root: '/references')]
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
	 * Resolve multiple references from a public page
	 *
	 * @param list<string> $references References to resolve
	 * @param string $sharingToken Token of the public share
	 * @param int $limit Maximum amount of references to resolve, limited to 15
	 * @return DataResponse<Http::STATUS_OK, array{references: array<string, CoreReference|null>}, array{}>
	 *
	 * 200: References returned
	 */
	#[ApiRoute(verb: 'POST', url: '/resolvePublic', root: '/references')]
	#[PublicPage]
	#[AnonRateLimit(limit: 10, period: 120)]
	public function resolvePublic(array $references, string $sharingToken, int $limit = 1): DataResponse {
		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ >= min($limit, self::LIMIT_MAX)) {
				break;
			}

			$result[$reference] = $this->referenceManager->resolveReference($reference, true, $sharingToken)?->jsonSerialize();
		}

		return new DataResponse([
			'references' => $result
		]);
	}

	/**
	 * Get the providers
	 *
	 * @return DataResponse<Http::STATUS_OK, list<CoreReferenceProvider>, array{}>
	 *
	 * 200: Providers returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/providers', root: '/references')]
	public function getProvidersInfo(): DataResponse {
		$providers = $this->referenceManager->getDiscoverableProviders();
		$jsonProviders = array_values(array_map(static function (IDiscoverableReferenceProvider $provider) {
			return $provider->jsonSerialize();
		}, $providers));
		return new DataResponse($jsonProviders);
	}

	/**
	 * Touch a provider
	 *
	 * @param string $providerId ID of the provider
	 * @param int|null $timestamp Timestamp of the last usage
	 * @return DataResponse<Http::STATUS_OK, array{success: bool}, array{}>
	 *
	 * 200: Provider touched
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/provider/{providerId}', root: '/references')]
	public function touchProvider(string $providerId, ?int $timestamp = null): DataResponse {
		if ($this->userId !== null) {
			$success = $this->referenceManager->touchProvider($this->userId, $providerId, $timestamp);
			return new DataResponse(['success' => $success]);
		}
		return new DataResponse(['success' => false]);
	}
}
