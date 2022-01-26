<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use Exception;
use OC;
use OC\Files\Storage\Local;
use OC\Files\View;
use OCP\IConfig;
use OCP\ITempManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Exception
	 */
	protected function setupUser($name, $password): View {
		$this->createUser($name, $password);
		$tmpFolder = OC::$server->get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($name, Local::class, '/' . $name, ['datadir' => $tmpFolder]);
		// we use the master key
		OC::$server->get(IConfig::class)->setAppValue('encryption', 'useMasterKey', '1');
		$this->setupForUser($name, $password);
		$this->loginWithEncryption($name);
		return new View('/' . $name . '/files');
	}
}
