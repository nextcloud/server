<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Authentication\Events\AppPasswordCreatedEvent;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\User\Session;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\PasswordUnavailableException;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;

class AppPasswordController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ISession $session,
		private ISecureRandom $random,
		private IProvider $tokenProvider,
		private IStore $credentialStore,
		private IEventDispatcher $eventDispatcher,
		private Session $userSession,
		private IUserManager $userManager,
		private IThrottler $throttler,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Create app password
	 *
	 * @return DataResponse<Http::STATUS_OK, array{apppassword: string}, array{}>
	 * @throws OCSForbiddenException Creating app password is not allowed
	 *
	 * 200: App password returned
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'GET', url: '/getapppassword', root: '/core')]
	public function getAppPassword(): DataResponse {
		// We do not allow the creation of new tokens if this is an app password
		if ($this->session->exists('app_password')) {
			throw new OCSForbiddenException('You cannot request an new apppassword with an apppassword');
		}

		try {
			$credentials = $this->credentialStore->getLoginCredentials();
		} catch (CredentialsUnavailableException $e) {
			throw new OCSForbiddenException();
		}

		try {
			$password = $credentials->getPassword();
		} catch (PasswordUnavailableException $e) {
			$password = null;
		}

		$userAgent = $this->request->getHeader('user-agent');

		$token = $this->random->generate(72, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);

		$generatedToken = $this->tokenProvider->generateToken(
			$token,
			$credentials->getUID(),
			$credentials->getLoginName(),
			$password,
			$userAgent,
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

		$this->eventDispatcher->dispatchTyped(
			new AppPasswordCreatedEvent($generatedToken)
		);

		return new DataResponse([
			'apppassword' => $token
		]);
	}

	/**
	 * Delete app password
	 *
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSForbiddenException Deleting app password is not allowed
	 *
	 * 200: App password deleted successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/apppassword', root: '/core')]
	public function deleteAppPassword(): DataResponse {
		if (!$this->session->exists('app_password')) {
			throw new OCSForbiddenException('no app password in use');
		}

		$appPassword = $this->session->get('app_password');

		try {
			$token = $this->tokenProvider->getToken($appPassword);
		} catch (InvalidTokenException $e) {
			throw new OCSForbiddenException('could not remove apptoken');
		}

		$this->tokenProvider->invalidateTokenById($token->getUID(), $token->getId());
		return new DataResponse();
	}

	/**
	 * Rotate app password
	 *
	 * @return DataResponse<Http::STATUS_OK, array{apppassword: string}, array{}>
	 * @throws OCSForbiddenException Rotating app password is not allowed
	 *
	 * 200: App password returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/apppassword/rotate', root: '/core')]
	public function rotateAppPassword(): DataResponse {
		if (!$this->session->exists('app_password')) {
			throw new OCSForbiddenException('no app password in use');
		}

		$appPassword = $this->session->get('app_password');

		try {
			$token = $this->tokenProvider->getToken($appPassword);
		} catch (InvalidTokenException $e) {
			throw new OCSForbiddenException('could not rotate apptoken');
		}

		$newToken = $this->random->generate(72, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		$this->tokenProvider->rotate($token, $appPassword, $newToken);

		return new DataResponse([
			'apppassword' => $newToken,
		]);
	}

	/**
	 * Confirm the user password
	 *
	 * @param string $password The password of the user
	 *
	 * @return DataResponse<Http::STATUS_OK, array{lastLogin: int}, array{}>|DataResponse<Http::STATUS_FORBIDDEN, list<empty>, array{}>
	 *
	 * 200: Password confirmation succeeded
	 * 403: Password confirmation failed
	 */
	#[NoAdminRequired]
	#[BruteForceProtection(action: 'sudo')]
	#[UseSession]
	#[ApiRoute(verb: 'PUT', url: '/apppassword/confirm', root: '/core')]
	public function confirmUserPassword(string $password): DataResponse {
		$loginName = $this->userSession->getLoginName();
		$loginResult = $this->userManager->checkPassword($loginName, $password);
		if ($loginResult === false) {
			$response = new DataResponse([], Http::STATUS_FORBIDDEN);
			$response->throttle(['loginName' => $loginName]);
			return $response;
		}

		$confirmTimestamp = time();
		$this->session->set('last-password-confirm', $confirmTimestamp);
		$this->throttler->resetDelay($this->request->getRemoteAddress(), 'sudo', ['loginName' => $loginName]);
		return new DataResponse(['lastLogin' => $confirmTimestamp], Http::STATUS_OK);
	}
}
