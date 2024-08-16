<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\View;
use Test\Traits\EncryptionTrait;

/**
 * @group DB
 */
class EncryptedSizePropagationTest extends SizePropagationTest {
	use EncryptionTrait;

	protected function setupUser($name, $password = '') {
		$this->createUser($name, $password);
		$tmpFolder = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->registerMount($name, '\OC\Files\Storage\Local', '/' . $name, ['datadir' => $tmpFolder]);
		$this->config->setAppValue('encryption', 'useMasterKey', '0');
		$this->setupForUser($name, $password);
		$this->loginWithEncryption($name);
		return new View('/' . $name . '/files');
	}
}
