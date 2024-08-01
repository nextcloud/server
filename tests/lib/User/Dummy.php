<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

class Dummy extends Backend {
	protected function setUp(): void {
		parent::setUp();
		$this->backend = new \Test\Util\User\Dummy();
	}
}
