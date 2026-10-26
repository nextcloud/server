<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Session;

use OC\Authentication\Token\IProvider;
use OC\Diagnostics\TLogSlowOperation;
use OC\User\Session as UserSession;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Server;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Psr\Log\LoggerInterface;
use function call_user_func_array;

/**
 * Class Internal
 *
 * wrap php's internal session handling into the Session interface
 *
 * @package OC\Session
 */
class Internal extends Session {

	use TLogSlowOperation;

	/**
	 * Marks a session whose id was replaced by regenerateId()
	 */
	public const SUPERSEDED_AT = 'superseded_at';

	/**
	 * Seconds after a regeneration in which requests on the old id get no cookie, as the browser may hold the new id
	 */
	public const SUPERSEDED_GRACE_PERIOD = 60;

	/**
	 * False while the browser may hold a newer id than this request, see leaveSupersededSession()
	 */
	private bool $cookieAllowed = true;

	/**
	 * @param string $name
	 * @throws \Exception
	 */
	public function __construct(
		string $name,
		private readonly LoggerInterface $logger,
	) {
		set_error_handler([$this, 'trapError']);
		$this->invoke('session_name', [$name]);
		$this->invoke('session_cache_limiter', ['']);
		try {
			$this->startSession();
		} catch (\Exception $e) {
			setcookie($this->invoke('session_name'), '', -1, \OC::$WEBROOT ?: '/');
		}
		restore_error_handler();
		if (!isset($_SESSION)) {
			throw new \Exception('Failed to start session');
		}
		$this->leaveSupersededSession(atRequestStart: true);
	}

	/**
	 * @param string $key
	 * @param integer $value
	 */
	#[\Override]
	public function set(string $key, $value) {
		$reopened = $this->reopen();
		$_SESSION[$key] = $value;
		if ($reopened) {
			$this->close();
		}
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	#[\Override]
	public function get(string $key) {
		if (!$this->exists($key)) {
			return null;
		}
		return $_SESSION[$key];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	#[\Override]
	public function exists(string $key): bool {
		return isset($_SESSION[$key]);
	}

	/**
	 * @param string $key
	 */
	#[\Override]
	public function remove(string $key) {
		if (isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
	}

	#[\Override]
	public function clear() {
		$this->reopen();
		$this->invoke('session_unset');
		$this->regenerateId();
		$_SESSION = [];
	}

	#[\Override]
	public function close() {
		$this->invoke('session_write_close');
		parent::close();
	}

	/**
	 * Wrapper around session_regenerate_id
	 *
	 * @param bool $deleteOldSession Whether to delete the old id, or replace its data with the superseded marker if the client sent it
	 * @param bool $updateToken Whether to update the associated auth token
	 * @return void
	 */
	#[\Override]
	public function regenerateId(bool $deleteOldSession = true, bool $updateToken = false) {
		$oldId = (string)$this->invoke('session_id', [], true);
		if (!$this->switchToNewId($oldId, $deleteOldSession)) {
			$this->logger->warning('Could not regenerate the session id');
			return;
		}
		// The browser gets the new id from this request, so it needs a replacement of it as well
		$this->cookieAllowed = true;

		if ($updateToken && $oldId !== '') {
			// Get the new id to update the token
			$newId = $this->getId();

			/** @var IProvider $tokenProvider */
			$tokenProvider = Server::get(IProvider::class);

			try {
				$tokenProvider->renewSessionToken($oldId, $newId);
				Server::get(UserSession::class)->renewMagicSessionId($oldId);
			} catch (InvalidTokenException $e) {
				// Just ignore
			}
		}
	}

	/**
	 * Wrapper around session_id
	 *
	 * @return string
	 * @throws SessionNotAvailableException
	 * @since 9.1.0
	 */
	#[\Override]
	public function getId(): string {
		$id = $this->invoke('session_id', [], true);
		if ($id === '') {
			throw new SessionNotAvailableException();
		}
		return $id;
	}

	/**
	 * @throws \Exception
	 */
	#[\Override]
	public function reopen(): bool {
		if ($this->sessionClosed) {
			$id = $this->invoke('session_id', [], true);
			$this->startSession(readAndClose: false, sendCookie: false);
			if ($this->cookieAllowed && $this->invoke('session_id', [], true) !== $id) {
				// Strict mode replaced an id that is no longer stored, the browser needs the new one
				$this->restartWithCookie();
			}
			$this->sessionClosed = false;
			$this->leaveSupersededSession(atRequestStart: false);
			return true;
		}

		return false;
	}

	/**
	 * @param int $errorNumber
	 * @param string $errorString
	 * @throws \ErrorException
	 */
	public function trapError(int $errorNumber, string $errorString) {
		if ($errorNumber & E_ERROR) {
			throw new \ErrorException($errorString);
		}
	}

	/**
	 * @param string $functionName the full session_* function name
	 * @param array $parameters
	 * @param bool $silence whether to suppress warnings
	 * @throws \ErrorException via trapError
	 * @return mixed
	 */
	private function invoke(string $functionName, array $parameters = [], bool $silence = false) {
		try {
			return $this->monitorAndLog(
				$this->logger,
				$functionName,
				function () use ($silence, $functionName, $parameters) {
					if ($silence) {
						return @call_user_func_array($functionName, $parameters);
					} else {
						return call_user_func_array($functionName, $parameters);
					}
				}
			);
		} catch (\Error $e) {
			$this->trapError($e->getCode(), $e->getMessage());
		}
	}

	/**
	 * @param bool $sendCookie Whether PHP may send the session cookie, which can replace a newer id the browser holds
	 */
	private function startSession(bool $readAndClose = true, bool $sendCookie = true) {
		$sessionParams = [
			'cookie_samesite' => 'Lax',
			'use_cookies' => $sendCookie,
		];
		if (\OC::hasSessionRelaxedExpiry()) {
			$sessionParams['read_and_close'] = $readAndClose;
		}
		$this->invoke('session_start', [$sessionParams]);
	}

	private function restartWithCookie(): void {
		$this->invoke('session_write_close', [], true);
		$this->startSession(readAndClose: false);
	}

	private function isActive(): bool {
		return session_status() === PHP_SESSION_ACTIVE;
	}

	/**
	 * @return bool Whether the session moved to a new id. If not, it stays on $oldId with its data.
	 */
	private function switchToNewId(string $oldId, bool $deleteOldSession): bool {
		// session_regenerate_id() needs an active session that may send cookies, reopen() starts it without.
		// Also within the grace period: a login or logout on a replaced id has to reach the browser.
		if (!$this->isActive() || !ini_get('session.use_cookies')) {
			$this->restartWithCookie();
		}

		$data = $_SESSION;
		// The restart may have read the marker of a concurrent regeneration
		unset($data[self::SUPERSEDED_AT]);
		// Only an id the client sent can come back in another request
		$markOldId = $deleteOldSession && ($_COOKIE[session_name()] ?? null) === $oldId;
		if ($markOldId) {
			// The old id keeps the marker instead of the data, so session_regenerate_id() must not delete it
			$_SESSION = [self::SUPERSEDED_AT => time()];
		}

		$regenerated = $this->invoke('session_regenerate_id', [$deleteOldSession && !$markOldId], true) === true;
		if (!$regenerated && $oldId !== '' && !$this->isActive()) {
			// Continue on the old id
			$this->invoke('session_id', [$oldId], true);
			$this->startSession(readAndClose: false, sendCookie: false);
		}
		$_SESSION = $data;
		$this->sessionClosed = !$this->isActive();
		return $regenerated;
	}

	/**
	 * Move a request on an id replaced by regenerateId() to a fresh session, so the old id can't log in again.
	 * It gets a cookie only if the request starts after the grace period, see SUPERSEDED_GRACE_PERIOD.
	 */
	private function leaveSupersededSession(bool $atRequestStart): void {
		if (!isset($_SESSION[self::SUPERSEDED_AT])) {
			return;
		}

		$supersededAt = (int)$_SESSION[self::SUPERSEDED_AT];
		$this->logger->debug('Request uses a session id that was replaced at {supersededAt}', [
			'supersededAt' => $supersededAt,
		]);
		$this->invoke('session_abort', [], true);
		$_SESSION = [];
		$this->cookieAllowed = $atRequestStart && time() - $supersededAt >= self::SUPERSEDED_GRACE_PERIOD;
		$freshId = $this->invoke('session_create_id', [], true);
		if (is_string($freshId) && $this->invoke('session_id', [$freshId], true) !== false) {
			$this->startSession(readAndClose: $atRequestStart, sendCookie: $this->cookieAllowed);
		}
		$this->sessionClosed = !$this->isActive();
	}
}
