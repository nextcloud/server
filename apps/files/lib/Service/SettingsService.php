<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Service;

use OC\Files\FilenameValidator;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class SettingsService {

	protected const WINDOWS_EXTENSION = [
		' ',
		'.',
	];

	protected const WINDOWS_BASENAMES = [
		'con', 'prn', 'aux', 'nul', 'com0', 'com1', 'com2', 'com3', 'com4', 'com5',
		'com6', 'com7', 'com8', 'com9', 'com¹', 'com²', 'com³', 'lpt0', 'lpt1', 'lpt2',
		'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9', 'lpt¹', 'lpt²', 'lpt³',
	];

	protected const WINDOWS_CHARACTERS = [
		'<', '>', ':',
		'"', '|', '?',
		'*',
	];

	public function __construct(
		private IConfig $config,
		private FilenameValidator $filenameValidator,
		private LoggerInterface $logger,
	) {
	}

	public function hasFilesWindowsSupport(): bool {
		return empty(array_diff(self::WINDOWS_BASENAMES, $this->filenameValidator->getForbiddenBasenames()))
			&& empty(array_diff(self::WINDOWS_CHARACTERS, $this->filenameValidator->getForbiddenCharacters()))
			&& empty(array_diff(self::WINDOWS_EXTENSION, $this->filenameValidator->getForbiddenExtensions()));
	}

	public function setFilesWindowsSupport(bool $enabled = true): void {
		if ($enabled) {
			$basenames = array_unique(array_merge(self::WINDOWS_BASENAMES, $this->filenameValidator->getForbiddenBasenames()));
			$characters = array_unique(array_merge(self::WINDOWS_CHARACTERS, $this->filenameValidator->getForbiddenCharacters()));
			$extensions = array_unique(array_merge(self::WINDOWS_EXTENSION, $this->filenameValidator->getForbiddenExtensions()));
		} else {
			$basenames = array_unique(array_values(array_diff($this->filenameValidator->getForbiddenBasenames(), self::WINDOWS_BASENAMES)));
			$characters = array_unique(array_values(array_diff($this->filenameValidator->getForbiddenCharacters(), self::WINDOWS_CHARACTERS)));
			$extensions = array_unique(array_values(array_diff($this->filenameValidator->getForbiddenExtensions(), self::WINDOWS_EXTENSION)));
		}
		$values = [
			'forbidden_filename_basenames' => empty($basenames) ? null : $basenames,
			'forbidden_filename_characters' => empty($characters) ? null : $characters,
			'forbidden_filename_extensions' => empty($extensions) ? null : $extensions,
		];
		$this->config->setSystemValues($values);
	}
}
