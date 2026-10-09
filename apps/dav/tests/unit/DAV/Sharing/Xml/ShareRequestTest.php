<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Sharing\Xml;

use OCA\DAV\DAV\Sharing\Xml\ShareRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Service;
use Test\TestCase;

class ShareRequestTest extends TestCase {
	private const PREFIX = '<?xml version="1.0" encoding="utf-8" ?><CS:share xmlns:D="DAV:" xmlns:CS="http://owncloud.org/ns">';
	private const SUFFIX = '</CS:share>';

	private function parse(string $xml): ShareRequest {
		$service = new Service();
		$service->elementMap[ShareRequest::ELEMENT_SHARE] = ShareRequest::class;
		return $service->parse($xml);
	}

	public static function dataValid(): array {
		return [
			'self-closing share' => [
				'<?xml version="1.0" encoding="utf-8" ?><CS:share xmlns:CS="http://owncloud.org/ns"/>',
				[],
				[],
			],
			'text body' => [
				self::PREFIX . 'text' . self::SUFFIX,
				[],
				[],
			],
			'set read-only' => [
				self::PREFIX . '<CS:set><D:href>principal:principals/users/alice</D:href></CS:set>' . self::SUFFIX,
				[['href' => 'principal:principals/users/alice', 'commonName' => null, 'readOnly' => true]],
				[],
			],
			'set read-write with common-name' => [
				self::PREFIX . '<CS:set><D:href>principal:principals/users/alice</D:href><CS:common-name>Alice</CS:common-name><CS:read-write/></CS:set>' . self::SUFFIX,
				[['href' => 'principal:principals/users/alice', 'commonName' => 'Alice', 'readOnly' => false]],
				[],
			],
			'remove' => [
				self::PREFIX . '<CS:remove><D:href>mailto:bob@example.com</D:href></CS:remove>' . self::SUFFIX,
				[],
				['mailto:bob@example.com'],
			],
		];
	}

	#[DataProvider('dataValid')]
	public function testValid(string $xml, array $set, array $remove): void {
		$shareRequest = $this->parse($xml);

		$this->assertSame($set, $shareRequest->set);
		$this->assertSame($remove, $shareRequest->remove);
	}

	public static function dataInvalid(): array {
		return [
			'set without href' => [
				self::PREFIX . '<CS:set><CS:read-write/></CS:set>' . self::SUFFIX,
				'{http://owncloud.org/ns}set requires a href element',
			],
			'self-closing set' => [
				self::PREFIX . '<CS:set/>' . self::SUFFIX,
				'{http://owncloud.org/ns}set requires a href element',
			],
			'remove without href' => [
				self::PREFIX . '<CS:remove></CS:remove>' . self::SUFFIX,
				'{http://owncloud.org/ns}remove requires a href element',
			],
			'empty href' => [
				self::PREFIX . '<CS:remove><D:href/></CS:remove>' . self::SUFFIX,
				'{http://owncloud.org/ns}remove requires a href element',
			],
			'href with child element' => [
				self::PREFIX . '<CS:set><D:href><D:href>nested</D:href></D:href></CS:set>' . self::SUFFIX,
				'{http://owncloud.org/ns}set requires a href element',
			],
			'common-name with child element' => [
				self::PREFIX . '<CS:set><D:href>principal:principals/users/alice</D:href><CS:common-name><D:href>x</D:href></CS:common-name></CS:set>' . self::SUFFIX,
				'{http://owncloud.org/ns}set requires common-name to be text',
			],
		];
	}

	#[DataProvider('dataInvalid')]
	public function testInvalid(string $xml, string $message): void {
		$this->expectException(BadRequest::class);
		$this->expectExceptionMessage($message);

		$this->parse($xml);
	}
}
