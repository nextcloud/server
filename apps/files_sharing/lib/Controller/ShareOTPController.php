<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\ResponseDefinitions;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\IManager as IOTPManager;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingOTPSendSuccess from ResponseDefinitions
 * @psalm-import-type Files_SharingOTPSendError from ResponseDefinitions
 */
class ShareOTPController extends ApiController {

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
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Request an OTP to be send to the configured recipient
	 *
	 * @param string $token Token of the share
	 *
	 * @return JSONResponse 201: OTP sent successfully 403: OTP not configured for the share 404: Share not found 500: Sending OTP failed
	 *
	 * @psalm-return JSONResponse<int, array{error?: string}, array<never, never>>
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'sendotp')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function request(string $token): JSONResponse {
		$this->logger->warning('requesting OTP for share: ' . $token);
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
			$this->otpManager->sendOTP($share->getOneTimePassword());
		} catch (\DateInvalidTimeZoneException|Exception $e) {
			return new JSONResponse(['error' => 'internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (OTPSendException $e) {
			return new JSONResponse(['error' => 'Error sending OTP to the recipient: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return new JSONResponse([], Http::STATUS_CREATED);
	}
}
