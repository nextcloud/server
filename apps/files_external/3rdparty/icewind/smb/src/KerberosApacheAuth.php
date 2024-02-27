<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace Icewind\SMB;

use Icewind\SMB\Exception\DependencyException;
use Icewind\SMB\Exception\Exception;

/**
 * Use existing kerberos ticket to authenticate and reuse the apache ticket cache (mod_auth_kerb)
 */
class KerberosApacheAuth extends KerberosAuth implements IAuth {
	/** @var string */
	private $ticketPath = "";

	/** @var bool */
	private $init = false;

	/** @var string|false */
	private $ticketName;

	public function __construct() {
		$this->ticketName = getenv("KRB5CCNAME");
	}


	/**
	 * Copy the ticket to a temporary location and use that ticket for authentication
	 *
	 * @return void
	 */
	public function copyTicket(): void {
		if (!$this->checkTicket()) {
			return;
		}
		$krb5 = new \KRB5CCache();
		$krb5->open($this->ticketName);
		$tmpFilename = tempnam("/tmp", "krb5cc_php_");
		$tmpCacheFile = "FILE:" . $tmpFilename;
		$krb5->save($tmpCacheFile);
		$this->ticketPath = $tmpFilename;
		$this->ticketName = $tmpCacheFile;
	}

	/**
	 * Pass the ticket to smbclient by memory instead of path
	 *
	 * @return void
	 */
	public function passTicketFromMemory(): void {
		if (!$this->checkTicket()) {
			return;
		}
		$krb5 = new \KRB5CCache();
		$krb5->open($this->ticketName);
		$this->ticketName = (string)$krb5->getName();
	}

	/**
	 * Check if a valid kerberos ticket is present
	 *
	 * @return bool
	 * @psalm-assert-if-true string $this->ticketName
	 */
	public function checkTicket(): bool {
		//read apache kerberos ticket cache
		if (!$this->ticketName) {
			return false;
		}

		$krb5 = new \KRB5CCache();
		$krb5->open($this->ticketName);
		/** @psalm-suppress MixedArgument */
		return count($krb5->getEntries()) > 0;
	}

	private function init(): void {
		if ($this->init) {
			return;
		}
		$this->init = true;
		// inspired by https://git.typo3.org/TYPO3CMS/Extensions/fal_cifs.git

		if (!extension_loaded("krb5")) {
			// https://pecl.php.net/package/krb5
			throw new DependencyException('Ensure php-krb5 is installed.');
		}

		//read apache kerberos ticket cache
		if (!$this->checkTicket()) {
			throw new Exception('No kerberos ticket cache environment variable (KRB5CCNAME) found.');
		}

		// note that even if the ticketname is the value we got from `getenv("KRB5CCNAME")` we still need to set the env variable ourselves
		// this is because `getenv` also reads the variables passed from the SAPI (apache-php) and we need to set the variable in the OS's env
		putenv("KRB5CCNAME=" . $this->ticketName);
	}

	public function getExtraCommandLineArguments(): string {
		$this->init();
		return parent::getExtraCommandLineArguments();
	}

	public function setExtraSmbClientOptions($smbClientState): void {
		$this->init();
		try {
			parent::setExtraSmbClientOptions($smbClientState);
		} catch (Exception $e) {
			// suppress
		}
	}

	public function __destruct() {
		if (!empty($this->ticketPath) && file_exists($this->ticketPath) && is_file($this->ticketPath)) {
			unlink($this->ticketPath);
		}
	}
}
