<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
