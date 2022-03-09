<?php

namespace OCP\Validator;

/**
 * This object represents a constraint violation when validating a value.
 */
class Violation {
	private string $message;
	private array $parameters;

	public function __construct(string $message) {
		$this->message = $message;
		$this->parameters = [];
	}

	/**
	 * Returns the violation message. This can be directly displayed to the
	 * user, if wanted.
	 */
	public function getMessage(): string {
		$message = $this->message;
		foreach ($this->parameters as $value => $representation) {
			$message = str_replace($representation, $value, $message);
		}
		return $message;
	}

	/**
	 * Inject a parameter inside the violation message.
	 *
	 * This allows to inject dynamic information in the violation message.
	 *
	 * ```php
	 * $violation = new Violation('This value should be less than {{ max }}.');
	 * $violation->addParameter('{{ max }}', 100);
	 * assert($violation->getMessage() === 'This value should be less than 100.')
	 * ```
	 */
	public function addParameter(string $representation, string $value): self {
		$this->parameters[] = [
			$representation => $value,
		];
		return $this;
	}
}
