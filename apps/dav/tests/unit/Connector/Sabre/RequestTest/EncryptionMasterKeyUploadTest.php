<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OC\Files\View;
use Test\Traits\EncryptionTrait;

/**
 * Class EncryptionMasterKeyUploadTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Connector\Sabre\RequestTest
 */
class EncryptionMasterKeyUploadTest extends UploadTest {
	use EncryptionTrait;

	protected function setupUser($name, $password) {
		$this->createUser($name, $password);
		$tmpFolder = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->registerMount($name, '\OC\Files\Storage\Local', '/' . $name, ['datadir' => $tmpFolder]);
		// we use the master key
		\OC::$server->getConfig()->setAppValue('encryption', 'useMasterKey', '1');
		$this->setupForUser($name, $password);
		$this->loginWithEncryption($name);
		return new View('/' . $name . '/files');
	}
}
