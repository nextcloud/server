<?php

/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL3
 */

class OC_Connector_Sabre_FilesPlugin extends \Sabre\DAV\ServerPlugin
{

	// namespace
	const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

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
		$server->protectedProperties[] = '{' . self::NS_OWNCLOUD . '}id';
		$server->protectedProperties[] = '{' . self::NS_OWNCLOUD . '}permissions';
		$server->protectedProperties[] = '{' . self::NS_OWNCLOUD . '}size';
		$server->protectedProperties[] = '{' . self::NS_OWNCLOUD . '}downloadURL';

		$this->server = $server;
		$this->server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));
		$this->server->subscribeEvent('afterBind', array($this, 'sendFileIdHeader'));
		$this->server->subscribeEvent('afterWriteContent', array($this, 'sendFileIdHeader'));
	}

	/**
	 * Adds all ownCloud-specific properties
	 *
	 * @param string $path
	 * @param \Sabre\DAV\INode $node
	 * @param array $requestedProperties
	 * @param array $returnedProperties
	 * @return void
	 */
	public function beforeGetProperties($path, \Sabre\DAV\INode $node, array &$requestedProperties, array &$returnedProperties) {

		if ($node instanceof OC_Connector_Sabre_Node) {

			$fileIdPropertyName = '{' . self::NS_OWNCLOUD . '}id';
			$permissionsPropertyName = '{' . self::NS_OWNCLOUD . '}permissions';
			if (array_search($fileIdPropertyName, $requestedProperties)) {
				unset($requestedProperties[array_search($fileIdPropertyName, $requestedProperties)]);
			}
			if (array_search($permissionsPropertyName, $requestedProperties)) {
				unset($requestedProperties[array_search($permissionsPropertyName, $requestedProperties)]);
			}

			/** @var $node OC_Connector_Sabre_Node */
			$fileId = $node->getFileId();
			if (!is_null($fileId)) {
				$returnedProperties[200][$fileIdPropertyName] = $fileId;
			}

			$permissions = $node->getDavPermissions();
			if (!is_null($permissions)) {
				$returnedProperties[200][$permissionsPropertyName] = $permissions;
			}
		}

		if ($node instanceof OC_Connector_Sabre_File) {
			/** @var $node OC_Connector_Sabre_File */
			$directDownloadUrl = $node->getDirectDownload();
			if (isset($directDownloadUrl['url'])) {
				$directDownloadUrlPropertyName = '{' . self::NS_OWNCLOUD . '}downloadURL';
				$returnedProperties[200][$directDownloadUrlPropertyName] = $directDownloadUrl['url'];
			}
		}

		if ($node instanceof OC_Connector_Sabre_Directory) {
			$sizePropertyName = '{' . self::NS_OWNCLOUD . '}size';

			/** @var $node OC_Connector_Sabre_Directory */
			$returnedProperties[200][$sizePropertyName] = $node->getSize();
		}
	}

	/**
	 * @param string $filePath
	 * @param \Sabre\DAV\INode $node
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function sendFileIdHeader($filePath, \Sabre\DAV\INode $node = null) {
		// chunked upload handling
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			list($path, $name) = \Sabre\DAV\URLUtil::splitPath($filePath);
			$info = OC_FileChunking::decodeName($name);
			if (!empty($info)) {
				$filePath = $path . '/' . $info['name'];
			}
		}

		// we get the node for the given $filePath here because in case of afterCreateFile $node is the parent folder
		if (!$this->server->tree->nodeExists($filePath)) {
			return;
		}
		$node = $this->server->tree->getNodeForPath($filePath);
		if ($node instanceof OC_Connector_Sabre_Node) {
			$fileId = $node->getFileId();
			if (!is_null($fileId)) {
				$this->server->httpResponse->setHeader('OC-FileId', $fileId);
			}
		}
	}

}
