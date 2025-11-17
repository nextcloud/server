<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\L10N;

use OCP\IConfig;
use OCP\IUser;
use OCP\L10N\ILanguageIterator;

class LanguageIterator implements ILanguageIterator {
	private $i = 0;

	public function __construct(
		private IUser $user,
		private IConfig $config,
	) {
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	public function rewind(): void {
		$this->i = 0;
	}

	/**
	 * Return the current element
	 *
	 * @since 14.0.0
	 */
	public function current(): string {
		switch ($this->i) {
			/** @noinspection PhpMissingBreakStatementInspection */
			case 0:
				$forcedLang = $this->config->getSystemValue('force_language', false);
				if (is_string($forcedLang)) {
					return $forcedLang;
				}
				$this->next();
				/** @noinspection PhpMissingBreakStatementInspection */
				// no break
			case 1:
				$forcedLang = $this->config->getSystemValue('force_language', false);
				if (is_string($forcedLang)
					&& ($truncated = $this->getTruncatedLanguage($forcedLang)) !== $forcedLang
				) {
					return $truncated;
				}
				$this->next();
				/** @noinspection PhpMissingBreakStatementInspection */
				// no break
			case 2:
				$userLang = $this->config->getUserValue($this->user->getUID(), 'core', 'lang', null);
				if (is_string($userLang)) {
					return $userLang;
				}
				$this->next();
				/** @noinspection PhpMissingBreakStatementInspection */
				// no break
			case 3:
				$userLang = $this->config->getUserValue($this->user->getUID(), 'core', 'lang', null);
				if (is_string($userLang)
					&& ($truncated = $this->getTruncatedLanguage($userLang)) !== $userLang
				) {
					return $truncated;
				}
				$this->next();
				// no break
			case 4:
				return $this->config->getSystemValueString('default_language', 'en');
				/** @noinspection PhpMissingBreakStatementInspection */
			case 5:
				$defaultLang = $this->config->getSystemValueString('default_language', 'en');
				if (($truncated = $this->getTruncatedLanguage($defaultLang)) !== $defaultLang) {
					return $truncated;
				}
				$this->next();
				// no break
			default:
				return 'en';
		}
	}

	/**
	 * Move forward to next element
	 *
	 * @since 14.0.0
	 */
	public function next(): void {
		++$this->i;
	}

	/**
	 * Return the key of the current element
	 *
	 * @since 14.0.0
	 */
	public function key(): int {
		return $this->i;
	}

	/**
	 * Checks if current position is valid
	 *
	 * @since 14.0.0
	 */
	public function valid(): bool {
		return $this->i <= 6;
	}

	protected function getTruncatedLanguage(string $lang):string {
		$pos = strpos($lang, '_');
		if ($pos !== false) {
			$lang = substr($lang, 0, $pos);
		}
		return $lang;
	}
}
