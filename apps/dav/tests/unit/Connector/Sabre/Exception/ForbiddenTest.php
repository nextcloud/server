<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\Exception;

use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use Sabre\DAV\Server;

class ForbiddenTest extends \Test\TestCase {
	public function testSerialization(): void {

		// create xml doc
		$DOM = new \DOMDocument('1.0', 'utf-8');
		$DOM->formatOutput = true;
		$error = $DOM->createElementNS('DAV:', 'd:error');
		$error->setAttribute('xmlns:s', \Sabre\DAV\Server::NS_SABREDAV);
		$DOM->appendChild($error);

		// serialize the exception
		$message = '1234567890';
		$retry = false;
		$expectedXml = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:o="http://owncloud.org/ns">
  <o:retry xmlns:o="o:">false</o:retry>
  <o:reason xmlns:o="o:">1234567890</o:reason>
</d:error>

EOD;

		$ex = new Forbidden($message, $retry);
		$server = $this->createMock(Server::class);
		$ex->serialize($server, $error);

		// assert
		$xml = $DOM->saveXML();
		$this->assertEquals($expectedXml, $xml);
	}
}
