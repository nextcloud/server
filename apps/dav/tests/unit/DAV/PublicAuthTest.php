<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\DAV\PublicAuth;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class PublicAuthTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private PublicAuth $auth;

	protected function setUp(): void {
		parent::setUp();
		$this->userSession = $this->createMock(IUserSession::class);
		$this->auth = new PublicAuth($this->userSession);
	}

	private function check(string $path): array {
		$request = $this->createMock(RequestInterface::class);
		$request->method('getPath')->willReturn($path);
		return $this->auth->check($request, $this->createMock(ResponseInterface::class));
	}

	public static function dataPublicPaths(): array {
		return [
			['public-calendars'],
			['public-calendars/token'],
			['public-calendars/token/event.ics'],
			['principals/system/public'],
		];
	}

	#[DataProvider('dataPublicPaths')]
	public function testPublicPathIsAnonymous(string $path): void {
		$this->userSession->expects($this->once())
			->method('setIncognitoMode')
			->with(true);

		$this->assertSame([true, 'principals/system/public'], $this->check($path));
	}

	public static function dataNonPublicPaths(): array {
		return [
			['principals/system/publicX'],
			['public-calendarsX/token'],
			['principals/system'],
			['calendars/alice/personal'],
			['files/alice/public-calendars'],
			[''],
		];
	}

	#[DataProvider('dataNonPublicPaths')]
	public function testOtherPathsAreNotPublic(string $path): void {
		$this->userSession->expects($this->never())
			->method('setIncognitoMode');

		$this->assertFalse($this->check($path)[0]);
	}
}
