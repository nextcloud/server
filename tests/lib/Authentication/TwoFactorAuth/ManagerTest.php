<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Authentication\TwoFactorAuth;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider as TokenProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\TwoFactorAuth\IActivatableAtLogin;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use function reset;

class ManagerTest extends TestCase {
	private IUser&MockObject $user;
	private IProvider&MockObject $fakeProvider;
	private IProvider&MockObject $backupProvider;

	private Manager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('user-uid');

		$this->manager = $this->createInstanceWithMocks(Manager::class);

		$this->fakeProvider = $this->createMock(IProvider::class);
		$this->fakeProvider->method('getId')->willReturn('email');

		$this->backupProvider = $this->getMockBuilder('\OCP\Authentication\TwoFactorAuth\IProvider')->getMock();
		$this->backupProvider->method('getId')->willReturn('backup_codes');
		$this->backupProvider->method('isTwoFactorAuthEnabledForUser')->willReturn(true);
	}

	private function prepareNoProviders() {
		$this->mocks[ProviderLoader::class]->method('getProviders')
			->with($this->user)
			->willReturn([]);
	}

	private function prepareProviders() {
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->with($this->user)
			->willReturn([
				$this->fakeProvider->getId() => true,
			]);
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->with($this->user)
			->willReturn([$this->fakeProvider]);
	}

	private function prepareProvidersWitBackupProvider() {
		$this->mocks[ProviderLoader::class]->method('getProviders')
			->with($this->user)
			->willReturn([
				$this->fakeProvider,
				$this->backupProvider,
			]);
	}

	public function testIsTwoFactorAuthenticatedEnforced(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(true);

		$enabled = $this->manager->isTwoFactorAuthenticated($this->user);

		$this->assertTrue($enabled);
	}

	public function testIsTwoFactorAuthenticatedNoProviders(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->willReturn([]); // No providers registered
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->willReturn([]); // No providers loadable

		$this->assertFalse($this->manager->isTwoFactorAuthenticated($this->user));
	}

	public function testIsTwoFactorAuthenticatedOnlyBackupCodes(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->willReturn([
				'backup_codes' => true,
			]);
		$backupCodesProvider = $this->createMock(IProvider::class);
		$backupCodesProvider
			->method('getId')
			->willReturn('backup_codes');
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->willReturn([
				$backupCodesProvider,
			]);

		$this->assertFalse($this->manager->isTwoFactorAuthenticated($this->user));
	}

	public function testIsTwoFactorAuthenticatedFailingProviders(): void {
		$this->mocks[MandatoryTwoFactor::class]->expects($this->once())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->willReturn([
				'twofactor_totp' => true,
				'twofactor_u2f' => false,
			]); // Two providers registered, but …
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->willReturn([]); // … none of them is able to load, however …

		// … 2FA is still enforced
		$this->assertTrue($this->manager->isTwoFactorAuthenticated($this->user));
	}

	public static function providerStatesFixData(): array {
		return [
			[false, false],
			[true, true],
		];
	}

	/**
	 * If the 2FA registry has not been populated when a user logs in,
	 * the 2FA manager has to first fix the state before it checks for
	 * enabled providers.
	 *
	 * If any of these providers is active, 2FA is enabled
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerStatesFixData')]
	public function testIsTwoFactorAuthenticatedFixesProviderStates(bool $providerEnabled, bool $expected): void {
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->willReturn([]); // Nothing registered yet
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->willReturn([
				$this->fakeProvider
			]);
		$this->fakeProvider->expects($this->once())
			->method('isTwoFactorAuthEnabledForUser')
			->with($this->user)
			->willReturn($providerEnabled);
		if ($providerEnabled) {
			$this->mocks[IRegistry::class]->expects($this->once())
				->method('enableProviderFor')
				->with(
					$this->fakeProvider,
					$this->user
				);
		} else {
			$this->mocks[IRegistry::class]->expects($this->once())
				->method('disableProviderFor')
				->with(
					$this->fakeProvider,
					$this->user
				);
		}

		$this->assertEquals($expected, $this->manager->isTwoFactorAuthenticated($this->user));
	}

	public function testGetProvider(): void {
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->with($this->user)
			->willReturn([
				$this->fakeProvider->getId() => true,
			]);
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->with($this->user)
			->willReturn([$this->fakeProvider]);

		$provider = $this->manager->getProvider($this->user, $this->fakeProvider->getId());

		$this->assertSame($this->fakeProvider, $provider);
	}

	public function testGetInvalidProvider(): void {
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->with($this->user)
			->willReturn([]);
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->with($this->user)
			->willReturn([]);

		$provider = $this->manager->getProvider($this->user, 'nonexistent');

		$this->assertNull($provider);
	}

	public function testGetLoginSetupProviders(): void {
		$provider1 = $this->createMock(IProvider::class);
		$provider2 = $this->createMock(IActivatableAtLogin::class);
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->with($this->user)
			->willReturn([
				$provider1,
				$provider2,
			]);

		$providers = $this->manager->getLoginSetupProviders($this->user);

		$this->assertCount(1, $providers);
		$this->assertSame($provider2, reset($providers));
	}

	public function testGetProviders(): void {
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->with($this->user)
			->willReturn([
				$this->fakeProvider->getId() => true,
			]);
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->with($this->user)
			->willReturn([$this->fakeProvider]);
		$expectedProviders = [
			'email' => $this->fakeProvider,
		];

		$providerSet = $this->manager->getProviderSet($this->user);
		$providers = $providerSet->getProviders();

		$this->assertEquals($expectedProviders, $providers);
		$this->assertFalse($providerSet->isProviderMissing());
	}

	public function testGetProvidersOneMissing(): void {
		$this->mocks[IRegistry::class]->expects($this->once())
			->method('getProviderStates')
			->with($this->user)
			->willReturn([
				$this->fakeProvider->getId() => true,
			]);
		$this->mocks[ProviderLoader::class]->expects($this->once())
			->method('getProviders')
			->with($this->user)
			->willReturn([]);
		$expectedProviders = [
			'email' => $this->fakeProvider,
		];

		$providerSet = $this->manager->getProviderSet($this->user);

		$this->assertTrue($providerSet->isProviderMissing());
	}

	public function testVerifyChallenge(): void {
		$this->prepareProviders();

		$challenge = 'passme';
		$event = $this->createMock(IEvent::class);
		$this->fakeProvider->expects($this->once())
			->method('verifyChallenge')
			->with($this->user, $challenge)
			->willReturn(true);
		$this->mocks[ISession::class]->expects($this->once())
			->method('get')
			->with('two_factor_remember_login')
			->willReturn(false);

		$calls = [
			['two_factor_auth_uid'],
			['two_factor_remember_login'],
		];
		$this->mocks[ISession::class]->expects($this->exactly(2))
			->method('remove')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->mocks[ISession::class]->expects($this->once())
			->method('set')
			->with(Manager::SESSION_UID_DONE, $this->user->getUID());
		$this->mocks[ISession::class]->method('getId')
			->willReturn('mysessionid');
		$this->mocks[IManager::class]->expects($this->once())
			->method('generateEvent')
			->willReturn($event);
		$event->expects($this->once())
			->method('setApp')
			->with($this->equalTo('core'))
			->willReturnSelf();
		$event->expects($this->once())
			->method('setType')
			->with($this->equalTo('security'))
			->willReturnSelf();
		$event->expects($this->once())
			->method('setAuthor')
			->with($this->equalTo($this->user->getUID()))
			->willReturnSelf();
		$event->expects($this->once())
			->method('setAffectedUser')
			->with($this->equalTo($this->user->getUID()))
			->willReturnSelf();
		$this->fakeProvider
			->method('getDisplayName')
			->willReturn('Fake 2FA');
		$event->expects($this->once())
			->method('setSubject')
			->with($this->equalTo('twofactor_success'), $this->equalTo([
				'provider' => 'Fake 2FA',
			]))
			->willReturnSelf();
		$token = $this->createMock(IToken::class);
		$this->mocks[TokenProvider::class]->method('getToken')
			->with('mysessionid')
			->willReturn($token);
		$token->method('getId')
			->willReturn(42);
		$this->mocks[IConfig::class]->expects($this->once())
			->method('deleteUserValue')
			->with($this->user->getUID(), 'login_token_2fa', '42');

		$result = $this->manager->verifyChallenge('email', $this->user, $challenge);

		$this->assertTrue($result);
	}

	public function testVerifyChallengeInvalidProviderId(): void {
		$this->prepareProviders();

		$challenge = 'passme';
		$this->fakeProvider->expects($this->never())
			->method('verifyChallenge')
			->with($this->user, $challenge);
		$this->mocks[ISession::class]->expects($this->never())
			->method('remove');

		$this->assertFalse($this->manager->verifyChallenge('dontexist', $this->user, $challenge));
	}

	public function testVerifyInvalidChallenge(): void {
		$this->prepareProviders();

		$challenge = 'dontpassme';
		$event = $this->createMock(IEvent::class);
		$this->fakeProvider->expects($this->once())
			->method('verifyChallenge')
			->with($this->user, $challenge)
			->willReturn(false);
		$this->mocks[ISession::class]->expects($this->never())
			->method('remove');
		$this->mocks[IManager::class]->expects($this->once())
			->method('generateEvent')
			->willReturn($event);
		$event->expects($this->once())
			->method('setApp')
			->with($this->equalTo('core'))
			->willReturnSelf();
		$event->expects($this->once())
			->method('setType')
			->with($this->equalTo('security'))
			->willReturnSelf();
		$event->expects($this->once())
			->method('setAuthor')
			->with($this->equalTo($this->user->getUID()))
			->willReturnSelf();
		$event->expects($this->once())
			->method('setAffectedUser')
			->with($this->equalTo($this->user->getUID()))
			->willReturnSelf();
		$this->fakeProvider
			->method('getDisplayName')
			->willReturn('Fake 2FA');
		$event->expects($this->once())
			->method('setSubject')
			->with($this->equalTo('twofactor_failed'), $this->equalTo([
				'provider' => 'Fake 2FA',
			]))
			->willReturnSelf();

		$this->assertFalse($this->manager->verifyChallenge('email', $this->user, $challenge));
	}

	public function testNeedsSecondFactor(): void {
		$user = $this->createMock(IUser::class);

		$calls = [
			['app_password'],
			['two_factor_auth_uid'],
			[Manager::SESSION_UID_DONE],
		];
		$this->mocks[ISession::class]->expects($this->exactly(3))
			->method('exists')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
				return false;
			});

		$this->mocks[ISession::class]->method('getId')
			->willReturn('mysessionid');
		$token = $this->createMock(IToken::class);
		$this->mocks[TokenProvider::class]->method('getToken')
			->with('mysessionid')
			->willReturn($token);
		$token->method('getId')
			->willReturn(42);

		$user->method('getUID')
			->willReturn('user');
		$this->mocks[IConfig::class]->method('getUserKeys')
			->with('user', 'login_token_2fa')
			->willReturn([
				'42'
			]);

		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->mocks[ProviderLoader::class],
				$this->mocks[IRegistry::class],
				$this->mocks[MandatoryTwoFactor::class],
				$this->mocks[ISession::class],
				$this->mocks[IConfig::class],
				$this->mocks[IManager::class],
				$this->mocks[LoggerInterface::class],
				$this->mocks[TokenProvider::class],
				$this->mocks[ITimeFactory::class],
				$this->mocks[IEventDispatcher::class],
			])
			->onlyMethods(['isTwoFactorAuthenticated'])// Do not actually load the apps
			->getMock();

		$manager->method('isTwoFactorAuthenticated')
			->with($user)
			->willReturn(true);

		$this->assertTrue($manager->needsSecondFactor($user));
	}

	public function testNeedsSecondFactorUserIsNull(): void {
		$user = null;
		$this->mocks[ISession::class]->expects($this->never())
			->method('exists');

		$this->assertFalse($this->manager->needsSecondFactor($user));
	}

	public function testNeedsSecondFactorWithNoProviderAvailableAnymore(): void {
		$this->prepareNoProviders();

		$user = null;
		$this->mocks[ISession::class]->expects($this->never())
			->method('exists')
			->with('two_factor_auth_uid')
			->willReturn(true);
		$this->mocks[ISession::class]->expects($this->never())
			->method('remove')
			->with('two_factor_auth_uid');

		$this->assertFalse($this->manager->needsSecondFactor($user));
	}

	public function testPrepareTwoFactorLogin(): void {
		$calls = [
			['two_factor_auth_uid', $this->user->getUID()],
			['two_factor_remember_login', true],
		];
		$this->mocks[ISession::class]->expects($this->exactly(2))
			->method('set')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->mocks[ISession::class]->method('getId')
			->willReturn('mysessionid');
		$token = $this->createMock(IToken::class);
		$this->mocks[TokenProvider::class]->method('getToken')
			->with('mysessionid')
			->willReturn($token);
		$token->method('getId')
			->willReturn(42);

		$this->mocks[ITimeFactory::class]->method('getTime')
			->willReturn(1337);

		$this->mocks[IConfig::class]->method('setUserValue')
			->with($this->user->getUID(), 'login_token_2fa', '42', '1337');

		$this->manager->prepareTwoFactorLogin($this->user, true);
	}

	public function testPrepareTwoFactorLoginDontRemember(): void {
		$calls = [
			['two_factor_auth_uid', $this->user->getUID()],
			['two_factor_remember_login', false],
		];
		$this->mocks[ISession::class]->expects($this->exactly(2))
			->method('set')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->mocks[ISession::class]->method('getId')
			->willReturn('mysessionid');
		$token = $this->createMock(IToken::class);
		$this->mocks[TokenProvider::class]->method('getToken')
			->with('mysessionid')
			->willReturn($token);
		$token->method('getId')
			->willReturn(42);

		$this->mocks[ITimeFactory::class]->method('getTime')
			->willReturn(1337);

		$this->mocks[IConfig::class]->method('setUserValue')
			->with($this->user->getUID(), 'login_token_2fa', '42', '1337');

		$this->manager->prepareTwoFactorLogin($this->user, false);
	}

	public function testNeedsSecondFactorSessionAuth(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->mocks[ISession::class]->method('exists')
			->willReturnCallback(function ($var) {
				if ($var === Manager::SESSION_UID_KEY) {
					return false;
				} elseif ($var === 'app_password') {
					return false;
				} elseif ($var === 'app_api') {
					return false;
				}
				return true;
			});
		$this->mocks[ISession::class]->method('get')
			->willReturnCallback(function ($var) {
				if ($var === Manager::SESSION_UID_KEY) {
					return 'user';
				} elseif ($var === 'app_api') {
					return true;
				}
				return null;
			});
		$this->mocks[ISession::class]->expects($this->once())
			->method('get')
			->willReturnMap([
				[Manager::SESSION_UID_DONE, 'user'],
				['app_api', true]
			]);

		$this->assertFalse($this->manager->needsSecondFactor($user));
	}

	public function testNeedsSecondFactorSessionAuthFailDBPass(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->mocks[ISession::class]->method('exists')
			->willReturn(false);
		$this->mocks[ISession::class]->method('getId')
			->willReturn('mysessionid');

		$token = $this->createMock(IToken::class);
		$token->method('getId')
			->willReturn(40);

		$this->mocks[TokenProvider::class]->method('getToken')
			->with('mysessionid')
			->willReturn($token);

		$this->mocks[IConfig::class]->method('getUserKeys')
			->with('user', 'login_token_2fa')
			->willReturn([
				'42', '43', '44'
			]);

		$this->mocks[ISession::class]->expects($this->once())
			->method('set')
			->with(Manager::SESSION_UID_DONE, 'user');

		$this->assertFalse($this->manager->needsSecondFactor($user));
	}

	public function testNeedsSecondFactorInvalidToken(): void {
		$this->prepareNoProviders();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->mocks[ISession::class]->method('exists')
			->willReturn(false);
		$this->mocks[ISession::class]->method('getId')
			->willReturn('mysessionid');

		$this->mocks[TokenProvider::class]->method('getToken')
			->with('mysessionid')
			->willThrowException(new InvalidTokenException());

		$this->mocks[IConfig::class]->method('getUserKeys')->willReturn([]);

		$this->assertFalse($this->manager->needsSecondFactor($user));
	}

	public function testNeedsSecondFactorAppPassword(): void {
		$user = $this->createMock(IUser::class);
		$this->mocks[ISession::class]->method('exists')
			->willReturnMap([
				['app_password', true],
				['app_api', true]
			]);

		$this->assertFalse($this->manager->needsSecondFactor($user));
	}

	public function testClearTwoFactorPending() {
		$this->mocks[IConfig::class]->method('getUserKeys')
			->with('theUserId', 'login_token_2fa')
			->willReturn([
				'42', '43', '44'
			]);

		$deleteUserValueCalls = [
			['theUserId', 'login_token_2fa', '42'],
			['theUserId', 'login_token_2fa', '43'],
			['theUserId', 'login_token_2fa', '44'],
		];
		$this->mocks[IConfig::class]->expects($this->exactly(3))
			->method('deleteUserValue')
			->willReturnCallback(function () use (&$deleteUserValueCalls): void {
				$expected = array_shift($deleteUserValueCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$invalidateCalls = [
			['theUserId', 42],
			['theUserId', 43],
			['theUserId', 44],
		];
		$this->mocks[TokenProvider::class]->expects($this->exactly(3))
			->method('invalidateTokenById')
			->willReturnCallback(function () use (&$invalidateCalls): void {
				$expected = array_shift($invalidateCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->manager->clearTwoFactorPending('theUserId');
	}

	public function testClearTwoFactorPendingTokenDoesNotExist() {
		$this->mocks[IConfig::class]->method('getUserKeys')
			->with('theUserId', 'login_token_2fa')
			->willReturn([
				'42', '43', '44'
			]);

		$deleteUserValueCalls = [
			['theUserId', 'login_token_2fa', '42'],
			['theUserId', 'login_token_2fa', '43'],
			['theUserId', 'login_token_2fa', '44'],
		];
		$this->mocks[IConfig::class]->expects($this->exactly(3))
			->method('deleteUserValue')
			->willReturnCallback(function () use (&$deleteUserValueCalls): void {
				$expected = array_shift($deleteUserValueCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$invalidateCalls = [
			['theUserId', 42],
			['theUserId', 43],
			['theUserId', 44],
		];
		$this->mocks[TokenProvider::class]->expects($this->exactly(3))
			->method('invalidateTokenById')
			->willReturnCallback(function ($user, $tokenId) use (&$invalidateCalls): void {
				$expected = array_shift($invalidateCalls);
				$this->assertEquals($expected, func_get_args());
				if ($tokenId === 43) {
					throw new DoesNotExistException('token does not exist');
				}
			});

		$this->manager->clearTwoFactorPending('theUserId');
	}
}
