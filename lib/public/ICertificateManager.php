<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCP;

/**
 * Manage trusted certificates
 * @since 8.0.0
 */
interface ICertificateManager {
	/**
	 * Returns all certificates trusted by the system
	 *
	 * @return \OCP\ICertificate[]
	 * @since 8.0.0
	 */
	public function listCertificates(): array;

	/**
	 * @param string $certificate the certificate data
	 * @param string $name the filename for the certificate
	 * @return \OCP\ICertificate
	 * @throws \Exception If the certificate could not get added
	 * @since 8.0.0 - since 8.1.0 throws exception instead of returning false
	 */
	public function addCertificate(string $certificate, string $name): \OCP\ICertificate;

	/**
	 * @param string $name
	 * @return bool
	 * @since 8.0.0
	 */
	public function removeCertificate(string $name): bool;

	/**
	 * Get the path to the certificate bundle
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getCertificateBundle(): string;

	/**
	 * Get the full local path to the certificate bundle
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getAbsoluteBundlePath(): string;
}
