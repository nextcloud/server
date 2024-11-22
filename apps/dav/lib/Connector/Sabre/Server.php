<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author scolebrook <scolebrook@mac.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\Exception;
use Sabre\DAV\Version;
use TypeError;

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
			try {
				$this->emit('exception', [$e]);
			} catch (\Exception) {
			}

			if ($e instanceof TypeError) {
				/*
				 * The TypeError includes the file path where the error occurred,
				 * potentially revealing the installation directory.
				 */
				$e = new TypeError('A type error occurred. For more details, please refer to the logs, which provide additional context about the type error.');
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
