<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OneTimePassword;

use OC\OneTimePassword\Manager;
use OC\OneTimePassword\OneTimePassword;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\OneTimePassword\Exceptions\OTPProviderNotFoundException;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\Exceptions\OTPThrottledException;
use OCP\OneTimePassword\IOneTimePassword;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class OneTimePasswordTest
 *
 * @package Test\OneTimePassword
 */
#[Group(name: 'OTP')]
class ManagerTest extends TestCase {
	protected Manager $manager;
	protected LoggerInterface&MockObject $logger;
	protected ISecureRandom&MockObject $secureRandom;
	protected IEventDispatcher&MockObject $dispatcher;
	protected IDBConnection&MockObject $connection;
	protected IHasher&MockObject $hasher;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->hasher = $this->createMock(IHasher::class);

		$this->manager = new Manager(
			$this->logger,
			$this->secureRandom,
			$this->dispatcher,
			$this->connection,
			$this->hasher,
		);
	}

	private function createManagerMock(): MockBuilder {
		return $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->logger,
				$this->secureRandom,
				$this->dispatcher,
				$this->connection,
				$this->hasher,
			]);
	}

	private function getBasicQueryBuilderMock(): IQueryBuilder&MockObject {
		$qb = $this->createMock(IQueryBuilder::class);
		$qb->method('delete')->willReturn($qb);
		$qb->method('select')->willReturn($qb);
		$qb->method('insert')->willReturn($qb);
		$qb->method('update')->willReturn($qb);
		$qb->method('from')->willReturn($qb);
		$qb->method('where')->willReturn($qb);
		$qb->method('andWhere')->willReturn($qb);
		$qb->method('setValue')->willReturn($qb);
		$qb->method('set')->willReturn($qb);
		$expr = $this->createMock(IExpressionBuilder::class);
		$expr->method('eq')->willReturnCallback(function (string $arg1, $arg2) {
			return $arg1 . '=' . $arg2;
		});
		$qb->method('expr')->willReturn($expr);
		return $qb;
	}

	public static function dataTestDelete(): array {
		return [
			['email', 'someon@somewhere', 'secret', '2026-07-01']
		];
	}

	#[DataProvider('dataTestDelete')]
	public function testDelete($providerId, $recipient, $passwordHash, $expiration): void {
		$otp = (new OneTimePassword($providerId, $recipient))
			->setId(1) // usually set by DB
			->setPassword($passwordHash)
			->setExpirationTime($expiration !== null ? new \DateTime($expiration) : null);

		$qb = $this->getBasicQueryBuilderMock();
		$qb->method('createNamedParameter')->willReturnArgument(0);
		$qb->method('executeStatement')->willReturn(1);
		$this->connection
			->method('getQueryBuilder')
			->willReturn($qb);

		$qb->expects($this->once())
			->method('delete')
			->with('one_time_password');
		$qb->expects($this->once())
			->method('where')
			->with('id=1');

		$this->manager->deleteOTP($otp->getId());
	}

	public static function dataTestGet(): array {
		return [
			[1, 'email', 'someone@somewhere', 'secret', '2026-07-01 00:00:00'],
			[5, 'mcok', 'someoneelse@somewhere', null, null],
			[1, 'email', 'someon@somewhere', 'secret', null],
		];
	}

	#[DataProvider('dataTestGet')]
	public function testGet($otpId, $providerId, $recipient, $passwordHash, $expiration): void {
		$otpData = [
			'id' => $otpId,
			'provider' => $providerId,
			'recipient' => $recipient,
			'password' => $passwordHash,
			'expiration' => $expiration,
		];

		$expirationDT = $expiration === null ? null : self::parseDateTime($expiration);

		$cursor = $this->createMock(IResult::class);
		$cursor->method('fetch')->willReturn($otpData);
		$cursor->method('closeCursor');
		$qb = $this->getBasicQueryBuilderMock();
		$qb->method('createNamedParameter')->willReturnArgument(0);
		$qb->method('executeQuery')->willReturn($cursor);

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$otp = $this->manager->getOTP($otpId);

		$this->assertEquals($otpId, $otp->getId());
		$this->assertEquals($providerId, $otp->getProviderId());
		$this->assertEquals($recipient, $otp->getRecipient());
		$this->assertEquals($passwordHash, $otp->getPassword());
		$this->assertEquals($expirationDT, $otp->getExpirationTime());
	}

	private static function parseDateTime(string $datetime) {
		return \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
	}

	public static function dataTestCreate() {
		return [
			[1, 'mock', 'receiver', ''],
			[2, 'invalid', 'receiver', ''],
			[3, 'mock', 'someone@example.com', '^[^@]+@[^@]+\\.[^@]+$'],
			[4, 'mock', 'admin@localhost', '^[^@]+@[^@]+\\.[^@]+$'],
			[5, 'mock', '', '^[^@]+@[^@]+\\.[^@]+$'],
		];
	}

	#[DataProvider('dataTestCreate')]
	public function testCreate($otpId, $providerId, $recipient, string $recipientPattern): void {
		$expected = (new OneTimePassword($providerId, $recipient))
			->setId($otpId);
		$qb = $this->getBasicQueryBuilderMock();
		$qb->method('createNamedParameter')->willReturnArgument(0);
		$qb->method('executeStatement');
		$qb->method('getLastInsertId')->willReturn($otpId);

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$setValueCalls = [
			'provider' => false,
			'recipient' => false,
		];
		$setValueExpected = [
			'provider' => $providerId,
			'recipient' => $recipient,
		];

		$qb->method('setValue')
			->willReturnCallback(function ($name, $value, $type = null) use ($setValueExpected, &$setValueCalls): void {
				$this->assertFalse($setValueCalls[$name], $name . ' was already set');
				$setValueCalls[$name] = true;
				$this->assertEquals($setValueExpected[$name], $value);
			});

		$validRecipient = $recipientPattern === '' || preg_match('/' . $recipientPattern . '/', $recipient) === 1;
		if ($providerId === 'invalid') {
			$this->expectException(OTPProviderNotFoundException::class);
		} elseif (!$validRecipient) {
			$this->expectException(\InvalidArgumentException::class);
			$qb->expects($this->never())->method('executeStatement');
		}
		$provider = $this->createMock(IOneTimePasswordProvider::class);
		$provider->method('getProviderId')->willReturn($providerId === 'invalid' ? 'valid' : $providerId);
		$provider->method('getRecipientPattern')->willReturn($recipientPattern);
		$this->manager->registerOTPProvider($provider);
		$actual = $this->manager->createOTP($providerId, $recipient);

		$this->assertTrue($setValueCalls['provider'], 'provider was not set');
		$this->assertTrue($setValueCalls['recipient'], 'recipient was not set');

		$this->assertEquals($expected, $actual);
	}

	public static function dataTestUpdate() {
		return [
			[1, 'mock', 'receiver', null, null],
			[5, 'debug', 'receiver', 'pw', self::parseDateTime('2026-07-01 02:30:12')],
			[2, 'test', 'receiver', null, self::parseDateTime('2026-07-01 16:30:12')],
			[12, 'mail', 'receiver', 'pw', null],
		];
	}

	#[DataProvider('dataTestUpdate')]
	public function testUpdate($otpId, $providerId, $recipient, $passwordHash, $expiration): void {
		$expected = (new OneTimePassword($providerId, $recipient))
			->setId($otpId)
			->setPassword($passwordHash)
			->setExpirationTime($expiration);
		$qb = $this->getBasicQueryBuilderMock();
		$qb->method('createNamedParameter')->willReturnArgument(0);
		$qb->method('executeStatement');
		$qb->method('getLastInsertId')->willReturn($otpId);
		$qb->method('executeStatement');

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$setValueCalls = [
			'provider' => false,
			'recipient' => false,
			'password' => false,
			'expiration' => false
		];
		$setValueExpected = [
			'provider' => $providerId,
			'recipient' => $recipient,
			'password' => $passwordHash,
			'expiration' => $expiration
		];

		$qb->method('set')
			->willReturnCallback(function ($name, $value, $type = null) use ($setValueExpected, &$setValueCalls): void {
				$this->assertFalse($setValueCalls[$name], $name . ' was already set');
				$setValueCalls[$name] = true;
				$this->assertEquals($setValueExpected[$name], $value);
			});

		$qb->expects($this->once())
			->method('where')
			->with("id=$otpId");

		$this->manager->updateOTP($expected);

		$this->assertTrue($setValueCalls['provider'], 'provider was not set');
		$this->assertTrue($setValueCalls['recipient'], 'recipient was not set');
		$this->assertTrue($setValueCalls['password'], 'password was not set');
		$this->assertTrue($setValueCalls['expiration'], 'expiration was not set');

	}

	public function testGetOTPProviders() {
		$provider = $this->createMock(IOneTimePasswordProvider::class);
		$this->manager->registerOTPProvider($provider);
		$providers = $this->manager->getOTPProviders();

		$this->assertContains($provider, $providers);
	}

	public static function dataTestGetOTPProviderByIdData() {
		return [
			['email', [], true],
			['mock', ['mock', 'debug'], false],
			['debug', ['debug'], false]
		];
	}

	#[DataProvider('dataTestGetOTPProviderByIdData')]
	public function testGetOTPProviderById($providerId, $availableProviderIds, $shouldThrow) {
		if ($shouldThrow) {
			$this->expectException(OTPProviderNotFoundException::class);
		}

		$expected = null;
		foreach ($availableProviderIds as $pId) {
			$provider = $this->createMock(IOneTimePasswordProvider::class);
			$provider->method('getProviderId')->willReturn($pId);
			$this->manager->registerOTPProvider($provider);
			if ($expected === null && $pId === $providerId) {
				$expected = $provider;
			}
		}

		$actual = $this->manager->getOTPProviderById($providerId);

		$this->assertEquals($expected, $actual);
	}

	public function testSendOTPMissingProvider() {
		$otp = new OneTimePassword('invalid', 'receiver');
		$this->expectException(OTPProviderNotFoundException::class);

		$manager = $this->createManagerMock()
			->onlyMethods(['updateOTP'])
			->getMock();

		$mockProvider = $this->createMock(IOneTimePasswordProvider::class);
		$mockProvider->method('getProviderId')->willReturn('valid');
		$mockProvider->expects($this->never())->method('sendOTP');
		$manager->registerOTPProvider($mockProvider);
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$manager->expects($this->never())
			->method('updateOTP');

		$this->expectException(OTPProviderNotFoundException::class);

		$manager->sendOTP($otp, 'testmessage');
	}

	public static function dataTestSendOTP() {
		return [
			[new OneTimePassword('mock', 'reca')],
			[new OneTimePassword('broken', 'recd')],
			[new OneTimePassword('mail', 'recd')],
		];
	}

	#[DataProvider('dataTestSendOTP')]
	public function testSendOTP(OneTimePassword $otp) {
		if ($otp->getProviderId() === 'broken') {
			$this->expectException(OTPSendException::class);
		}

		$manager = $this->createManagerMock()
			->onlyMethods(['updateOTP'])
			->getMock();

		$mockProvider = $this->createMock(IOneTimePasswordProvider::class);
		$mockProvider->method('getProviderId')->willReturn($otp->getProviderId());
		if ($otp->getProviderId() === 'broken') {
			$mockProvider->expects($this->once())->method('sendOTP')
				->willThrowException(new OTPSendException('broken provider'));
		} else {
			$mockProvider->expects($this->once())->method('sendOTP')
				->with($otp->getRecipient(), $this->anything(), 'testmessage');
		}

		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(GenerateSecurePasswordEvent::class));

		$updateCountExpected = 1;
		if ($otp->getProviderId() === 'broken') {
			$updateCountExpected = 2; // will generate password and invalidate after send failure
		}

		$updateCountActual = 0;
		$manager->expects($this->exactly($updateCountExpected))
			->method('updateOTP')
			->willReturnCallback(function (IOneTimePassword $otp) use (&$updateCountActual): void {
				if ($updateCountActual === 0) {
					// sets a generated password
					$this->assertNotNull($otp->getPassword());
					$this->assertNotNull($otp->getExpirationTime());
				} else {
					// clears a generated password (after send failure)
					$this->assertNull($otp->getPassword());
				}
				$updateCountActual += 1;
			});

		try {
			$manager->registerOTPProvider($mockProvider);
			$manager->sendOTP($otp, 'testmessage');
		} finally {
			$this->assertEquals($updateCountExpected, $updateCountActual);
		}
	}

	public static function dataTestSendOTPThrottled(): array {
		return [
			'sent 30 seconds ago' => ['PT14M30S', true],
			'sent 5 minutes ago' => ['PT10M', false],
		];
	}

	#[DataProvider('dataTestSendOTPThrottled')]
	public function testSendOTPThrottled(string $remainingValidity, bool $throttled): void {
		$otp = (new OneTimePassword('mock', 'reca'))
			->setPassword('hashed(pw)')
			->setExpirationTime((new \DateTime())->add(new \DateInterval($remainingValidity)));

		$manager = $this->createManagerMock()
			->onlyMethods(['updateOTP'])
			->getMock();
		$mockProvider = $this->createMock(IOneTimePasswordProvider::class);
		$mockProvider->method('getProviderId')->willReturn('mock');
		$manager->registerOTPProvider($mockProvider);

		if ($throttled) {
			$this->expectException(OTPThrottledException::class);
			$mockProvider->expects($this->never())->method('sendOTP');
			$manager->expects($this->never())->method('updateOTP');
		} else {
			$mockProvider->expects($this->once())->method('sendOTP');
			$manager->expects($this->once())->method('updateOTP');
		}

		$manager->sendOTP($otp, 'testmessage');
	}

	public static function dataTestValidateOTP() {
		return [
			// otp(provider, recipient, pw,           expires),  given pw, consumed rows, valid?
			[ 'test',        'reca',    null,         null,      'pw',     1,             false ], // empty otp password should fail
			[ 'test',        'recb',    'hashed(pw)', null,      'pw',     1,             true  ], // correct password should succeed
			[ 'test',        'recc',    'hashed(pw)', null,      'xy',     1,             false ], // wrong password should fail
			[ 'test',        'recd',    'hashed(pw)', null,      null,     1,             false ], // missing password should fail
			[ 'test',        'rece',    'hashed(pw)', self::parseDateTime('1990-01-01 00:00:00'), 'pw', 1, false ], // expired OTP should fail
			[ 'test',        'rece',    'hashed(pw)', self::parseDateTime('3000-01-01 00:00:00'), 'pw', 1, true ], // non-expired OTP should succeed
			[ 'test',        'recf',    'hashed(pw)', null,      'pw',     0,             false ], // OTP used up concurrently should fail
		];
	}

	#[DataProvider('dataTestValidateOTP')]
	public function testValidateOTP(string $provider, string $recipient, ?string $hashedPw, ?\DateTime $expires, ?string $password, int $consumedRows, bool $expectedValid) {
		$otp = (new OneTimePassword($provider, $recipient))
			->setId(7)
			->setPassword($hashedPw)
			->setExpirationTime($expires);

		$this->hasher->method('verify')->willReturnCallback(function (string $pw, string $hashed) {
			return "hashed($pw)" === $hashed;
		});

		$qb = $this->getBasicQueryBuilderMock();
		$qb->method('createNamedParameter')->willReturnArgument(0);
		$qb->method('executeStatement')->willReturn($consumedRows);
		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$reachesUpdate = $hashedPw !== null && $password !== null && $hashedPw === "hashed($password)"
			&& ($expires === null || $expires > new \DateTime());
		$qb->expects($reachesUpdate ? $this->once() : $this->never())
			->method('update')
			->with('one_time_password');
		if ($reachesUpdate) {
			$qb->expects($this->once())->method('set')->with('password', null);
			$qb->expects($this->once())->method('where')->with('id=7');
			$qb->expects($this->once())->method('andWhere')->with("password=$hashedPw");
		}

		$valid = $this->manager->validateOTP($otp, $password);

		$this->assertEquals($expectedValid, $valid);
		$this->assertEquals($expectedValid ? null : $hashedPw, $otp->getPassword());
	}

	public function testRegisterProvider(): void {
		$mockProvider = $this->createMock(IOneTimePasswordProvider::class);
		$mockProvider->method('getProviderId')->willReturn('mockprovider');
		$this->manager->registerOTPProvider($mockProvider);
		$this->assertContains($mockProvider, $this->manager->getOTPProviders());
		$this->assertEquals($mockProvider, $this->manager->getOTPProviderById('mockprovider'));
	}
}
