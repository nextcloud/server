<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\Exception;
use Sabre\DAV\Version;

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

	/**
	 *
	 * @return void
	 */
	public function start() {
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
			if ($e instanceof \TypeError) {
				/*
				 * The TypeError includes the file path where the error occurred,
				 * potentially revealing the installation directory.
				 *
				 * By re-throwing the exception, we ensure that the
				 * default exception handler processes it.
				 */
				throw $e;
			}

			try {
				$this->emit('exception', [$e]);
			} catch (\Exception $ignore) {
			}

			$DOM = new \DOMDocument('1.0', 'utf-8');
			$DOM->formatOutput = true;

			$error = $DOM->createElementNS('DAV:', 'd:error');
			$error->setAttribute('xmlns:s', self::NS_SABREDAV);
			$DOM->appendChild($error);

			$h = function ($v) {
				return htmlspecialchars((string)$v, ENT_NOQUOTES, 'UTF-8');
			};

			if (self::$exposeVersion) {
				$error->appendChild($DOM->createElement('s:sabredav-version', $h(Version::VERSION)));
			}

			$error->appendChild($DOM->createElement('s:exception', $h(get_class($e))));
			$error->appendChild($DOM->createElement('s:message', $h($e->getMessage())));
			if ($this->debugExceptions) {
				$error->appendChild($DOM->createElement('s:file', $h($e->getFile())));
				$error->appendChild($DOM->createElement('s:line', $h($e->getLine())));
				$error->appendChild($DOM->createElement('s:code', $h($e->getCode())));
				$error->appendChild($DOM->createElement('s:stacktrace', $h($e->getTraceAsString())));
			}

			if ($this->debugExceptions) {
				$previous = $e;
				while ($previous = $previous->getPrevious()) {
					$xPrevious = $DOM->createElement('s:previous-exception');
					$xPrevious->appendChild($DOM->createElement('s:exception', $h(get_class($previous))));
					$xPrevious->appendChild($DOM->createElement('s:message', $h($previous->getMessage())));
					$xPrevious->appendChild($DOM->createElement('s:file', $h($previous->getFile())));
					$xPrevious->appendChild($DOM->createElement('s:line', $h($previous->getLine())));
					$xPrevious->appendChild($DOM->createElement('s:code', $h($previous->getCode())));
					$xPrevious->appendChild($DOM->createElement('s:stacktrace', $h($previous->getTraceAsString())));
					$error->appendChild($xPrevious);
				}
			}

			if ($e instanceof Exception) {
				$httpCode = $e->getHTTPCode();
				$e->serialize($this, $error);
				$headers = $e->getHTTPHeaders($this);
			} else {
				$httpCode = 500;
				$headers = [];
			}
			$headers['Content-Type'] = 'application/xml; charset=utf-8';

			$this->httpResponse->setStatus($httpCode);
			$this->httpResponse->setHeaders($headers);
			$this->httpResponse->setBody($DOM->saveXML());
			$this->sapi->sendResponse($this->httpResponse);
		}
	}
}
