<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\ChallengeRequester;
use OC\Authentication\TwoFactorAuth\Manager;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class ChallengeRequesterTest extends TestCase {
	private Manager&MockObject $manager;
	private ISession&MockObject $session;
	private IURLGenerator&MockObject $urlGenerator;
	private IUser&MockObject $user;
	private ChallengeRequester $requester;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->session = $this->createMock(ISession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->user = $this->createMock(IUser::class);
		$this->requester = new ChallengeRequester($this->manager, $this->session, $this->urlGenerator);
	}

	private function withSession(bool $appPassword, bool $appApi, bool $hasSecondFactor): void {
		$this->session->method('exists')
			->willReturnCallback(static fn (string $key): bool => $key === 'app_password' && $appPassword);
		$this->session->method('get')
			->willReturnCallback(static fn (string $key): mixed => $key === 'app_api' ? $appApi : null);
		$this->manager->method('isTwoFactorAuthenticated')
			->with($this->user)
			->willReturn($hasSecondFactor);
	}

	public static function dataIsAvailable(): array {
		return [
			'second factor' => [false, false, true, true],
			'no second factor' => [false, false, false, false],
			'app password session' => [true, false, true, false],
			'AppAPI session' => [false, true, true, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataIsAvailable')]
	public function testIsAvailable(bool $appPassword, bool $appApi, bool $hasSecondFactor, bool $expected): void {
		$this->withSession($appPassword, $appApi, $hasSecondFactor);

		$this->assertSame($expected, $this->requester->isAvailable($this->user));
	}

	public function testRequestChallengeMarksTheSessionAndReturnsTheChallengeUrl(): void {
		$this->withSession(false, false, true);
		$this->session->expects($this->once())
			->method('remove')
			->with(Manager::SESSION_UID_DONE);
		$this->manager->expects($this->once())
			->method('prepareTwoFactorLogin')
			->with($this->user, false);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.TwoFactorChallenge.selectChallenge', ['redirect_url' => '/apps/settings/user/security'])
			->willReturn('/index.php/login/selectchallenge?redirect_url=%2Fapps%2Fsettings%2Fuser%2Fsecurity');

		$this->assertSame(
			'/index.php/login/selectchallenge?redirect_url=%2Fapps%2Fsettings%2Fuser%2Fsecurity',
			$this->requester->requestChallenge($this->user, '/apps/settings/user/security'),
		);
	}

	public function testRequestChallengeRefusesSessionsThatCannotBeChallenged(): void {
		$this->withSession(true, false, true);
		$this->manager->expects($this->never())
			->method('prepareTwoFactorLogin');

		$this->expectException(\LogicException::class);
		$this->requester->requestChallenge($this->user, '/');
	}
}
