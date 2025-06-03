<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\LDAP;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class LDAPTest extends TestCase {
	private LDAP&MockObject $ldap;

	protected function setUp(): void {
		parent::setUp();
		$this->ldap = $this->getMockBuilder(LDAP::class)
			->onlyMethods(['invokeLDAPMethod'])
			->getMock();
	}

	public static function errorProvider(): array {
		return [
			[
				'ldap_search(): Partial search results returned: Sizelimit exceeded at /srv/http/nextcloud/master/apps/user_ldap/lib/LDAP.php#292',
				false
			],
			[
				'Some other error', true
			]
		];
	}

	/**
	 * @dataProvider errorProvider
	 */
	public function testSearchWithErrorHandler(string $errorMessage, bool $passThrough): void {
		$wasErrorHandlerCalled = false;
		$errorHandler = function ($number, $message, $file, $line) use (&$wasErrorHandlerCalled): void {
			$wasErrorHandlerCalled = true;
		};

		set_error_handler($errorHandler);

		$this->ldap
			->expects($this->once())
			->method('invokeLDAPMethod')
			->with('search', $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything())
			->willReturnCallback(function () use ($errorMessage): void {
				trigger_error($errorMessage);
			});

		$fakeResource = ldap_connect();
		$this->ldap->search($fakeResource, 'base', 'filter', []);
		$this->assertSame($wasErrorHandlerCalled, $passThrough);

		restore_error_handler();
	}

	public function testModReplace(): void {
		$link = $this->createMock(LDAP::class);
		$userDN = 'CN=user';
		$password = 'MyPassword';
		$this->ldap
			->expects($this->once())
			->method('invokeLDAPMethod')
			->with('mod_replace', $link, $userDN, ['userPassword' => $password])
			->willReturn(true);

		$this->assertTrue($this->ldap->modReplace($link, $userDN, $password));
	}
}
