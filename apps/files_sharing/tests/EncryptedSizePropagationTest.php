<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\View;
use OCP\ITempManager;
use OCP\Server;
use Test\Traits\EncryptionTrait;

/**
 * @group DB
 */
class EncryptedSizePropagationTest extends SizePropagationTest {
	use EncryptionTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->config->setAppValue('encryption', 'useMasterKey', '0');
	}

	protected function setupUser($name, $password = '') {
		$this->createUser($name, $password);
		$tmpFolder = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($name, '\OC\Files\Storage\Local', '/' . $name, ['datadir' => $tmpFolder]);
		$this->setupForUser($name, $password);
		$this->loginWithEncryption($name);
		return new View('/' . $name . '/files');
	}

	protected function loginHelper($user, $create = false, $password = false) {
		$this->setupForUser($user, $password);
		parent::loginHelper($user, $create, $password);
	}
}
