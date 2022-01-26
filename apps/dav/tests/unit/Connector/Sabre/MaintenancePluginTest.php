<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Valdnet <47037905+Valdnet@users.noreply.github.com>
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
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\ServiceUnavailable;
use Test\TestCase;

/**
 * Class MaintenancePluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class MaintenancePluginTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;
	/** @var IL10N|MockObject  */
	private $l10n;
	/** @var MaintenancePlugin */
	private $maintenancePlugin;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->maintenancePlugin = new MaintenancePlugin($this->config, $this->l10n);
	}


	public function testMaintenanceMode() {
		$this->expectException(ServiceUnavailable::class);
		$this->expectExceptionMessage('System is in maintenance mode.');

		$this->config
			->expects($this->exactly(1))
			->method('getSystemValueBool')
			->with('maintenance')
			->willReturn(true);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->maintenancePlugin->checkMaintenanceMode();
	}
}
