<?php
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

class AvatarUserDummy extends \Test\Util\User\Dummy {
	public function canChangeAvatar($uid) {
		return true;
	}
}
