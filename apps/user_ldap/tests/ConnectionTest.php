<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\ILDAPWrapper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class Test_Connection
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class ConnectionTest extends \Test\TestCase {
	protected ILDAPWrapper&MockObject $ldap;
	protected Connection $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->ldap = $this->createMock(ILDAPWrapper::class);
		// we use a mock here to replace the cache mechanism, due to missing DI in LDAP backend.
		$this->connection = $this->getMockBuilder(Connection::class)
			->onlyMethods(['getFromCache', 'writeToCache'])
			->setConstructorArgs([$this->ldap, '', null])
			->getMock();

		$this->ldap->expects($this->any())
			->method('areLDAPFunctionsAvailable')
			->willReturn(true);
	}

	public function testOriginalAgentUnchangedOnClone(): void {
		//background: upon login a bind is done with the user credentials
		//which is valid for the whole LDAP resource. It needs to be reset
		//to the agent's credentials
		$lw = $this->createMock(ILDAPWrapper::class);

		$connection = new Connection($lw, '', null);
		$agent = [
			'ldapAgentName' => 'agent',
			'ldapAgentPassword' => '123456',
		];
		$connection->setConfiguration($agent);

		$testConnection = clone $connection;
		$user = [
			'ldapAgentName' => 'user',
			'ldapAgentPassword' => 'password',
		];
		$testConnection->setConfiguration($user);

		$agentName = $connection->ldapAgentName;
		$agentPawd = $connection->ldapAgentPassword;

		$this->assertSame($agentName, $agent['ldapAgentName']);
		$this->assertSame($agentPawd, $agent['ldapAgentPassword']);
	}

	public function testUseBackupServer(): void {
		$mainHost = 'ldap://nixda.ldap';
		$backupHost = 'ldap://fallback.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $mainHost,
			'ldapPort' => 389,
			'ldapBackupHost' => $backupHost,
			'ldapBackupPort' => 389,
			'ldapAgentName' => 'uid=agent',
			'ldapAgentPassword' => 'SuchASecret'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

		$this->ldap->expects($this->any())
			->method('isResource')
			->willReturn(true);

		$this->ldap->expects($this->any())
			->method('setOption')
			->willReturn(true);

		$this->ldap->expects($this->exactly(3))
			->method('connect')
			->willReturn(ldap_connect('ldap://example.com'));

		$this->ldap->expects($this->any())
			->method('errno')
			->willReturn(0);

		// Not called often enough? Then, the fallback to the backup server is broken.
		$this->connection->expects($this->exactly(2))
			->method('getFromCache')
			->with('overrideMainServer')
			->will($this->onConsecutiveCalls(false, false, true, true));

		$this->connection->expects($this->once())
			->method('writeToCache')
			->with('overrideMainServer', true);

		$isThrown = false;
		$this->ldap->expects($this->exactly(3))
			->method('bind')
			->willReturnCallback(function () use (&$isThrown) {
				if (!$isThrown) {
					$isThrown = true;
					throw new ServerNotAvailableException();
				}
				return true;
			});

		$this->connection->init();
		$this->connection->resetConnectionResource();
		// with the second init() we test whether caching works
		$this->connection->init();
	}

	public function testDontUseBackupServerOnFailedAuth(): void {
		$mainHost = 'ldap://nixda.ldap';
		$backupHost = 'ldap://fallback.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $mainHost,
			'ldapPort' => 389,
			'ldapBackupHost' => $backupHost,
			'ldapBackupPort' => 389,
			'ldapAgentName' => 'uid=agent',
			'ldapAgentPassword' => 'SuchASecret'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

		$this->ldap->expects($this->any())
			->method('isResource')
			->willReturn(true);

		$this->ldap->expects($this->any())
			->method('setOption')
			->willReturn(true);

		$this->ldap->expects($this->once())
			->method('connect')
			->willReturn(ldap_connect('ldap://example.com'));

		$this->ldap->expects($this->any())
			->method('errno')
			->willReturn(49);

		$this->connection->expects($this->any())
			->method('getFromCache')
			->with('overrideMainServer')
			->willReturn(false);

		$this->connection->expects($this->never())
			->method('writeToCache');

		$this->ldap->expects($this->exactly(1))
			->method('bind')
			->willReturn(false);

		$this->connection->init();
	}

	public function testBindWithInvalidCredentials(): void {
		// background: Bind with invalid credentials should return false
		// and not throw a ServerNotAvailableException.

		$host = 'ldap://nixda.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $host,
			'ldapPort' => 389,
			'ldapBackupHost' => '',
			'ldapAgentName' => 'user',
			'ldapAgentPassword' => 'password'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

		$this->ldap->expects($this->any())
			->method('isResource')
			->willReturn(true);

		$this->ldap->expects($this->any())
			->method('setOption')
			->willReturn(true);

		$this->ldap->expects($this->any())
			->method('connect')
			->willReturn(ldap_connect('ldap://example.com'));

		$this->ldap->expects($this->once())
			->method('bind')
			->willReturn(false);

		// LDAP_INVALID_CREDENTIALS
		$this->ldap->expects($this->any())
			->method('errno')
			->willReturn(0x31);

		try {
			$this->assertFalse($this->connection->bind(), 'Connection::bind() should not return true with invalid credentials.');
		} catch (ServerNotAvailableException $e) {
			$this->fail('Failed asserting that exception of type "OC\ServerNotAvailableException" is not thrown.');
		}
	}

	public function testStartTlsNegotiationFailure(): void {
		// background: If Start TLS negotiation fails,
		// a ServerNotAvailableException should be thrown.

		$host = 'ldap://nixda.ldap';
		$port = 389;
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $host,
			'ldapPort' => $port,
			'ldapTLS' => true,
			'ldapBackupHost' => '',
			'ldapAgentName' => 'user',
			'ldapAgentPassword' => 'password'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

		$this->ldap->expects($this->any())
			->method('isResource')
			->willReturn(true);

		$this->ldap->expects($this->any())
			->method('connect')
			->willReturn(ldap_connect('ldap://example.com'));

		$this->ldap->expects($this->any())
			->method('setOption')
			->willReturn(true);

		$this->ldap->expects($this->any())
			->method('bind')
			->willReturn(true);

		$this->ldap->expects($this->any())
			->method('errno')
			->willReturn(0);

		$this->ldap->expects($this->any())
			->method('startTls')
			->willReturn(false);

		$this->expectException(ServerNotAvailableException::class);
		$this->expectExceptionMessage('Start TLS failed, when connecting to LDAP host ' . $host . '.');

		$this->connection->init();
	}
}
