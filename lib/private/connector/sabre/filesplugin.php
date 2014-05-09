<?php

/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL3
 */

class OC_Connector_Sabre_FilesPlugin extends Sabre_DAV_ServerPlugin
{

	// namespace
	const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/**
	 * Reference to main server object
	 *
	 * @var Sabre_DAV_Server
	 */
	private $server;

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre_DAV_Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Sabre_DAV_Server $server
	 * @return void
	 */
	public function initialize(Sabre_DAV_Server $server) {

		$server->xmlNamespaces[self::NS_OWNCLOUD] = 'oc';
		$server->protectedProperties[] = '{' . self::NS_OWNCLOUD . '}id';
		$server->protectedProperties[] = '{' . self::NS_OWNCLOUD . '}perm';

		$this->server = $server;
		$this->server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));
		$this->server->subscribeEvent('afterCreateFile', array($this, 'sendFileIdHeader'));
		$this->server->subscribeEvent('afterWriteContent', array($this, 'sendFileIdHeader'));
	}

	/**
	 * Adds all ownCloud-specific properties
	 *
	 * @param string $path
	 * @param Sabre_DAV_INode $node
	 * @param array $requestedProperties
	 * @param array $returnedProperties
	 * @return void
	 */
	public function beforeGetProperties($path, Sabre_DAV_INode $node, array &$requestedProperties, array &$returnedProperties) {

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
			if (!is_null($fileId)) {
				$returnedProperties[200][$permissionsPropertyName] = $permissions;
			}

		}

	}

	/**
	 * @param $filePath
	 * @param Sabre_DAV_INode $node
	 * @throws Sabre_DAV_Exception_BadRequest
	 */
	public function sendFileIdHeader($filePath, Sabre_DAV_INode $node = null) {
		// chunked upload handling
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			list($path, $name) = \Sabre_DAV_URLUtil::splitPath($filePath);
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
