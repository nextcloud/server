<?php
/**
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_External\NotFoundException;
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
	public function __construct($appName,
								IRequest $request,
								IManager $shareManager) {
		parent::__construct($appName, $request);

		$this->shareManager = $shareManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $t
	 * @param null $password
	 * @param null $dir
	 * @return JSONResponse
	 * @throws ShareNotFound
	 */
	public function info($t, $password = null, $dir = null) {
		try {
			$share = $this->shareManager->getShareByToken($t);
		} catch (ShareNotFound $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		if ($share->getPassword() && !$this->shareManager->checkPassword($share, $password)) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		if (!($share->getPermissions() & Constants::PERMISSION_READ)) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$isWritable = $share->getPermissions() & (\OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_CREATE);
		if (!$isWritable) {
			$this->addROWrapper();
		}

		$node = $share->getNode();

		if ($dir !== null && $node instanceof Folder) {
			try {
				$node = $node->get($dir);
			} catch (NotFoundException $e) {

			}
		}

		return new JSONResponse($this->parseNode($node));
	}

	private function parseNode(Node $node) {
		if ($node instanceof File) {
			return $this->parseFile($node);
		}
		return $this->parseFolder($node);
	}

	private function parseFile(File $file) {
		return $this->format($file);
	}

	private function parseFolder(Folder $folder) {
		$data = $this->format($folder);

		$data['children'] = [];

		$nodes = $folder->getDirectoryListing();
		foreach ($nodes as $node) {
			$data['children'][] = $this->parseNode($node);
		}

		return $data;
	}

	private function format(Node $node) {
		$entry = [];

		$entry['id'] = $node->getId();
		$entry['parentId'] = $node->getParent()->getId();
		$entry['mtime'] = $node->getMTime();

		$entry['name'] = $node->getName();
		$entry['permissions'] = $node->getPermissions();
		$entry['mimetype'] = $node->getMimetype();
		$entry['size'] = $node->getSize();
		$entry['type'] = $node->getType();
		$entry['etag'] = $node->getEtag();

		return $entry;
	}

	protected function addROWrapper() {
		// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
		$previousLog = \OC\Files\Filesystem::logWarningWhenAddingStorageWrapper(false);
		\OC\Files\Filesystem::addStorageWrapper('readonly', function ($mountPoint, $storage) {
			return new \OC\Files\Storage\Wrapper\PermissionsMask(array('storage' => $storage, 'mask' => \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_SHARE));
		});
		\OC\Files\Filesystem::logWarningWhenAddingStorageWrapper($previousLog);
	}
}
