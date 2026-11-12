<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OC\Files\View;
use OCP\IAppConfig;
use OCP\ITempManager;
use OCP\Server;
use Test\Traits\EncryptionTrait;

/**
 * Runs the chunked upload assembly scenarios on encrypted storage with
 * per-user keys.
 *
 * Note: this class is named so that it sorts after the other encryption
 * request test classes. Master key setup only works for the first master key
 * class in a process, so new encryption classes have to run at the end -
 * per-user-keys tests are not sensitive to their position.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class EncryptionUserKeyChunkedUploadAssemblyTest extends ChunkedUploadAssemblyTest {
	use EncryptionTrait;

	protected function setupUser($name, $password): View {
		$this->createUser($name, $password);
		$tmpFolder = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($name, '\OC\Files\Storage\Local', '/' . $name, ['datadir' => $tmpFolder]);
		// we use per-user keys
		Server::get(IAppConfig::class)->setValueBool('encryption', 'useMasterKey', false);
		$this->setupForUser($name, $password);
		$this->loginWithEncryption($name);
		return new View('/' . $name . '/files');
	}
}
