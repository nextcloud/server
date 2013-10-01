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
	 * @var integer
	 */
	protected $count;

	public function __construct($l10n, $text, $parameters, $count = 1) {
		$this->l10n = $l10n;
		$this->text = $text;
		$this->parameters = $parameters;
		$this->count = $count;
	}

	public function __toString() {
		$translations = $this->l10n->getTranslations();

		$text = $this->text;
		if(array_key_exists($this->text, $translations)) {
			if(is_array($translations[$this->text])) {
				$fn = $this->l10n->getPluralFormFunction();
				$id = $fn($this->count);
				$text = $translations[$this->text][$id];
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
