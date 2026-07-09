<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\OneTimePassword\Exceptions\OTPProviderNotFoundException;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\IManager as IOTPManager;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingOTPSendSuccess from ResponseDefinitions
 * @psalm-import-type Files_SharingOTPSendError from ResponseDefinitions
 * @psalm-import-type Files_SharingOTPProvider from ResponseDefinitions
 */
class ShareOTPController extends ApiController {
	private IL10N $l;

	/**
	 * ShareOTPController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IShareManager $shareManager
	 * @param IOTPManager $otpManager
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IShareManager $shareManager,
		private readonly IOTPManager $otpManager,
		private readonly LoggerInterface $logger,
		private readonly IFactory $l10nFactory,
		private readonly IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
		$this->l = $this->l10nFactory->get(Application::APP_ID);
	}

	/**
	 * Request an OTP to be send to the configured recipient
	 *
	 * @param string $token Token of the share
	 *
	 * @return JSONResponse<Http::STATUS_CREATED, Files_SharingOTPSendSuccess, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, list<empty>, array{}>|JSONResponse<Http::STATUS_INTERNAL_SERVER_ERROR, Files_SharingOTPSendError, array{}>
	 *
	 * 201: OTP sent successfully
	 * 400: Invalid otp provider/recipient requested
	 * 403: OTP not configured for the share
	 * 404: Share not found
	 * 500: Sending OTP failed
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'sendotp')]
	#[UserRateLimit(limit: 5, period: 60)]
	#[AnonRateLimit(limit: 5, period: 60)]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function request(string $token): JSONResponse {
		$this->logger->debug('requesting OTP for share: ' . $token, ['app' => 'files_sharing']);
		try {
			$share = $this->shareManager->getShareByToken($token);
		} catch (ShareNotFound $e) {
			$response = new JSONResponse([], Http::STATUS_NOT_FOUND);
			$response->throttle(['token' => $token]);
			return $response;
		}
		if ($share->getOneTimePassword() === null) {
			$response = new JSONResponse([], Http::STATUS_FORBIDDEN);
			$response->throttle(['token' => $token]);
			return $response;
		}

		try {
			$message = $this->l->t('A one-time password was requested to access a Nextcloud share.');
			if ($share->getShareType() === IShare::TYPE_LINK || $share->getShareType() === IShare::TYPE_EMAIL) {
				$url = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $token]);
				$message .= ' ' . $this->l->t('You can access it at the following URL by entering the password below') . " <a href=\"$url\">" . $url . '</a>:';
			}
			$this->logger->debug('sending otp to \'' . $share->getOneTimePassword()->getProviderId() . '(' . $share->getOneTimePassword()->getRecipient() . ')');
			$this->otpManager->sendOTP($share->getOneTimePassword(), $message);
		} catch (OTPSendException $e) {
			return new JSONResponse(['error' => 'Error sending OTP to the recipient: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (OTPProviderNotFoundException $e) {
			return new JSONResponse(['error' => $this->l->t('No OTP provider found for id {provider}', ['provider' => $share->getOneTimePassword()->getProviderId()])], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse([], Http::STATUS_CREATED);
	}

	/**
	 * Return the list of available OTP providers
	 *
	 * @return JSONResponse<Http::STATUS_OK, list<Files_SharingOTPProvider>, array{}>
	 *
	 * 200: OTP providers successfully returned
	 */
	#[NoAdminRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function listProviders(): JSONResponse {
		$providers = $this->otpManager->getOTPProviders();
		$result = [];
		foreach ($providers as $provider) {
			$result[] = [
				'id' => $provider->getProviderId(),
				'name' => $provider->getName(),
				'description' => $provider->getDescription(),
				'recipientPattern' => $provider->getRecipientPattern()
			];
		}

		return new JSONResponse($result, Http::STATUS_OK);
	}
}
