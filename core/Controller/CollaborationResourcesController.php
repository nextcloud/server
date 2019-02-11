<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\DataResponse;
use OCP\Collaboration\Resources\CollectionException;
use OCP\Collaboration\Resources\ICollection;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\IRequest;
use OCP\IUserSession;

class CollaborationResourcesController extends OCSController {

	/** @var IManager */
	private $manager;

	/** @var IUserSession */
	private $userSession;

	public function __construct(
		string $appName,
		IRequest $request,
		IManager $manager,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);

		$this->manager = $manager;
		$this->userSession = $userSession;
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
	 * @param int $collectionId
	 * @return DataResponse
	 */
	public function listCollection(int $collectionId): DataResponse {
		try {
			$collection = $this->getCollection($collectionId);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->prepareCollection($collection));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $filter
	 * @return DataResponse
	 */
	public function searchCollections(string $filter): DataResponse {
		try {
			$collections = $this->manager->searchCollections($this->userSession->getUser(), $filter);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse(array_map([$this, 'prepareCollection'], $collections));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $collectionId
	 * @param string $resourceType
	 * @param string $resourceId
	 * @return DataResponse
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

		return new DataResponse($this->prepareCollection($collection));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $collectionId
	 * @param string $resourceType
	 * @param string $resourceId
	 * @return DataResponse
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

		return new DataResponse($this->prepareCollection($collection));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $resourceType
	 * @param string $resourceId
	 * @return DataResponse
	 */
	public function getCollectionsByResource(string $resourceType, string $resourceId): DataResponse {
		try {
			$resource = $this->manager->getResourceForUser($resourceType, $resourceId, $this->userSession->getUser());
		} catch (ResourceException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (!$resource->canAccess($this->userSession->getUser())) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse(array_map([$this, 'prepareCollection'], $resource->getCollections()));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $baseResourceType
	 * @param string $baseResourceId
	 * @param string $name
	 * @return DataResponse
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

		return new DataResponse($this->prepareCollection($collection));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $collectionId
	 * @param string $collectionName
	 * @return DataResponse
	 */
	public function renameCollection(int $collectionId, string $collectionName): DataResponse {
		try {
			$collection = $this->getCollection($collectionId);
		} catch (CollectionException $exception) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$collection->setName($collectionName);

		return new DataResponse($this->prepareCollection($collection));
	}

	protected function prepareCollection(ICollection $collection): array {
		return [
			'id' => $collection->getId(),
			'name' => $collection->getName(),
			'resources' => array_values(array_filter(array_map([$this, 'prepareResources'], $collection->getResources()))),
		];
	}

	protected function prepareResources(IResource $resource): ?array {
		if (!$resource->canAccess($this->userSession->getUser())) {
			return null;
		}

		return [
			'type' => $resource->getType(),
			'id' => $resource->getId(),
			'name' => $resource->getName(),
			'iconClass' => $resource->getIconClass(),
			'link' => $resource->getLink(),
		];
	}
}
