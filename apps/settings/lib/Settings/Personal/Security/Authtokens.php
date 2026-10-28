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
use OCA\Settings\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\Settings\ISettings;
use OCP\Util;

class Authtokens implements ISettings {

	public function __construct(
		private IAuthTokenProvider $tokenProvider,
		private ISession $session,
		private IUserSession $userSession,
		private IInitialState $initialState,
		private IConfig $serverConfig,
		private ?string $userId,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState(
			'app_tokens',
			$this->getAppTokens()
		);

		$this->initialState->provideInitialState(
			'can_create_app_token',
			$this->userSession->getImpersonatingUserID() === null
			&& $this->serverConfig->getSystemValueBool('auth_can_create_app_token', true)
		);

		Util::addStyle(Application::APP_ID, 'personal-security-authtokens');
		Util::addScript(Application::APP_ID, 'personal-security-authtokens');

		return new TemplateResponse('settings', 'settings/personal/security/authtokens');
	}

	#[\Override]
	public function getSection(): string {
		return 'security';
	}

	#[\Override]
	public function getPriority(): int {
		return 100;
	}

	/**
	 * One entry per login, so a browser keeps one entry with the same id while it renews or copies its session token
	 */
	private function getAppTokens(): array {
		$logins = $this->tokenProvider->getLoginsOfUser($this->userId);

		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException $ex) {
			return [];
		}
		try {
			$currentLoginId = $this->tokenProvider->getLoginId($this->tokenProvider->getToken($sessionId));
		} catch (InvalidTokenException $ex) {
			return [];
		}

		$entries = [];
		foreach ($logins as $loginId => $tokens) {
			// The most recently used token of the login
			$token = $tokens[0];

			$data = $token->jsonSerialize();
			$data['id'] = $loginId;
			$data['canDelete'] = true;
			$data['canRename'] = $token instanceof INamedToken && $data['type'] !== IToken::WIPE_TOKEN;
			if ($loginId === $currentLoginId) {
				$data['canDelete'] = false;
				$data['canRename'] = false;
				$data['current'] = true;
			}
			$entries[] = $data;
		}
		return $entries;
	}
}
