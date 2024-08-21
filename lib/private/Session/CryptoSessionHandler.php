<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Session;

use Exception;
use OCP\IRequest;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use SessionHandler;
use function explode;
use function implode;
use function json_decode;
use function OCP\Log\logger;
use function session_decode;
use function session_encode;

class CryptoSessionHandler extends SessionHandler {

	public function __construct(private ISecureRandom $secureRandom,
		private ICrypto $crypto,
		private IRequest $request) {
	}

	/**
	 * @param string $id
	 *
	 * @return array{0: string, 1: ?string}
	 */
	private function parseId(string $id): array {
		$parts = explode('|', $id);
		return [$parts[0], $parts[1]];
	}

	public function create_sid(): string {
		$id = parent::create_sid();
		$passphrase = $this->secureRandom->generate(128);
		return implode('|', [$id, $passphrase]);
	}

	/**
	 * Read and decrypt session data
	 *
	 * The decryption passphrase is encoded in the id since Nextcloud 31. For
	 * backwards compatibility after upgrade we also read the pass phrase from
	 * the old cookie and try to restore session from the legacy format.
	 *
	 * @param string $id
	 *
	 * @return false|string
	 */
	public function read(string $id): false|string {
		[$sessionId, $passphrase] = $this->parseId($id);

		/**
		 * Legacy handling
		 *
		 * @TODO remove in Nextcloud 32
		 */
		if ($passphrase === null) {
			if (($passphrase = $this->request->getCookie(CryptoWrapper::COOKIE_NAME)) === false) {
				// No passphrase in the ID or an old cookie. Time to give up.
				return false;
			}

			// Read the encrypted and encoded data
			$serializedData = parent::read($sessionId);
			if ($serializedData === '') {
				// Nothing to decode or decrypt
				return '';
			}
			// Back up the superglobal before we decode/restore the legacy session data
			// This is necessary because session_decode populates the superglobals
			// We restore the old state end the end (the final block, also run in
			// case of an error)
			$globalBackup = $_SESSION;
			try {
				if (!session_decode($serializedData)) {
					// Bail if we can't decode the data
					return false;
				}
				$encryptedData = $_SESSION['encrypted_session_data'];
				try {
					$sessionValues = json_decode(
						$this->crypto->decrypt($encryptedData, $passphrase),
						true,
						512,
						JSON_THROW_ON_ERROR,
					);
					foreach ($sessionValues as $key => $value) {
						$_SESSION[$key] = $value;
					}
				} catch (Exception $e) {
					logger('core')->critical('Could not decrypt or decode encrypted legacy session data', [
						'exception' => $e,
						'sessionId' => $sessionId,
					]);
				}
				return session_encode();
			} finally {
				foreach (array_keys($_SESSION) as $key) {
					unset($_SESSION[$key]);
				}
				foreach ($globalBackup as $key => $value) {
					$_SESSION[$key] = $value;
				}
				$_SESSION = $globalBackup;
			}
		}

		$read = parent::read($sessionId);
		return $read;
	}

	/**
	 * Encrypt and write session data
	 *
	 * @param string $id
	 * @param string $data
	 *
	 * @return bool
	 */
	public function write(string $id, string $data): bool {
		[$sessionId, $passphrase] = $this->parseId($id);

		if ($passphrase === null) {
			$passphrase = $this->request->getCookie(CryptoWrapper::COOKIE_NAME);
			// return false;
		}

		return parent::write($sessionId, $data);
	}

	public function close(): bool {
		parent::close();
	}

}
