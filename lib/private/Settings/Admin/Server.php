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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Settings\IAdmin;

class Server implements IAdmin {

	/** @var IDBConnection|Connection */
	private $db;

	/** @var IConfig */
	private $config;

	public function __construct(IDBConnection $db, IConfig $config) {
		$this->db = $db;
		$this->config = $config;
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

		$parameters = [
			// Diagnosis
			'readOnlyConfigEnabled' => \OC_Helper::isReadOnlyConfigEnabled(),
			'isLocaleWorking' => \OC_Util::isSetLocaleWorking(),
			'isAnnotationsWorking' => \OC_Util::isAnnotationsWorking(),
			'checkForWorkingWellKnownSetup', $this->config->getSystemValue('check_for_working_wellknown_setup'),
			'has_fileinfo' => \OC_Util::fileInfoLoaded(),
			'invalidTransactionIsolationLevel' => $invalidTransactionIsolationLevel,

			// Background jobs
			'backgroundjobs_mode' => $this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax'),
			'cron_log'            => $this->config->getSystemValue('cron_log', true),
			'lastcron'            => $this->config->getAppValue('core', 'lastcron', false),

			// Mail
			'sendmail_is_available' => (bool) \OC_Helper::findBinaryPath('sendmail'),
			'mail_domain'           => $this->config->getSystemValue('mail_domain', ''),
			'mail_from_address'     => $this->config->getSystemValue('mail_from_address', ''),
			'mail_smtpmode'         => $this->config->getSystemValue('mail_smtpmode', ''),
			'mail_smtpsecure'       => $this->config->getSystemValue('mail_smtpsecure', ''),
			'mail_smtphost'         => $this->config->getSystemValue('mail_smtphost', ''),
			'mail_smtpport'         => $this->config->getSystemValue('mail_smtpport', ''),
			'mail_smtpauthtype'     => $this->config->getSystemValue('mail_smtpauthtype', ''),
			'mail_smtpauth'         => $this->config->getSystemValue('mail_smtpauth', false),
			'mail_smtpname'         => $this->config->getSystemValue('mail_smtpname', ''),
			'mail_smtppassword'     => $this->config->getSystemValue('mail_smtppassword', ''),
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
