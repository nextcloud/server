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

	// only working with specific library (mod_auth_kerb, krb5, smbclient) versions
	/** @var bool */
	private $saveTicketInMemory = false;

	/** @var bool */
	private $init = false;

	/**
	 * @param bool $saveTicketInMemory
	 */
	public function __construct(bool $saveTicketInMemory = false) {
		$this->saveTicketInMemory = $saveTicketInMemory;
	}

	/**
	 * Check if a valid kerberos ticket is present
	 *
	 * @return bool
	 */
	public function checkTicket(): bool {
		//read apache kerberos ticket cache
		$cacheFile = getenv("KRB5CCNAME");
		if (!$cacheFile) {
			return false;
		}

		$krb5 = new \KRB5CCache();
		$krb5->open($cacheFile);
		return (bool)$krb5->isValid();
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
		$cacheFile = getenv("KRB5CCNAME");
		if (!$cacheFile) {
			throw new Exception('No kerberos ticket cache environment variable (KRB5CCNAME) found.');
		}

		$krb5 = new \KRB5CCache();
		$krb5->open($cacheFile);
		if (!$krb5->isValid()) {
			throw new Exception('Kerberos ticket cache is not valid.');
		}


		if ($this->saveTicketInMemory) {
			putenv("KRB5CCNAME=" . (string)$krb5->getName());
		} else {
			//workaround: smbclient is not working with the original apache ticket cache.
			$tmpFilename = tempnam("/tmp", "krb5cc_php_");
			$tmpCacheFile = "FILE:" . $tmpFilename;
			$krb5->save($tmpCacheFile);
			$this->ticketPath = $tmpFilename;
			putenv("KRB5CCNAME=" . $tmpCacheFile);
		}
	}

	public function getExtraCommandLineArguments(): string {
		$this->init();
		return parent::getExtraCommandLineArguments();
	}

	public function setExtraSmbClientOptions($smbClientState): void {
		$this->init();
		parent::setExtraSmbClientOptions($smbClientState);
	}

	public function __destruct() {
		if (!empty($this->ticketPath) && file_exists($this->ticketPath) && is_file($this->ticketPath)) {
			unlink($this->ticketPath);
		}
	}
}
