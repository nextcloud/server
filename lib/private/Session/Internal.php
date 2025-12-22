<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Session;

use OC\Authentication\Token\IProvider;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\ILogger;
use OCP\Server;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Psr\Log\LoggerInterface;
use function call_user_func_array;
use function microtime;

/**
 * Class Internal
 *
 * wrap php's internal session handling into the Session interface
 *
 * @package OC\Session
 */
class Internal extends Session {
	/**
	 * @param string $name
	 * @throws \Exception
	 */
	public function __construct(
		string $name,
		private ?LoggerInterface $logger,
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
	}

	/**
	 * @param string $key
	 * @param integer $value
	 */
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
	public function exists(string $key): bool {
		return isset($_SESSION[$key]);
	}

	/**
	 * @param string $key
	 */
	public function remove(string $key) {
		if (isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
	}

	public function clear() {
		$this->reopen();
		$this->invoke('session_unset');
		$this->regenerateId();
		$this->invoke('session_write_close');
		$this->startSession(true);
		$_SESSION = [];
	}

	public function close() {
		$this->invoke('session_write_close');
		parent::close();
	}

	/**
	 * Wrapper around session_regenerate_id
	 *
	 * @param bool $deleteOldSession Whether to delete the old associated session file or not.
	 * @param bool $updateToken Whether to update the associated auth token
	 * @return void
	 */
	public function regenerateId(bool $deleteOldSession = true, bool $updateToken = false) {
		$this->reopen();
		$oldId = null;

		if ($updateToken) {
			// Get the old id to update the token
			try {
				$oldId = $this->getId();
			} catch (SessionNotAvailableException $e) {
				// We can't update a token if there is no previous id
				$updateToken = false;
			}
		}

		try {
			@session_regenerate_id($deleteOldSession);
		} catch (\Error $e) {
			$this->trapError($e->getCode(), $e->getMessage());
		}

		if ($updateToken) {
			// Get the new id to update the token
			$newId = $this->getId();

			/** @var IProvider $tokenProvider */
			$tokenProvider = Server::get(IProvider::class);

			try {
				$tokenProvider->renewSessionToken($oldId, $newId);
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
	public function reopen(): bool {
		if ($this->sessionClosed) {
			$this->startSession(false, false);
			$this->sessionClosed = false;
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
			$timeBefore = microtime(true);
			if ($silence) {
				$result = @call_user_func_array($functionName, $parameters);
			} else {
				$result = call_user_func_array($functionName, $parameters);
			}
			$timeAfter = microtime(true);
			$timeSpent = $timeAfter - $timeBefore;
			if ($timeSpent > 0.1) {
				$logLevel = match (true) {
					$timeSpent > 25 => ILogger::ERROR,
					$timeSpent > 10 => ILogger::WARN,
					$timeSpent > 0.5 => ILogger::INFO,
					default => ILogger::DEBUG,
				};
				$this->logger?->log(
					$logLevel,
					"Slow session operation $functionName detected",
					[
						'parameters' => $parameters,
						'timeSpent' => $timeSpent,
					],
				);
			}
			return $result;
		} catch (\Error $e) {
			$this->trapError($e->getCode(), $e->getMessage());
		}
	}

	private function startSession(bool $silence = false, bool $readAndClose = true) {
		$sessionParams = ['cookie_samesite' => 'Lax'];
		if (\OC::hasSessionRelaxedExpiry()) {
			$sessionParams['read_and_close'] = $readAndClose;
		}
		$this->invoke('session_start', [$sessionParams], $silence);
	}
}
