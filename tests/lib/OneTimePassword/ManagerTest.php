<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\OneTimePassword;

use OC\OneTimePassword\Manager;
use OC\OneTimePassword\OneTimePassword;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\OneTimePassword\Events\GetOneTimePasswordProvidersEvent;
use OCP\OneTimePassword\Events\SendOneTimePasswordEvent;
use OCP\OneTimePassword\Exceptions\OTPProviderNotFoundException;
use OCP\OneTimePassword\Exceptions\OTPSendException;
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
			$this->hasher);

	}

	private function createManagerMock(): MockBuilder {
		return $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->logger,
				$this->secureRandom,
				$this->dispatcher,
				$this->connection,
				$this->hasher
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
		$qb->method('setValue')->willReturn($qb);
		$qb->method('set')->willReturn($qb);
		$expr = $this->createMock(IExpressionBuilder::class);
		$expr->method('eq')->willReturnCallback(function (string $arg1, int $arg2) {
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
			[1, 'mock', 'receiver', null, null],
			[5, 'debug', 'receiver', 'pw', self::parseDateTime('2026-07-01 02:30:12')],
			[2, 'test', 'receiver', null, self::parseDateTime('2026-07-01 16:30:12')],
			[12, 'mail', 'receiver', 'pw', null],
		];
	}

	#[DataProvider('dataTestCreate')]
	public function testCreate($otpId, $providerId, $recipient, $passwordHash, $expiration): void {
		$expected = (new OneTimePassword($providerId, $recipient))
			->setId($otpId)
			->setPassword($passwordHash)
			->setExpirationTime($expiration);
		$qb = $this->getBasicQueryBuilderMock();
		$qb->method('createNamedParameter')->willReturnArgument(0);
		$qb->method('executeStatement');
		$qb->method('getLastInsertId')->willReturn($otpId);

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

		$qb->method('setValue')
			->willReturnCallback(function ($name, $value, $type = null) use ($setValueExpected, &$setValueCalls): void {
				$this->assertFalse($setValueCalls[$name], $name . ' was already set');
				$setValueCalls[$name] = true;
				$this->assertEquals($setValueExpected[$name], $value);
			});

		$actual = $this->manager->createOTP($providerId, $recipient, $passwordHash, $expiration);

		$this->assertTrue($setValueCalls['provider'], 'provider was not set');
		$this->assertTrue($setValueCalls['recipient'], 'recipient was not set');
		$this->assertTrue($setValueCalls['password'], 'password was not set');
		$this->assertTrue($setValueCalls['expiration'], 'expiration was not set');

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
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new GetOneTimePasswordProvidersEvent());
		$this->manager->getOTPProviders();
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
		$providers = [];
		foreach ($availableProviderIds as $pId) {
			$provider = $this->createMock(IOneTimePasswordProvider::class);
			$provider->method('getProviderId')->willReturn($pId);
			$providers[] = $provider;
			if ($expected === null && $pId === $providerId) {
				$expected = $provider;
			}
		}

		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function (GetOneTimePasswordProvidersEvent $event) use ($providers): void {
				foreach ($providers as $p) {
					$event->addProvider($p);
				}
			});
		$actual = $this->manager->getOTPProviderById($providerId);

		$this->assertEquals($expected, $actual);
	}

	public static function dataTestSendOTP() {
		return [
			[new OneTimePassword('mock', 'reca')],
			[new OneTimePassword('broken', 'recd')],
			[new OneTimePassword('invalid', 'recc')],
			[new OneTimePassword('mail', 'recd')],
		];
	}

	#[DataProvider('dataTestSendOTP')]
	public function testSendOTP(OneTimePassword $otp) {
		if ($otp->getProviderId() === 'invalid') {
			$this->expectException(OTPProviderNotFoundException::class);
		} elseif ($otp->getProviderId() === 'broken') {
			$this->expectException(OTPSendException::class);
		}

		$manager = $this->createManagerMock()
			->onlyMethods(['updateOTP'])
			->getMock();

		$passwordEventCount = 0;

		$this->dispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(function (SendOneTimePasswordEvent|GenerateSecurePasswordEvent $event) use (&$passwordEventCount): void {
				if ($event instanceof GenerateSecurePasswordEvent) {
					$passwordEventCount += 1;
					return;
				}

				$this->assertFalse($event->getWasConsumed());
				$this->assertNotNull($event->getPassword());
				switch ($event->getProvider()) {
					case 'invalid':
						return;
					case 'broken':
						$event->markConsumed();
						$event->setError('provider is broken');
						break;
					default:
						$event->markConsumed();
						$event->setMessage($event->getProvider() . ' processed');
				}
			});

		$updateCountExpected = $otp->getProviderId() === 'broken' ? 2 : 1;
		$updateCountActual = 0;
		$manager->expects($this->exactly($updateCountExpected))
			->method('updateOTP')
			->willReturnCallback(function (IOneTimePassword $otp) use (&$updateCountActual): void {
				if ($updateCountActual === 0) {
					$this->assertNotNull($otp->getPassword());
					$this->assertNotNull($otp->getExpirationTime());
				} else {
					$this->assertNull($otp->getPassword());
				}
				$updateCountActual += 1;
			});

		$manager->sendOTP($otp);

		$this->assertEquals($updateCountExpected, $updateCountActual);
		$this->assertEquals(1, $passwordEventCount);

	}

	public static function dataTestValidateOTP() {
		return [
			// otp(provider, recipient, pw,           expires),  given pw, valid?, newHash?
			[ 'test',        'reca',    null,         null,      'pw',     false,  false ], // empty otp password should fail
			[ 'test',        'recb',    'hashed(pw)', null,      'pw',     true,   false ], // correct password should succeed
			[ 'test',        'recc',    'hashed(pw)', null,      'xy',     false,  false ], // wrong password should fail
			[ 'test',        'recd',    'hashed(pw)', null,      'pw',     true,   true  ], // hash should be regenerated if necessary
			[ 'test',        'rece',    'hashed(pw)', self::parseDateTime('1990-01-01 00:00:00'), 'pw', false, false ], // expired OTP should fail
			[ 'test',        'rece',    'hashed(pw)', self::parseDateTime('3000-01-01 00:00:00'), 'pw', true, false ], // non-expired OTP should succeed
		];
	}

	#[DataProvider('dataTestValidateOTP')]
	public function testValidateOTP(string $provider, string $recipient, ?string $hashedPw, ?\DateTime $expires, ?string $password, bool $expectedValid, bool $generateNewHash) {
		$otp = (new OneTimePassword($provider, $recipient))
			->setPassword($hashedPw)
			->setExpirationTime($expires);
		$manager = $this->createManagerMock()
			->onlyMethods(['updateOTP'])
			->getMock();

		$this->hasher->method('hash')->willReturnCallback(function (string $pw) {
			return "hashed($pw)";
		});
		$this->hasher->method('verify')->willReturnCallback(function (string $pw, string $hashed, string &$newHash) use ($generateNewHash) {
			if ($generateNewHash) {
				$newHash = "hashedNew($pw)";
			}
			return "hashed($pw)" === $hashed;
		});

		if ($generateNewHash) {
			$manager->expects($this->once())
				->method('updateOTP')
				->with((new OneTimePassword($provider, $recipient))
					->setPassword("hashedNew($password)")
					->setExpirationTime($expires));
		}

		$valid = $manager->validateOTP($otp, $password);

		$this->assertEquals($expectedValid, $valid);
	}

}
