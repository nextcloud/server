<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_L10N_String{
	protected $l10n;
	public function __construct($l10n, $text, $parameters, $count = 1) {
		$this->l10n = $l10n;
		$this->text = $text;
		$this->parameters = $parameters;
		$this->count = $count;

	}

	public function __toString() {
		$translations = $this->l10n->getTranslations();
		$localizations = $this->l10n->getLocalizations();

		$text = $this->text;
		if(array_key_exists($this->text, $translations)) {
			if(is_array($translations[$this->text])) {
				$id = $localizations["selectplural"]( $count );
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
