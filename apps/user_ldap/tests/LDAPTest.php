<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\LDAP;
use Test\TestCase;

class LDAPTest extends TestCase {
	/** @var LDAP|\PHPUnit\Framework\MockObject\MockObject */
	private $ldap;

	protected function setUp(): void {
		parent::setUp();
		$this->ldap = $this->getMockBuilder(LDAP::class)
			->setMethods(['invokeLDAPMethod'])
			->getMock();
	}

	public function errorProvider() {
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
	 * @param string $errorMessage
	 * @param bool $passThrough
	 * @dataProvider errorProvider
	 */
	public function testSearchWithErrorHandler(string $errorMessage, bool $passThrough) {
		$wasErrorHandlerCalled = false;
		$errorHandler = function ($number, $message, $file, $line) use (&$wasErrorHandlerCalled) {
			$wasErrorHandlerCalled = true;
		};

		set_error_handler($errorHandler);

		$this->ldap
			->expects($this->once())
			->method('invokeLDAPMethod')
			->with('search', $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything())
			->willReturnCallback(function () use ($errorMessage) {
				trigger_error($errorMessage);
			});

		$fakeResource = ldap_connect();
		$this->ldap->search($fakeResource, 'base', 'filter', []);
		$this->assertSame($wasErrorHandlerCalled, $passThrough);

		restore_error_handler();
	}

	public function testModReplace() {
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
