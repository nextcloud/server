<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_L10N_String{
	protected $l10n;
	public function __construct($l10n, $text, $parameters) {
		$this->l10n = $l10n;
		$this->text = $text;
		$this->parameters = $parameters;

	}

	public function __toString() {
		$translations = $this->l10n->getTranslations();
		if(array_key_exists($this->text, $translations)) {
			return vsprintf($translations[$this->text], $this->parameters);
		}
		return vsprintf($this->text, $this->parameters);
	}
}
