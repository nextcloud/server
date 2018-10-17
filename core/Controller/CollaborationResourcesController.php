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
		$collection = $this->manager->getCollection($collectionId);

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

		try {
			$resource = $this->manager->getResource($resourceType, $resourceId);
		} catch (ResourceException $e) {
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
			$resource = $this->manager->getResource($resourceType, $resourceId);
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
			$resource = $this->manager->getResource($resourceType, $resourceId);
		} catch (CollectionException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (!$resource->canAccess($this->userSession->getUser())) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse(array_map([$this, 'prepareCollection'], $resource->getCollections()));
	}

	protected function prepareCollection(ICollection $collection): array {
		return array_map([$this, 'prepareResources'], $collection->getResources());
	}

	protected function prepareResources(IResource $resource): array {
		return [
			'type' => $resource->getType(),
			'id' => $resource->getId(),
			'name' => $resource->getName(),
		];
	}
}
