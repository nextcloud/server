<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Class OC_Connector_Sabre_AbortedUploadDetectionPlugin
 *
 * This plugin will verify if the uploaded data has been stored completely.
 * This is done by comparing the content length of the request with the file size on storage.
 */
class OC_Connector_Sabre_AbortedUploadDetectionPlugin extends Sabre_DAV_ServerPlugin {

	/**
	 * Reference to main server object
	 *
	 * @var Sabre_DAV_Server
	 */
	private $server;

	/**
	 * is kept public to allow overwrite for unit testing
	 *
	 * @var \OC\Files\View
	 */
	public $fileView;

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre_DAV_Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the requires event subscriptions.
	 *
	 * @param Sabre_DAV_Server $server
	 */
	public function initialize(Sabre_DAV_Server $server) {

		$this->server = $server;

		$server->subscribeEvent('afterCreateFile', array($this, 'verifyContentLength'), 10);
		$server->subscribeEvent('afterWriteContent', array($this, 'verifyContentLength'), 10);
	}

	/**
	 * @param $filePath
	 * @param Sabre_DAV_INode $node
	 * @throws Sabre_DAV_Exception_BadRequest
	 */
	public function verifyContentLength($filePath, Sabre_DAV_INode $node = null) {

		// we should only react on PUT which is used for upload
		// e.g. with LOCK this will not work, but LOCK uses createFile() as well
		if ($this->server->httpRequest->getMethod() !== 'PUT' ) {
			return;
		}

		// ownCloud chunked upload will be handled in its own plugin
		$chunkHeader = $this->server->httpRequest->getHeader('OC-Chunked');
		if ($chunkHeader) {
			return;
		}

		// compare expected and actual size
		$expected = $this->getLength();
		if (!$expected) {
			return;
		}
		$actual = $this->getFileView()->filesize($filePath);
		if ($actual != $expected) {
			$this->getFileView()->unlink($filePath);
			throw new Sabre_DAV_Exception_BadRequest('expected filesize ' . $expected . ' got ' . $actual);
		}

	}

	/**
	 * @return string
	 */
	public function getLength()
	{
		$req = $this->server->httpRequest;
		$length = $req->getHeader('X-Expected-Entity-Length');
		if (!$length) {
			$length = $req->getHeader('Content-Length');
		}

		return $length;
	}

	/**
	 * @return \OC\Files\View
	 */
	public function getFileView()
	{
		if (is_null($this->fileView)) {
			// initialize fileView
			$this->fileView = \OC\Files\Filesystem::getView();
		}

		return $this->fileView;
	}
}
