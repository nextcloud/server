<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
