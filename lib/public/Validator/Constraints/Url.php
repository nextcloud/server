<?php

namespace OCP\Validator\Constraints;

class Url extends Constraint {
	/** @var string[] */
	private array $protocols;
	private bool $relativeUrl;
	private string $message;

	/**
	 * @param string|null $message Overwrite the default translated error message
	 *                             to use when the constraint is not fulfilled.
	 */
	public function __construct(bool $relativeUrl = false, array $protocols = ['http', 'https'], ?string $message = null) {
		parent::__construct();
		$this->protocols = $protocols;
		$this->message = $message === null ? $this->l10n->t('"{{ value }}" is not an url') : $message;
		$this->relativeUrl = $relativeUrl;
	}

	public function getProtocols(): array {
		return $this->protocols;
	}

	public function isRelativeUrl(): bool {
		return $this->relativeUrl;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
