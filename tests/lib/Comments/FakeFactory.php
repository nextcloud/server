<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\Comments;

use OCP\IServerContainer;

/**
 * Class FakeFactory
 */
class FakeFactory implements \OCP\Comments\ICommentsManagerFactory {
	public function __construct(IServerContainer $serverContainer) {
	}

	public function getManager() {
		return new FakeManager();
	}
}
