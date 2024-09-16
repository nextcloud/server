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
use OCP\ISession;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AuthtokensTest extends TestCase {

	/** @var IAuthTokenProvider|MockObject */
	private $authTokenProvider;

	/** @var ISession|MockObject */
	private $session;

	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var IInitialState|MockObject */
	private $initialState;

	/** @var string */
	private $uid;

	/** @var Authtokens */
	private $section;

	protected function setUp(): void {
		parent::setUp();

		$this->authTokenProvider = $this->createMock(IAuthTokenProvider::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->uid = 'test123';

		$this->section = new Authtokens(
			$this->authTokenProvider,
			$this->session,
			$this->userSession,
			$this->initialState,
			$this->uid
		);
	}

	public function testGetForm(): void {
		$token1 = new PublicKeyToken();
		$token1->setId(100);
		$token2 = new PublicKeyToken();
		$token2->setId(200);
		$tokens = [
			$token1,
			$token2,
		];
		$sessionToken = new PublicKeyToken();
		$sessionToken->setId(100);

		$this->authTokenProvider->expects($this->once())
			->method('getTokenByUser')
			->with($this->uid)
			->willReturn($tokens);
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('session123');
		$this->authTokenProvider->expects($this->once())
			->method('getToken')
			->with('session123')
			->willReturn($sessionToken);
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->withConsecutive(
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
			);

		$form = $this->section->getForm();

		$expected = new TemplateResponse('settings', 'settings/personal/security/authtokens');
		$this->assertEquals($expected, $form);
	}
}
