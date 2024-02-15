<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Provisioning_API\Controller;

use InvalidArgumentException;
use OC\Security\Crypto;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Controller;
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

	/** @var IVerificationToken */
	private $verificationToken;
	/** @var IUserManager */
	private $userManager;
	/** @var IL10N */
	private $l10n;
	/** @var IUserSession */
	private $userSession;
	/** @var IAccountManager */
	private $accountManager;
	/** @var Crypto */
	private $crypto;

	public function __construct(
		string $appName,
		IRequest $request,
		IVerificationToken $verificationToken,
		IUserManager $userManager,
		IL10N $l10n,
		IUserSession $userSession,
		IAccountManager $accountManager,
		Crypto $crypto
	) {
		parent::__construct($appName, $request);
		$this->verificationToken = $verificationToken;
		$this->userManager = $userManager;
		$this->l10n = $l10n;
		$this->userSession = $userSession;
		$this->accountManager = $accountManager;
		$this->crypto = $crypto;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
	public function showVerifyMail(string $token, string $userId, string $key): TemplateResponse {
		if ($this->userSession->getUser()->getUID() !== $userId) {
			// not a public page, hence getUser() must return an IUser
			throw new InvalidArgumentException('Logged in user is not mail address owner');
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
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @BruteForceProtection(action=emailVerification)
	 */
	public function verifyMail(string $token, string $userId, string $key): TemplateResponse {
		$throttle = false;
		try {
			if ($this->userSession->getUser()->getUID() !== $userId) {
				throw new InvalidArgumentException('Logged in user is not mail address owner');
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
