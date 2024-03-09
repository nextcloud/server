<?php
/**
 * @copyright Copyright (c) 2024 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Repair\NC29;

use OC\Files\View;
use OC\Repair\NC29\MoveCertificateBundles;
use OCP\IConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class FixMountStoragesTest
 *
 * @package Test\Repair\NC11
 * @group DB
 */
class MoveCertificateBundlesTest extends TestCase {
	private View|MockObject $view;
	private IConfig|MockObject $config;
	private IOutput $output;

	private MoveCertificateBundles $repair;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(IOutput::class);

		$this->view = $this->createMock(View::class);
		$this->config = $this->createMock(IConfig::class);
		$this->config->expects($this->once())->method('getSystemValue')->with('datadirectory', \OC::$SERVERROOT . '/data-autotest')->willReturn(\OC::$SERVERROOT . '/data-autotest');

		$this->repair = new MoveCertificateBundles(
			$this->view,
			$this->config
		);
	}

	public function testGetName() {
		$this->assertSame('Move the certificate bundles from data/files_external/ to data/certificate_manager/', $this->repair->getName());
	}

	public function testSkipRepairStep() {
		$this->view->expects($this->once())->method('file_exists')->with('/data-autotest/certificate_manager/rootcerts.crt')->willReturn(true);
		$this->view->expects($this->never())->method('copy');
		$this->repair->run($this->output);
	}
}
