<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_L10N_String{
	/**
	 * @var OC_L10N
	 */
	protected $l10n;

	/**
	 * @var string
	 */
	protected $text;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var array
	 */
	protected $plurals;

	/**
	 * @var integer
	 */
	protected $count;

	/**
	 * @param OC_L10N $l10n
	 */
	public function __construct($l10n, $text, $parameters, $count = 1, $plurals = array()) {
		$this->l10n = $l10n;
		$this->text = $text;
		$this->parameters = $parameters;
		$this->count = $count;
		$this->plurals = $plurals;
	}

	public function __toString() {
		$translations = $this->l10n->getTranslations();

		$text = $this->text;
		if(array_key_exists($this->text, $translations)) {
			if(is_array($translations[$this->text])) {
				$fn = $this->l10n->getPluralFormFunction();
				$id = $fn($this->count);

				if ($translations[$this->text][$id] !== '') {
					// The translation of this plural case is not empty, so use it
					$text = $translations[$this->text][$id];
				} else {
					// We didn't find the plural in the language,
					// so we fall back to english.
					$id = ($id != 0) ? 1 : 0;
					if (isset($this->plurals[$id])) {
						// Fallback to the english plural
						$text = $this->plurals[$id];
					}
				}
			}
			else{
				$text = $translations[$this->text];
			}
		}

		// Replace %n first (won't interfere with vsprintf)
		$text = str_replace('%n', $this->count, $text);
		return vsprintf($text, $this->parameters);
	}
}
