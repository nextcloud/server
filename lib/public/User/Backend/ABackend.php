<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Backend;

use OC\User\Backend;
use OCP\IUserBackend;
use OCP\UserInterface;

/**
 * @since 14.0.0
 */
abstract class ABackend implements IUserBackend, UserInterface {
	/**
	 * @deprecated 14.0.0
	 * @since 14.0.0
	 *
	 * @param int $actions The action to check for
	 * @return bool
	 */
	public function implementsActions($actions): bool {
		$implements = 0;

		if ($this instanceof ICreateUserBackend) {
			$implements |= Backend::CREATE_USER;
		}
		if ($this instanceof ISetPasswordBackend) {
			$implements |= Backend::SET_PASSWORD;
		}
		if ($this instanceof ICheckPasswordBackend) {
			$implements |= Backend::CHECK_PASSWORD;
		}
		if ($this instanceof IGetHomeBackend) {
			$implements |= Backend::GET_HOME;
		}
		if ($this instanceof IGetDisplayNameBackend) {
			$implements |= Backend::GET_DISPLAYNAME;
		}
		if ($this instanceof ISetDisplayNameBackend) {
			$implements |= Backend::SET_DISPLAYNAME;
		}
		if ($this instanceof IProvideAvatarBackend) {
			$implements |= Backend::PROVIDE_AVATAR;
		}
		if ($this instanceof ICountUsersBackend) {
			$implements |= Backend::COUNT_USERS;
		}

		return (bool)($actions & $implements);
	}
}
