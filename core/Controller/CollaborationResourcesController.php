<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use Exception;
use OC\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Collaboration\Resources\CollectionException;
use OCP\Collaboration\Resources\ICollection;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type CoreResource from ResponseDefinitions
 * @psalm-import-type CoreCollection from ResponseDefinitions
 */
class CollaborationResourcesController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IManager $manager,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param int $collectionId
	 * @return ICollection
	 * @throws CollectionException when the collection was not found for the user
	 */
	protected function getCollection(int $collectionId): ICollection {
		$collection = $this->manager->getCollectionForUser($collectionId, $this->userSession->getUser());

		if (!$collection->canAccess($this->userSession->getUser())) {
			throw new CollectionException('Not found');
		}

		return $collection;
	}

	/**
	 * Get a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, list<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/resources/collections/{collectionId}', root: '/collaboration')]
	public function listCollection(int $collectionId): DataResponse {
		try {
			$collection = $this->getCollection($collectionId);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return $this->respondCollection($collection);
	}

	/**
	 * Search for collections
	 *
	 * @param string $filter Filter collections
	 * @return DataResponse<Http::STATUS_OK, list<CoreCollection>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Collections returned
	 * 404: Collection not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/resources/collections/search/{filter}', root: '/collaboration')]
	public function searchCollections(string $filter): DataResponse {
		try {
			$collections = $this->manager->searchCollections($this->userSession->getUser(), $filter);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->prepareCollections($collections));
	}

	/**
	 * Add a resource to a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @param string $resourceType Name of the resource
	 * @param string $resourceId ID of the resource
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, list<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection not found or resource inaccessible
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/resources/collections/{collectionId}', root: '/collaboration')]
	public function addResource(int $collectionId, string $resourceType, string $resourceId): DataResponse {
		try {
			$collection = $this->getCollection($collectionId);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$resource = $this->manager->createResource($resourceType, $resourceId);

		if (!$resource->canAccess($this->userSession->getUser())) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		try {
			$collection->addResource($resource);
		} catch (ResourceException $e) {
		}

		return $this->respondCollection($collection);
	}

	/**
	 * Remove a resource from a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @param string $resourceType Name of the resource
	 * @param string $resourceId ID of the resource
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, list<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection or resource not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/resources/collections/{collectionId}', root: '/collaboration')]
	public function removeResource(int $collectionId, string $resourceType, string $resourceId): DataResponse {
		try {
			$collection = $this->getCollection($collectionId);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		try {
			$resource = $this->manager->getResourceForUser($resourceType, $resourceId, $this->userSession->getUser());
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$collection->removeResource($resource);

		return $this->respondCollection($collection);
	}

	/**
	 * Get collections by resource
	 *
	 * @param string $resourceType Type of the resource
	 * @param string $resourceId ID of the resource
	 * @return DataResponse<Http::STATUS_OK, list<CoreCollection>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Collections returned
	 * 404: Resource not accessible
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/resources/{resourceType}/{resourceId}', root: '/collaboration')]
	public function getCollectionsByResource(string $resourceType, string $resourceId): DataResponse {
		try {
			$resource = $this->manager->getResourceForUser($resourceType, $resourceId, $this->userSession->getUser());
		} catch (ResourceException $e) {
			$resource = $this->manager->createResource($resourceType, $resourceId);
		}

		if (!$resource->canAccess($this->userSession->getUser())) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->prepareCollections($resource->getCollections()));
	}

	/**
	 * Create a collection for a resource
	 *
	 * @param string $baseResourceType Type of the base resource
	 * @param string $baseResourceId ID of the base resource
	 * @param string $name Name of the collection
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, list<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 400: Creating collection is not possible
	 * 404: Resource inaccessible
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/resources/{baseResourceType}/{baseResourceId}', root: '/collaboration')]
	public function createCollectionOnResource(string $baseResourceType, string $baseResourceId, string $name): DataResponse {
		if (!isset($name[0]) || isset($name[64])) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$resource = $this->manager->createResource($baseResourceType, $baseResourceId);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (!$resource->canAccess($this->userSession->getUser())) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$collection = $this->manager->newCollection($name);
		$collection->addResource($resource);

		return $this->respondCollection($collection);
	}

	/**
	 * Rename a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @param string $collectionName New name
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, list<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/resources/collections/{collectionId}', root: '/collaboration')]
	public function renameCollection(int $collectionId, string $collectionName): DataResponse {
		try {
			$collection = $this->getCollection($collectionId);
		} catch (CollectionException $exception) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$collection->setName($collectionName);

		return $this->respondCollection($collection);
	}

	/**
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, list<empty>, array{}>
	 */
	protected function respondCollection(ICollection $collection): DataResponse {
		try {
			return new DataResponse($this->prepareCollection($collection));
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			$this->logger->critical($e->getMessage(), ['exception' => $e, 'app' => 'core']);
			return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @return list<CoreCollection>
	 */
	protected function prepareCollections(array $collections): array {
		$result = [];

		foreach ($collections as $collection) {
			try {
				$result[] = $this->prepareCollection($collection);
			} catch (CollectionException $e) {
			} catch (Exception $e) {
				$this->logger->critical($e->getMessage(), ['exception' => $e, 'app' => 'core']);
			}
		}

		return $result;
	}

	/**
	 * @return CoreCollection
	 */
	protected function prepareCollection(ICollection $collection): array {
		if (!$collection->canAccess($this->userSession->getUser())) {
			throw new CollectionException('Can not access collection');
		}

		return [
			'id' => $collection->getId(),
			'name' => $collection->getName(),
			'resources' => $this->prepareResources($collection->getResources()),
		];
	}

	/**
	 * @return list<CoreResource>
	 */
	protected function prepareResources(array $resources): array {
		$result = [];

		foreach ($resources as $resource) {
			try {
				$result[] = $this->prepareResource($resource);
			} catch (ResourceException $e) {
			} catch (Exception $e) {
				$this->logger->critical($e->getMessage(), ['exception' => $e, 'app' => 'core']);
			}
		}

		return $result;
	}

	/**
	 * @return CoreResource
	 */
	protected function prepareResource(IResource $resource): array {
		if (!$resource->canAccess($this->userSession->getUser())) {
			throw new ResourceException('Can not access resource');
		}

		return $resource->getRichObject();
	}
}
