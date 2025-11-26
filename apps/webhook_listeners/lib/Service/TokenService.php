<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Service;

use OC\Authentication\Token\IProvider;
use OCA\WebhookListeners\Db\EphemeralTokenMapper;
use OCA\WebhookListeners\Db\WebhookListener;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Token\IToken;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class TokenService {
	public function __construct(
		private IProvider $tokenProvider,
		private ISecureRandom $random,
		private EphemeralTokenMapper $tokenMapper,
		private LoggerInterface $logger,
		private ITimeFactory $time,
		private IFactory $l10nFactory,
		private IUserManager $userManager,
	) {
	}

	/**
	 * creates an array which includes two arrays of tokens: 'user_ids' and 'user_roles'
	 * The array ['user_ids' => ['jane', 'bob'], 'user_roles' => ['owner', 'trigger']]
	 * as requested tokens in the registered webhook produces a result like
	 * ['user_ids' => [['jane' => 'abcdtokenabcd1'], ['bob','=> 'abcdtokenabcd2']], 'user_roles' => ['owner' => ['admin' => 'abcdtokenabcd3'], 'trigger' => ['user1' => 'abcdtokenabcd4']]]
	 * Created auth tokens are valid for 1 hour.
	 *
	 * @param WebhookListener $webhookListener
	 * @param ?string $triggerUserId the user that triggered the webhook call
	 * @return array{user_ids?:array<string,string>,user_roles?:array{owner?:array<string,string>,trigger?:array<string,string>}}
	 */
	public function getTokens(WebhookListener $webhookListener, ?string $triggerUserId): array {
		$tokens = [
			'user_ids' => [],
			'user_roles' => [],
		];
		$tokenNeeded = $webhookListener->getTokenNeeded();
		if (isset($tokenNeeded['user_ids'])) {
			foreach ($tokenNeeded['user_ids'] as $userId) {
				try {
					$tokens['user_ids'][$userId] = $this->createEphemeralToken($userId);
				} catch (\Exception $e) {
					$this->logger->error('Webhook token creation for user ' . $userId . ' failed: ' . $e->getMessage(), ['exception' => $e]);
				}

			}
		}
		if (isset($tokenNeeded['user_roles'])) {
			foreach ($tokenNeeded['user_roles'] as $user_role) {
				switch ($user_role) {
					case 'owner':
						// token for the person who created the flow
						$ownerId = $webhookListener->getUserId();
						if (is_null($ownerId)) { // no owner uid available
							break;
						}
						$tokens['user_roles']['owner'] = [
							$ownerId => $this->createEphemeralToken($ownerId)
						];
						break;
					case 'trigger':
						// token for the person who triggered the webhook
						if (is_null($triggerUserId)) { // no trigger uid available
							break;
						}
						$tokens['user_roles']['trigger'] = [
							$triggerUserId => $this->createEphemeralToken($triggerUserId)
						];
						break;
					default:
						$this->logger->error('Webhook token creation for user role ' . $user_role . ' not defined. ', ['Not defined' => $user_role]);

				}
			}
		}
		return $tokens;
	}
	private function createEphemeralToken(string $userId): string {
		$token = $this->generateRandomDeviceToken();

		// we need the user`s language to have the token name showing up in the session list in the correct language
		$user = $this->userManager->get($userId);
		$lang = $this->l10nFactory->getUserLanguage($user);
		$l = $this->l10nFactory->get('webhook_listeners', $lang);
		$name = $l->t('Ephemeral webhook authentication');
		$password = null;
		$deviceToken = $this->tokenProvider->generateToken(
			$token,
			$userId,
			$userId,
			$password,
			$name,
			IToken::PERMANENT_TOKEN);

		$this->tokenMapper->addEphemeralToken(
			$deviceToken->getId(),
			$userId,
			$this->time->getTime());
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
