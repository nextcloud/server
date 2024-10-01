<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Publishing;

use OCA\DAV\CalDAV\Publishing\Xml\Publisher;
use Sabre\Xml\Writer;
use Test\TestCase;

class PublisherTest extends TestCase {
	public const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

	public function testSerializePublished(): void {
		$publish = new Publisher('urltopublish', true);

		$xml = $this->write([
			'{' . self::NS_CALENDARSERVER . '}publish-url' => $publish,
		]);

		$this->assertEquals('urltopublish', $publish->getValue());

		$this->assertXmlStringEqualsXmlString(
			'<?xml version="1.0"?>
			<x1:publish-url xmlns:d="DAV:" xmlns:x1="' . self::NS_CALENDARSERVER . '">
			<d:href>urltopublish</d:href>
			</x1:publish-url>', $xml);
	}

	public function testSerializeNotPublished(): void {
		$publish = new Publisher('urltopublish', false);

		$xml = $this->write([
			'{' . self::NS_CALENDARSERVER . '}pre-publish-url' => $publish,
		]);

		$this->assertEquals('urltopublish', $publish->getValue());

		$this->assertXmlStringEqualsXmlString(
			'<?xml version="1.0"?>
			<x1:pre-publish-url xmlns:d="DAV:" xmlns:x1="' . self::NS_CALENDARSERVER . '">urltopublish</x1:pre-publish-url>', $xml);
	}


	protected $elementMap = [];
	protected $namespaceMap = ['DAV:' => 'd'];
	protected $contextUri = '/';

	private function write($input) {
		$writer = new Writer();
		$writer->contextUri = $this->contextUri;
		$writer->namespaceMap = $this->namespaceMap;
		$writer->openMemory();
		$writer->setIndent(true);
		$writer->write($input);
		return $writer->outputMemory();
	}
}
