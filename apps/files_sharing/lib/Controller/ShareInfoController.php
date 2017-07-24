<?php

namespace OCA\Files_Sharing\Controller;

use OCA\Files_External\NotFoundException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

class ShareInfoController extends ApiController {

	/** @var IManager */
	private $shareManager;

	/** @var ILogger */
	private $logger;

	public function __construct($appName,
								IRequest $request,
								IManager $shareManager,
								ILogger $logger) {
		parent::__construct($appName, $request);

		$this->shareManager = $shareManager;
		$this->logger = $logger;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $t
	 * @param null $password
	 * @param null $dir
	 * @return JSONResponse
	 */
	public function info($t, $password = null, $dir = null) {
		$this->logger->error('HERE!');
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

		// TODO FIX!!!
		$isWritable = $share->getPermissions() & (\OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_CREATE);
		if (!$isWritable) {
			// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
			$previousLog = \OC\Files\Filesystem::logWarningWhenAddingStorageWrapper(false);
			\OC\Files\Filesystem::addStorageWrapper('readonly', function ($mountPoint, $storage) {
				return new \OC\Files\Storage\Wrapper\PermissionsMask(array('storage' => $storage, 'mask' => \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_SHARE));
			});
			\OC\Files\Filesystem::logWarningWhenAddingStorageWrapper($previousLog);
		}

		$node = $share->getNode();

		if ($dir !== null && $node instanceof Folder) {
			try {
				$node = $node->get($dir);
			} catch (NotFoundException $e) {

			}
		}

		$result = [
			'data' => $this->parseNode($node),
			'status' => 'success'
		];

		return new JSONResponse($result);
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
}
