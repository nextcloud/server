<?php

/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file. */

class OC_Connector_Sabre_Exception_InvalidPath extends \Sabre\DAV\Exception {

	/**
	 * @var bool
	 */
	private $retry;

	/**
	 * @param string $message
	 * @param bool $retry
	 */
	public function __construct($message, $retry = false) {
		parent::__construct($message);
		$this->retry = $retry;
	}

	/**
	 * Returns the HTTP status code for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {

		return 400;

	}

	/**
	 * This method allows the exception to include additional information into the WebDAV error response
	 *
	 * @param \Sabre\DAV\Server $server
	 * @param \DOMElement $errorNode
	 * @return void
	 */
	public function serialize(\Sabre\DAV\Server $server,\DOMElement $errorNode) {

		// set owncloud namespace
		$errorNode->setAttribute('xmlns:o', OC_Connector_Sabre_FilesPlugin::NS_OWNCLOUD);

		// adding the retry node
		$error = $errorNode->ownerDocument->createElementNS('o:','o:retry', var_export($this->retry, true));
		$errorNode->appendChild($error);

		// adding the message node
		$error = $errorNode->ownerDocument->createElementNS('o:','o:reason', $this->getMessage());
		$errorNode->appendChild($error);
	}

}
