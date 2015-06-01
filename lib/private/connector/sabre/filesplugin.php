<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Connector\Sabre;

use Sabre\DAV\IFile;
use \Sabre\DAV\PropFind;
use \Sabre\DAV\PropPatch;
use \Sabre\HTTP\RequestInterface;
use \Sabre\HTTP\ResponseInterface;

class FilesPlugin extends \Sabre\DAV\ServerPlugin {

	// namespace
	const NS_OWNCLOUD = 'http://owncloud.org/ns';
	const FILEID_PROPERTYNAME = '{http://owncloud.org/ns}id';
	const PERMISSIONS_PROPERTYNAME = '{http://owncloud.org/ns}permissions';
	const DOWNLOADURL_PROPERTYNAME = '{http://owncloud.org/ns}downloadURL';
	const SIZE_PROPERTYNAME = '{http://owncloud.org/ns}size';
	const GETETAG_PROPERTYNAME = '{DAV:}getetag';
	const GETLASTMODIFIED_PROPERTYNAME = '{DAV:}getlastmodified';

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\Tree
	 */
	private $tree;

	/**
	 * @param \Sabre\DAV\Tree $tree
	 */
	public function __construct(\Sabre\DAV\Tree $tree) {
		$this->tree = $tree;
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

		$server->xmlNamespaces[self::NS_OWNCLOUD] = 'oc';
		$server->protectedProperties[] = self::FILEID_PROPERTYNAME;
		$server->protectedProperties[] = self::PERMISSIONS_PROPERTYNAME;
		$server->protectedProperties[] = self::SIZE_PROPERTYNAME;
		$server->protectedProperties[] = self::DOWNLOADURL_PROPERTYNAME;

		// normally these cannot be changed (RFC4918), but we want them modifiable through PROPPATCH
		$allowedProperties = ['{DAV:}getetag', '{DAV:}getlastmodified'];
		$server->protectedProperties = array_diff($server->protectedProperties, $allowedProperties);

		$this->server = $server;
		$this->server->on('propFind', array($this, 'handleGetProperties'));
		$this->server->on('propPatch', array($this, 'handleUpdateProperties'));
		$this->server->on('afterBind', array($this, 'sendFileIdHeader'));
		$this->server->on('afterWriteContent', array($this, 'sendFileIdHeader'));
		$this->server->on('afterMethod:GET', [$this,'httpGet']);
		$this->server->on('afterResponse', function($request, ResponseInterface $response) {
			$body = $response->getBody();
			if (is_resource($body)) {
				fclose($body);
			}
		});
	}

	/**
	 * Plugin that adds a 'Content-Disposition: attachment' header to all files
	 * delivered by SabreDAV.
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	function httpGet(RequestInterface $request, ResponseInterface $response) {
		// Only handle valid files
		$node = $this->tree->getNodeForPath($request->getPath());
		if (!($node instanceof IFile)) return;

		$response->addHeader('Content-Disposition', 'attachment');
	}

	/**
	 * Adds all ownCloud-specific properties
	 *
	 * @param PropFind $propFind
	 * @param \Sabre\DAV\INode $node
	 * @return void
	 */
	public function handleGetProperties(PropFind $propFind, \Sabre\DAV\INode $node) {

		if ($node instanceof \OC\Connector\Sabre\Node) {

			$propFind->handle(self::FILEID_PROPERTYNAME, function() use ($node) {
				return $node->getFileId();
			});

			$propFind->handle(self::PERMISSIONS_PROPERTYNAME, function() use ($node) {
				return $node->getDavPermissions();
			});

			$propFind->handle(self::GETETAG_PROPERTYNAME, function() use ($node) {
				return $node->getEtag();
			});
		}

		if ($node instanceof \OC\Connector\Sabre\File) {
			$propFind->handle(self::DOWNLOADURL_PROPERTYNAME, function() use ($node) {
				/** @var $node \OC\Connector\Sabre\File */
				$directDownloadUrl = $node->getDirectDownload();
				if (isset($directDownloadUrl['url'])) {
					return $directDownloadUrl['url'];
				}
				return false;
			});
		}

		if ($node instanceof \OC\Connector\Sabre\Directory) {
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
		$propPatch->handle(self::GETLASTMODIFIED_PROPERTYNAME, function($time) use ($path) {
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
		if ($node instanceof \OC\Connector\Sabre\Node) {
			$fileId = $node->getFileId();
			if (!is_null($fileId)) {
				$this->server->httpResponse->setHeader('OC-FileId', $fileId);
			}
		}
	}

}
