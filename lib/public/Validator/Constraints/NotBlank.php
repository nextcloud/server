<?php

namespace OCP\Validator\Constraints;

class NotBlank extends Constraint {
	private string $message;
	private bool $allowNull;

	/**
	 * @param string|null $message Overwrite the default translated error message
	 *                             to use when the constraint is not fulfilled.
	 */
	public function __construct(bool $allowNull = false, ?string $message = null) {
		parent::__construct();
		$this->allowNull = $allowNull;
		$this->message = $message === null ? $this->l10n->t('The value is blank') : $message;
	}

	public function allowNull(): bool {
		return $this->allowNull;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
