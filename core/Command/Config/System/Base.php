<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Config\System;

use OC\SystemConfig;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

abstract class Base extends \OC\Core\Command\Base {
	public function __construct(
		protected SystemConfig $systemConfig,
	) {
		parent::__construct();
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'name') {
			$words = $this->getPreviousNames($context, $context->getWordIndex());
			if (empty($words)) {
				$completions = $this->systemConfig->getKeys();
			} else {
				$key = array_shift($words);
				$value = $this->systemConfig->getValue($key);
				$completions = array_keys($value);

				while (!empty($words) && is_array($value)) {
					$key = array_shift($words);
					if (!isset($value[$key]) || !is_array($value[$key])) {
						break;
					}

					$value = $value[$key];
					$completions = array_keys($value);
				}
			}

			return $completions;
		}
		return parent::completeArgumentValues($argumentName, $context);
	}

	/**
	 * @param CompletionContext $context
	 * @param int $currentIndex
	 * @return string[]
	 */
	protected function getPreviousNames(CompletionContext $context, $currentIndex) {
		$word = $context->getWordAtIndex($currentIndex - 1);
		if ($word === $this->getName() || $currentIndex <= 0) {
			return [];
		}

		$words = $this->getPreviousNames($context, $currentIndex - 1);
		$words[] = $word;
		return $words;
	}
}
