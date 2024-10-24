<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Provisioning_API\Controller;

use InvalidArgumentException;
use OC\Security\Crypto;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\VerificationToken\InvalidTokenException;
use OCP\Security\VerificationToken\IVerificationToken;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class VerificationController extends Controller {

	/** @var Crypto */
	private $crypto;

	public function __construct(
		string $appName,
		IRequest $request,
		private IVerificationToken $verificationToken,
		private IUserManager $userManager,
		private IL10N $l10n,
		private IUserSession $userSession,
		private IAccountManager $accountManager,
		Crypto $crypto,
	) {
		parent::__construct($appName, $request);
		$this->crypto = $crypto;
	}

	/**
	 * @NoSubAdminRequired
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showVerifyMail(string $token, string $userId, string $key): TemplateResponse {
		if ($this->userSession->getUser()->getUID() !== $userId) {
			// not a public page, hence getUser() must return an IUser
			throw new InvalidArgumentException('Logged in account is not mail address owner');
		}
		$email = $this->crypto->decrypt($key);

		return new TemplateResponse(
			'core', 'confirmation', [
				'title' => $this->l10n->t('Email confirmation'),
				'message' => $this->l10n->t('To enable the email address %s please click the button below.', [$email]),
				'action' => $this->l10n->t('Confirm'),
			], TemplateResponse::RENDER_AS_GUEST);
	}

	/**
	 * @NoSubAdminRequired
	 */
	#[NoAdminRequired]
	#[BruteForceProtection(action: 'emailVerification')]
	public function verifyMail(string $token, string $userId, string $key): TemplateResponse {
		$throttle = false;
		try {
			if ($this->userSession->getUser()->getUID() !== $userId) {
				throw new InvalidArgumentException('Logged in account is not mail address owner');
			}
			$email = $this->crypto->decrypt($key);
			$ref = \substr(hash('sha256', $email), 0, 8);

			$user = $this->userManager->get($userId);
			$this->verificationToken->check($token, $user, 'verifyMail' . $ref, $email);

			$userAccount = $this->accountManager->getAccount($user);
			$emailProperty = $userAccount->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)
				->getPropertyByValue($email);

			if ($emailProperty === null) {
				throw new InvalidArgumentException($this->l10n->t('Email was already removed from account and cannot be confirmed anymore.'));
			}
			$emailProperty->setLocallyVerified(IAccountManager::VERIFIED);
			$this->accountManager->updateAccount($userAccount);
			$this->verificationToken->delete($token, $user, 'verifyMail' . $ref);
		} catch (InvalidTokenException $e) {
			if ($e->getCode() === InvalidTokenException::TOKEN_EXPIRED) {
				$error = $this->l10n->t('Could not verify mail because the token is expired.');
			} else {
				$throttle = true;
				$error = $this->l10n->t('Could not verify mail because the token is invalid.');
			}
		} catch (InvalidArgumentException $e) {
			$error = $e->getMessage();
		} catch (\Exception $e) {
			$error = $this->l10n->t('An unexpected error occurred. Please contact your admin.');
		}

		if (isset($error)) {
			$response = new TemplateResponse(
				'core', 'error', [
					'errors' => [['error' => $error]]
				], TemplateResponse::RENDER_AS_GUEST);
			if ($throttle) {
				$response->throttle();
			}
			return $response;
		}

		return new TemplateResponse(
			'core', 'success', [
				'title' => $this->l10n->t('Email confirmation successful'),
				'message' => $this->l10n->t('Email confirmation successful'),
			], TemplateResponse::RENDER_AS_GUEST);
	}
}
