<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Session;

use OCP\ISession;
use OCP\Security\ICrypto;
use OCP\Session\Exceptions\SessionNotAvailableException;
use function json_decode;
use function OCP\Log\logger;

/**
 * @package OC\Session
 * @template-implements \ArrayAccess<string,mixed>
 * @deprecated 31.0.0
 */
class LegacyCryptoSessionData implements \ArrayAccess, ISession {
	/** @var ISession */
	protected $session;
	/** @var ICrypto */
	protected $crypto;
	/** @var string */
	protected $passphrase;
	public const encryptedSessionName = 'encrypted_session_data';

	/**
	 * @param ISession $session
	 * @param ICrypto $crypto
	 * @param string $passphrase
	 * @deprecated 31.0.0
	 */
	public function __construct(ISession $session,
		ICrypto $crypto,
		string $passphrase) {
		$this->crypto = $crypto;
		$this->session = $session;
		$this->passphrase = $passphrase;
		$this->initializeSession();
	}

	/**
	 * Close session if class gets destructed
	 * @deprecated 31.0.0
	 */
	public function __destruct() {
		try {
			$this->close();
		} catch (SessionNotAvailableException $e) {
			// This exception can occur if session is already closed
			// So it is safe to ignore it and let the garbage collector to proceed
		}
	}

	/**
	 * @deprecated 31.0.0
	 */
	protected function initializeSession() {
		$encryptedSessionData = $this->session->get(self::encryptedSessionName) ?: '';
		if ($encryptedSessionData === '') {
			// Nothing to decrypt
			return;
		}
		try {
			$sessionValues = json_decode(
				$this->crypto->decrypt($encryptedSessionData, $this->passphrase),
				true,
				512,
				JSON_THROW_ON_ERROR,
			);
			foreach ($sessionValues as $key => $value) {
				$this->session->set($key, $value);
			}
			$this->session->remove(self::encryptedSessionName);
		} catch (\Exception $e) {
			logger('core')->critical('Could not decrypt or decode encrypted legacy session data', [
				'exception' => $e,
			]);
			$this->regenerateId(true, false);
		}
	}

	/**
	 * Set a value in the session
	 *
	 * @param string $key
	 * @param mixed $value
	 * @deprecated 31.0.0
	 */
	public function set(string $key, $value) {
		$this->session->set($key, $value);
	}

	/**
	 * Get a value from the session
	 *
	 * @param string $key
	 * @return string|null Either the value or null
	 * @deprecated 31.0.0
	 */
	public function get(string $key) {
		return $this->session->get($key);
	}

	/**
	 * Check if a named key exists in the session
	 *
	 * @param string $key
	 * @return bool
	 * @deprecated 31.0.0
	 */
	public function exists(string $key): bool {
		return $this->session->exists($key);
	}

	/**
	 * Remove a $key/$value pair from the session
	 *
	 * @param string $key
	 * @deprecated 31.0.0
	 */
	public function remove(string $key) {
		$this->session->remove($key);
	}

	/**
	 * Reset and recreate the session
	 * @deprecated 31.0.0
	 */
	public function clear() {
		$requesttoken = $this->get('requesttoken');
		$this->session->clear();
		if ($requesttoken !== null) {
			$this->set('requesttoken', $requesttoken);
		}
	}

	/**
	 * @deprecated 31.0.0
	 */
	public function reopen(): bool {
		$reopened = $this->session->reopen();
		if ($reopened) {
			$this->initializeSession();
		}
		return $reopened;
	}

	/**
	 * Wrapper around session_regenerate_id
	 *
	 * @param bool $deleteOldSession Whether to delete the old associated session file or not.
	 * @param bool $updateToken Wheater to update the associated auth token
	 * @return void
	 * @deprecated 31.0.0
	 */
	public function regenerateId(bool $deleteOldSession = true, bool $updateToken = false) {
		$this->session->regenerateId($deleteOldSession, $updateToken);
	}

	/**
	 * Wrapper around session_id
	 *
	 * @return string
	 * @throws SessionNotAvailableException
	 * @since 9.1.0
	 * @deprecated 31.0.0
	 */
	public function getId(): string {
		return $this->session->getId();
	}

	/**
	 * Close the session and release the lock, also writes all changed data in batch
	 * @deprecated 31.0.0
	 */
	public function close() {
		$this->session->close();
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 * @deprecated 31.0.0
	 */
	public function offsetExists($offset): bool {
		return $this->exists($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 * @deprecated 31.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @deprecated 31.0.0
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 * @deprecated 31.0.0
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}
}
