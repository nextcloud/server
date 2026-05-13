<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Manage trusted certificates and the effective CA bundle used by Nextcloud.
 *
 * Implementations provide access to uploaded trusted certificates and the
 * generated bundle that is consumed by HTTP clients and external storage
 * integrations.
 *
 * @since 8.0.0
 */
interface ICertificateManager {
	/**
	 * Returns all uploaded trusted certificates.
	 *
	 * This does not include the shipped default CA bundle or any system CA bundle
	 * appended when building the effective bundle.
	 *
	 * @return \OCP\ICertificate[]
	 * @since 8.0.0
	 */
	public function listCertificates(): array;

	/**
	 * Add a trusted certificate to the certificate store.
	 *
	 * @param string $certificate The certificate data in PEM format
	 * @param string $name The filename for the certificate
	 * @return \OCP\ICertificate
	 * @throws \Exception If the certificate could not be added
	 * @since 8.0.0 - since 8.1.0 throws exception instead of returning false
	 */
	public function addCertificate(string $certificate, string $name): \OCP\ICertificate;

	/**
	 * Remove a trusted certificate from the certificate store.
	 *
	 * @param string $name The filename for the certificate
	 * @return bool
	 * @since 8.0.0
	 */
	public function removeCertificate(string $name): bool;

	/**
	 * Get the relative path to the generated certificate bundle.
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getCertificateBundle(): string;

	/**
	 * Get the full local path to the effective certificate bundle.
	 *
	 * Implementations should return the generated bundle path, but may log and fall back
	 * to the shipped default CA bundle if resolution fails.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getAbsoluteBundlePath(): string;

	/**
	 * Get the path of the shipped default certificates bundle.
	 *
	 * @since 33.0.0
	 */
	public function getDefaultCertificatesBundlePath(): string;
}
