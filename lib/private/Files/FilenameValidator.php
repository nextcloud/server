<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files;

use OCP\Files\EmptyFileNameException;
use OCP\Files\FileNameTooLongException;
use OCP\Files\IFilenameValidator;
use OCP\Files\InvalidCharacterInPathException;
use OCP\Files\InvalidPathException;
use OCP\Files\ReservedWordException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

/**
 * @since 30.0.0
 */
class FilenameValidator implements IFilenameValidator {

	private IL10N $l10n;

	/**
	 * @var list<string>
	 */
	private array $forbiddenNames = [];

	/**
	 * @var list<string>
	 */
	private array $forbiddenCharacters = [];

	/**
	 * @var list<string>
	 */
	private array $forbiddenExtensions = [];

	public function __construct(
		IFactory $l10nFactory,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		$this->l10n = $l10nFactory->get('core');
	}

	/**
	 * Get a list of reserved filenames that must not be used
	 * This list should be checked case-insensitive, all names are returned lowercase.
	 * @return list<string>
	 * @since 30.0.0
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
	 * Get a list of forbidden filename extensions that must not be used
	 * This list should be checked case-insensitive, all names are returned lowercase.
	 * @return list<string>
	 * @since 30.0.0
	 */
	public function getForbiddenFilenames(): array {
		if (empty($this->forbiddenNames)) {
			$forbiddenNames = $this->config->getSystemValue('forbidden_filenames', ['.htaccess']);
			if (!is_array($forbiddenNames)) {
				$this->logger->error('Invalid system config value for "forbidden_filenames" is ignored.');
				$forbiddenNames = ['.htaccess'];
			}

			// Handle legacy config option
			// TODO: Drop with Nextcloud 34
			$legacyForbiddenNames = $this->config->getSystemValue('blacklisted_files', []);
			if (!is_array($legacyForbiddenNames)) {
				$this->logger->error('Invalid system config value for "blacklisted_files" is ignored.');
				$legacyForbiddenNames = [];
			}
			if (!empty($legacyForbiddenNames)) {
				$this->logger->warning('System config option "blacklisted_files" is deprecated and will be removed in Nextcloud 34, use "forbidden_filenames" instead.');
			}
			$forbiddenNames = array_merge($legacyForbiddenNames, $forbiddenNames);

			// The list is case insensitive so we provide it always lowercase
			$forbiddenNames = array_map('mb_strtolower', $forbiddenNames);
			$this->forbiddenNames = array_values($forbiddenNames);
		}
		return $this->forbiddenNames;
	}

	/**
	 * Get a list of characters forbidden in filenames
	 *
	 * Note: Characters in the range [0-31] are always forbidden,
	 * even if not inside this list (see OCP\Files\Storage\IStorage::verifyPath).
	 *
	 * @return list<string>
	 * @since 30.0.0
	 */
	public function getForbiddenCharacters(): array {
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

			// Handle legacy config option
			// TODO: Drop with Nextcloud 34
			$legacyForbiddenCharacters = $this->config->getSystemValue('forbidden_chars', []);
			if (!is_array($legacyForbiddenCharacters)) {
				$this->logger->error('Invalid system config value for "forbidden_chars" is ignored.');
				$legacyForbiddenCharacters = [];
			}
			if (!empty($legacyForbiddenCharacters)) {
				$this->logger->warning('System config option "forbidden_chars" is deprecated and will be removed in Nextcloud 34, use "forbidden_filename_characters" instead.');
			}
			$forbiddenCharacters = array_merge($legacyForbiddenCharacters, $forbiddenCharacters);

			$this->forbiddenCharacters = array_values($forbiddenCharacters);
		}
		return $this->forbiddenCharacters;
	}

	/**
	 * @inheritdoc
	 */
	public function isFilenameValid(string $filename): bool {
		try {
			$this->validateFilename($filename);
		} catch (\OCP\Files\InvalidPathException) {
			return false;
		}
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function validateFilename(string $filename): void {
		$trimmed = trim($filename);
		if ($trimmed === '') {
			throw new EmptyFileNameException();
		}

		// the special directories . and .. would cause never ending recursion
		if ($trimmed === '.' || $trimmed === '..') {
			throw new ReservedWordException();
		}

		// 255 characters is the limit on common file systems (ext/xfs)
		// oc_filecache has a 250 char length limit for the filename
		if (isset($filename[250])) {
			throw new FileNameTooLongException();
		}

		if ($this->isForbidden($filename)) {
			throw new ReservedWordException();
		}

		$this->checkForbiddenExtension($filename);

		$this->checkForbiddenCharacters($filename);
	}

	/**
	 * Check if the filename is forbidden
	 * @param string $filename
	 * @return bool True if invalid name, False otherwise
	 */
	public function isForbidden(string $filename): bool {
		$filename = basename($filename);
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

	/**
	 * Check if a filename contains any of the forbidden characters
	 * @param string $filename
	 * @throws InvalidCharacterInPathException
	 */
	protected function checkForbiddenCharacters(string $filename): void {
		$sanitizedFileName = filter_var($filename, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
		if ($sanitizedFileName !== $filename) {
			throw new InvalidCharacterInPathException();
		}

		foreach ($this->getForbiddenCharacters() as $char) {
			if (str_contains($filename, $char)) {
				throw new InvalidCharacterInPathException($char);
			}
		}
	}

	/**
	 * Check if a filename has a forbidden filename extension
	 * @param string $filename The filename to validate
	 * @throws InvalidPathException
	 */
	protected function checkForbiddenExtension(string $filename): void {
		$filename = mb_strtolower($filename);
		// Check for forbidden filename exten<sions
		$forbiddenExtensions = $this->getForbiddenExtensions();
		foreach ($forbiddenExtensions as $extension) {
			if (str_ends_with($filename, $extension)) {
				throw new InvalidPathException($this->l10n->t('Invalid filename extension "%1$s"', [$extension]));
			}
		}
	}
};
