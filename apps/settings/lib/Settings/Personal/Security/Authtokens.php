<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Personal\Security;

use OC\Authentication\Token\INamedToken;
use OC\Authentication\Token\IProvider as IAuthTokenProvider;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\Settings\ISettings;
use function array_map;

class Authtokens implements ISettings {

	public function __construct(
		private IAuthTokenProvider $tokenProvider,
		private ISession $session,
		private IUserSession $userSession,
		private IInitialState $initialState,
		private ?string $userId,
	) {
	}

	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState(
			'app_tokens',
			$this->getAppTokens()
		);

		$this->initialState->provideInitialState(
			'can_create_app_token',
			$this->userSession->getImpersonatingUserID() === null
		);

		return new TemplateResponse('settings', 'settings/personal/security/authtokens');
	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 100;
	}

	private function getAppTokens(): array {
		$tokens = $this->tokenProvider->getTokenByUser($this->userId);

		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException $ex) {
			return [];
		}
		try {
			$sessionToken = $this->tokenProvider->getToken($sessionId);
		} catch (InvalidTokenException $ex) {
			return [];
		}

		return array_map(function (IToken $token) use ($sessionToken) {
			$data = $token->jsonSerialize();
			$data['canDelete'] = true;
			$data['canRename'] = $token instanceof INamedToken && $data['type'] !== IToken::WIPE_TOKEN;
			if ($sessionToken->getId() === $token->getId()) {
				$data['canDelete'] = false;
				$data['canRename'] = false;
				$data['current'] = true;
			}
			return $data;
		}, $tokens);
	}
}
