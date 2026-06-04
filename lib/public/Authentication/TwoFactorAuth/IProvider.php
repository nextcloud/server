<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;
use OCP\Template\ITemplate;

/**
 * @since 9.1.0
 */
interface IProvider {
	/**
	 * Get unique identifier of this 2FA provider
	 *
	 * @since 9.1.0
	 *
	 * @return string
	 */
	public function getId(): string;

	/**
	 * Get the display name for selecting the 2FA provider
	 *
	 * Example: "Email"
	 *
	 * @since 9.1.0
	 *
	 * @return string
	 */
	public function getDisplayName(): string;

	/**
	 * Get the description for selecting the 2FA provider
	 *
	 * Example: "Get a token via e-mail"
	 *
	 * @since 9.1.0
	 *
	 * @return string
	 */
	public function getDescription(): string;

	/**
	 * Get the template for rending the 2FA provider view
	 *
	 * @since 9.1.0
	 * @since 32.0.0 Broader return type ITemplate instead of \OCP\Template.
	 */
	public function getTemplate(IUser $user): ITemplate;

	/**
	 * Verify the given challenge
	 *
	 * @since 9.1.0
	 *
	 * @param IUser $user
	 * @param string $challenge
	 * @return bool
	 */
	public function verifyChallenge(IUser $user, string $challenge): bool;

	/**
	 * Decides whether 2FA is enabled for the given user
	 *
	 * @since 9.1.0
	 *
	 * @param IUser $user
	 * @return bool
	 */
	public function isTwoFactorAuthEnabledForUser(IUser $user): bool;
}
