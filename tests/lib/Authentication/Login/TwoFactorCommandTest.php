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
use PHPUnit\Framework\MockObject\MockObject;

class TwoFactorCommandTest extends ALoginCommandTest {
	/** @var Manager|MockObject */
	private $twoFactorManager;

	/** @var MandatoryTwoFactor|MockObject */
	private $mandatoryTwoFactor;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->twoFactorManager = $this->createMock(Manager::class);
		$this->mandatoryTwoFactor = $this->createMock(MandatoryTwoFactor::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->cmd = new TwoFactorCommand(
			$this->twoFactorManager,
			$this->mandatoryTwoFactor,
			$this->urlGenerator
		);
	}

	public function testNotTwoFactorAuthenticated(): void {
		$data = $this->getLoggedInLoginData();
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(false);
		$this->twoFactorManager->expects($this->never())
			->method('prepareTwoFactorLogin');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessOneActiveProvider(): void {
		$data = $this->getLoggedInLoginData();
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(ITwoFactorAuthProvider::class);
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], false));
		$this->twoFactorManager->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mandatoryTwoFactor->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$provider->expects($this->once())
			->method('getId')
			->willReturn('test');
		$this->urlGenerator->expects($this->once())
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
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(ITwoFactorAuthProvider::class);
		$provider->expects($this->once())
			->method('getId')
			->willReturn('test1');
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], true));
		$this->twoFactorManager->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mandatoryTwoFactor->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$this->urlGenerator->expects($this->once())
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
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
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
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider1,
				$provider2,
			], false));
		$this->twoFactorManager->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mandatoryTwoFactor->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$this->urlGenerator->expects($this->once())
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
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([], true));
		$this->twoFactorManager->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mandatoryTwoFactor->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(true);
		$this->urlGenerator->expects($this->once())
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
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(IActivatableAtLogin::class);
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], true));
		$this->twoFactorManager->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mandatoryTwoFactor->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(true);
		$this->urlGenerator->expects($this->once())
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
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([], false));
		$this->twoFactorManager->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mandatoryTwoFactor->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(true);
		$this->urlGenerator->expects($this->once())
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
		$this->twoFactorManager->expects($this->once())
			->method('isTwoFactorAuthenticated')
			->willReturn(true);
		$this->twoFactorManager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with(
				$this->user,
				$data->isRememberLogin()
			);
		$provider = $this->createMock(ITwoFactorAuthProvider::class);
		$this->twoFactorManager->expects($this->once())
			->method('getProviderSet')
			->willReturn(new ProviderSet([
				$provider,
			], false));
		$this->twoFactorManager->expects($this->once())
			->method('getLoginSetupProviders')
			->with($this->user)
			->willReturn([]);
		$this->mandatoryTwoFactor->expects($this->any())
			->method('isEnforcedFor')
			->with($this->user)
			->willReturn(false);
		$provider->expects($this->once())
			->method('getId')
			->willReturn('test');
		$this->urlGenerator->expects($this->once())
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
