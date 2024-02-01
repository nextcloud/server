<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\Files_Sharing\Controller;

use OCA\Files_External\NotFoundException;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

/**
 * @psalm-import-type Files_SharingShareInfo from ResponseDefinitions
 */
class ShareInfoController extends ApiController {

	/** @var IManager */
	private $shareManager;

	/**
	 * ShareInfoController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IManager $shareManager
	 */
	public function __construct(string $appName,
		IRequest $request,
		IManager $shareManager) {
		parent::__construct($appName, $request);

		$this->shareManager = $shareManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @BruteForceProtection(action=shareinfo)
	 *
	 * Get the info about a share
	 *
	 * @param string $t Token of the share
	 * @param string|null $password Password of the share
	 * @param string|null $dir Subdirectory to get info about
	 * @param int $depth Maximum depth to get info about
	 * @return JSONResponse<Http::STATUS_OK, Files_SharingShareInfo, array{}>|JSONResponse<Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Share info returned
	 * 403: Getting share info is not allowed
	 * 404: Share not found
	 */
	public function info(string $t, ?string $password = null, ?string $dir = null, int $depth = -1): JSONResponse {
		try {
			$share = $this->shareManager->getShareByToken($t);
		} catch (ShareNotFound $e) {
			$response = new JSONResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $t]);
			return $response;
		}

		if ($share->getPassword() && !$this->shareManager->checkPassword($share, $password)) {
			$response = new JSONResponse([], Http::STATUS_FORBIDDEN);
			$response->throttle(['token' => $t]);
			return $response;
		}

		if (!($share->getPermissions() & Constants::PERMISSION_READ)) {
			$response = new JSONResponse([], Http::STATUS_FORBIDDEN);
			$response->throttle(['token' => $t]);
			return $response;
		}

		$permissionMask = $share->getPermissions();
		$node = $share->getNode();

		if ($dir !== null && $node instanceof Folder) {
			try {
				$node = $node->get($dir);
			} catch (NotFoundException $e) {
			}
		}

		return new JSONResponse($this->parseNode($node, $permissionMask, $depth));
	}

	/**
	 * @return Files_SharingShareInfo
	 */
	private function parseNode(Node $node, int $permissionMask, int $depth): array {
		if ($node instanceof File) {
			return $this->parseFile($node, $permissionMask);
		}
		/** @var Folder $node */
		return $this->parseFolder($node, $permissionMask, $depth);
	}

	/**
	 * @return Files_SharingShareInfo
	 */
	private function parseFile(File $file, int $permissionMask): array {
		return $this->format($file, $permissionMask);
	}

	/**
	 * @return Files_SharingShareInfo
	 */
	private function parseFolder(Folder $folder, int $permissionMask, int $depth): array {
		$data = $this->format($folder, $permissionMask);

		if ($depth === 0) {
			return $data;
		}

		$data['children'] = [];

		$nodes = $folder->getDirectoryListing();
		foreach ($nodes as $node) {
			$data['children'][] = $this->parseNode($node, $permissionMask, $depth <= -1 ? -1 : $depth - 1);
		}

		return $data;
	}

	/**
	 * @return Files_SharingShareInfo
	 */
	private function format(Node $node, int $permissionMask): array {
		$entry = [];

		$entry['id'] = $node->getId();
		$entry['parentId'] = $node->getParent()->getId();
		$entry['mtime'] = $node->getMTime();

		$entry['name'] = $node->getName();
		$entry['permissions'] = $node->getPermissions() & $permissionMask;
		$entry['mimetype'] = $node->getMimetype();
		$entry['size'] = $node->getSize();
		$entry['type'] = $node->getType();
		$entry['etag'] = $node->getEtag();

		return $entry;
	}
}
