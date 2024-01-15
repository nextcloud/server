<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 *
 */

namespace OC\Core\Controller;

use Exception;
use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
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
	 * @NoAdminRequired
	 *
	 * Get a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection not found
	 */
	public function listCollection(int $collectionId): DataResponse {
		try {
			$collection = $this->getCollection($collectionId);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return $this->respondCollection($collection);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Search for collections
	 *
	 * @param string $filter Filter collections
	 * @return DataResponse<Http::STATUS_OK, CoreCollection[], array{}>|DataResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Collections returned
	 * 404: Collection not found
	 */
	public function searchCollections(string $filter): DataResponse {
		try {
			$collections = $this->manager->searchCollections($this->userSession->getUser(), $filter);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->prepareCollections($collections));
	}

	/**
	 * @NoAdminRequired
	 *
	 * Add a resource to a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @param string $resourceType Name of the resource
	 * @param string $resourceId ID of the resource
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection not found or resource inaccessible
	 */
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
	 * @NoAdminRequired
	 *
	 * Remove a resource from a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @param string $resourceType Name of the resource
	 * @param string $resourceId ID of the resource
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection or resource not found
	 */
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
	 * @NoAdminRequired
	 *
	 * Get collections by resource
	 *
	 * @param string $resourceType Type of the resource
	 * @param string $resourceId ID of the resource
	 * @return DataResponse<Http::STATUS_OK, CoreCollection[], array{}>|DataResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Collections returned
	 * 404: Resource not accessible
	 */
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
	 * @NoAdminRequired
	 *
	 * Create a collection for a resource
	 *
	 * @param string $baseResourceType Type of the base resource
	 * @param string $baseResourceId ID of the base resource
	 * @param string $name Name of the collection
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 400: Creating collection is not possible
	 * 404: Resource inaccessible
	 */
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
	 * @NoAdminRequired
	 *
	 * Rename a collection
	 *
	 * @param int $collectionId ID of the collection
	 * @param string $collectionName New name
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array<empty>, array{}>
	 *
	 * 200: Collection returned
	 * 404: Collection not found
	 */
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
	 * @return DataResponse<Http::STATUS_OK, CoreCollection, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array<empty>, array{}>
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
	 * @return CoreCollection[]
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
	 * @return CoreResource[]
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
