<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\IntegrityCheck;

use OC\Core\Command\Maintenance\Mimetype\GenerateMimetypeFileBuilder;
use OC\IntegrityCheck\Exceptions\InvalidSignatureException;
use OC\IntegrityCheck\Helpers\EnvironmentHelper;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OC\IntegrityCheck\Iterator\ExcludeFileByNameFilterIterator;
use OC\IntegrityCheck\Iterator\ExcludeFoldersByPathFilterIterator;
use OCP\App\IAppManager;
use OCP\Files\IMimeTypeDetector;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ServerVersion;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;

/**
 * Performs creation and verification of code integrity signatures (signature.json) for 
 * Nextcloud core and applications.
 *
 *  - computing SHA‑512 hashes of installed files
 *  - signing/verifying the hash list with X.509/RSA‑PSS certificates
 *  - validating the certificate chain against the shipped root CA
 *  - reporting missing/extra/modified files (with repository-specific exclusions 
 *    and cached results).
 *
 * Nextcloud is shipped with a root CA certificate (resources/codesigning/root.crt).
 * That root certificate is used to validate leaf certificates that in turn sign
 * application or core signature.json files. The certificate's Common Name (CN)
 * is used to scope a certificate to a specific application (app id) or to 'core'.
 * A certificate with CN 'core' is always trusted for core verification.
 *
 */
class Checker {
	public const CACHE_KEY = 'oc.integritycheck.checker';

	private ICache $cache;

	public function __construct(
		private ServerVersion $serverVersion,
		private EnvironmentHelper $environmentHelper,
		private FileAccessHelper $fileAccessHelper,
		private ?IConfig $config,
		private ?IAppConfig $appConfig,
		ICacheFactory $cacheFactory,
		private IAppManager $appManager,
		private IMimeTypeDetector $mimeTypeDetector,
	) {
		$this->cache = $cacheFactory->createDistributed(self::CACHE_KEY);
	}

	/**
	 * Whether code signing is enforced or not.
	 */
	public function isCodeCheckEnforced(): bool {
		$notSignedChannels = [ '', 'git'];
		if (\in_array($this->serverVersion->getChannel(), $notSignedChannels, true)) {
			return false;
		}

		/**
		 * This config option is undocumented and supposed to be so, it's only
		 * applicable for very specific scenarios and we should not advertise it
		 * too prominent. So please do not add it to config.sample.php.
		 */
		return !($this->config?->getSystemValueBool('integrity.check.disabled', false) ?? false);
	}

	/**
	 * Enumerates all files belonging to the folder. Sensible defaults are excluded.
	 *
	 * @param string $folderToIterate
	 * @param string $root
	 * @return \RecursiveIteratorIterator
	 * @throws \Exception
	 */
	private function getFolderIterator(string $folderToIterate, string $root = ''): \RecursiveIteratorIterator {
		$dirItr = new \RecursiveDirectoryIterator(
			$folderToIterate,
			\RecursiveDirectoryIterator::SKIP_DOTS
		);
		if ($root === '') {
			$root = \OC::$SERVERROOT;
		}
		$root = rtrim($root, '/');

		$excludeGenericFilesIterator = new ExcludeFileByNameFilterIterator($dirItr);
		$excludeFoldersIterator = new ExcludeFoldersByPathFilterIterator($excludeGenericFilesIterator, $root);

		return new \RecursiveIteratorIterator(
			$excludeFoldersIterator,
			\RecursiveIteratorIterator::SELF_FIRST
		);
	}

	/**
	* Generate SHA-512 hases for all files found by the iterator and return
	* as a list of ['file' => relativePath, 'hash' => sha512] entries for all
	* files.
	*
	* This avoids using filenames as PHP array keys while collecting hashes,
	* which prevents PHP from coercing numeric-looking keys (e.g. "01" -> 1)
	* and silently collapsing distinct filenames.
	*
	* @param \RecursiveIteratorIterator $folderFilesIterator Iterator over 
	*	files in the folder (must iterate files under $basePath)
	* @param string $basePath Absolute filesystem path used as the base/root.
	* 	This prefix is stripped from each iterated filename to produce the
	*	relative keys in the returned array; it must match the root used to
	*	build the iterator (e.g. '/var/www/nextcloud' or an app folder like
	*	'/var/www/nextcloud/apps/calendar'). Trailing slash is ignored.
	* @return array<int,array{file:string,hash:string}>
	*/
	private function generateHashes(\RecursiveIteratorIterator $folderFilesIterator, string $basePath): array {
		$entries = [];
		$basePathLength = \strlen($basePath);

		/** @var \RecursiveIteratorIterator<string,\DirectoryIterator> $folderFilesIterator */ 
		foreach ($folderFilesIterator as $absoluteFilePath => $dirEntry) {
			/** @var \DirectoryIterator $dirEntry */
			if ($dirEntry->isDir()) {
				continue;
			}

			$relativeFilePath = ltrim(substr($absoluteFilePath, $basePathLength), '/');

			// Exclude app/core signature files from the hashes
			if ($relativeFilePath === 'appinfo/signature.json' || $relativeFilePath === 'core/signature.json') {
				continue;
			}

			// Special-case: ignore installation-specific content (that can be appended
 			// to the root .htaccess) and only hash the stable content above the marker.
			if ($absoluteFilePath === $this->environmentHelper->getServerRoot() . '/.htaccess') {
				$fileContent = $this->fileAccessHelper->file_get_contents($absoluteFilePath);
				if (!is_string($fileContent)) {
					throw new \RuntimeException('Failed to read .htaccess at ' . $absoluteFilePath);
				}

				$marker = '#### DO NOT CHANGE ANYTHING ABOVE THIS LINE ####';
				$markerPos = strpos($fileContent, $marker);

				// If the marker is present, ignore anything appended.
				if ($markerPos !== false) {
					// only hash the content above the marker
					$contentAboveMarker = substr($fileContent, 0, $markerPos);
					$entries[] = [
						'file' => $relativeFilePath,
						'hash' => hash('sha512', $contentAboveMarker),
					];
					continue;
				}
				// If there's no marker, fall through and let the normal file hashing proceed.
			}

			// Special-case: ignore local alias additions (that lead to non-default on disk
			// core/js/mimetypelist.js) and only hash a stable (canonical/default-generated) version.
			if ($absoluteFilePath === $this->environmentHelper->getServerRoot() . '/core/js/mimetypelist.js') {
				// While local aliases are supported, core/js/mimetypelist.js must always be generated, so 
				// direct modifications to it are _not_ supported. We detect direct modifications by comparing
				// what a generated version should look like to what's on disk.
				$mimetypeFileBuilder = new GenerateMimetypeFileBuilder();
				$generatedWithAllAliases = $mimetypeFileBuilder->generateFile(
					$this->mimeTypeDetector->getAllAliases(),
					$this->mimeTypeDetector->getAllNamings()
				);
				$onDiskContent = $this->fileAccessHelper->file_get_contents($absoluteFilePath);
				if (!is_string($onDiskContent)) {
					throw new \RuntimeException('Failed to read mimetypelist.js at ' . $absoluteFilePath);
				}

				// If what's on disk matches, no unsupported direct modifications are present.
				if ($generatedWithAllAliases === $onDiskContent) {
					// only hash a canonical version w/o any local aliases
					$generatedWithDefaultAliases = $mimetypeFileBuilder->generateFile(
						$this->mimeTypeDetector->getOnlyDefaultAliases(),
						$this->mimeTypeDetector->getAllNamings()
					);
					$entries[] = [
						'file' => $relativeFilePath,
						'hash' => hash('sha512', $generatedWithDefaultAliases),
					];
					continue;
				}
				// If what's on disk does not match expectations, fall through and let the normal file hashing proceed.
			}

			// Default (any files without special exclusions/handling above)
			$hashResult = hash_file('sha512', $absoluteFilePath);
			if ($hashResult === false) {
				throw new \RuntimeException('Failed to hash file: ' . $absoluteFilePath);
			}

			$entries[] = [
				'file' => $relativeFilePath,
				'hash' => $hashResult,
			];
		}

		return $entries;
	}

	/**
	 * Creates the signature data per the schema.
	 *
	 * Returned structure:
 	 * [
	 *   'format_version' => 2,
	 *   'hashes' => [ ['file'=>'...','hash'=>'...'], ... ],
	 *   'signature' => 'BASE64...',
	 *   'certificate' => '-----BEGIN CERTIFICATE-----...'
	 * ]
	 *
	 * @param array<int,array{file:string,hash:string}> $entries
	 * @param X509 $certificate
	 * @param RSA $privateKey
	 * @return array
	 */
	private function createSignatureData(
		array $entries,
		X509 $certificate,
		RSA $privateKey
	): array {
		// Build a map to ensure unique filenames (last-wins if duplicate entries present)
		$map = [];
		foreach ($entries as $entry) {
			// The cast to (string) ensures all keys are treated as strings
			$map[(string)$entry['file']] = (string)$entry['hash'];
		}

		$files = array_keys($map);
		sort($files, SORT_STRING); // Explicit string sorting

		$sortedEntries = [];
		foreach ($files as $file) {
			// $file is guaranteed to be a string key found directly in $map
			$sortedEntries[] = ['file' => $file, 'hash' => $map[$file]];
		}

		// Sign the canonical array of entries (json-encoded)
		$payloadToSign = json_encode($sortedEntries);
		if ($payloadToSign === false) {
			throw new \RuntimeException('Failed to JSON-encode hash list for signing.');
		}

		$privateKey->setSignatureMode(RSA::SIGNATURE_PSS);
		$privateKey->setMGFHash('sha512');
		$privateKey->setSaltLength(0); // See https://tools.ietf.org/html/rfc3447#page-38

		$signature = $privateKey->sign($payloadToSign);

		return [
			'format_version' => 2,
			'hashes' => $sortedEntries,
			'signature' => base64_encode($signature),
			'certificate' => $certificate->saveX509($certificate->currentCert),
		];
	}

	/**
	 * Write the signature of the app in the specified folder
	 *
	 * @param string $path
	 * @param X509 $certificate
	 * @param RSA $privateKey
	 * @throws \Exception if signature file is not writable
	 */
	public function writeAppSignature(
		string $path,
		X509 $certificate,
		RSA $privateKey
	): void {
		$appInfoDir = $path . '/appinfo';
		$appSigPath = $appInfoDir . '/signature.json';

		try {
			$this->fileAccessHelper->assertDirectoryExists($appInfoDir);

			$iterator = $this->getFolderIterator($path);
			$entries = $this->generateHashes($iterator, $path);
			$signatureData = $this->createSignatureData($entries, $certificate, $privateKey);

			$this->fileAccessHelper->file_put_contents(
				$appSigPath,
				json_encode($signatureData, JSON_PRETTY_PRINT)
			);
		} catch (\Exception $e) {
			if (!$this->fileAccessHelper->is_writable($appInfoDir)) {
				throw new \Exception($appInfoDir . ' is not writable');
			}
			throw $e;
		}
	}

	/**
	 * Write the signature of core
	 *
	 * @param string $path
	 * @param X509 $certificate
	 * @param RSA $privateKey
	 * @throws \Exception if signature file is not writable
	 */
	public function writeCoreSignature(
		string $path,
		X509 $certificate,
		RSA $privateKey
	): void {
		$coreDir = $path . '/core';
		$coreSigPath = $coreDir . '/signature.json';

		try {
			$this->fileAccessHelper->assertDirectoryExists($coreDir);

			$iterator = $this->getFolderIterator($path, $path);
			$entries = $this->generateHashes($iterator, $path);
			$signatureData = $this->createSignatureData($entries, $certificate, $privateKey);

			$this->fileAccessHelper->file_put_contents(
				$coreSigPath,
				json_encode($signatureData, JSON_PRETTY_PRINT)
			);
		} catch (\Exception $e) {
			if (!$this->fileAccessHelper->is_writable($coreDir)) {
				throw new \Exception($coreDir . ' is not writable');
			}
			throw $e;
		}
	}

	/**
	 * Split the certificate file in individual certs
	 *
	 * @param string $cert
	 * @return string[]
	 */
	private function splitCerts(string $cert): array {
		preg_match_all('([\-]{3,}[\S\ ]+?[\-]{3,}[\S\s]+?[\-]{3,}[\S\ ]+?[\-]{3,})', $cert, $matches);

		return $matches[0];
	}

	/**
	 * Verify the signature for the specified path.
	 *
	 * @param string $signaturePath Path to signature.json
	 * @param string $basePath Filesystem root to verify (app folder or server root)
	 * @param string $expectedCn Expected Common Name (CN) of the signing certificate (app id or 'core')
	 * @param bool $force When true, forces verification even if configured to skip enforcement (defaults to false)
	 * @return array Array of differences organized by category (EXTRA_FILE, FILE_MISSING, INVALID_HASH)
	 * @throws InvalidSignatureException on signature/certificate validation failures
	 * @throws \RuntimeException on IO/encoding failures
	 */
	private function verify(
		string $signaturePath,
		string $basePath,
		string $expectedCn,
		bool $force = false
	): array {
		// Skip verification if not forced and code checks are either unsupported in the active release channel or disabled within the config.
		if (!$force && !$this->isCodeCheckEnforced()) {
			return [];
		}

		/**
		 * 1. Load the signature.json data:
		 * 	- Load the raw signature.json file from disk.
		 *	- Decode it and perform basic validation of the contents of the signature.json file.
		 */
		
		// Load the raw signature.json file
		$signatureContent = $this->fileAccessHelper->file_get_contents($signaturePath);
		if ($signatureContent === false || !\is_string($signatureContent)) {
			throw new \RuntimeException('Could not read signature.json at ' . $signaturePath);
		}

		/** @var array{format_version?:int,hashes:array<int,array{file:string,hash:string}>|array<string,string>,signature:string,certificate:string} $signatureData */
		$signatureData = json_decode($signatureContent, true);
		if (!\is_array($signatureData) || !isset($signatureData['hashes'], $signatureData['signature'], $signatureData['certificate'])) {
			throw new InvalidSignatureException('Signature data is malformed.');
		}

		/** 
		 * @var array<int,array{file:string,hash:string}>|array<string,string> $hashesRaw
		 *
		 * - array<int,array{file:string,hash:string}> : New format (format_version = 2).
		 *     Example: [
		 *       ['file' => 'lib/Some.php', 'hash' => '0123...'],
		 *       ['file' => 'index.php',    'hash' => 'abcd...'],
		 *     ]
		 *
		 * - array<string,string> : Legacy format (filename => hash).
		 *     Example: [
		 *       'lib/Some.php' => '0123...',
		 *       'index.php'    => 'abcd...',
		 *     ]
		 *
		 * $hashesRaw is either the canonical list-of-entries (signed for format_version=2) or the legacy 
		 * associative map (kept for backwards compatibility).
		 */
		$hashesRaw = $signatureData['hashes'];
		$signatureB64 = $signatureData['signature'];
		$certificatePem = $signatureData['certificate'];
		
		/**
		 * 2. Establish a CA store from the shipped root bundle:
		 * 	- Load the raw PEM shipped root CA bundle from disk.
		 *	- Split the raw PEM shipped root CA bundle into individual PEM-encoded certificate
		 *	  blocks.
		 *	- Parse and load each certificate block and designate each as a trusted CA (for
		 *	  subsequent certificate validations).
		 */
		$rootBundlePath = $this->environmentHelper->getServerRoot() . '/resources/codesigning/root.crt';
		$rootBundlePem = $this->fileAccessHelper->file_get_contents($rootBundlePath);
		if ($rootBundlePem === false) {
			throw new \RuntimeException('Could not read root CA bundle at ' . $rootBundlePath);
		}
		$rootCaCerts = $this->splitCerts($rootBundlePem);
		if (empty($rootCaCerts)) {
			throw new \RuntimeException('Root CA bundle contains no certificates.');
		}

		$caStoreX509 = new X509();
		foreach ($rootCaCerts as $rootPem) {
			if ($caStoreX509->loadCA($rootPem) === false) {
				throw new \RuntimeException('Failed to parse a certificate from the root CA bundle.');
			}
		}

		/**
		 * 3. Load and validate signing certificate (from signature.json)
		 *	- Parse and load the signing certificate PEM into the class instance.
		 *	- Validate the provided signing cert against the CA chain.
		 */
		if ($caStoreX509->loadX509($certificatePem) === false) {
			throw new InvalidSignatureException('Failed to parse signing certificate.');
		}
		if (!$caStoreX509->validateSignature()) {
			throw new InvalidSignatureException('Signing certificate did not validate against the trusted CA bundle.');
		}

		/**
		 * 4. Check certificate CN (scope).
		 *	- Extract CN
		 *	- If certificate CN is "core", it is considered valid for any scope (i.e. not just 
		 *	  the "core" tree and can legitimately sign core + shipped apps).
		 */
		$signerDn = $caStoreX509->getDN(X509::DN_OPENSSL);
		$signerCn = (is_array($signerDn) && isset($signerDn['CN'])) ? $signerDn['CN'] : null;
		if ($signerCn === null) {
			throw new InvalidSignatureException('Signing certificate contains no Common Name (CN).');
		}
		if ($signerCn !== $expectedCn && $signerCn !== 'core') {
			// getDN(true) returns a string representation; use it for the error message (if available)
			$signerDnString = $caStoreX509->getDN(true) ?: '';
			throw new InvalidSignatureException(
				sprintf('Certificate is not valid for required scope. (Requested: %s, current: CN=%s, DN=%s)',
						$expectedCn, $signerCn, $signerDnString)
			);
		}

		/**
		 * 5. Verify the signature over the hash blob using the signing cert's public key.
		 *	- Parse and load the signing certificates public key.
		 * 	- Prepare RSA verifier from the signing certificate's public key.
		 *		- Set RSA-PSS signature parameters: PSS mode, MGF1 with SHA-512, saltLength = 0
		 *	- Decode and validate signature and hashes payload
		 *	  - Sort the hashes array by key (using implied SORT_REGULAR sorting behavior).
		 */
		if (!isset($caStoreX509->currentCert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'])) {
			throw new InvalidSignatureException('Signing certificate public key not available.');
		}
		$signingPublicKeyPem = $caStoreX509->currentCert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'];

		$signingRsaVerifier = new RSA();
		if ($signingRsaVerifier->loadKey($signingPublicKeyPem) === false) {
			throw new InvalidSignatureException('Failed to load signing public key.');
		}
		$signingRsaVerifier->setSignatureMode(RSA::SIGNATURE_PSS);
		$signingRsaVerifier->setMGFHash('sha512');
		$signingRsaVerifier->setSaltLength(0); // See https://tools.ietf.org/html/rfc3447#page-38

		$signatureBinary = base64_decode($signatureB64, true);
		if ($signatureBinary === false) {
			throw new InvalidSignatureException('Signature is not valid base64.');
		}

		// Recreate exactly the bytes that were signed:
		// - For new format (format_version=2) the signer encoded the hashes array-of-entries.
		// - For legacy format the signer encoded the associative map (filename => hash).
		$payloadForVerification = json_encode($hashesRaw);
		if ($payloadForVerification === false) {
			throw new \RuntimeException('Failed to JSON-encode hash list for verification.');
		}
		
		if (!$signingRsaVerifier->verify($payloadForVerification, $signatureBinary)) {
			throw new InvalidSignatureException('Signature verification failed.');
		}

		// Normalize decoded hashes into string-keyed map for comparisons
		$expectedHashes = $this->normalizeDecodedHashesForComparison($signatureData);

		/**
		 * 6. Ignore "updater/" folder when verifying core (core CN or verifying the server root):
		 *  - since updater is replaced later.
		 * 	- also relied upon by install methods that remove the "updater/" folder outright
		 *	  (e.g. Community Docker, Snap, RPM, etc.).
		 */
		if ($expectedCn === 'core' || $basePath === $this->environmentHelper->getServerRoot()) {
			foreach ($expectedHashes as $fileName => $hash) {
				if (str_starts_with($fileName, 'updater/')) {
					unset($expectedHashes[$fileName]);
				}
			}
		}

		/**
		 * 7. Determine if any on-disk (core or app) files do not match their expected hashes.
		 *	- Compute hashes of on-disk files (within the target path)
		 *  - Compare and itemize entries that differ in either direction
		 *  - Organize difference entries by category (EXTRA_FILE, FILE_MISSING, INVALID_HASH)
		 */
		$currentEntries = $this->generateHashes($this->getFolderIterator($basePath), $basePath);
		$currentInstanceHashes = [];
		foreach ($currentEntries as $entry) {
			$currentInstanceHashes[(string)$entry['file']] = strtolower((string)$entry['hash']);
		}

		ksort($expectedHashes, SORT_STRING);
		ksort($currentInstanceHashes, SORT_STRING);

		// Validate expected hash format and ensure lowercase
		foreach ($expectedHashes as $file => $hash) {
			if (!is_string($hash)) {
				throw new InvalidSignatureException('Malformed hash value for ' . (string)$file);
			}
			if (!preg_match('/^[0-9a-f]{128}$/', $hash)) {
				throw new InvalidSignatureException('Malformed hash value for ' . (string)$file);
			}
			$expectedHashes[$file] = $hash;
		}

		// Compute diffs
		$expectedMissingOrDifferent = array_diff_assoc($expectedHashes, $currentInstanceHashes);
		$actualExtraOrDifferent = array_diff_assoc($currentInstanceHashes, $expectedHashes);
		// Union by keys (preserve one of the hash values for reporting)
		$allDiffs = $expectedMissingOrDifferent + $actualExtraOrDifferent;
		
		$result = [];
		foreach ($allDiffs as $filename => $hash) {
			// extra on-disk file
			if (!array_key_exists($filename, $expectedHashes)) {
				$result['EXTRA_FILE'][$filename]['expected'] = '';
				$result['EXTRA_FILE'][$filename]['current'] = $hash;
				continue;
			}

			// expected file missing on disk
			if (!array_key_exists($filename, $currentInstanceHashes)) {
				$result['FILE_MISSING'][$filename]['expected'] = $expectedHashes[$filename];
				$result['FILE_MISSING'][$filename]['current'] = '';
				continue;
			}

			// present but hash mismatch
			if ($expectedHashes[$filename] !== $currentInstanceHashes[$filename]) {
				$result['INVALID_HASH'][$filename]['expected'] = $expectedHashes[$filename];
				$result['INVALID_HASH'][$filename]['current'] = $currentInstanceHashes[$filename];
				continue;
			}
			
			// Should never happen.
			throw new \LogicException(sprintf(
				'Unexpected behavior while comparing file hashes for "%s": expected=%s current=%s',
				$filename, var_export($expectedHashes[$filename], true), var_export($currentInstanceHashes[$filename], true)
			));
		}

		return $result;
	}

	/**
	 * Normalize decoded signature.json into a string-keyed map for in-memory comparisons.
	 *
	 * Supported shapes (associative-array outer):
	 *  - New format (format_version=2): 'hashes' is an array of ['file'=>'...','hash'=>'...'] entries.
	 *  - Legacy format: 'hashes' is an associative map filename => hash.
	 *
	 * @param array<string,mixed> $decodedSig Associative array as returned by json_decode($content, true)
	 * @return array<string,string> filename => lowercase-hash
	 * @throws InvalidSignatureException|\RuntimeException
	 */
	private function normalizeDecodedHashesForComparison(array $decodedSig): array {
		$formatVersion = $decodedSig['format_version'] ?? null;
		$hashes = $decodedSig['hashes'];

		// New format: array-of-entries
		if ($formatVersion === 2) {
			if (!is_array($hashes)) {
				throw new InvalidSignatureException('Malformed signature.json: hashes must be an array for format_version 2');
			}
			$result = [];
			foreach ($hashes as $entry) {
				if (!is_array($entry)) {
					throw new InvalidSignatureException('Malformed hash entry in signature.json');
				}
				$file = $entry['file'] ?? null;
				$hash = $entry['hash'] ?? null;
				if (!is_string($file) || !is_string($hash)) {
					throw new InvalidSignatureException('Malformed hash entry in signature.json');
				}
				if (array_key_exists($file, $result)) {
					throw new \RuntimeException('Duplicate filename in signature.json: ' . $file);
				}
				$result[$file] = strtolower($hash);
			}
			return $result;
		}

		// Legacy associative map keyed by filename (keep around for older apps)
		if (is_array($hashes)) {
			return $this->normalizeKeysToStringWithCollisionCheck($hashes);
		}

		throw new InvalidSignatureException('Malformed signature hashes structure.');
	}

	
	/**
	 * Convert array keys to strings and throw if two different original keys collapse
	 * to the same normalized string key (e.g. "01" and "1").
	 *
	 * NOTE: This function only re-casts existing keys to strings. It does NOT recover entries
	 * lost earlier when using the legacy format due to PHP coercing numeric-string keys to integers at 
	 * the time of insertion.
	 *
	 * @param array $arr
	 * @return array<string,string>
	 * @throws \RuntimeException on collision (rare)
	 */
	private function normalizeKeysToStringWithCollisionCheck(array $arr): array {
		$result = [];
		$firstOriginalKey = [];

		foreach ($arr as $origKey => $value) {
			$stringKey = (string)$origKey;
			if (array_key_exists($stringKey, $result)) {
				$first = $firstOriginalKey[$stringKey];
				// should be quite rare
				throw new \RuntimeException(sprintf(
					'Filename key collision after normalization: original keys %s and %s both normalize to %s',
					var_export($first, true),
					var_export($origKey, true),
					var_export($stringKey, true)
				));
			}
			if (!is_string($value)) {
				throw new InvalidSignatureException('Malformed hash value in signature.json for key ' . (string)$origKey);
			}
			$result[$stringKey] = strtolower($value);
			$firstOriginalKey[$stringKey] = $origKey;
		}

		return $result;
	}
	
	/**
	 * Whether the code integrity check has passed successful or not
	 *
	 * @return bool
	 */
	public function hasPassedCheck(): bool {
		$results = $this->getResults();
		if ($results !== null && empty($results)) {
			return true;
		}

		return false;
	}

	/**
	 * @return array|null Either the results or null if no results available
	 */
	public function getResults(): ?array {
		$cachedResults = $this->cache->get(self::CACHE_KEY);
		if (!\is_null($cachedResults) && $cachedResults !== false) {
			return json_decode($cachedResults, true);
		}

		if ($this->appConfig?->hasKey('core', self::CACHE_KEY, lazy: true)) {
			return $this->appConfig->getValueArray('core', self::CACHE_KEY, lazy: true);
		}

		// No results available
		return null;
	}

	/**
	 * Stores the results in the app config as well as cache
	 *
	 * @param string $scope
	 * @param array $result
	 */
	private function storeResults(string $scope, array $result) {
		$resultArray = $this->getResults() ?? [];
		unset($resultArray[$scope]);
		if (!empty($result)) {
			$resultArray[$scope] = $result;
		}
		$this->appConfig?->setValueArray('core', self::CACHE_KEY, $resultArray, lazy: true);
		$this->cache->set(self::CACHE_KEY, json_encode($resultArray));
	}

	/**
	 *
	 * Clean previous results for a proper rescanning. Otherwise
	 */
	private function cleanResults() {
		$this->appConfig->deleteKey('core', self::CACHE_KEY);
		$this->cache->remove(self::CACHE_KEY);
	}

	/**
	 * Verify the signature of $appId. Returns an array with the following content:
	 * [
	 * 	'FILE_MISSING' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'EXTRA_FILE' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'INVALID_HASH' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * ]
	 *
	 * Array may be empty in case no problems have been found.
	 *
	 * @param string $appId
	 * @param string $path Optional path. If none is given it will be guessed.
	 * @param bool $forceVerify
	 * @return array
	 */
	public function verifyAppSignature(string $appId, string $path = '', bool $forceVerify = false): array {
		try {
			if ($path === '') {
				$path = $this->appManager->getAppPath($appId);
			}
			$result = $this->verify(
				$path . '/appinfo/signature.json',
				$path,
				$appId,
				$forceVerify
			);
		} catch (\Exception $e) {
			$result = [
				'EXCEPTION' => [
					'class' => \get_class($e),
					'message' => $e->getMessage(),
				],
			];
		}
		$this->storeResults($appId, $result);

		return $result;
	}

	/**
	 * Verify the signature of core. Returns an array with the following content:
	 * [
	 * 	'FILE_MISSING' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'EXTRA_FILE' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'INVALID_HASH' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * ]
	 *
	 * Array may be empty in case no problems have been found.
	 *
	 * @return array
	 */
	public function verifyCoreSignature(): array {
		try {
			$result = $this->verify(
				$this->environmentHelper->getServerRoot() . '/core/signature.json',
				$this->environmentHelper->getServerRoot(),
				'core'
			);
		} catch (\Exception $e) {
			$result = [
				'EXCEPTION' => [
					'class' => \get_class($e),
					'message' => $e->getMessage(),
				],
			];
		}
		$this->storeResults('core', $result);

		return $result;
	}

	/**
	 * Verify the core code of the instance as well as all applicable applications
	 * and store the results.
	 */
	public function runInstanceVerification() {
		$this->cleanResults();
		$this->verifyCoreSignature();
		$appIds = $this->appManager->getAllAppsInAppsFolders();
		foreach ($appIds as $appId) {
			// If an application is shipped a valid signature is required
			$isShipped = $this->appManager->isShipped($appId);
			$appNeedsToBeChecked = false;
			if ($isShipped) {
				$appNeedsToBeChecked = true;
			} elseif ($this->fileAccessHelper->file_exists($this->appManager->getAppPath($appId) . '/appinfo/signature.json')) {
				// Otherwise only if the application explicitly ships a signature.json file
				$appNeedsToBeChecked = true;
			}

			if ($appNeedsToBeChecked) {
				$this->verifyAppSignature($appId);
			}
		}
	}
}
