<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Tests\Command;

use OCA\Files_External\Command\ListCommand;
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCA\Files_External\Lib\Auth\Password\Password;
use OCA\Files_External\Lib\Auth\Password\SessionCredentials;
use OCA\Files_External\Lib\Backend\Local;
use OCA\Files_External\Lib\StorageConfig;
use Symfony\Component\Console\Output\BufferedOutput;

class ListCommandTest extends CommandTest {
	/**
	 * @return \OCA\Files_External\Command\ListCommand|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getInstance() {
		/** @var \OCA\Files_External\Service\GlobalStoragesService|\PHPUnit_Framework_MockObject_MockObject $globalService */
		$globalService = $this->getMock('\OCA\Files_External\Service\GlobalStoragesService', null, [], '', false);
		/** @var \OCA\Files_External\Service\UserStoragesService|\PHPUnit_Framework_MockObject_MockObject $userService */
		$userService = $this->getMock('\OCA\Files_External\Service\UserStoragesService', null, [], '', false);
		/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject $userManager */
		$userManager = $this->getMock('\OCP\IUserManager');
		/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject $userSession */
		$userSession = $this->getMock('\OCP\IUserSession');

		return new ListCommand($globalService, $userService, $userSession, $userManager);
	}

	public function testListAuthIdentifier() {
		$l10n = $this->getMock('\OC_L10N', null, [], '', false);
		$session = $this->getMock('\OCP\ISession');
		$crypto = $this->getMock('\OCP\Security\ICrypto');
		$instance = $this->getInstance();
		$mount1 = new StorageConfig();
		$mount1->setAuthMechanism(new Password($l10n));
		$mount1->setBackend(new Local($l10n, new NullMechanism($l10n)));
		$mount2 = new StorageConfig();
		$mount2->setAuthMechanism(new SessionCredentials($l10n, $session, $crypto));
		$mount2->setBackend(new Local($l10n, new NullMechanism($l10n)));
		$input = $this->getInput($instance, [], [
			'output' => 'json'
		]);
		$output = new BufferedOutput();

		$instance->listMounts('', [$mount1, $mount2], $input, $output);
		$output = json_decode($output->fetch(), true);

		$this->assertNotEquals($output[0]['authentication_type'], $output[1]['authentication_type']);
	}
}
