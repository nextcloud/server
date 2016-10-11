<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\UpdateNotification\Tests;

use OC\Updater;
use OCA\UpdateNotification\UpdateChecker;
use Test\TestCase;

class UpdateCheckerTest extends TestCase {
	/** @var Updater */
	private $updater;
	/** @var UpdateChecker */
	private $updateChecker;

	public function setUp() {
		parent::setUp();

		$this->updater = $this->getMockBuilder('\OC\Updater\VersionCheck')
			->disableOriginalConstructor()->getMock();
		$this->updateChecker = new UpdateChecker($this->updater);
	}

	public function testGetUpdateStateWithUpdateAndInvalidLink() {
		$this->updater
			->expects($this->once())
			->method('check')
			->willReturn([
				'version' => 123,
				'versionstring' => 'Nextcloud 123',
				'web'=> 'javascript:alert(1)',
				'url'=> 'javascript:alert(2)',
				'autoupdater'=> '0',
			]);

		$expected = [
			'updateAvailable' => true,
			'updateVersion' => 'Nextcloud 123',
			'updaterEnabled' => false,
		];
		$this->assertSame($expected, $this->updateChecker->getUpdateState());
	}

	public function testGetUpdateStateWithUpdateAndValidLink() {
		$this->updater
			->expects($this->once())
			->method('check')
			->willReturn([
				'version' => 123,
				'versionstring' => 'Nextcloud 123',
				'web'=> 'https://docs.nextcloud.com/myUrl',
				'url'=> 'https://downloads.nextcloud.org/server',
				'autoupdater'=> '1',
			]);

		$expected = [
			'updateAvailable' => true,
			'updateVersion' => 'Nextcloud 123',
			'updaterEnabled' => true,
			'updateLink' => 'https://docs.nextcloud.com/myUrl',
			'downloadLink' => 'https://downloads.nextcloud.org/server',
		];
		$this->assertSame($expected, $this->updateChecker->getUpdateState());
	}

	public function testGetUpdateStateWithoutUpdate() {
		$this->updater
			->expects($this->once())
			->method('check')
			->willReturn([]);

		$expected = [];
		$this->assertSame($expected, $this->updateChecker->getUpdateState());
	}
}
