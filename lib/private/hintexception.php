<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

class HintException extends \Exception {

	private $hint;

	public function __construct($message, $hint = '', $code = 0, \Exception $previous = null) {
		$this->hint = $hint;
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
	}

	public function getHint() {
		return $this->hint;
	}
}
