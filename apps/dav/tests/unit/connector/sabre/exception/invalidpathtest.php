<?php

namespace Test\Connector\Sabre\Exception;

use OCA\DAV\Connector\Sabre\Exception\InvalidPath;

/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class InvalidPathTest extends \Test\TestCase {

	public function testSerialization() {

		// create xml doc
		$DOM = new \DOMDocument('1.0','utf-8');
		$DOM->formatOutput = true;
		$error = $DOM->createElementNS('DAV:','d:error');
		$error->setAttribute('xmlns:s', \Sabre\DAV\Server::NS_SABREDAV);
		$DOM->appendChild($error);

		// serialize the exception
		$message = "1234567890";
		$retry = false;
		$expectedXml = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:o="http://owncloud.org/ns">
  <o:retry xmlns:o="o:">false</o:retry>
  <o:reason xmlns:o="o:">1234567890</o:reason>
</d:error>

EOD;

		$ex = new InvalidPath($message, $retry);
		$server = $this->getMock('Sabre\DAV\Server');
		$ex->serialize($server, $error);

		// assert
		$xml = $DOM->saveXML();
		$this->assertEquals($expectedXml, $xml);
	}
}
