<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Service;

use OC\Authentication\Token\IProvider;
use OCA\WebhookListeners\Db\TemporaryTokenMapper;
use OCA\WebhookListeners\Db\WebhookListener;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Token\IToken;
use OCP\Security\ISecureRandom;

class TokenService {
	public function __construct(
		private IProvider $tokenProvider,
		private ISecureRandom $random,
		private TemporaryTokenMapper $tokenMapper,
		private ITimeFactory $time,
	) {
	}


	/**
	 * creates an array which includes two arrays of tokens: 'user_ids' and 'user_roles'
	 * The array ['user_ids' => ['jane', 'bob'], 'user_roles' => ['owner', 'trigger']]
	 * as requested tokens in the registered webhook produces a result like
	 * ['user_ids' => [['jane' => 'abcdtokenabcd1'], ['bob','=> 'abcdtokenabcd2']], 'user_roles' => [['owner' => ['admin' => 'abcdtokenabcd3']], ['trigger' => ['user1' => 'abcdtokenabcd4']]]]
	 * Created auth tokens are valid for 1 hour.
	 *
	 * @param WebhookListener $webhookListener
	 * @param string|null $triggerUserId the user that triggered the webhook call
	 * @return array
	 */
	public function getTokens(WebhookListener $webhookListener, ?string $triggerUserId): array {
		$tokens = [
			'user_ids' => [],
			'user_roles' => [],
		];
		$tokenNeeded = $webhookListener->getTokenNeeded();
		if (isset($tokenNeeded['user_ids'])) {
			foreach ($tokenNeeded['user_ids'] as $userId) {
				$tokens['user_ids'][$userId] = $this->createTemporaryToken($userId);
			}
		}
		if (isset($tokenNeeded['user_ids'])) {
			foreach ($tokenNeeded['user_roles'] as $function) {
				switch ($function) {
					case 'owner':
						// token for the person who created the flow
						$functionId = $webhookListener->getUserId();
						if (is_null($functionId)) { // no owner uid available
							break;
						}
						$tokens['user_roles']['owner'] = [
							$functionId => $this->createTemporaryToken($functionId)
						];
						break;
					case 'trigger':
						// token for the person who triggered the webhook
						if (is_null($triggerUserId)) { // no trigger uid available
							break;
						}
						$tokens['user_roles']['trigger'] = [
							$triggerUserId => $this->createTemporaryToken($triggerUserId)
						];
						break;
				}
			}
		}

		return $tokens;
	}


	private function createTemporaryToken(string $userId): string {
		$token = $this->generateRandomDeviceToken();
		$name = 'Ephemeral webhook authentication';
		$password = null;
		$deviceToken = $this->tokenProvider->generateToken($token, $userId, $userId, $password, $name, IToken::PERMANENT_TOKEN);
		$this->tokenMapper->addTemporaryToken($deviceToken->getId(), $deviceToken->getToken(), $userId, $this->time->getTime());
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
