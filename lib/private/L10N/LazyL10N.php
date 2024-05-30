<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\L10N;

use OCP\IL10N;

class LazyL10N implements IL10N {
	/** @var IL10N */
	private $l;

	/** @var \Closure */
	private $factory;


	public function __construct(\Closure $factory) {
		$this->factory = $factory;
	}

	private function getL(): IL10N {
		if ($this->l === null) {
			$this->l = ($this->factory)();
		}

		return $this->l;
	}

	public function t(string $text, $parameters = []): string {
		return $this->getL()->t($text, $parameters);
	}

	public function n(string $text_singular, string $text_plural, int $count, array $parameters = []): string {
		return $this->getL()->n($text_singular, $text_plural, $count, $parameters);
	}

	public function l(string $type, $data, array $options = []) {
		return $this->getL()->l($type, $data, $options);
	}

	public function getLanguageCode(): string {
		return $this->getL()->getLanguageCode();
	}

	public function getLocaleCode(): string {
		return $this->getL()->getLocaleCode();
	}
}
