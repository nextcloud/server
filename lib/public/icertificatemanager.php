<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

/**
 * Manage trusted certificates for users
 */
interface ICertificateManager {
	/**
	 * Returns all certificates trusted by the user
	 *
	 * @return \OCP\ICertificate[]
	 */
	public function listCertificates();

	/**
	 * @param string $certificate the certificate data
	 * @param string $name the filename for the certificate
	 * @return bool | \OCP\ICertificate
	 */
	public function addCertificate($certificate, $name);

	/**
	 * @param string $name
	 */
	public function removeCertificate($name);

	/**
	 * Get the path to the certificate bundle for this user
	 *
	 * @return string
	 */
	public function getCertificateBundle();
}
