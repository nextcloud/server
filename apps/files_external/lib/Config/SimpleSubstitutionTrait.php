<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Config;

/**
 * Trait SimpleSubstitutionTrait
 *
 * @package OCA\Files_External\Config
 * @since 16.0.0
 */
trait SimpleSubstitutionTrait {
	/**
	 * @var string the placeholder without $ prefix
	 * @since 16.0.0
	 */
	protected $placeholder;

	/** @var string */
	protected $sanitizedPlaceholder;

	/**
	 * @param mixed $optionValue
	 * @param string $replacement
	 * @return mixed
	 * @since 16.0.0
	 */
	private function processInput($optionValue, string $replacement) {
		$this->checkPlaceholder();
		if (is_array($optionValue)) {
			foreach ($optionValue as &$value) {
				$value = $this->substituteIfString($value, $replacement);
			}
		} else {
			$optionValue = $this->substituteIfString($optionValue, $replacement);
		}
		return $optionValue;
	}

	/**
	 * @throws \RuntimeException
	 */
	protected function checkPlaceholder(): void {
		$this->sanitizedPlaceholder = trim(strtolower($this->placeholder));
		if (!(bool)\preg_match('/^[a-z0-9]*$/', $this->sanitizedPlaceholder)) {
			throw new \RuntimeException(sprintf(
				'Invalid placeholder %s, only [a-z0-9] are allowed', $this->sanitizedPlaceholder
			));
		}
		if ($this->sanitizedPlaceholder === '') {
			throw new \RuntimeException('Invalid empty placeholder');
		}
	}

	/**
	 * @param mixed $value
	 * @param string $replacement
	 * @return mixed
	 */
	protected function substituteIfString($value, string $replacement) {
		if (is_string($value)) {
			return str_ireplace('$' . $this->sanitizedPlaceholder, $replacement, $value);
		}
		return $value;
	}
}
