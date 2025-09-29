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
use OCP\Files\InvalidDirectoryException;
use OCP\Files\InvalidPathException;
use OCP\Files\ReservedWordException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

/**
 * @since 30.0.0
 */
class FilenameValidator implements IFilenameValidator {

	public const INVALID_FILE_TYPE = 100;

	private IL10N $l10n;

	/**
	 * @var list<string>
	 */
	private array $forbiddenNames = [];

	/**
	 * @var list<string>
	 */
	private array $forbiddenBasenames = [];
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
		private IDBConnection $database,
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
			$forbiddenExtensions = $this->getConfigValue('forbidden_filename_extensions', ['.filepart']);

			// Always forbid .part files as they are used internally
			$forbiddenExtensions[] = '.part';

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
			$forbiddenNames = $this->getConfigValue('forbidden_filenames', ['.htaccess']);

			// Handle legacy config option
			// TODO: Drop with Nextcloud 34
			$legacyForbiddenNames = $this->getConfigValue('blacklisted_files', []);
			if (!empty($legacyForbiddenNames)) {
				$this->logger->warning('System config option "blacklisted_files" is deprecated and will be removed in Nextcloud 34, use "forbidden_filenames" instead.');
			}
			$forbiddenNames = array_merge($legacyForbiddenNames, $forbiddenNames);

			// Ensure we are having a proper string list
			$this->forbiddenNames = array_values($forbiddenNames);
		}
		return $this->forbiddenNames;
	}

	/**
	 * Get a list of forbidden file basenames that must not be used
	 * This list should be checked case-insensitive, all names are returned lowercase.
	 * @return list<string>
	 * @since 30.0.0
	 */
	public function getForbiddenBasenames(): array {
		if (empty($this->forbiddenBasenames)) {
			$forbiddenBasenames = $this->getConfigValue('forbidden_filename_basenames', []);
			// Ensure we are having a proper string list
			$this->forbiddenBasenames = array_values($forbiddenBasenames);
		}
		return $this->forbiddenBasenames;
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

			// Get admin defined invalid characters
			$additionalChars = $this->config->getSystemValue('forbidden_filename_characters', []);
			if (!is_array($additionalChars)) {
				$this->logger->error('Invalid system config value for "forbidden_filename_characters" is ignored.');
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
		// we check the trimmed name here to ensure unexpected trimming will not cause severe issues
		if ($trimmed === '.' || $trimmed === '..') {
			throw new InvalidDirectoryException($this->l10n->t('Dot files are not allowed'));
		}

		// 255 characters is the limit on common file systems (ext/xfs)
		// oc_filecache has a 250 char length limit for the filename
		if (isset($filename[250])) {
			throw new FileNameTooLongException();
		}

		if (!$this->database->supports4ByteText()) {
			// verify database - e.g. mysql only 3-byte chars
			if (preg_match('%(?:
      \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
    | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
    | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
)%xs', $filename)) {
				throw new InvalidCharacterInPathException();
			}
		}

		$this->checkForbiddenName($filename);

		$this->checkForbiddenExtension($filename);

		$this->checkForbiddenCharacters($filename);
	}

	/**
	 * Check if the filename is forbidden
	 * @param string $path Path to check the filename
	 * @return bool True if invalid name, False otherwise
	 */
	public function isForbidden(string $path): bool {
		// We support paths here as this function is also used in some storage internals
		$filename = basename($path);
		$filename = mb_strtolower($filename);

		if ($filename === '') {
			return false;
		}

		// Check for forbidden filenames
		$forbiddenNames = $this->getForbiddenFilenames();
		if (in_array($filename, $forbiddenNames)) {
			return true;
		}

		// Filename is not forbidden
		return false;
	}

	public function sanitizeFilename(string $name, ?string $charReplacement = null): string {
		$forbiddenCharacters = $this->getForbiddenCharacters();

		if ($charReplacement === null) {
			$charReplacement = array_diff(['_', '-', ' '], $forbiddenCharacters);
			$charReplacement = reset($charReplacement) ?: '';
		}
		if (mb_strlen($charReplacement) !== 1) {
			throw new \InvalidArgumentException('No or invalid character replacement given');
		}

		$nameLowercase = mb_strtolower($name);
		foreach ($this->getForbiddenExtensions() as $extension) {
			if (str_ends_with($nameLowercase, $extension)) {
				$name = substr($name, 0, strlen($name) - strlen($extension));
			}
		}

		$basename = strlen($name) > 1
			? substr($name, 0, strpos($name, '.', 1) ?: null)
			: $name;
		if (in_array(mb_strtolower($basename), $this->getForbiddenBasenames())) {
			$name = str_replace($basename, $this->l10n->t('%1$s (renamed)', [$basename]), $name);
		}

		if ($name === '') {
			$name = $this->l10n->t('renamed file');
		}

		if (in_array(mb_strtolower($name), $this->getForbiddenFilenames())) {
			$name = $this->l10n->t('%1$s (renamed)', [$name]);
		}

		$name = str_replace($forbiddenCharacters, $charReplacement, $name);
		return $name;
	}

	protected function checkForbiddenName(string $filename): void {
		$filename = mb_strtolower($filename);
		if ($this->isForbidden($filename)) {
			throw new ReservedWordException($this->l10n->t('"%1$s" is a forbidden file or folder name.', [$filename]));
		}

		// Check for forbidden basenames - basenames are the part of the file until the first dot
		// (except if the dot is the first character as this is then part of the basename "hidden files")
		$basename = substr($filename, 0, strpos($filename, '.', 1) ?: null);
		$forbiddenNames = $this->getForbiddenBasenames();
		if (in_array($basename, $forbiddenNames)) {
			throw new ReservedWordException($this->l10n->t('"%1$s" is a forbidden prefix for file or folder names.', [$filename]));
		}
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
				throw new InvalidCharacterInPathException($this->l10n->t('"%1$s" is not allowed inside a file or folder name.', [$char]));
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
		// Check for forbidden filename extensions
		$forbiddenExtensions = $this->getForbiddenExtensions();
		foreach ($forbiddenExtensions as $extension) {
			if (str_ends_with($filename, $extension)) {
				if (str_starts_with($extension, '.')) {
					throw new InvalidPathException($this->l10n->t('"%1$s" is a forbidden file type.', [$extension]), self::INVALID_FILE_TYPE);
				} else {
					throw new InvalidPathException($this->l10n->t('Filenames must not end with "%1$s".', [$extension]));
				}
			}
		}
	}

	/**
	 * Helper to get lower case list from config with validation
	 * @return string[]
	 */
	private function getConfigValue(string $key, array $fallback): array {
		$values = $this->config->getSystemValue($key, $fallback);
		if (!is_array($values)) {
			$this->logger->error('Invalid system config value for "' . $key . '" is ignored.');
			$values = $fallback;
		}

		return array_map(mb_strtolower(...), $values);
	}
};
