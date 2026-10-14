<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OCA\Files\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\Files\NodeAttributes\INodeSerializer;
use OCP\Files\NodeAttributes\NodeAttributeContext;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

/**
 * @psalm-import-type FilesNode from ResponseDefinitions
 */
class NodesApiController extends OCSController {
	public const MAX_IDS = 100;

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IRootFolder $rootFolder,
		private readonly INodeSerializer $serializer,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get files and folders by id, with the same data as a PROPFIND
	 *
	 * @param list<int> $ids File ids, at most 100
	 * @param string|null $dir Only return the paths within this folder of the user, the folder itself included
	 * @param list<string>|null $attributes Attributes to include, in Clark notation like the WebDAV properties. All of them if null
	 * @return DataResponse<Http::STATUS_OK, list<FilesNode>, array{}>
	 * @throws OCSBadRequestException Too many ids
	 *
	 * 200: The files the user can access. A file reachable at several paths is listed once per path, unknown or inaccessible ids are left out
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/nodes')]
	public function getNodes(array $ids, ?string $dir = null, ?array $attributes = null): DataResponse {
		if (count($ids) > self::MAX_IDS) {
			throw new OCSBadRequestException('At most ' . self::MAX_IDS . ' ids are allowed');
		}
		/** @var IUser $user NoAdminRequired requires a logged in user */
		$user = $this->userSession->getUser();

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$scope = rtrim($userFolder->getPath() . '/' . trim($dir ?? '', '/'), '/');
		$nodes = [];
		foreach (array_unique(array_map('intval', $ids)) as $id) {
			// Only the user's own mounts are searched, so only files the user can access are found
			foreach ($userFolder->getById($id) as $node) {
				$path = $node->getPath();
				if ($path === $scope || str_starts_with($path, $scope . '/')) {
					$nodes[] = $node;
				}
			}
		}

		/** @var list<FilesNode> $data */
		$data = $this->serializer->serialize(NodeAttributeContext::forUser($user), $nodes, $attributes);
		return new DataResponse($data);
	}
}
