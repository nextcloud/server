<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\FederatedCalendarAuth;
use OCA\DAV\DAV\Sharing\SharingMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class FederatedCalendarAuthTest extends TestCase {
	private FederatedCalendarAuth $auth;

	private SharingMapper&MockObject $sharingMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->sharingMapper = $this->createMock(SharingMapper::class);

		$this->auth = new FederatedCalendarAuth(
			$this->sharingMapper,
		);
	}

	private static function encodeBasicAuthHeader(array $userPass): string {
		return 'Basic ' . base64_encode(implode(':', $userPass));
	}

	public static function provideCheckData(): array {
		return [
			// Valid credentials
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				self::encodeBasicAuthHeader(['abcdef123', 'token']),
				[['uri' => 'cal1', 'principaluri' => 'principals/users/user1']],
				[true, 'principals/remote-users/abcdef123'],
			],
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				self::encodeBasicAuthHeader(['abcdef123', 'token']),
				[
					['uri' => 'other-cal', 'principaluri' => 'principals/users/user1'],
					['uri' => 'cal1', 'principaluri' => 'principals/users/user1'],
				],
				[true, 'principals/remote-users/abcdef123'],
			],

			// Invalid basic auth header
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				self::encodeBasicAuthHeader(['abcdef123']),
				null,
				[false, "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured"],
			],
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				'Bearer secret-bearer-token',
				null,
				[false, "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured"],
			],
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				null,
				null,
				[false, "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured"],
			],

			// Invalid request path
			[
				'calendars/user1/cal1',
				self::encodeBasicAuthHeader(['abcdef123', 'token']),
				null,
				[false, 'This request is not for a federated calendar'],
			],

			// No shared calendars (or invalid credentials)
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				self::encodeBasicAuthHeader(['abcdef123', 'token']),
				[],
				[false, 'Username or password was incorrect'],
			],

			// Shared calendar with invalid URI
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				self::encodeBasicAuthHeader(['abcdef123', 'token']),
				[['uri' => 'other-cal', 'principaluri' => 'principals/users/user1']],
				[false, 'Username or password was incorrect'],
			],

			// Shared calendar from invalid sharer
			[
				'remote-calendars/abcdef123/cal1_shared_by_user1',
				self::encodeBasicAuthHeader(['abcdef123', 'token']),
				[['uri' => 'cal1', 'principaluri' => 'principals/users/user2']],
				[false, 'Username or password was incorrect'],
			],
		];
	}

	#[DataProvider(methodName: 'provideCheckData')]
	public function testCheck(
		string $requestPath,
		?string $authHeader,
		?array $rows,
		array $expected,
	): void {
		$request = $this->createMock(RequestInterface::class);
		$request->method('getPath')
			->willReturn($requestPath);
		$request->method('getHeader')
			->with('Authorization')
			->willReturn($authHeader);
		$response = $this->createMock(ResponseInterface::class);

		if ($rows === null) {
			$this->sharingMapper->expects(self::never())
				->method('getSharedCalendarsForRemoteUser');
		} else {
			$this->sharingMapper->expects(self::once())
				->method('getSharedCalendarsForRemoteUser')
				->with('principals/remote-users/abcdef123', 'token')
				->willReturn($rows);
		}

		$this->assertEquals($expected, $this->auth->check($request, $response));
	}
}
