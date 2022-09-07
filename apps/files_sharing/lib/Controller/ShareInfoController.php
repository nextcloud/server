<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

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
	 * @param string $t
	 * @param null $password
	 * @param null $dir
	 * @return JSONResponse
	 */
	public function info($t, $password = null, $dir = null, int $startAt = 0) {
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

		try {
			$node = $this->getFirstNode($share->getNode(), $startAt);
		} catch (NotFoundException $e) {
			$response = new JSONResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $t]);
			return $response;
		}

		if ($dir !== null && $node instanceof Folder) {
			try {
				$node = $node->get($dir);
			} catch (NotFoundException $e) {
			}
		}

		return new JSONResponse($this->parseNode($node, $permissionMask));
	}

	private function parseNode(Node $node, int $permissionMask, bool $recursive = true) {
		if ($node instanceof File) {
			return $this->parseFile($node, $permissionMask);
		}

		return $this->parseFolder($node, $permissionMask, $recursive);
	}

	private function parseFile(File $file, int $permissionMask) {
		return $this->format($file, $permissionMask);
	}


	private function parseFolder(Folder $folder, int $permissionMask, bool $recursive = true) {
		$data = $this->format($folder, $permissionMask);
		$data['children'] = [];

		if (!$recursive && $folder->getSize() > 0) {
			$data['hasChildren'] = true;

			return $data;
		}

		// in case of [sub]folders containing only empty files
		$nodes = $folder->getDirectoryListing();
		if (!$recursive && count($nodes) > 0) {
			$data['hasChildren'] = $data['containsEmptyFilesOnly'] = true;

			return $data;
		}

		foreach ($nodes as $node) {
			$data['children'][] = $this->parseNode($node, $permissionMask, false);
		}

		return $data;
	}

	/**
	 * @param Node $node
	 * @param int $startAt
	 *
	 * @return Node
	 */
	private function getFirstNode(Node $node, int $startAt): Node {
		// returns current node if $st is not set, or set to current nodeId
		if ($startAt < 1 || $node->getId() === $startAt) {
			return $node;
		}

		// checking all path that link to node_id set as $st
		// will returns the node that fit current node full path
		/** @var Node[] $subs */
		$subs = $node->getById($startAt);
		if (empty($subs)) {
			throw new NotFoundException();
		}

		$curr = $node->getPath();
		foreach($subs as $sub) {
			$pos = strpos($sub->getPath(), $curr);
			if ($pos === false || $pos <> 0) {
				continue;
			}

			$subPath = trim(substr($sub->getPath(), strlen($curr)), '/');
			try {
				$new = $node;
				foreach (explode('/', $subPath) as $subFolder) {
					$new = $new->get($subFolder);
				}
			} catch (NotFoundException $e) {
				continue;
			}

			return $new;
		}

		throw new NotFoundException();
	}


	private function format(Node $node, int $permissionMask) {
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
