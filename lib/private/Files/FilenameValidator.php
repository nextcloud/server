<?php
declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files;

use OCP\Files\IFilenameValidator;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * @since 30.0.0
 */
class FilenameValidator implements IFilenameValidator {

	/**
	 * @var list<string>
	 */
	private array $forbiddenNames;

	/**
	 * @var list<string>
	 */
	private array $forbiddenCharacters;

	/**
	 * @var list<string>
	 */
	private array $forbiddenExtensions;

	public function __construct(
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getForbiddenExtensions(): array {
		if (empty($this->forbiddenExtensions)) {
			$forbiddenExtensions = $this->config->getSystemValue('forbidden_filename_extensions', ['.filepart']);
			if (!is_array($forbiddenExtensions)) {
				$this->logger->error('Invalid system config value for "forbidden_filename_extensions" is ignored.');
				$forbiddenExtensions = ['.filepart'];
			}

			// Always forbid .part files as they are used internally
			$forbiddenExtensions = array_merge($forbiddenExtensions, ['.part']);

			// The list is case insensitive so we provide it always lowercase
			$forbiddenExtensions = array_map('mb_strtolower', $forbiddenExtensions);
			$this->forbiddenExtensions = array_values($forbiddenExtensions);
		}
		return $this->forbiddenExtensions;
	}

	/**
	 * @inheritdoc
	 */
	public function getForbiddenFilenames(): array {
		if (empty($this->forbiddenNames)) {
			$forbiddenNames = $this->config->getSystemValue('blacklisted_files', ['.htaccess']);
			if (!is_array($forbiddenNames)) {
				$this->logger->error('Invalid system config value for "blacklisted_files" is ignored.');
				$forbiddenNames = ['.htaccess'];
			}

			// The list is case insensitive so we provide it always lowercase
			$forbiddenNames = array_map('mb_strtolower', $forbiddenNames);
			$this->forbiddenNames = array_values($forbiddenNames);
		}
		return $this->forbiddenNames;
	}

	/**
	 * @inheritdoc
	 */
	public function getForbiddenCharacters(): array
	{
		if (empty($this->forbiddenCharacters)) {
			// Get always forbidden characters
			$forbiddenCharacters = str_split(\OCP\Constants::FILENAME_INVALID_CHARS);
			if ($forbiddenCharacters === false) {
				$forbiddenCharacters = [];
			}

			// Get admin defined invalid characters
			$additionalChars = $this->config->getSystemValue('forbidden_chars', []);
			if (!is_array($additionalChars)) {
				$this->logger->error('Invalid system config value for "forbidden_chars" is ignored.');
				$additionalChars = [];
			}

			$forbiddenCharacters = array_merge($forbiddenCharacters, $additionalChars);
			$this->forbiddenCharacters = array_values($forbiddenCharacters);
		}
		return $this->forbiddenCharacters;
	}

	/**
	 * @inheritdoc
	 */
	public function isFilenameValid(string $filename): bool {
		// Ensure we are working on the filename
		$filename = \OC\Files\Filesystem::normalizePath($filename);
		$filename = basename($filename);

		// the special directories . and .. would cause never ending recursion
		$trimmed = trim($filename);
		if ($trimmed === '' || $trimmed === '.' || $trimmed === '..') {
			return false;
		}

		if ($this->isForbidden($filename)) {
			return false;
		}

		if ($this->hasForbiddenExtension($filename)) {
			return false;
		}

		if ($this->hasForbiddenCharacters($filename)) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a filename contains any of the forbidden characters
	 * @param string $filename
	 * @return bool
	 */
	protected function hasForbiddenCharacters(string $filename): bool {
		foreach ($this->getForbiddenCharacters() as $char) {
			if (str_contains($filename, $char)) {
				return true;
			}
		}

		$sanitizedFileName = filter_var($filename, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
		if ($sanitizedFileName !== $filename) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a filename has a forbidden filename extension
	 * @param string $filename The filename to validate
	 * @return bool
	 */
	protected function hasForbiddenExtension(string $filename): bool {
		$filename = mb_strtolower($filename);
		// Check for forbidden filename exten<sions
		$forbiddenExtensions = $this->getForbiddenExtensions();
		foreach ($forbiddenExtensions as $extension) {
			if (str_ends_with($filename, $extension)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the filename is forbidden
	 * @param string $filename
	 * @return bool True if invalid name, False otherwise
	 */
	protected function isForbidden(string $filename): bool {
		$filename = mb_strtolower($filename);

		if ($filename === '') {
			return false;
		}

		// The name part without extension
		$basename = substr($filename, 0, strpos($filename, '.', 1) ?: null);
		// Check for forbidden filenames
		$forbiddenNames = $this->getForbiddenFilenames();
		if (in_array($basename, $forbiddenNames)) {
			return true;
		}

		// Filename is not forbidden
		return false;
	}
};
