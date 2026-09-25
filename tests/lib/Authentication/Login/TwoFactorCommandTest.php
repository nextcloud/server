<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\TwoFactorCommand;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Authentication\TwoFactorAuth\ProviderSet;
use OCP\Authentication\TwoFactorAuth\IActivatableAtLogin;
use OCP\Authentication\TwoFactorAuth\IProvider as ITwoFactorAuthProvider;
use OCP\IURLGenerator;

class TwoFactorCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(TwoFactorCommand::class);
	}

	public function testNotTwoFactorAuthenticated(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(false);
		$this->mocks[Manager::class]->expects($this->never())
			->method('prepareTwoFactorLogin');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testSkippedForVerifiedWebAuthnLogin(): void {
		$data = $this->getLoggedInLoginData();
		$data->setWebAuthnUserVerified(true);
		$this->mocks[Manager::class]->expects($this->never())
			->method('prepareTwoFactorLogin');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertNull($result->getRedirectUrl());
	}

	public function testNotSkippedForWebAuthnLoginWithoutUserVerification(): void {
		$data = $this->getLoggedInLoginData();
		$data->setWebAuthnUserVerified(false);
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin');
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([], false));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->willReturn([]);
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}

	public function testProcessOneActiveProvider(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(ITwoFactorAuthProvider::class);
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], false));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mocks[MandatoryTwoFactor::class]->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$provider->expects($this->once())
			->method('getId')
			->willReturn('test');
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.TwoFactorChallenge.showChallenge',
				[
					'challengeProviderId' => 'test'
				]
			)
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}

	public function testProcessMissingProviders(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(ITwoFactorAuthProvider::class);
		$provider->expects($this->once())
			->method('getId')
			->willReturn('test1');
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], true));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mocks[MandatoryTwoFactor::class]->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.TwoFactorChallenge.selectChallenge'
			)
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}

	public function testProcessTwoActiveProviders(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider1 = $this->createMock(ITwoFactorAuthProvider::class);
		$provider2 = $this->createMock(ITwoFactorAuthProvider::class);
		$provider1->expects($this->once())
			->method('getId')
			->willReturn('test1');
		$provider2->expects($this->once())
			->method('getId')
			->willReturn('test2');
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider1,
				$provider2,
			], false));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mocks[MandatoryTwoFactor::class]->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.TwoFactorChallenge.selectChallenge'
			)
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}

	public function testProcessFailingProviderAndEnforcedButNoSetupProviders(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([], true));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mocks[MandatoryTwoFactor::class]->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(true);
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.TwoFactorChallenge.selectChallenge'
			)
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}

	public function testProcessFailingProviderAndEnforced(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(IActivatableAtLogin::class);
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], true));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mocks[MandatoryTwoFactor::class]->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(true);
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.TwoFactorChallenge.selectChallenge'
			)
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}

	public function testProcessNoProvidersButEnforced(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([], false));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mocks[MandatoryTwoFactor::class]->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(true);
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.TwoFactorChallenge.selectChallenge'
			)
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}

	public function testProcessWithRedirectUrl(): void {
		$data = $this->getLoggedInLoginDataWithRedirectUrl();
		$this->mocks[Manager::class]->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->mocks[Manager::class]->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(ITwoFactorAuthProvider::class);
		$this->mocks[Manager::class]->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], false));
		$this->mocks[Manager::class]->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mocks[MandatoryTwoFactor::class]->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$provider->expects($this->once())
			->method('getId')
			->willReturn('test');
		$this->mocks[IURLGenerator::class]->expects($this->once())
			->method('linkToRoute')
			->with(
				'core.TwoFactorChallenge.showChallenge',
				[
					'challengeProviderId' => 'test',
					'redirect_url' => $this->redirectUrl,
				]
			)
			->willReturn('two/factor/url');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals('two/factor/url', $result->getRedirectUrl());
	}
}
