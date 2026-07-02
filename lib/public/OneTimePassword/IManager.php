<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\OneTimePassword;

use OCP\AppFramework\Attribute\Consumable;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\Exceptions\OTPProviderNotFoundException;

/**
 * This interface allows to manage one-time passwords.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IManager {

	/**
	 * Generate and send a new OTP
	 *
	 * @param IOneTimePassword $otp The configuration for generating the OTP
	 * @return void
	 * @throws OTPSendException
	 */
	public function sendOTP(IOneTimePassword $otp): void;

	/**
	 * Check if an OTP is valid and matches the password
	 *
	 * @param IOneTimePassword $otp
	 * @param string|null $password
	 * @return bool
	 */
	public function validateOTP(IOneTimePassword $otp, ?string $password): bool;

	/**
	 * Returns a list of registered OTP providers
	 *
	 * @return IOneTimePasswordProvider[]
	 */
	public function getOTPProviders(): array;

	/**
	 * Returns the first matching OTP provider
	 *
	 * @param string $providerId
	 * @return IOneTimePasswordProvider
	 * @throws OTPProviderNotFoundException
	 */
	public function getOTPProviderById(string $providerId): IOneTimePasswordProvider;

	// DB interface

	/**
	 * create new OTP
	 *
	 * @param string $provider
	 * @param string $recipient
	 * @param string|null $password
	 * @param \DateTime|null $expirationTime
	 * @return IOneTimePassword
	 */
	public function createOTP(string $provider, string $recipient, ?string $password, ?\DateTime $expirationTime): IOneTimePassword;

	/**
	 * update OTP in DB
	 *
	 * @param IOneTimePassword $otp
	 * @return void
	 */
	public function updateOTP(IOneTimePassword $otp): void;

	/**
	 * get OTP from DB
	 *
	 * @param int $otpId
	 * @return IOneTimePassword
	 */
	public function getOTP(int $otpId): IOneTimePassword;

	/**
	 * delete OTP from DB
	 *
	 * @param int $otpId
	 * @return void
	 */
	public function deleteOTP(int $otpId): void;
}
