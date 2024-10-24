<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

/**
 * Class \OCA\DAV\Connector\Sabre\Server
 *
 * This class overrides some methods from @see \Sabre\DAV\Server.
 *
 * @see \Sabre\DAV\Server
 */
class Server extends \Sabre\DAV\Server {
	/** @var CachingTree $tree */

	/**
	 * @see \Sabre\DAV\Server
	 */
	public function __construct($treeOrNode = null) {
		parent::__construct($treeOrNode);
		self::$exposeVersion = false;
		$this->enablePropfindDepthInfinity = true;
	}

	// Copied from 3rdparty/sabre/dav/lib/DAV/Server.php
	// Should be them exact same without the exception output.
	public function start(): void {
		try {
			// If nginx (pre-1.2) is used as a proxy server, and SabreDAV as an
			// origin, we must make sure we send back HTTP/1.0 if this was
			// requested.
			// This is mainly because nginx doesn't support Chunked Transfer
			// Encoding, and this forces the webserver SabreDAV is running on,
			// to buffer entire responses to calculate Content-Length.
			$this->httpResponse->setHTTPVersion($this->httpRequest->getHTTPVersion());

			// Setting the base url
			$this->httpRequest->setBaseUrl($this->getBaseUri());
			$this->invokeMethod($this->httpRequest, $this->httpResponse);
		} catch (\Throwable $e) {
			try {
				$this->emit('exception', [$e]);
			} catch (\Exception $ignore) {
			}
		}
	}
}
