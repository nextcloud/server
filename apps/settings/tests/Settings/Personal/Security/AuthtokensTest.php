<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Settings\Personal\Security;

use OC\Authentication\Token\IProvider as IAuthTokenProvider;
use OC\Authentication\Token\PublicKeyToken;
use OCA\Settings\Settings\Personal\Security\Authtokens;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\Token\IToken;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AuthtokensTest extends TestCase {
	private IAuthTokenProvider&MockObject $authTokenProvider;
	private ISession&MockObject $session;
	private IUserSession&MockObject $userSession;
	private IInitialState&MockObject $initialState;
	private IConfig&MockObject $serverConfig;
	private string $uid;
	private Authtokens $section;

	protected function setUp(): void {
		parent::setUp();

		$this->authTokenProvider = $this->createMock(IAuthTokenProvider::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->serverConfig = $this->createMock(IConfig::class);
		$this->uid = 'test123';

		$this->section = new Authtokens(
			$this->authTokenProvider,
			$this->session,
			$this->userSession,
			$this->initialState,
			$this->serverConfig,
			$this->uid,
		);
	}

	public function testGetForm(): void {
		$token1 = new PublicKeyToken();
		$token1->setId(100);
		$token2 = new PublicKeyToken();
		$token2->setId(200);
		$sessionToken = new PublicKeyToken();
		$sessionToken->setId(100);

		$this->serverConfig->method('getSystemValueBool')
			->with('auth_can_create_app_token', true)
			->willReturn(true);
		$this->authTokenProvider->expects($this->once())
			->method('getLoginsOfUser')
			->with($this->uid)
			->willReturn([100 => [$token1], 200 => [$token2]]);
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('session123');
		$this->authTokenProvider->expects($this->once())
			->method('getToken')
			->with('session123')
			->willReturn($sessionToken);
		$this->authTokenProvider->method('getLoginId')
			->with($sessionToken)
			->willReturn(100);

		$calls = [
			[
				'app_tokens', [
					[
						'id' => 100,
						'name' => null,
						'lastActivity' => 0,
						'type' => 0,
						'canDelete' => false,
						'current' => true,
						'scope' => [IToken::SCOPE_FILESYSTEM => true],
						'canRename' => false,
					],
					[
						'id' => 200,
						'name' => null,
						'lastActivity' => 0,
						'type' => 0,
						'canDelete' => true,
						'scope' => [IToken::SCOPE_FILESYSTEM => true],
						'canRename' => true,
					],
				]
			],
			['can_create_app_token', true],
		];
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$form = $this->section->getForm();

		$expected = new TemplateResponse('settings', 'settings/personal/security/authtokens');
		$this->assertEquals($expected, $form);
	}

	public function testGetFormShowsOneEntryPerLogin(): void {
		// A browser login whose session token was renewed to 101 and copied to 102 for a concurrent request
		$renewed = $this->sessionToken(101, 'Firefox', 1000);
		$copy = $this->sessionToken(102, 'Firefox', 1500);
		$otherLogin = $this->sessionToken(200, 'Chrome', 900);

		$this->serverConfig->method('getSystemValueBool')
			->willReturn(true);
		$this->authTokenProvider->method('getLoginsOfUser')
			->with($this->uid)
			->willReturn([100 => [$copy, $renewed], 200 => [$otherLogin]]);
		$this->session->method('getId')
			->willReturn('session123');
		$this->authTokenProvider->method('getToken')
			->with('session123')
			->willReturn($renewed);
		$this->authTokenProvider->method('getLoginId')
			->with($renewed)
			->willReturn(100);

		$appTokens = null;
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function (string $key, $data) use (&$appTokens): void {
				if ($key === 'app_tokens') {
					$appTokens = $data;
				}
			});

		$this->section->getForm();

		$this->assertEquals([
			[
				'id' => 100,
				'name' => 'Firefox',
				'lastActivity' => 1500,
				'type' => IToken::TEMPORARY_TOKEN,
				'scope' => [IToken::SCOPE_FILESYSTEM => true],
				'canDelete' => false,
				'canRename' => false,
				'current' => true,
			],
			[
				'id' => 200,
				'name' => 'Chrome',
				'lastActivity' => 900,
				'type' => IToken::TEMPORARY_TOKEN,
				'scope' => [IToken::SCOPE_FILESYSTEM => true],
				'canDelete' => true,
				'canRename' => true,
			],
		], $appTokens);
	}

	private function sessionToken(int $id, string $name, int $lastActivity): PublicKeyToken {
		$token = new PublicKeyToken();
		$token->setId($id);
		$token->setName($name);
		$token->setLastActivity($lastActivity);
		$token->setType(IToken::TEMPORARY_TOKEN);
		return $token;
	}
}
