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
 * Class CryptoSessionData
 *
 * @package OC\Session
 * @template-implements \ArrayAccess<string,mixed>
 */
class CryptoSessionData implements \ArrayAccess, ISession {
	/** @var ISession */
	protected $session;
	/** @var \OCP\Security\ICrypto */
	protected $crypto;
	/** @var string */
	protected $passphrase;
	/** @var array */
	protected $sessionValues;
	/** @var bool */
	protected $isModified = false;
	public const encryptedSessionName = 'encrypted_session_data';

	/**
	 * @param ISession $session
	 * @param ICrypto $crypto
	 * @param string $passphrase
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
	 */
	public function __destruct() {
		try {
			$this->close();
		} catch (SessionNotAvailableException $e) {
			// This exception can occur if session is already closed
			// So it is safe to ignore it and let the garbage collector to proceed
		}
	}

	protected function initializeSession() {
		$encryptedSessionData = $this->session->get(self::encryptedSessionName) ?: '';
		if ($encryptedSessionData === '') {
			// Nothing to decrypt
			$this->sessionValues = [];
		} else {
			try {
				$this->sessionValues = json_decode(
					$this->crypto->decrypt($encryptedSessionData, $this->passphrase),
					true,
					512,
					JSON_THROW_ON_ERROR,
				);
			} catch (\Exception $e) {
				logger('core')->critical('Could not decrypt or decode encrypted session data', [
					'exception' => $e,
				]);
				$this->sessionValues = [];
				$this->regenerateId(true, false);
			}
		}
	}

	public function set(string $key, $value) {
		if ($this->get($key) === $value) {
			// Do not write the session if the value hasn't changed to avoid reopening
			return;
		}

		$reopened = $this->reopen();
		$this->sessionValues[$key] = $value;
		$this->isModified = true;
		if ($reopened) {
			$this->close();
		}
	}

	public function get(string $key) {
		if (isset($this->sessionValues[$key])) {
			return $this->sessionValues[$key];
		}

		return null;
	}

	public function exists(string $key): bool {
		return isset($this->sessionValues[$key]);
	}

	public function remove(string $key) {
		$reopened = $this->reopen();
		$this->isModified = true;
		unset($this->sessionValues[$key]);
		if ($reopened) {
			$this->close();
		}
	}

	public function clear() {
		$reopened = $this->reopen();
		$requesttoken = $this->get('requesttoken');
		$this->sessionValues = [];
		if ($requesttoken !== null) {
			$this->set('requesttoken', $requesttoken);
		}
		$this->isModified = true;
		$this->session->clear();
		if ($reopened) {
			$this->close();
		}
	}

	public function reopen(): bool {
		$reopened = $this->session->reopen();
		if ($reopened) {
			$this->initializeSession();
		}
		return $reopened;
	}

	public function regenerateId(bool $deleteOldSession = true, bool $updateToken = false) {
		$this->session->regenerateId($deleteOldSession, $updateToken);
	}

	public function getId(): string {
		return $this->session->getId();
	}

	public function close() {
		if ($this->isModified) {
			$encryptedValue = $this->crypto->encrypt(json_encode($this->sessionValues), $this->passphrase);
			$this->session->set(self::encryptedSessionName, $encryptedValue);
			$this->isModified = false;
		}
		$this->session->close();
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool {
		return $this->exists($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}
}
