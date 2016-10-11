<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OC\Settings\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use OC\Lock\DBLockingProvider;
use OC\Lock\NoopLockingProvider;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Lock\ILockingProvider;
use OCP\Settings\ISettings;

class Server implements ISettings {
	/** @var IDBConnection|Connection */
	private $db;
	/** @var IConfig */
	private $config;
	/** @var ILockingProvider */
	private $lockingProvider;
	/** @var IL10N */
	private $l;

	/**
	 * @param IDBConnection $db
	 * @param IConfig $config
	 * @param ILockingProvider $lockingProvider
	 * @param IL10N $l
	 */
	public function __construct(IDBConnection $db,
								IConfig $config,
								ILockingProvider $lockingProvider,
								IL10N $l) {
		$this->db = $db;
		$this->config = $config;
		$this->lockingProvider = $lockingProvider;
		$this->l = $l;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		try {
			if ($this->db->getDatabasePlatform() instanceof SqlitePlatform) {
				$invalidTransactionIsolationLevel = false;
			} else {
				$invalidTransactionIsolationLevel = $this->db->getTransactionIsolation() !== Connection::TRANSACTION_READ_COMMITTED;
			}
		} catch (DBALException $e) {
			// ignore
			$invalidTransactionIsolationLevel = false;
		}

		$envPath = getenv('PATH');

		// warn if outdated version of a memcache module is used
		$caches = [
			'apcu'	=> ['name' => $this->l->t('APCu'), 'version' => '4.0.6'],
			'redis'	=> ['name' => $this->l->t('Redis'), 'version' => '2.2.5'],
		];
		$outdatedCaches = [];
		foreach ($caches as $php_module => $data) {
			$isOutdated = extension_loaded($php_module) && version_compare(phpversion($php_module), $data['version'], '<');
			if ($isOutdated) {
				$outdatedCaches[$php_module] = $data;
			}
		}

		if ($this->lockingProvider instanceof NoopLockingProvider) {
			$fileLockingType = 'none';
		} else if ($this->lockingProvider instanceof DBLockingProvider) {
			$fileLockingType = 'db';
		} else {
			$fileLockingType = 'cache';
		}

		// If the current web root is non-empty but the web root from the config is,
		// and system cron is used, the URL generator fails to build valid URLs.
		$shouldSuggestOverwriteCliUrl = $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax') === 'cron'
			&& \OC::$WEBROOT && \OC::$WEBROOT !== '/'
			&& !$this->config->getSystemValue('overwrite.cli.url', '');
		$suggestedOverwriteCliUrl = ($shouldSuggestOverwriteCliUrl) ? \OC::$WEBROOT : '';

		$parameters = [
			// Diagnosis
			'readOnlyConfigEnabled'            => \OC_Helper::isReadOnlyConfigEnabled(),
			'isLocaleWorking'                  => \OC_Util::isSetLocaleWorking(),
			'isAnnotationsWorking'             => \OC_Util::isAnnotationsWorking(),
			'checkForWorkingWellKnownSetup'    => $this->config->getSystemValue('check_for_working_wellknown_setup', true),
			'has_fileinfo'                     => \OC_Util::fileInfoLoaded(),
			'invalidTransactionIsolationLevel' => $invalidTransactionIsolationLevel,
			'getenvServerNotWorking'           => empty($envPath),
			'OutdatedCacheWarning'             => $outdatedCaches,
			'fileLockingType'                  => $fileLockingType,
			'suggestedOverwriteCliUrl'         => $suggestedOverwriteCliUrl,

			// Background jobs
			'backgroundjobs_mode' => $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax'),
			'cron_log'            => $this->config->getSystemValue('cron_log', true),
			'lastcron'            => $this->config->getAppValue('core', 'lastcron', false),
			'cronErrors'		  => $this->config->getAppValue('core', 'cronErrors'),
		];

		return new TemplateResponse('settings', 'admin/server', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'server';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 0;
	}
}
