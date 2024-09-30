<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OC\Security;

use OCP\ICertificate;

class Certificate implements ICertificate {
	protected string $name;

	protected ?string $commonName;

	protected ?string $organization;


	protected \DateTime $issueDate;

	protected \DateTime $expireDate;

	protected ?string $issuerName;

	protected ?string $issuerOrganization;

	/**
	 * @param string $data base64 encoded certificate
	 * @throws \Exception If the certificate could not get parsed
	 */
	public function __construct(string $data, string $name) {
		$this->name = $name;
		$gmt = new \DateTimeZone('GMT');

		// If string starts with "file://" ignore the certificate
		$query = 'file://';
		if (strtolower(substr($data, 0, strlen($query))) === $query) {
			throw new \Exception('Certificate could not get parsed.');
		}

		$info = openssl_x509_parse($data);
		if (!is_array($info)) {
			// There is a non-standardized certificate format only used by OpenSSL. Replace all
			// separators and try again.
			$data = str_replace(
				['-----BEGIN TRUSTED CERTIFICATE-----', '-----END TRUSTED CERTIFICATE-----'],
				['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'],
				$data,
			);
			$info = openssl_x509_parse($data);
		}
		if (!is_array($info)) {
			throw new \Exception('Certificate could not get parsed.');
		}

		$this->commonName = $info['subject']['CN'] ?? null;
		$this->organization = $info['subject']['O'] ?? null;
		$this->issueDate = new \DateTime('@' . $info['validFrom_time_t'], $gmt);
		$this->expireDate = new \DateTime('@' . $info['validTo_time_t'], $gmt);
		$this->issuerName = $info['issuer']['CN'] ?? null;
		$this->issuerOrganization = $info['issuer']['O'] ?? null;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getCommonName(): ?string {
		return $this->commonName;
	}

	public function getOrganization(): ?string {
		return $this->organization;
	}

	public function getIssueDate(): \DateTime {
		return $this->issueDate;
	}

	public function getExpireDate(): \DateTime {
		return $this->expireDate;
	}

	public function isExpired(): bool {
		$now = new \DateTime();
		return $this->issueDate > $now or $now > $this->expireDate;
	}

	public function getIssuerName(): ?string {
		return $this->issuerName;
	}

	public function getIssuerOrganization(): ?string {
		return $this->issuerOrganization;
	}
}
