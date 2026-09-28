<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OC\Files\View;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\Server;
use Test\Traits\EncryptionTrait;

/**
 * Class EncryptionUploadTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Connector\Sabre\RequestTest
 */
class EncryptionUploadTest extends UploadTest {
	use EncryptionTrait;

	protected function setupUser($name, $password): View {
		$this->createUser($name, $password);
		$tmpFolder = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($name, '\OC\Files\Storage\Local', '/' . $name, ['datadir' => $tmpFolder]);
		// we use per-user keys
		Server::get(IConfig::class)->setAppValue('encryption', 'useMasterKey', '0');
		$this->setupForUser($name, $password);
		$this->loginWithEncryption($name);
		return new View('/' . $name . '/files');
	}
}
