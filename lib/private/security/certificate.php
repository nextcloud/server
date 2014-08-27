<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Security;

use OCP\ICertificate;

class Certificate implements ICertificate {
	protected $name;

	protected $commonName;

	protected $organization;

	protected $serial;

	protected $issueDate;

	protected $expireDate;

	protected $issuerName;

	protected $issuerOrganization;

	/**
	 * @param string $data base64 encoded certificate
	 * @param string $name
	 * @throws \Exception If the certificate could not get parsed
	 */
	public function __construct($data, $name) {
		$this->name = $name;
		try {
			$info = openssl_x509_parse($data);
			$this->commonName = isset($info['subject']['CN']) ? $info['subject']['CN'] : null;
			$this->organization = isset($info['subject']['O']) ? $info['subject']['O'] : null;
			$this->serial = $this->formatSerial($info['serialNumber']);
			$this->issueDate = new \DateTime('@' . $info['validFrom_time_t']);
			$this->expireDate = new \DateTime('@' . $info['validTo_time_t']);
			$this->issuerName = isset($info['issuer']['CN']) ? $info['issuer']['CN'] : null;
			$this->issuerOrganization = isset($info['issuer']['O']) ? $info['issuer']['O'] : null;
		} catch (\Exception $e) {
			throw new \Exception('Certificate could not get parsed.');
		}
	}

	/**
	 * Format the numeric serial into AA:BB:CC hex format
	 *
	 * @param int $serial
	 * @return string
	 */
	protected function formatSerial($serial) {
		$hex = strtoupper(dechex($serial));
		return trim(chunk_split($hex, 2, ':'), ':');
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getCommonName() {
		return $this->commonName;
	}

	/**
	 * @return string
	 */
	public function getOrganization() {
		return $this->organization;
	}

	/**
	 * @return string
	 */
	public function getSerial() {
		return $this->serial;
	}

	/**
	 * @return \DateTime
	 */
	public function getIssueDate() {
		return $this->issueDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getExpireDate() {
		return $this->expireDate;
	}

	/**
	 * @return bool
	 */
	public function isExpired() {
		$now = new \DateTime();
		return $this->issueDate > $now or $now > $this->expireDate;
	}

	/**
	 * @return string|null
	 */
	public function getIssuerName() {
		return $this->issuerName;
	}

	/**
	 * @return string|null
	 */
	public function getIssuerOrganization() {
		return $this->issuerOrganization;
	}
}
