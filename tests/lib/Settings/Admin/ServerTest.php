<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Settings\Admin;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use OC\Settings\Admin\Server;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Lock\ILockingProvider;
use Test\TestCase;

class ServerTest extends TestCase {
	/** @var Server */
	private $admin;
	/** @var IDBConnection */
	private $dbConnection;
	/** @var IConfig */
	private $config;
	/** @var ILockingProvider */
	private $lockingProvider;
	/** @var IL10N */
	private $l10n;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
		$this->dbConnection = $this->getMockBuilder('\OCP\IDBConnection')->getMock();
		$this->lockingProvider = $this->getMockBuilder('\OCP\Lock\ILockingProvider')->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')->getMock();

		$this->admin = new Server(
			$this->dbConnection,
			$this->config,
			$this->lockingProvider,
			$this->l10n
		);
	}

	public function testGetForm() {
		$this->dbConnection
			->expects($this->once())
			->method('getDatabasePlatform')
			->willReturn(new SqlitePlatform());
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'backgroundjobs_mode', 'ajax')
			->willReturn('ajax');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('core', 'backgroundjobs_mode', 'ajax')
			->willReturn('ajax');
		$this->config
			->expects($this->at(4))
			->method('getAppValue')
			->with('core', 'lastcron', false)
			->willReturn(false);
		$this->config
			->expects($this->at(5))
			->method('getAppValue')
			->with('core', 'cronErrors')
			->willReturn('');
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('check_for_working_wellknown_setup', true)
			->willReturn(true);
		$this->config
			->expects($this->at(3))
			->method('getSystemValue')
			->with('cron_log', true)
			->willReturn(true);
		$this->l10n
			->expects($this->at(0))
			->method('t')
			->with('APCu')
			->willReturn('APCu');
		$this->l10n
			->expects($this->at(1))
			->method('t')
			->with('Redis')
			->willReturn('Redis');
		$outdatedCaches = [];
		$caches = [
			'apcu'	=> ['name' => 'APCu', 'version' => '4.0.6'],
			'redis'	=> ['name' => 'Redis', 'version' => '2.2.5'],
		];
		foreach ($caches as $php_module => $data) {
			$isOutdated = extension_loaded($php_module) && version_compare(phpversion($php_module), $data['version'], '<');
			if ($isOutdated) {
				$outdatedCaches[$php_module] = $data;
			}
		}
		$envPath = getenv('PATH');
		$expected = new TemplateResponse(
			'settings',
			'admin/server',
			[
				// Diagnosis
				'readOnlyConfigEnabled'            => \OC_Helper::isReadOnlyConfigEnabled(),
				'isLocaleWorking'                  => \OC_Util::isSetLocaleWorking(),
				'isAnnotationsWorking'             => \OC_Util::isAnnotationsWorking(),
				'checkForWorkingWellKnownSetup'    => true,
				'has_fileinfo'                     => \OC_Util::fileInfoLoaded(),
				'invalidTransactionIsolationLevel' => false,
				'getenvServerNotWorking'           => empty($envPath),
				'OutdatedCacheWarning'             => $outdatedCaches,
				'fileLockingType'                  => 'cache',
				'suggestedOverwriteCliUrl'         => '',

				// Background jobs
				'backgroundjobs_mode' => 'ajax',
				'cron_log'            => true,
				'lastcron'            => false,
				'cronErrors'		  => ''
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
