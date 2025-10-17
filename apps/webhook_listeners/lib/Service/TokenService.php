<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Service;

use OC\Authentication\Token\IProvider;
use OCA\WebhookListeners\Db\WebhookListener;
use OCP\Authentication\Token\IToken;
use OCP\Security\ISecureRandom;

class TokenService {
	public function __construct(
		private IProvider $tokenProvider,
		private ISecureRandom $random,
	) {
	}


	/**
	 * creates an array which includes two arrays of tokens: 'users' and 'functions'
	 * The array ['users' => ['jane', 'bob'], 'functions' => ['owner', 'trigger']]
	 * as requested tokens in the registered webhook produces a result like
	 * ['users' => [['jane' => 'abcdtokenabcd1'], ['bob','=> 'abcdtokenabcd2']], 'functions' => [['owner' => ['admin' => 'abcdtokenabcd3']], ['trigger' => ['user1' => 'abcdtokenabcd4']]]]
	 *
	 * @param WebhookListener $webhookListener
	 * @param string|null $triggerUserId the user that triggered the webhook call
	 * @return array
	 */
	public function getTokens(WebhookListener $webhookListener, ?string $triggerUserId): array {
		$tokens = [
			'users' => [],
			'functions' => [],
		];
		$tokenNeeded = $webhookListener->getTokenNeeded();
		if (isset($tokenNeeded['users'])) {
			foreach ($tokenNeeded['users'] as $userId) {
				$tokens['users'][$userId] = $webhookListener->createTemporaryToken($userId);
			}
		}
		if (isset($tokenNeeded['users'])) {
			foreach ($tokenNeeded['functions'] as $function) {
				switch ($function) {
					case 'owner':
						// token for the person who created the flow
						$functionId = $webhookListener->getUserId();
						$tokens['functions']['owner'] = [
							$functionId => $webhookListener->createTemporaryToken($functionId)
						];
						break;
					case 'trigger':
						// token for the person who triggered the webhook
						$tokens['functions']['trigger'] = [
							$triggerUserId => $webhookListener->createTemporaryToken($triggerUserId)
						];
						break;
				}
			}
		}

		return $tokens;
	}


	public function createTemporaryToken(string $userId): string {
		$token = $this->generateRandomDeviceToken();
		$name = 'Ephemeral webhook authentication';
		$password = null;
		$deviceToken = $this->tokenProvider->generateToken($token, $userId, $userId, $password, $name, IToken::PERMANENT_TOKEN);
		return $token;
	}

	private function generateRandomDeviceToken(): string {
		$groups = [];
		for ($i = 0; $i < 5; $i++) {
			$groups[] = $this->random->generate(5, ISecureRandom::CHAR_HUMAN_READABLE);
		}
		return implode('-', $groups);
	}
}
