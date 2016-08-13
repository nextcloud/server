<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Connector\Sabre;

use OC\Files\View;
use OCA\DAV\Upload\FutureFile;
use OCP\Files\ForbiddenException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IFile;
use \Sabre\DAV\PropFind;
use \Sabre\DAV\PropPatch;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Tree;
use \Sabre\HTTP\RequestInterface;
use \Sabre\HTTP\ResponseInterface;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\IRequest;

class FilesPlugin extends ServerPlugin {

	// namespace
	const NS_OWNCLOUD = 'http://owncloud.org/ns';
	const FILEID_PROPERTYNAME = '{http://owncloud.org/ns}id';
	const INTERNAL_FILEID_PROPERTYNAME = '{http://owncloud.org/ns}fileid';
	const PERMISSIONS_PROPERTYNAME = '{http://owncloud.org/ns}permissions';
	const SHARE_PERMISSIONS_PROPERTYNAME = '{http://open-collaboration-services.org/ns}share-permissions';
	const DOWNLOADURL_PROPERTYNAME = '{http://owncloud.org/ns}downloadURL';
	const SIZE_PROPERTYNAME = '{http://owncloud.org/ns}size';
	const GETETAG_PROPERTYNAME = '{DAV:}getetag';
	const LASTMODIFIED_PROPERTYNAME = '{DAV:}lastmodified';
	const OWNER_ID_PROPERTYNAME = '{http://owncloud.org/ns}owner-id';
	const OWNER_DISPLAY_NAME_PROPERTYNAME = '{http://owncloud.org/ns}owner-display-name';
	const CHECKSUMS_PROPERTYNAME = '{http://owncloud.org/ns}checksums';
	const DATA_FINGERPRINT_PROPERTYNAME = '{http://owncloud.org/ns}data-fingerprint';

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var Tree
	 */
	private $tree;

	/**
	 * Whether this is public webdav.
	 * If true, some returned information will be stripped off.
	 *
	 * @var bool
	 */
	private $isPublic;

	/**
	 * @var View
	 */
	private $fileView;

	/**
	 * @var bool
	 */
	private $downloadAttachment;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IRequest
	 */
	private $request;

	/**
	 * @param Tree $tree
	 * @param View $view
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param bool $isPublic
	 * @param bool $downloadAttachment
	 */
	public function __construct(Tree $tree,
								View $view,
								IConfig $config,
								IRequest $request,
								$isPublic = false,
								$downloadAttachment = true) {
		$this->tree = $tree;
		$this->fileView = $view;
		$this->config = $config;
		$this->request = $request;
		$this->isPublic = $isPublic;
		$this->downloadAttachment = $downloadAttachment;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {

		$server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';
		$server->protectedProperties[] = self::FILEID_PROPERTYNAME;
		$server->protectedProperties[] = self::INTERNAL_FILEID_PROPERTYNAME;
		$server->protectedProperties[] = self::PERMISSIONS_PROPERTYNAME;
		$server->protectedProperties[] = self::SHARE_PERMISSIONS_PROPERTYNAME;
		$server->protectedProperties[] = self::SIZE_PROPERTYNAME;
		$server->protectedProperties[] = self::DOWNLOADURL_PROPERTYNAME;
		$server->protectedProperties[] = self::OWNER_ID_PROPERTYNAME;
		$server->protectedProperties[] = self::OWNER_DISPLAY_NAME_PROPERTYNAME;
		$server->protectedProperties[] = self::CHECKSUMS_PROPERTYNAME;
		$server->protectedProperties[] = self::DATA_FINGERPRINT_PROPERTYNAME;

		// normally these cannot be changed (RFC4918), but we want them modifiable through PROPPATCH
		$allowedProperties = ['{DAV:}getetag'];
		$server->protectedProperties = array_diff($server->protectedProperties, $allowedProperties);

		$this->server = $server;
		$this->server->on('propFind', array($this, 'handleGetProperties'));
		$this->server->on('propPatch', array($this, 'handleUpdateProperties'));
		$this->server->on('afterBind', array($this, 'sendFileIdHeader'));
		$this->server->on('afterWriteContent', array($this, 'sendFileIdHeader'));
		$this->server->on('afterMethod:GET', [$this,'httpGet']);
		$this->server->on('afterMethod:GET', array($this, 'handleDownloadToken'));
		$this->server->on('afterResponse', function($request, ResponseInterface $response) {
			$body = $response->getBody();
			if (is_resource($body)) {
				fclose($body);
			}
		});
		$this->server->on('beforeMove', [$this, 'checkMove']);
	}

	/**
	 * Plugin that checks if a move can actually be performed.
	 *
	 * @param string $source source path
	 * @param string $destination destination path
	 * @throws Forbidden
	 * @throws NotFound
	 */
	function checkMove($source, $destination) {
		$sourceNode = $this->tree->getNodeForPath($source);
		if (!$sourceNode instanceof Node) {
			return;
		}
		list($sourceDir,) = \Sabre\HTTP\URLUtil::splitPath($source);
		list($destinationDir,) = \Sabre\HTTP\URLUtil::splitPath($destination);

		if ($sourceDir !== $destinationDir) {
			$sourceNodeFileInfo = $sourceNode->getFileInfo();
			if (is_null($sourceNodeFileInfo)) {
				throw new NotFound($source . ' does not exist');
			}

			if (!$sourceNodeFileInfo->isDeletable()) {
				throw new Forbidden($source . " cannot be deleted");
			}
		}
	}

	/**
	 * This sets a cookie to be able to recognize the start of the download
	 * the content must not be longer than 32 characters and must only contain
	 * alphanumeric characters
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	function handleDownloadToken(RequestInterface $request, ResponseInterface $response) {
		$queryParams = $request->getQueryParameters();

		/**
		 * this sets a cookie to be able to recognize the start of the download
		 * the content must not be longer than 32 characters and must only contain
		 * alphanumeric characters
		 */
		if (isset($queryParams['downloadStartSecret'])) {
			$token = $queryParams['downloadStartSecret'];
			if (!isset($token[32])
				&& preg_match('!^[a-zA-Z0-9]+$!', $token) === 1) {
				// FIXME: use $response->setHeader() instead
				setcookie('ocDownloadStarted', $token, time() + 20, '/');
			}
		}
	}

	/**
	 * Add headers to file download
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	function httpGet(RequestInterface $request, ResponseInterface $response) {
		// Only handle valid files
		$node = $this->tree->getNodeForPath($request->getPath());
		if (!($node instanceof IFile)) return;

		// adds a 'Content-Disposition: attachment' header
		if ($this->downloadAttachment) {
			$filename = $node->getName();
			if ($this->request->isUserAgent(
				[
					\OC\AppFramework\Http\Request::USER_AGENT_IE,
					\OC\AppFramework\Http\Request::USER_AGENT_ANDROID_MOBILE_CHROME,
					\OC\AppFramework\Http\Request::USER_AGENT_FREEBOX,
				])) {
				$response->addHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($filename) . '"');
			} else {
				$response->addHeader('Content-Disposition', 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
													 . '; filename="' . rawurlencode($filename) . '"');
			}
		}

		if ($node instanceof \OCA\DAV\Connector\Sabre\File) {
			//Add OC-Checksum header
			/** @var $node File */
			$checksum = $node->getChecksum();
			if ($checksum !== null && $checksum !== '') {
				$response->addHeader('OC-Checksum', $checksum);
			}
		}
	}

	/**
	 * Adds all ownCloud-specific properties
	 *
	 * @param PropFind $propFind
	 * @param \Sabre\DAV\INode $node
	 * @return void
	 */
	public function handleGetProperties(PropFind $propFind, \Sabre\DAV\INode $node) {

		$httpRequest = $this->server->httpRequest;

		if ($node instanceof \OCA\DAV\Connector\Sabre\Node) {

			$propFind->handle(self::FILEID_PROPERTYNAME, function() use ($node) {
				return $node->getFileId();
			});

			$propFind->handle(self::INTERNAL_FILEID_PROPERTYNAME, function() use ($node) {
				return $node->getInternalFileId();
			});

			$propFind->handle(self::PERMISSIONS_PROPERTYNAME, function() use ($node) {
				$perms = $node->getDavPermissions();
				if ($this->isPublic) {
					// remove mount information
					$perms = str_replace(['S', 'M'], '', $perms);
				}
				return $perms;
			});

			$propFind->handle(self::SHARE_PERMISSIONS_PROPERTYNAME, function() use ($node, $httpRequest) {
				return $node->getSharePermissions(
					$httpRequest->getRawServerValue('PHP_AUTH_USER')
				);
			});

			$propFind->handle(self::GETETAG_PROPERTYNAME, function() use ($node) {
				return $node->getETag();
			});

			$propFind->handle(self::OWNER_ID_PROPERTYNAME, function() use ($node) {
				$owner = $node->getOwner();
				return $owner->getUID();
			});
			$propFind->handle(self::OWNER_DISPLAY_NAME_PROPERTYNAME, function() use ($node) {
				$owner = $node->getOwner();
				$displayName = $owner->getDisplayName();
				return $displayName;
			});
		}

		if ($node instanceof \OCA\DAV\Connector\Sabre\Node
			|| $node instanceof \OCA\DAV\Files\FilesHome) {
			$propFind->handle(self::DATA_FINGERPRINT_PROPERTYNAME, function() use ($node) {
				return $this->config->getSystemValue('data-fingerprint', '');
			});
		}

		if ($node instanceof \OCA\DAV\Connector\Sabre\File) {
			$propFind->handle(self::DOWNLOADURL_PROPERTYNAME, function() use ($node) {
				/** @var $node \OCA\DAV\Connector\Sabre\File */
				try {
					$directDownloadUrl = $node->getDirectDownload();
					if (isset($directDownloadUrl['url'])) {
						return $directDownloadUrl['url'];
					}
				} catch (StorageNotAvailableException $e) {
					return false;
				} catch (ForbiddenException $e) {
					return false;
				}
				return false;
			});

			$propFind->handle(self::CHECKSUMS_PROPERTYNAME, function() use ($node) {
				$checksum = $node->getChecksum();
				if ($checksum === NULL || $checksum === '') {
					return null;
				}

				return new ChecksumList($checksum);
			});

		}

		if ($node instanceof \OCA\DAV\Connector\Sabre\Directory) {
			$propFind->handle(self::SIZE_PROPERTYNAME, function() use ($node) {
				return $node->getSize();
			});
		}
	}

	/**
	 * Update ownCloud-specific properties
	 *
	 * @param string $path
	 * @param PropPatch $propPatch
	 *
	 * @return void
	 */
	public function handleUpdateProperties($path, PropPatch $propPatch) {
		$propPatch->handle(self::LASTMODIFIED_PROPERTYNAME, function($time) use ($path) {
			if (empty($time)) {
				return false;
			}
			$node = $this->tree->getNodeForPath($path);
			if (is_null($node)) {
				return 404;
			}
			$node->touch($time);
			return true;
		});
		$propPatch->handle(self::GETETAG_PROPERTYNAME, function($etag) use ($path) {
			if (empty($etag)) {
				return false;
			}
			$node = $this->tree->getNodeForPath($path);
			if (is_null($node)) {
				return 404;
			}
			if ($node->setEtag($etag) !== -1) {
				return true;
			}
			return false;
		});
	}

	/**
	 * @param string $filePath
	 * @param \Sabre\DAV\INode $node
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function sendFileIdHeader($filePath, \Sabre\DAV\INode $node = null) {
		// chunked upload handling
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			list($path, $name) = \Sabre\HTTP\URLUtil::splitPath($filePath);
			$info = \OC_FileChunking::decodeName($name);
			if (!empty($info)) {
				$filePath = $path . '/' . $info['name'];
			}
		}

		// we get the node for the given $filePath here because in case of afterCreateFile $node is the parent folder
		if (!$this->server->tree->nodeExists($filePath)) {
			return;
		}
		$node = $this->server->tree->getNodeForPath($filePath);
		if ($node instanceof \OCA\DAV\Connector\Sabre\Node) {
			$fileId = $node->getFileId();
			if (!is_null($fileId)) {
				$this->server->httpResponse->setHeader('OC-FileId', $fileId);
			}
		}
	}

}
