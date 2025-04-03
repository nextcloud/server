<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

use InvalidArgumentException;

/**
 * Interface IUser
 *
 * @since 8.0.0
 */
interface IUser {
	/**
	 * get the user id
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getUID();

	/**
	 * get the display name for the user, if no specific display name is set it will fallback to the user id
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getDisplayName();

	/**
	 * set the display name for the user
	 *
	 * @param string $displayName
	 * @return bool
	 * @since 8.0.0
	 *
	 * @since 25.0.0 Throw InvalidArgumentException
	 * @throws \InvalidArgumentException
	 */
	public function setDisplayName($displayName);

	/**
	 * returns the timestamp of the user's last login or 0 if the user did never
	 * login
	 *
	 * @return int
	 * @since 8.0.0
	 */
	public function getLastLogin(): int;

	/**
	 * Returns the timestamp of the user's first login, 0 if the user did never login, or -1 if the data is unknown (first login was on an older version)
	 *
	 * @since 31.0.0
	 */
	public function getFirstLogin(): int;

	/**
	 * Updates the timestamp of the most recent login of this user (and first login if needed)
	 *
	 * @return bool whether this is the first login
	 * @since 8.0.0
	 */
	public function updateLastLoginTimestamp(): bool;

	/**
	 * Delete the user
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function delete();

	/**
	 * Set the password of the user
	 *
	 * @param string $password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 * @return bool
	 * @since 8.0.0
	 */
	public function setPassword($password, $recoveryPassword = null);

	/**
	 * Get the password hash of the user
	 *
	 * @return ?string the password hash hashed by `\OCP\Security\IHasher::hash()`
	 * @since 30.0.0
	 */
	public function getPasswordHash(): ?string;

	/**
	 * Set the password hash of the user
	 *
	 * @param string $passwordHash the password hash hashed by `\OCP\Security\IHasher::hash()`
	 * @throws InvalidArgumentException when `$passwordHash` is not a valid hash
	 * @since 30.0.0
	 */
	public function setPasswordHash(string $passwordHash): bool;

	/**
	 * get the users home folder to mount
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getHome();

	/**
	 * Get the name of the backend class the user is connected with
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getBackendClassName();

	/**
	 * Get the backend for the current user object
	 * @return ?UserInterface
	 * @since 15.0.0
	 */
	public function getBackend();

	/**
	 * check if the backend allows the user to change their avatar on Personal page
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function canChangeAvatar();

	/**
	 * check if the backend supports changing passwords
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function canChangePassword();

	/**
	 * check if the backend supports changing display names
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function canChangeDisplayName();

	/**
	 * Check if the backend supports changing email
	 *
	 * @since 32.0.0
	 */
	public function canChangeEmail(): bool;

	/**
	 * check if the user is enabled
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function isEnabled();

	/**
	 * set the enabled status for the user
	 *
	 * @param bool $enabled
	 * @since 8.0.0
	 */
	public function setEnabled(bool $enabled = true);

	/**
	 * get the user's email address
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getEMailAddress();

	/**
	 * get the user's system email address
	 *
	 * The system mail address may be read only and may be set from different
	 * sources like LDAP, SAML or simply the admin.
	 *
	 * Use this getter only when the system address is needed. For picking the
	 * proper address to e.g. send a mail to, use getEMailAddress().
	 *
	 * @return string|null
	 * @since 23.0.0
	 */
	public function getSystemEMailAddress(): ?string;

	/**
	 * get the user's preferred email address
	 *
	 * The primary mail address may be set be the user to specify a different
	 * email address where mails by Nextcloud are sent to. It is not necessarily
	 * set.
	 *
	 * Use this getter only when the primary address is needed. For picking the
	 * proper address to e.g. send a mail to, use getEMailAddress().
	 *
	 * @return string|null
	 * @since 23.0.0
	 */
	public function getPrimaryEMailAddress(): ?string;

	/**
	 * get the avatar image if it exists
	 *
	 * @param int $size
	 * @return IImage|null
	 * @since 9.0.0
	 */
	public function getAvatarImage($size);

	/**
	 * get the federation cloud id
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getCloudId();

	/**
	 * set the email address of the user
	 *
	 * It is an alias to setSystemEMailAddress()
	 *
	 * @param string|null $mailAddress
	 * @return void
	 * @since 9.0.0
	 * @deprecated 23.0.0 use setSystemEMailAddress() or setPrimaryEMailAddress()
	 */
	public function setEMailAddress($mailAddress);

	/**
	 * Set the system email address of the user
	 *
	 * This is supposed to be used when the email is set from different sources
	 * (i.e. other user backends, admin).
	 *
	 * @since 23.0.0
	 */
	public function setSystemEMailAddress(string $mailAddress): void;

	/**
	 * Set the primary email address of the user.
	 *
	 * This method should be typically called when the user is changing their
	 * own primary address and is not allowed to change their system email.
	 *
	 * The mail address provided here must be already registered as an
	 * additional mail in the user account and also be verified locally. Also
	 * an empty string is allowed to delete this preference.
	 *
	 * @throws InvalidArgumentException when the provided email address does not
	 *                                  satisfy constraints.
	 *
	 * @since 23.0.0
	 */
	public function setPrimaryEMailAddress(string $mailAddress): void;

	/**
	 * get the users' quota in human readable form. If a specific quota is not
	 * set for the user, the default value is returned. If a default setting
	 * was not set otherwise, it is return as 'none', i.e. quota is not limited.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getQuota();

	/**
	 * set the users' quota
	 *
	 * @param string $quota
	 * @return void
	 * @since 9.0.0
	 */
	public function setQuota($quota);

	/**
	 * Get the user's manager UIDs
	 *
	 * @since 27.0.0
	 * @return string[]
	 */
	public function getManagerUids(): array;

	/**
	 * Set the user's manager UIDs
	 *
	 * @param string[] $uids UIDs of all managers
	 * @return void
	 * @since 27.0.0
	 */
	public function setManagerUids(array $uids): void;
}
