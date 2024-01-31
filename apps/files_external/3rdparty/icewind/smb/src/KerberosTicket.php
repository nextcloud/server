<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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

use Icewind\SMB\Exception\InvalidTicket;
use KRB5CCache;

class KerberosTicket {
	/** @var KRB5CCache */
	private $krb5;
	/** @var string */
	private $cacheName;

	public function __construct(KRB5CCache $krb5, string $cacheName) {
		$this->krb5 = $krb5;
		$this->cacheName = $cacheName;
	}

	public function getCacheName(): string {
		return $this->cacheName;
	}

	public function getName(): string{
		return $this->krb5->getName();
	}

	public function isValid(): bool {
		return count($this->krb5->getEntries()) > 0;
	}

	public function validate(): void {
		if (!$this->isValid()) {
			throw new InvalidTicket("No kerberos ticket found.");
		}
	}

	/**
	 * Load the ticket from the cache specified by the KRB5CCNAME variable.
	 *
	 * @return KerberosTicket|null
	 */
	public static function fromEnv(): ?KerberosTicket {
		$ticketName = getenv("KRB5CCNAME");
		if (!$ticketName) {
			return null;
		}
		$krb5 = new KRB5CCache();
		$krb5->open($ticketName);
		return new KerberosTicket($krb5, $ticketName);
	}

	public static function load(string $ticket): KerberosTicket {
		$tmpFilename = tempnam(sys_get_temp_dir(), "krb5cc_php_");
		file_put_contents($tmpFilename, $ticket);
		register_shutdown_function(function () use ($tmpFilename) {
			if (file_exists($tmpFilename)) {
				unlink($tmpFilename);
			}
		});

		$ticketName = "FILE:" . $tmpFilename;
		$krb5 = new KRB5CCache();
		$krb5->open($ticketName);
		return new KerberosTicket($krb5, $ticketName);
	}

	public function save(): string {
		if (substr($this->cacheName, 0, 5) === 'FILE:') {
			$ticket = file_get_contents(substr($this->cacheName, 5));
		} else {
			$tmpFilename = tempnam(sys_get_temp_dir(), "krb5cc_php_");
			$tmpCacheFile = "FILE:" . $tmpFilename;
			$this->krb5->save($tmpCacheFile);
			$ticket = file_get_contents($tmpFilename);
			unlink($tmpFilename);
		}
		return $ticket;
	}
}