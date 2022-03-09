<?php

namespace OCP\Validator\Constraints;

class Email extends Constraint {
	private string $message;
	/**
	 * @param string|null $message Overwrite the default translated error message
	 *                             to use when the constraint is not fulfilled.
	 */
	public function __construct(?string $message = null) {
		parent::__construct();
		$this->message = $message === null ? $this->l10n->t('"{{ value }}" is not a valid email address') : $message;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
