<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification;

use OCP\App\IAppManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

class Manager {

	private ?IUser $currentUser;

	public function __construct(
		IUserSession $currentSession,
		private IAppManager $appManager,
		private IFactory $l10NFactory,
		private LoggerInterface $logger,
	) {
		$this->currentUser = $currentSession->getUser();
	}

	/**
	 * Get the changelog entry for the given appId
	 * @param string $appId The app for which to query the entry
	 * @param string $version The version for which to query the changelog entry
	 * @param ?string $languageCode The language in which to query the changelog (defaults to current user language and fallsback to English)
	 * @return string|null Either the changelog entry or null if no changelog is found
	 */
	public function getChangelog(string $appId, string $version, ?string $languageCode = null): ?string {
		if ($languageCode === null) {
			$languageCode = $this->l10NFactory->getUserLanguage($this->currentUser);
		}

		$path = $this->getChangelogFile($appId, $languageCode);
		if ($path === null) {
			$this->logger->debug('No changelog file found for app ' . $appId . ' and language code ' . $languageCode);
			return null;
		}

		$changes = $this->retrieveChangelogEntry($path, $version);
		return $changes;
	}

	/**
	 * Get the changelog file in the requested language or fallback to English
	 * @param string $appId The app to load the changelog for
	 * @param string $languageCode The language code to search
	 * @return string|null Either the file path or null if not found
	 */
	public function getChangelogFile(string $appId, string $languageCode): ?string {
		try {
			$appPath = $this->appManager->getAppPath($appId);
			$files = ["CHANGELOG.$languageCode.md", 'CHANGELOG.en.md'];
			foreach ($files as $file) {
				$path = $appPath . '/' . $file;
				if (is_file($path)) {
					return $path;
				}
			}
		} catch (\Throwable $e) {
			// ignore and return null below
		}
		return null;
	}

	/**
	 * Retrieve a log entry from the changelog
	 * @param string $path The path to the changelog file
	 * @param string $version The version to query (make sure to only pass in "{major}.{minor}(.{patch}" format)
	 */
	protected function retrieveChangelogEntry(string $path, string $version): ?string {
		$matches = [];
		$content = file_get_contents($path);
		if ($content === false) {
			$this->logger->debug('Could not open changelog file', ['file-path' => $path]);
			return null;
		}

		$result = preg_match_all('/^## (?:\[)?(?:v)?(\d+\.\d+(\.\d+)?)/m', $content, $matches, PREG_OFFSET_CAPTURE);
		if ($result === false || $result === 0) {
			$this->logger->debug('No entries in changelog found', ['file_path' => $path]);
			return null;
		}

		// Get the key of the match that equals the requested version
		$index = array_key_first(
			// Get the array containing the match that equals the requested version, keys are preserved so: [1 => '1.2.4']
			array_filter(
				// This is the array of the versions found, like ['1.2.3', '1.2.4']
				$matches[1],
				// Callback to filter only version that matches the requested version
				fn (array $match) => version_compare($match[0], $version, '=='),
			)
		);

		if ($index === null) {
			$this->logger->debug('No changelog entry for version ' . $version . ' found', ['file_path' => $path]);
			return null;
		}

		$offsetChangelogEntry = $matches[0][$index][1];
		// Length of the changelog entry (offset of next match - own offset) or null if the whole rest should be considered
		$lengthChangelogEntry = $index < ($result - 1) ? ($matches[0][$index + 1][1] - $offsetChangelogEntry) : null;
		return substr($content, $offsetChangelogEntry, $lengthChangelogEntry);
	}
}
