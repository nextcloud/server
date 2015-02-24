<?php
/**
 * @author Clark Tomlinson  <fallen013@gmail.com>
 * @since 3/6/15, 11:36 AM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2015
 *
 */

namespace OCA\Encryption\Users;


use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCP\ILogger;
use OCP\IUserSession;

class Setup extends \OCA\Encryption\Setup {
	/**
	 * @var Crypt
	 */
	private $crypt;
	/**
	 * @var KeyManager
	 */
	private $keyManager;


	/**
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param Crypt $crypt
	 * @param KeyManager $keyManager
	 */
	public function __construct(ILogger $logger, IUserSession $userSession, Crypt $crypt, KeyManager $keyManager) {
		parent::__construct($logger, $userSession);
		$this->crypt = $crypt;
		$this->keyManager = $keyManager;
	}

	/**
	 * @param $password
	 * @return bool
	 */
	public function setupUser($password) {
		if ($this->keyManager->ready()) {
			$this->logger->debug('Encryption Library: User Account ' . $this->user->getUID() . ' Is not ready for encryption; configuration started');
			return $this->setupServerSide($password);
		}
	}

	/**
	 * @param $password
	 * @return bool
	 */
	private function setupServerSide($password) {
		// Check if user already has keys
		if (!$this->keyManager->userHasKeys($this->user->getUID())) {
			return $this->keyManager->storeKeyPair($password, $this->crypt->createKeyPair());
		}
		return true;
	}
}
