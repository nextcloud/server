<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Upload;

use OCA\NotifyPush\IAnonymousSessionManager;
use OCP\App\IAppManager;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Status reporting for requests that are answered early with "202 Accepted".
 *
 * Status messages are delivered through notify_push anonymous sessions. The
 * session token handed to the client is self-contained and signed, so nothing
 * needs to be stored on the server between requests: the client echoes the
 * token and the session id is recovered from it.
 */
class AsyncRequestService {
	public const SESSION_APP_ID = 'dav';
	public const MESSAGE_TYPE = 'dav:async_request';

	/**
	 * Matches the lifetime of a chunked upload session, which is the longest
	 * a client may have to wait for a status message.
	 */
	public const SESSION_TTL = 24 * 60 * 60;

	private ?object $sessionManager = null;
	private bool $sessionManagerResolved = false;

	public function __construct(
		private readonly IAppManager $appManager,
		private readonly LoggerInterface $logger,
	) {
	}

	public function isAvailable(): bool {
		return $this->getSessionManager() !== null;
	}

	/**
	 * Create a new anonymous session and return the token for the client.
	 */
	public function createSessionToken(): string {
		$session = $this->getSessionManager()?->createSession(self::SESSION_APP_ID, self::SESSION_TTL);
		if ($session === null) {
			throw new \RuntimeException('Asynchronous requests are not available');
		}
		return $session->getToken();
	}

	/**
	 * Recover the session id from a token the client echoed back.
	 *
	 * Only sessions created by this app are accepted, the app prefix of a
	 * session id is a convention in notify_push and not enforced there.
	 */
	public function resolveSessionId(string $token): ?string {
		if ($token === '') {
			return null;
		}
		$sessionId = $this->getSessionManager()?->validateToken($token);
		if (!is_string($sessionId) || !str_starts_with($sessionId, self::SESSION_APP_ID . ':')) {
			return null;
		}
		return $sessionId;
	}

	/**
	 * Push a status message to the client listening on the session.
	 *
	 * Failures are only logged, the HTTP response was already sent.
	 */
	public function send(string $sessionId, array $body): void {
		try {
			$this->getSessionManager()?->send($sessionId, self::MESSAGE_TYPE, $body);
		} catch (\Throwable $e) {
			$this->logger->error('Failed to push the status of an asynchronous request', [
				'exception' => $e,
				'session' => $sessionId,
			]);
		}
	}

	/**
	 * Hand the already prepared response to the client and keep running.
	 *
	 * Output sent after this point is discarded, so the response has to be
	 * complete before calling it.
	 */
	public function finishRequest(): void {
		ignore_user_abort(true);
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (function_exists('litespeed_finish_request')) {
			litespeed_finish_request();
		} else {
			flush();
		}
	}

	/**
	 * Resolve the notify_push session manager if the app is installed and
	 * supports token validation.
	 *
	 * @psalm-suppress UndefinedClass notify_push is not a dependency of the server
	 */
	protected function getSessionManager(): ?object {
		if ($this->sessionManagerResolved) {
			return $this->sessionManager;
		}
		$this->sessionManagerResolved = true;

		if (!$this->appManager->isEnabledForAnyone('notify_push')
			|| !interface_exists('\OCA\NotifyPush\IAnonymousSessionManager')) {
			return null;
		}

		try {
			/** @var object $manager */
			$manager = Server::get(IAnonymousSessionManager::class);
		} catch (ContainerExceptionInterface $e) {
			$this->logger->debug('notify_push is enabled but its anonymous session manager could not be resolved', ['exception' => $e]);
			return null;
		}

		// Older notify_push releases do not expose token validation
		if (!method_exists($manager, 'validateToken')) {
			return null;
		}

		$this->sessionManager = $manager;
		return $this->sessionManager;
	}
}
