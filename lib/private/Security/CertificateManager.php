<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security;

use OC\Files\View;
use OCP\ICertificate;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

/**
 * Manage trusted certificates for users
 */
class CertificateManager implements ICertificateManager {
	private ?string $bundlePath = null;

	public function __construct(
		protected View $view,
		protected IConfig $config,
		protected LoggerInterface $logger,
		protected ISecureRandom $random,
	) {
	}

	/**
	 * Returns all certificates trusted by the user
	 *
	 * @return \OCP\ICertificate[]
	 */
	public function listCertificates(): array {
		if (!$this->config->getSystemValueBool('installed', false)) {
			return [];
		}

		$path = $this->getPathToCertificates() . 'uploads/';
		if (!$this->view->is_dir($path)) {
			return [];
		}
		$result = [];
		$handle = $this->view->opendir($path);
		if (!is_resource($handle)) {
			return [];
		}
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				try {
					$content = $this->view->file_get_contents($path . $file);
					if ($content !== false) {
						$result[] = new Certificate($content, $file);
					} else {
						$this->logger->error("Failed to read certificate from $path");
					}
				} catch (\Exception $e) {
					$this->logger->error("Failed to read certificate from $path", ['exception' => $e]);
				}
			}
		}
		closedir($handle);
		return $result;
	}

	private function hasCertificates(): bool {
		if (!$this->config->getSystemValueBool('installed', false)) {
			return false;
		}

		$path = $this->getPathToCertificates() . 'uploads/';
		if (!$this->view->is_dir($path)) {
			return false;
		}
		$result = [];
		$handle = $this->view->opendir($path);
		if (!is_resource($handle)) {
			return false;
		}
		while (false !== ($file = readdir($handle))) {
			if ($file !== '.' && $file !== '..') {
				return true;
			}
		}
		closedir($handle);
		return false;
	}

	/**
	 * create the certificate bundle of all trusted certificated
	 */
	public function createCertificateBundle(): void {
		$path = $this->getPathToCertificates();
		$certs = $this->listCertificates();

		if (!$this->view->file_exists($path)) {
			$this->view->mkdir($path);
		}

		$defaultCertificates = file_get_contents($this->getDefaultCertificatesBundlePath());
		if (strlen($defaultCertificates) < 1024) { // sanity check to verify that we have some content for our bundle
			// log as exception so we have a stacktrace
			$e = new \Exception('Shipped ca-bundle is empty, refusing to create certificate bundle');
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return;
		}

		$certPath = $path . 'rootcerts.crt';
		$tmpPath = $certPath . '.tmp' . $this->random->generate(10, ISecureRandom::CHAR_DIGITS);
		$fhCerts = $this->view->fopen($tmpPath, 'w');

		if (!is_resource($fhCerts)) {
			throw new \RuntimeException('Unable to open file handler to create certificate bundle "' . $tmpPath . '".');
		}

		// Write user certificates
		foreach ($certs as $cert) {
			$file = $path . '/uploads/' . $cert->getName();
			$data = $this->view->file_get_contents($file);
			if (strpos($data, 'BEGIN CERTIFICATE')) {
				fwrite($fhCerts, $data);
				fwrite($fhCerts, "\r\n");
			}
		}

		// Append the default certificates
		fwrite($fhCerts, $defaultCertificates);

		// Append the system certificate bundle
		$systemBundle = $this->getCertificateBundle();
		if ($systemBundle !== $certPath && $this->view->file_exists($systemBundle)) {
			$systemCertificates = $this->view->file_get_contents($systemBundle);
			fwrite($fhCerts, $systemCertificates);
		}

		fclose($fhCerts);

		$this->view->rename($tmpPath, $certPath);
	}

	/**
	 * Save the certificate and re-generate the certificate bundle
	 *
	 * @param string $certificate the certificate data
	 * @param string $name the filename for the certificate
	 * @throws \Exception If the certificate could not get added
	 */
	public function addCertificate(string $certificate, string $name): ICertificate {
		$path = $this->getPathToCertificates() . 'uploads/' . $name;
		$directory = dirname($path);

		$this->view->verifyPath($directory, basename($path));
		$this->bundlePath = null;

		if (!$this->view->file_exists($directory)) {
			$this->view->mkdir($directory);
		}

		try {
			$certificateObject = new Certificate($certificate, $name);
			$this->view->file_put_contents($path, $certificate);
			$this->createCertificateBundle();
			return $certificateObject;
		} catch (\Exception $e) {
			throw $e;
		}
	}

	/**
	 * Remove the certificate and re-generate the certificate bundle
	 */
	public function removeCertificate(string $name): bool {
		$path = $this->getPathToCertificates() . 'uploads/' . $name;

		try {
			$this->view->verifyPath(dirname($path), basename($path));
		} catch (\Exception) {
			return false;
		}

		$this->bundlePath = null;
		if ($this->view->file_exists($path)) {
			$this->view->unlink($path);
			$this->createCertificateBundle();
		}
		return true;
	}

	/**
	 * Get the path to the certificate bundle
	 */
	public function getCertificateBundle(): string {
		return $this->getPathToCertificates() . 'rootcerts.crt';
	}

	/**
	 * Get the full local path to the certificate bundle
	 * @throws \Exception when getting bundle path fails
	 */
	public function getAbsoluteBundlePath(): string {
		try {
			if ($this->bundlePath === null) {
				if (!$this->hasCertificates()) {
					$this->bundlePath = $this->getDefaultCertificatesBundlePath();
				} else {
					if ($this->needsRebundling()) {
						$this->createCertificateBundle();
					}

					$certificateBundle = $this->getCertificateBundle();
					$this->bundlePath = $this->view->getLocalFile($certificateBundle) ?: null;

					if ($this->bundlePath === null) {
						throw new \RuntimeException('Unable to get certificate bundle "' . $certificateBundle . '".');
					}
				}
			}
			return $this->bundlePath;
		} catch (\Exception $e) {
			$this->logger->error('Failed to get absolute bundle path. Fallback to default ca-bundle.crt', ['exception' => $e]);
			return $this->getDefaultCertificatesBundlePath();
		}
	}

	private function getPathToCertificates(): string {
		return '/files_external/';
	}

	/**
	 * Check if we need to re-bundle the certificates because one of the sources has updated
	 */
	private function needsRebundling(): bool {
		$targetBundle = $this->getCertificateBundle();
		if (!$this->view->file_exists($targetBundle)) {
			return true;
		}

		$sourceMTime = $this->getFilemtimeOfCaBundle();
		return $sourceMTime > $this->view->filemtime($targetBundle);
	}

	/**
	 * get mtime of ca-bundle shipped by Nextcloud
	 */
	protected function getFilemtimeOfCaBundle(): int {
		return filemtime($this->getDefaultCertificatesBundlePath());
	}

	public function getDefaultCertificatesBundlePath(): string {
		return $this->config->getSystemValueString('default_certificates_bundle_path', \OC::$SERVERROOT . '/resources/config/ca-bundle.crt');
	}
}
