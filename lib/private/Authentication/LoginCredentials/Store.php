<?php

/**
 * @copyright 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Authentication\LoginCredentials;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\ILogger;
use OCP\ISession;
use OCP\Session\Exceptions\SessionNotAvailableException;

class Store implements IStore {

	/** @var ISession */
	private $session;

	/** @var IProvider */
	private $tokenProvider;

	/** @var ILogger */
	private $logger;

	/**
	 * @param ISession $session
	 * @param IProvider $tokenProvider
	 * @param ILogger $logger
	 */
	public function __construct(ISession $session, IProvider $tokenProvider, ILogger $logger) {
		$this->session = $session;
		$this->tokenProvider = $tokenProvider;
		$this->logger = $logger;
	}

	/**
	 * @since 9.2
	 *
	 * @return ICredentials the login credentials of the current user
	 * @throws CredentialsUnavailableException
	 */
	public function getLoginCredentials() {
		try {
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);

			$uid = $token->getUID();
			$user = $token->getLoginName();
			$password = $this->tokenProvider->getPassword($token, $sessionId);

			return new Credentials($uid, $user, $password);
		} catch (SessionNotAvailableException $ex) {
			$this->logger->debug('could not get login credentials because session is unavailable', ['app' => 'core']);
		} catch (InvalidTokenException $ex) {
			$this->logger->debug('could not get login credentials because the token is invalid', ['app' => 'core']);
		} catch (PasswordlessTokenException $ex) {
			$this->logger->debug('could not get login credentials because the token has no password', ['app' => 'core']);
		}
		// If we reach this line, an exception was thrown.
		throw new CredentialsUnavailableException();
	}

}
