<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OneTimePassword;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\Exception;
use OCP\OneTimePassword\Exceptions\OTPProviderNotFoundException;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\Exceptions\OTPThrottledException;

/**
 * This interface allows to manage one-time passwords.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IManager {

	/**
	 * Register an OTP provider
	 *
	 * @param IOneTimePasswordProvider $provider The OTP provider to register
	 * @since 36.0.0
	 */
	public function registerOTPProvider(IOneTimePasswordProvider $provider): void;

	/**
	 * Generate and send a new OTP
	 *
	 * @param IOneTimePassword $otp The configuration for generating the OTP
	 * @param ?string $message
	 * @return void
	 * @throws OTPThrottledException If the previous OTP was sent less than a minute ago and is still unused
	 * @throws OTPSendException
	 * @since 36.0.0
	 */
	public function sendOTP(IOneTimePassword $otp, ?string $message): void;

	/**
	 * Check if an OTP is valid and matches the password, and invalidate it on success
	 *
	 * An OTP can be validated successfully only once.
	 *
	 * @param IOneTimePassword $otp
	 * @param string|null $password
	 * @return bool
	 * @since 36.0.0
	 */
	public function validateOTP(IOneTimePassword $otp, ?string $password): bool;

	/**
	 * Returns a list of registered OTP providers
	 *
	 * @return IOneTimePasswordProvider[]
	 * @since 36.0.0
	 */
	public function getOTPProviders(): array;

	/**
	 * Returns the first matching OTP provider
	 *
	 * @param string $providerId
	 * @return IOneTimePasswordProvider
	 * @throws OTPProviderNotFoundException
	 * @since 36.0.0
	 */
	public function getOTPProviderById(string $providerId): IOneTimePasswordProvider;

	// DB interface

	/**
	 * create new OTP
	 *
	 * @param string $provider
	 * @param string $recipient
	 * @return IOneTimePassword
	 * @throws OTPProviderNotFoundException|\DateInvalidTimeZoneException|Exception
	 * @throws \InvalidArgumentException If the recipient does not match the recipient pattern of the provider
	 * @since 36.0.0
	 */
	public function createOTP(string $provider, string $recipient): IOneTimePassword;

	/**
	 * update OTP in DB
	 *
	 * @param IOneTimePassword $otp
	 * @return void
	 * @since 36.0.0
	 */
	public function updateOTP(IOneTimePassword $otp): void;

	/**
	 * get OTP from DB
	 *
	 * @param int $otpId
	 * @return IOneTimePassword
	 * @since 36.0.0
	 */
	public function getOTP(int $otpId): IOneTimePassword;

	/**
	 * delete OTP from DB
	 *
	 * @param int $otpId
	 * @return void
	 * @since 36.0.0
	 */
	public function deleteOTP(int $otpId): void;
}
